<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../auth/login.php");
   exit();
}
require_once "../includes/db.php";
// Real statistics for the current club (organisateur)
function fetchCount($conn, $sql, $params = []) {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return (int) $stmt->fetchColumn();
}

$organisateurId = $_SESSION['id'];

$stats = [
    'total'    => fetchCount($conn, "SELECT COUNT(*) FROM evenements WHERE organisateur_id = ?", [$organisateurId]),
    'pending'  => fetchCount($conn, "SELECT COUNT(*) FROM evenements WHERE organisateur_id = ? AND status = 'En attente'", [$organisateurId]),
    // Consider validated/approved as events that are available or ongoing/completed
    'approved' => fetchCount($conn, "SELECT COUNT(*) FROM evenements WHERE organisateur_id = ? AND status IN ('Disponible','En cours','Terminé','Sold out')", [$organisateurId]),
    'rejected' => fetchCount($conn, "SELECT COUNT(*) FROM evenements WHERE organisateur_id = ? AND status = 'Rejeté'", [$organisateurId])
];

// Recent events for this club
$stmt = $conn->prepare("SELECT nomEvent AS title, dateDepart AS date, lieu AS location, status FROM evenements WHERE organisateur_id = ? ORDER BY dateDepart DESC LIMIT 5");
$stmt->execute([$organisateurId]);
$recent_events = $stmt->fetchAll(PDO::FETCH_ASSOC);
include '../includes/header.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Club Dashboard</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="../includes/script.js"></script>
    
</head>
<body>


<div>
    <div class="tabs">
        <div class="tab active">Tableau de bord</div>
        <div class="tab" onclick="navigateTo('evenements_clubs.php')">Mes événements</div>
        <div class="tab" onclick="navigateTo('ajouter_evenement.php')">Ajouter un événement</div>
        <div class="tab" onclick="navigateTo('demandes_participants.php')">Participants</div>
        <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
        <div class="tab" onclick="navigateTo('certificats.php')">Certificats</div>

    </div>
    
    <div class="events-container">
        <div class="events-header">
            <h2>Tableau de bord</h2>
            <p>Vue d'ensemble de vos activités et statistiques</p>
        </div>
        
        <!-- Statistics Cards -->
        <style>
            .stat-card-modern { background: white; border-radius: 16px; padding: 24px; box-shadow: 0 8px 32px rgba(0,0,0,0.08); }
            .stat-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px; }
            .stat-title { color: #718096; font-size: 0.95rem; margin: 0; font-weight: 600; }
            .stat-value { font-size: 2rem; font-weight: 700; color: #1a202c; margin: 0; }
            .stat-icon { width: 44px; height: 44px; background: linear-gradient(135deg, rgba(102,126,234,.15), rgba(118,75,162,.15)); border-radius: 12px; display:flex; align-items:center; justify-content:center; }
            .stat-icon i { font-size: 1.4rem; color: #667eea; }
            .action-icon { width: 44px; height: 44px; background: linear-gradient(135deg, rgba(102,126,234,.15), rgba(118,75,162,.15)); border-radius: 12px; display:flex; align-items:center; justify-content:center; margin-bottom: 16px; }
            .action-icon i { font-size: 1.3rem; color: #667eea; }
        </style>
        <div class="stats-grid">
            <div class="stat-card-modern">
                <div class="stat-header">
                    <p class="stat-title">Événements totaux</p>
                    <div class="stat-icon"><i class="bi bi-bar-chart-fill"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['total']; ?></div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-header">
                    <p class="stat-title">En attente</p>
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['pending']; ?></div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-header">
                    <p class="stat-title">Approuvés</p>
                    <div class="stat-icon"><i class="bi bi-check-circle-fill"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['approved']; ?></div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-header">
                    <p class="stat-title">Rejetés</p>
                    <div class="stat-icon"><i class="bi bi-x-circle-fill"></i></div>
                </div>
                <div class="stat-value"><?php echo $stats['rejected']; ?></div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions-modern">
            <div class="action-card-modern">
                <div class="action-icon"><i class="bi bi-plus-circle"></i></div>
                <div class="action-content">
                    <h4>Créer un événement</h4>
                    <p>Lancez un nouvel événement pour votre club</p>
                    <a href="ajouter_evenement.php" class="btn-action-modern primary">Créer</a>
                </div>
            </div>
            <div class="action-card-modern">
                <div class="action-icon"><i class="bi bi-people-fill"></i></div>
                <div class="action-content">
                    <h4>Gérer les participants</h4>
                    <p>Consultez et gérez les demandes de participation</p>
                    <button class="btn-action-modern secondary" onclick="navigateTo('demandes_participants.php')">Voir</button>
                </div>
            </div>
            <div class="action-card-modern">
                <div class="action-icon"><i class="bi bi-calendar-event"></i></div>
                <div class="action-content">
                    <h4>Mes événements</h4>
                    <p>Consultez tous vos événements et leur statut</p>
                    <button class="btn-action-modern secondary" onclick="navigateTo('evenements_clubs.php')">Voir</button>
                </div>
            </div>
        </div>
        
        <!-- Recent Events -->
        <div class="recent-section">
            <h3>Événements récents</h3>
            <div class="events-list">
                <?php foreach ($recent_events as $event): ?>
                    <div class="event-card">
                        <div class="event-card-inner">
                            <div class="event-image">
                                <div class="event-icon"><i class="bi bi-calendar-event"></i></div>
                            </div>
                            <div class="event-content">
                                <div>
                                    <div class="event-header">
                                        <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                                        <span class="event-status status-pending"><?php echo htmlspecialchars($event['status']); ?></span>
                                    </div>
                                    <p class="event-description">Événement récemment créé</p>
                                    <div class="event-info">
                                        <div class="info-item">
                                            <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                            </svg>
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </div>
                                        <div class="info-item">
                                            <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                            </svg>
                                            <?php echo htmlspecialchars($event['date']); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="event-actions">
                                    <button class="btn-details">
                                        <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                        </svg>
                                        Détails
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

</body>
</html>
