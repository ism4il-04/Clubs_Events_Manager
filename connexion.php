<?php
try {
    $conn = new PDO(
        'mysql:host=localhost;dbname=clubs_events;charset=utf8',
        'root',
        ''
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die('Erreur de connexion : ' . $e->getMessage());
}
?>
