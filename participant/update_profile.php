<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit;
}

$id = $_SESSION['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $telephone = trim($_POST['telephone']);
    $filiere = trim($_POST['filiere']);
    $annee = trim($_POST['annee']);

    $stmt = $conn->prepare("
        UPDATE etudiants 
        SET nom = ?, prenom = ?, telephone = ?, filiere = ?, annee = ?
        WHERE id = ?
    ");
    $stmt->execute([$nom, $prenom, $telephone, $filiere, $annee, $id]);

    header('Location: profile.php');
    exit;
}
