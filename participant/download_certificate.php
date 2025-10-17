<?php
session_start();
if (!isset($_SESSION["email"])) {
    header("Location: ../login.php");
    exit;
}

include "../includes/db.php";

// Get certificate ID from query string
$certificateId = $_GET['id'] ?? null;

if (!$certificateId) {
    die("Certificate ID not provided");
}

// Get the certificate path from database
$stmt = $conn->prepare("SELECT p.attestation, e.nomEvent 
                        FROM participation p 
                        JOIN evenements e ON p.evenement_id = e.idEvent 
                        WHERE p.etudiant_id = ? AND p.attestation = ?");
$stmt->execute([$_SESSION['id'], $certificateId]);
$certificate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$certificate) {
    die("Certificate not found or you don't have permission to access it");
}

$filePath = '../' . $certificate['attestation'];

if (!file_exists($filePath)) {
    die("Certificate file not found: " . htmlspecialchars($certificate['attestation']));
}

// Set headers for PDF download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, max-age=0, must-revalidate');
header('Pragma: public');

// Clear output buffer
ob_clean();
flush();

// Read and output file
readfile($filePath);
exit;
?>

