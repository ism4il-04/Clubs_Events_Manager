<?php
session_start();
if (!isset($_SESSION['email'])) {
   header("Location: login.php");
   exit();
}
require_once "../includes/db.php";
function getTotalEvents($conn) {
    $stmt = $conn->prepare("SELECT count(*) as c FROM evenements JOIN organisateur on organisateur_id=organisateur.id WHERE organisateur_id=? group by organisateur_id;");
    $stmt->execute([$_SESSION['id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['total'] ?? 0;
}

// Dummy data for demonstration
$stats = [
    'total' => 3,
    'pending' => 1,
    'approved' => 1,
    'rejected' => 1
];
$recent_events = [
    [
        'title' => 'Conférence IA et Robotique',
        'date' => '2024-11-15',
        'location' => 'Amphithéâtre A',
        'status' => 'En attente'
    ]
];
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
    <script src="../includes/script.js"></script>
    
</head>
<body>

<header>
    <div class="header-container">
        <div class="header-left">
            <div class="logo-box">
                <!-- School icon (example SVG) -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3L2 9l10 6 10-6-10-6z" />
                </svg>
            </div>
            <div>
                <h1>Portail Club</h1>
                <p>Club Infotech</p>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <p>Bonjour, <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                <p>Club</p>
            </div>
            <form action="../logout.php" method="post">
                <button type="submit" class="logout-button">
                    <!-- Logout icon (example SVG) -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5" />
                    </svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </div>
</header>

<div>
    <div class="tabs">
        <div class="tab active">Tableau de bord</div>
        <div class="tab" onclick="navigateTo('evenements_clubs.php')">Mes événements</div>
        <div class="tab">Ajouter un événement</div>
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
        <div class="stats-grid">
            <div class="stat-card-modern">
                <div class="stat-icon">📊</div>
                <div class="stat-content">
                    <h3><?php echo $stats['total']; ?></h3>
                    <p>Événements totaux</p>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon">⏳</div>
                <div class="stat-content">
                    <h3><?php echo $stats['pending']; ?></h3>
                    <p>En attente</p>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon">✅</div>
                <div class="stat-content">
                    <h3><?php echo $stats['approved']; ?></h3>
                    <p>Approuvés</p>
                </div>
            </div>
            <div class="stat-card-modern">
                <div class="stat-icon">❌</div>
                <div class="stat-content">
                    <h3><?php echo $stats['rejected']; ?></h3>
                    <p>Rejetés</p>
                </div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="quick-actions-modern">
            <div class="action-card-modern">
                <div class="action-icon">➕</div>
                <div class="action-content">
                    <h4>Créer un événement</h4>
                    <p>Lancez un nouvel événement pour votre club</p>
                    <a href="ajouter_evenement.php" class="btn-action-modern primary">Créer</a>
                </div>
            </div>
            <div class="action-card-modern">
                <div class="action-icon">👥</div>
                <div class="action-content">
                    <h4>Gérer les participants</h4>
                    <p>Consultez et gérez les demandes de participation</p>
                    <button class="btn-action-modern secondary" onclick="navigateTo('demandes_participants.php')">Voir</button>
                </div>
            </div>
            <div class="action-card-modern">
                <div class="action-icon">📅</div>
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
                                <div class="event-icon">📅</div>
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
