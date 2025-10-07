<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
require_once "../includes/db.php";
include "../includes/header.php";

function fetchDemandes($conn) {
    $stmt = $conn->prepare("SELECT * FROM demandes_participation JOIN utilisateurs ON demandes_participation.utilisateur_id = utilisateurs.id");
    $stmt->execute();
    return $stmt->fetchAll();
}

$demandes = fetchDemandes($conn);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/script.js"></script>
    <title>Demandes des Participants</title>
</head>
<body>
<div>
    <div class="tabs">
        <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
        <div class="tab" onclick="navigateTo('evenements_clubs.php')">Mes événements</div>
        <div class="tab active">Participants</div>
        <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
        <div class="tab" onclick="navigateTo('certificats.php')">Certificats</div>
    </div>
    
    <div class="events-container">
        <div class="events-header">
            <h2>Demandes des Participants</h2>
            <p>Gérez les demandes de participation à vos événements</p>
        </div>
        
        <div class="events-list">
            <?php if (empty($demandes)): ?>
                <div class="text-center py-5">
                    <h4>Aucune demande pour le moment</h4>
                    <p class="text-muted">Les demandes de participation apparaîtront ici</p>
                </div>
            <?php else: ?>
                <?php foreach ($demandes as $demande): ?>
                    <div class="event-card">
                        <div class="event-card-inner">
                            <div class="event-image">
                                <div class="event-icon">👤</div>
                            </div>
                            <div class="event-content">
                                <div>
                                    <div class="event-header">
                                        <h3 class="event-title"><?= htmlspecialchars($demande['nom'] ?? 'Utilisateur') ?></h3>
                                        <span class="event-status status-pending">En attente</span>
                                    </div>
                                    <p class="event-description">Demande de participation à l'événement</p>
                                    <div class="event-info">
                                        <div class="info-item">
                                            <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path>
                                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path>
                                            </svg>
                                            <?= htmlspecialchars($demande['email'] ?? 'Email non disponible') ?>
                                        </div>
                                        <div class="info-item">
                                            <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                            </svg>
                                            Date de demande
                                        </div>
                                    </div>
                                </div>
                                <div class="event-actions">
                                    <button class="btn-action btn-edit">Accepter</button>
                                    <button class="btn-action btn-cancel">Refuser</button>
                                    <button class="btn-action btn-secondary">Détails</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>