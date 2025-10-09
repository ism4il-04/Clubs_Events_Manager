<?php
require __DIR__ . '/../vendor/autoload.php';

    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();

try {
    $conn = new PDO(
        'mysql:host='.$_ENV["DB_HOST"].';port='.$_ENV["DB_PORT"].';dbname='.$_ENV["DB_NAME"].';charset=utf8',
        $_ENV["DB_USER"],
        $_ENV["DB_PASSWORD"]
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>
