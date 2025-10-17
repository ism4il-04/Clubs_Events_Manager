<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Handle participation request submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['participer']) && isset($_POST['event_id'])) {
    $event_id = $_POST['event_id'];
    $etudiant_id = $_SESSION['id'];

    try {
        // Check if participation request already exists
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM participation WHERE etudiant_id = ? AND evenement_id = ?");
        $check_stmt->execute([$etudiant_id, $event_id]);
        $exists = $check_stmt->fetchColumn();

        if ($exists > 0) {
            header('Location: dashboard.php?error=already_requested');
            exit;
        }

        // Insert participation request
        $insert_stmt = $conn->prepare("INSERT INTO participation (etudiant_id, evenement_id, date_demande) VALUES (?, ?, NOW())");
        if ($insert_stmt->execute([$etudiant_id, $event_id])) {
            header('Location: dashboard.php?success=1');
            exit;
        } else {
            header('Location: dashboard.php?error=insert_failed');
            exit;
        }
    } catch (PDOException $e) {
        header('Location: dashboard.php?error=database_error');
        exit;
    }
}

// Handle participation request cancellation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['annuler']) && isset($_POST['event_id'])) {
    $event_id = $_POST['event_id'];
    $etudiant_id = $_SESSION['id'];

    try {
        // Delete participation request
        $delete_stmt = $conn->prepare("DELETE FROM participation WHERE etudiant_id = ? AND evenement_id = ?");
        if ($delete_stmt->execute([$etudiant_id, $event_id])) {
            header('Location: dashboard.php?cancelled=1');
            exit;
        } else {
            header('Location: dashboard.php?error=cancel_failed');
            exit;
        }
    } catch (PDOException $e) {
        header('Location: dashboard.php?error=database_error');
        exit;
    }
}

// If no valid action, redirect to dashboard
header('Location: dashboard.php');
exit;
?>