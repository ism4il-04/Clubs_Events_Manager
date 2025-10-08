<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['id'], $_POST['event_id'])) {
    header('Location: dashboard.php');
    exit;
}

$participant_id = $_SESSION['id'];
$event_id = (int)$_POST['event_id'];

// Avoid duplicate requests
$stmt = $conn->prepare("SELECT COUNT(*) FROM participations WHERE participant_id = ? AND event_id = ?");
$stmt->execute([$participant_id, $event_id]);

if ($stmt->fetchColumn() == 0) {
    $insert = $conn->prepare("INSERT INTO participations (participant_id, event_id, status, date_requested) VALUES (?, ?, 'en attente', NOW())");
    $insert->execute([$participant_id, $event_id]);
}

header('Location: dashboard.php');
exit;
