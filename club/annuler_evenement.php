<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../auth/login.php");
    exit();
}

require_once "../includes/db.php";

// Get event ID
$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    header("Location: evenements_clubs.php");
    exit();
}

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM evenements WHERE idEvent = ? AND organisateur_id = ?");
$stmt->execute([$eventId, $_SESSION['id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    $_SESSION['error_message'] = "Événement non trouvé.";
    header("Location: evenements_clubs.php");
    exit();
}

// Check if event can be cancelled directly (only En attente and Rejeté)
if (!in_array($event['status'], ['En attente', 'Rejeté'])) {
    $_SESSION['error_message'] = "Cet événement ne peut pas être annulé directement. Veuillez faire une demande d'annulation.";
    header("Location: evenements_clubs.php");
    exit();
}

// Cancel the event
$stmt = $conn->prepare("UPDATE evenements SET status = 'Annulé' WHERE idEvent = ? AND organisateur_id = ?");
$stmt->execute([$eventId, $_SESSION['id']]);

$_SESSION['success_message'] = "L'événement a été annulé avec succès.";
header("Location: evenements_clubs.php");
exit();
?>

