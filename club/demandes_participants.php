<?php

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
require_once "../includes/db.php";
function fetchDemandes($conn) {
    $stmt = $conn->prepare("SELECT * FROM demandes_participation JOIN utilisateurs ON demandes_participation.utilisateur_id = utilisateurs.id");
    $stmt->execute();
    return $stmt->fetchAll();
}

?>