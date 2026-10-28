<?php
/**
 * Connexion à MySQL avec PDO.
 * Utilisation : $stmt = db()->prepare('SELECT * FROM products WHERE id = ?');
 *               $stmt->execute([$id]);
 */
require_once __DIR__ . '/config.php';

function db(): PDO
{
    // "static" : la connexion est créée une seule fois puis réutilisée
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // erreur SQL = exception
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                  // vraies requêtes préparées
            ]);
        } catch (PDOException $e) {
            error_log('Connexion BD impossible : ' . $e->getMessage());
            http_response_code(500);
            exit(APP_DEBUG
                ? 'Connexion à la base impossible : ' . htmlspecialchars($e->getMessage())
                : 'Service momentanément indisponible.');
        }
    }

    return $pdo;
}
