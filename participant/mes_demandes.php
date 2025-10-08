<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['id'], $_POST['event_id'])) {
    header('Location: dashboard.php');
    exit;
}

$participant_id = $_SESSION['id'];
$event_id = (int)$_POST['event_id'];

$stmt = $conn->prepare("SELECT COUNT(*) FROM participation WHERE etudiant_id = ? AND evenement_id = ?");
$stmt->execute([$participant_id, $event_id]);

if ($stmt->fetchColumn() == 0) {
    $insert = $conn->prepare("INSERT INTO participation (etudiant_id, evenement_id, etat) VALUES (?, ?, 'en attente')");
    $insert->execute([$participant_id, $event_id]);
}

header('Location: dashboard.php');
exit;
