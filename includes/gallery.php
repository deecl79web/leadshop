<?php
/**
 * Galerie de photos défilable horizontalement (balayage du doigt, molette, flèches clavier).
 * Fonctionne sans JavaScript ; app.js ajoute le compteur et les boutons ‹ › sur ordinateur.
 *
 * Variables attendues :
 *   $images       : photos du produit (lignes de product_images)
 *   $galleryAlt   : texte alternatif (nom du produit)
 *   $gallerySize  : 'thumb' (listes) ou 'full' (page produit)
 *   $galleryLink  : adresse où mène un clic sur la photo (optionnel)
 */
$images      = $images ?? [];
$gallerySize = $gallerySize ?? 'thumb';
$galleryAlt  = $galleryAlt ?? '';
$galleryLink = $galleryLink ?? '';
$total       = count($images);
?>
<div class="gallery gallery-<?= e($gallerySize) ?>" data-gallery>
    <div class="gallery-track" tabindex="0" role="group" aria-label="Photos : <?= e($galleryAlt) ?>">
        <?php foreach ($images as $index => $image): ?>
            <div class="gallery-slide">
                <?php if ($galleryLink !== ''): ?><a href="<?= e($galleryLink) ?>" tabindex="-1"><?php endif; ?>
                <img src="<?= e(product_image_url($image['file_name'], $gallerySize === 'thumb')) ?>"
                     alt="<?= e($galleryAlt) ?> (photo <?= $index + 1 ?> sur <?= $total ?>)"
                     <?= ($index > 0 || $gallerySize === 'thumb') ? 'loading="lazy"' : '' ?> decoding="async">
                <?php if ($galleryLink !== ''): ?></a><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php if ($total > 1): ?>
        <button type="button" class="gallery-btn gallery-prev" aria-label="Photo précédente" hidden>‹</button>
        <button type="button" class="gallery-btn gallery-next" aria-label="Photo suivante" hidden>›</button>
        <span class="gallery-count" aria-live="polite">1 / <?= $total ?></span>
    <?php endif; ?>
</div>
