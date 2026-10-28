<?php
/**
 * Photos des produits : envoi sécurisé, redimensionnement, lecture.
 *
 * Sécurité de l'envoi (le point sensible d'un site PHP) :
 *  - le type est vérifié sur le CONTENU du fichier, jamais sur son nom ni son type annoncé ;
 *  - chaque image est décodée puis RÉ-ENCODÉE en JPEG : un fichier piégé (code PHP caché
 *    dans une image) est neutralisé, et les données EXIF (position GPS...) sont supprimées ;
 *  - le nom du fichier est aléatoire, l'extension toujours .jpg : le visiteur ne choisit rien ;
 *  - le dossier uploads/ interdit l'exécution de scripts (.htaccess).
 */
require_once __DIR__ . '/functions.php';

const PRODUCT_IMAGES_MAX       = 8;                 // photos par produit
const PRODUCT_IMAGE_MAX_BYTES  = 8 * 1024 * 1024;   // 8 Mo par fichier envoyé
const PRODUCT_IMAGE_MAX_PIXELS = 25000000;          // 25 mégapixels (protège la mémoire du serveur)
const PRODUCT_IMAGE_FULL       = 1200;              // grande version : 1200 px max
const PRODUCT_IMAGE_THUMB      = 480;               // miniature pour les listes : 480 px max

// --- Dossier d'envoi --------------------------------------------------------

function uploads_dir(): string
{
    return dirname(__DIR__) . '/uploads/products';
}

/** Crée le dossier des photos (avec ses protections) s'il n'existe pas encore. */
function ensure_uploads_dir(): string
{
    $dir = uploads_dir();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new RuntimeException("Impossible de créer le dossier des photos (uploads/products).");
    }

    $root = dirname($dir);

    // Aucun script ne doit jamais pouvoir s'exécuter depuis ce dossier
    $htaccess = $root . '/.htaccess';
    if (!is_file($htaccess)) {
        file_put_contents($htaccess,
            "# Dossier de photos : on peut LIRE les images, jamais exécuter de script.\n"
            . "<FilesMatch \"(?i)\\.(php[0-9]?|phtml|phar|pht|shtml|cgi|pl|py|sh)$\">\n"
            . "    Require all denied\n"
            . "</FilesMatch>\n"
            . "<IfModule mod_headers.c>\n"
            . "    Header set X-Content-Type-Options \"nosniff\"\n"
            . "</IfModule>\n"
        );
    }

    // Empêche l'affichage de la liste des fichiers du dossier
    foreach ([$root, $dir] as $folder) {
        if (!is_file($folder . '/index.html')) {
            file_put_contents($folder . '/index.html', '');
        }
    }

    return $dir;
}

/** Adresse web d'une photo. $fileName = nom aléatoire stocké en base (sans extension). */
function product_image_url(string $fileName, bool $thumb = false): string
{
    return url('uploads/products/' . $fileName . ($thumb ? '_t' : '') . '.jpg');
}

// --- Lecture ---------------------------------------------------------------

/**
 * Les photos de plusieurs produits en UNE requête.
 * @return array<int, array<int, array>> [id_produit => [photos dans l'ordre d'affichage]]
 */
function get_images_for_products(array $productIds): array
{
    $ids = array_values(array_unique(array_map('intval', $productIds)));
    if (!$ids) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = db()->prepare(
        "SELECT id, product_id, file_name, sort_order
         FROM product_images WHERE product_id IN ($placeholders)
         ORDER BY sort_order, id"
    );
    $stmt->execute($ids);

    $byProduct = [];
    foreach ($stmt as $row) {
        $byProduct[(int) $row['product_id']][] = $row;
    }
    return $byProduct;
}

// --- Envoi -----------------------------------------------------------------

/** Transforme $_FILES['images'] (tableaux parallèles) en une liste de fichiers. */
function normalize_uploaded_files(array $files): array
{
    if (!isset($files['name']) || !is_array($files['name'])) {
        return [];
    }

    $list = [];
    foreach (array_keys($files['name']) as $i) {
        $list[] = [
            'name'     => (string) $files['name'][$i],
            'tmp_name' => (string) $files['tmp_name'][$i],
            'error'    => (int) $files['error'][$i],
            'size'     => (int) $files['size'][$i],
        ];
    }
    return $list;
}

/** Nom de fichier affichable dans un message d'erreur (caractères sûrs, longueur limitée). */
function safe_label(string $name): string
{
    $name = preg_replace('/[^\p{L}\p{N}._ -]/u', '', $name);
    return mb_substr(trim($name), 0, 40) ?: 'fichier';
}

function upload_error_message(int $code): string
{
    switch ($code) {
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'fichier trop volumineux pour le serveur (limite PHP : ' . ini_get('upload_max_filesize') . ').';
        case UPLOAD_ERR_PARTIAL:
            return "envoi interrompu, réessaie.";
        case UPLOAD_ERR_NO_TMP_DIR:
        case UPLOAD_ERR_CANT_WRITE:
            return "le serveur ne peut pas enregistrer le fichier (dossier temporaire).";
        default:
            return "erreur pendant l'envoi.";
    }
}

/**
 * Enregistre les photos envoyées pour un produit.
 * @return array{saved:int, errors:string[]}
 */
function save_product_images(int $productId, array $files): array
{
    $result = ['saved' => 0, 'errors' => []];
    $list = normalize_uploaded_files($files);

    // Aucun fichier choisi : rien à faire
    $list = array_values(array_filter($list, function ($f) {
        return $f['error'] !== UPLOAD_ERR_NO_FILE;
    }));
    if (!$list) {
        return $result;
    }

    if (!extension_loaded('gd')) {
        $result['errors'][] = "L'extension PHP GD n'est pas activée : impossible de traiter les photos "
            . "(dans XAMPP : php.ini, enlève le point-virgule devant extension=gd, puis redémarre Apache).";
        return $result;
    }

    try {
        $dir = ensure_uploads_dir();
    } catch (RuntimeException $e) {
        $result['errors'][] = $e->getMessage();
        return $result;
    }

    $stmt = db()->prepare('SELECT COUNT(*), COALESCE(MAX(sort_order), -1) FROM product_images WHERE product_id = ?');
    $stmt->execute([$productId]);
    [$count, $maxOrder] = array_map('intval', $stmt->fetch(PDO::FETCH_NUM));

    foreach ($list as $file) {
        $label = safe_label($file['name']);

        if ($count >= PRODUCT_IMAGES_MAX) {
            $result['errors'][] = $label . ' : maximum ' . PRODUCT_IMAGES_MAX . ' photos par produit.';
            continue;
        }

        try {
            $baseName = process_one_image($file, $dir);

            $insert = db()->prepare('INSERT INTO product_images (product_id, file_name, sort_order) VALUES (?, ?, ?)');
            $insert->execute([$productId, $baseName, ++$maxOrder]);

            $count++;
            $result['saved']++;
        } catch (RuntimeException $e) {
            $result['errors'][] = $label . ' : ' . $e->getMessage();
        }
    }

    return $result;
}

/**
 * Contrôle, redimensionne et enregistre UNE image. Retourne son nom de base (sans extension).
 * @throws RuntimeException avec un message lisible par l'administrateur
 */
function process_one_image(array $file, string $dir): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException(upload_error_message($file['error']));
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('fichier invalide.');
    }
    if ($file['size'] > PRODUCT_IMAGE_MAX_BYTES) {
        throw new RuntimeException('trop lourd (8 Mo maximum).');
    }

    // Type réel d'après le CONTENU du fichier
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        throw new RuntimeException("ce n'est pas une image valide (JPEG, PNG ou WebP).");
    }
    $type = $info[2];
    if (!in_array($type, [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
        throw new RuntimeException('format non accepté (JPEG, PNG ou WebP seulement).');
    }
    if ($info[0] * $info[1] > PRODUCT_IMAGE_MAX_PIXELS) {
        throw new RuntimeException('dimensions trop grandes (25 mégapixels maximum).');
    }

    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = @imagecreatefromjpeg($file['tmp_name']);
            break;
        case IMAGETYPE_PNG:
            $source = @imagecreatefrompng($file['tmp_name']);
            break;
        default:
            $source = @imagecreatefromwebp($file['tmp_name']);
    }
    if (!$source) {
        throw new RuntimeException('image corrompue ou illisible.');
    }

    // Les photos de téléphone portent un "sens" dans les données EXIF : sans ça, elles s'affichent couchées
    if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
        $exif = @exif_read_data($file['tmp_name']);
        $angles = [3 => 180, 6 => -90, 8 => 90];
        $angle = $angles[(int) ($exif['Orientation'] ?? 1)] ?? 0;
        if ($angle !== 0) {
            $rotated = imagerotate($source, $angle, 0);
            if ($rotated) {
                $source = $rotated;
            }
        }
    }

    $baseName = bin2hex(random_bytes(16));
    $full  = $dir . '/' . $baseName . '.jpg';
    $thumb = $dir . '/' . $baseName . '_t.jpg';

    if (!write_resized_jpeg($source, PRODUCT_IMAGE_FULL, $full, 82)
        || !write_resized_jpeg($source, PRODUCT_IMAGE_THUMB, $thumb, 78)) {
        @unlink($full);
        @unlink($thumb);
        throw new RuntimeException("impossible d'enregistrer l'image (dossier uploads/products non accessible en écriture ?).");
    }

    return $baseName;
}

/** Réduit l'image (jamais agrandie) et l'enregistre en JPEG progressif, fond blanc pour les PNG transparents. */
function write_resized_jpeg($source, int $maxSide, string $path, int $quality): bool
{
    $width  = imagesx($source);
    $height = imagesy($source);
    $scale  = min(1, $maxSide / max($width, $height));
    $newW   = max(1, (int) round($width * $scale));
    $newH   = max(1, (int) round($height * $scale));

    $canvas = imagecreatetruecolor($newW, $newH);
    imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
    imagecopyresampled($canvas, $source, 0, 0, 0, 0, $newW, $newH, $width, $height);
    imageinterlace($canvas, true); // JPEG progressif : s'affiche petit à petit sur une connexion lente

    $ok = imagejpeg($canvas, $path, $quality);
    if ($ok) {
        @chmod($path, 0644);
    }
    return $ok;
}

// --- Suppression et ordre --------------------------------------------------

/** Supprime une photo (fichiers + ligne en base), seulement si elle appartient bien à ce produit. */
function delete_product_image(int $imageId, int $productId): bool
{
    $stmt = db()->prepare('SELECT file_name FROM product_images WHERE id = ? AND product_id = ?');
    $stmt->execute([$imageId, $productId]);
    $fileName = $stmt->fetchColumn();
    if ($fileName === false) {
        return false;
    }

    db()->prepare('DELETE FROM product_images WHERE id = ?')->execute([$imageId]);

    // Le nom vient de la base, mais on revérifie son format avant de toucher au disque
    if (preg_match('/^[a-f0-9]{32}$/', $fileName)) {
        @unlink(uploads_dir() . '/' . $fileName . '.jpg');
        @unlink(uploads_dir() . '/' . $fileName . '_t.jpg');
    }
    return true;
}

/** Place une photo en première position (= image principale). */
function make_image_first(int $imageId, int $productId): bool
{
    $stmt = db()->prepare('SELECT MIN(sort_order) FROM product_images WHERE product_id = ?');
    $stmt->execute([$productId]);
    $min = (int) $stmt->fetchColumn();

    $update = db()->prepare('UPDATE product_images SET sort_order = ? WHERE id = ? AND product_id = ?');
    $update->execute([$min - 1, $imageId, $productId]);
    return $update->rowCount() === 1;
}
