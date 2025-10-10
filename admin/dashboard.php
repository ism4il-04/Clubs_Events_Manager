<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";

// Statistiques
function getTotalEvents($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM evenements");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getPendingEvents($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM evenements WHERE status = 'En attente'");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getTotalClubs($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM organisateur");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getTotalParticipants($conn) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM etudiants");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

$stats = [
    'total_events' => getTotalEvents($conn),
    'pending_events' => getPendingEvents($conn),
    'total_clubs' => getTotalClubs($conn),
    'total_participants' => getTotalParticipants($conn)
];

// Événements récents
$stmt = $conn->prepare(" SELECT e.*, o.clubNom FROM evenements e JOIN organisateur o ON e.organisateur_id = o.id ORDER BY e.dateDepart DESC LIMIT 5");
$stmt->execute();
$recent_events = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <script src="../includes/script.js"></script>
    <style>
        .dashboard-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card-modern {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .stat-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .stat-content h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0 0 4px 0;
        }
        
        .stat-content p {
            color: #718096;
            font-size: 0.95rem;
            margin: 0;
        }
        
        .quick-actions-modern {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .action-card-modern {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }
        
        .action-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }
        
        .action-icon {
            font-size: 2rem;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }
        
        .action-content h4 {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1a202c;
            margin: 0 0 8px 0;
        }
        
        .action-content p {
            color: #718096;
            font-size: 0.95rem;
            margin: 0 0 16px 0;
            line-height: 1.5;
        }
        
        .btn-action-modern {
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-action-modern.primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .btn-action-modern.primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
    </style>
</head>
<body>

<header>
    <div class="header-container">
        <div class="header-left">
            <div class="logo-box">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3L2 9l10 6 10-6-10-6z" />
                </svg>
            </div>
            <div>
                <h1>Portail Administration</h1>
                <p>ENSA Tétouan - École Nationale des Sciences Appliquées</p>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <p>Bonjour, <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                <p>Administrateur</p>
            </div>
            <form action="../logout.php" method="post">
                <button type="submit" class="logout-button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5" />
                    </svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </div>
</header>

<div class="tabs">
    <div class="tab active">Tableau de bord</div>
    <div class="tab" onclick="navigateTo('demandes_evenements.php')">Demandes d'événements</div>
    <div class="tab" onclick="navigateTo('evenements.php')">Tous les événements</div>
    <div class="tab" onclick="navigateTo('clubs.php')">Gestion des clubs</div>
    <div class="tab" onclick="navigateTo('utilisateurs.php')">Utilisateurs</div>
</div>

<div class="dashboard-container">
    <div class="events-header">
        <h2>Tableau de Bord Administrateur</h2>
        <p>Vue d'ensemble de la plateforme et statistiques</p>
    </div>
    
    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card-modern">
            <div class="stat-icon">📊</div>
            <div class="stat-content">
                <h3><?php echo $stats['total_events']; ?></h3>
                <p>Événements totaux</p>
            </div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-icon">⏳</div>
            <div class="stat-content">
                <h3><?php echo $stats['pending_events']; ?></h3>
                <p>En attente de validation</p>
            </div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-icon">🏫</div>
            <div class="stat-content">
                <h3><?php echo $stats['total_clubs']; ?></h3>
                <p>Clubs actifs</p>
            </div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-icon">👥</div>
            <div class="stat-content">
                <h3><?php echo $stats['total_participants']; ?></h3>
                <p>Participants inscrits</p>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions-modern">
        <div class="action-card-modern">
            <div class="action-icon">📋</div>
            <div class="action-content">
                <h4>Gérer les demandes</h4>
                <p>Validez ou refusez les événements proposés par les clubs</p>
                <a href="demandes_evenements.php" class="btn-action-modern primary">Voir les demandes</a>
            </div>
        </div>
        <div class="action-card-modern">
            <div class="action-icon">📅</div>
            <div class="action-content">
                <h4>Tous les événements</h4>
                <p>Consultez et gérez tous les événements de la plateforme</p>
                <a href="evenements.php" class="btn-action-modern primary">Voir les événements</a>
            </div>
        </div>
        <div class="action-card-modern">
            <div class="action-icon">🏫</div>
            <div class="action-content">
                <h4>Gestion des clubs</h4>
                <p>Administrez les clubs et leurs responsables</p>
                <a href="clubs.php" class="btn-action-modern primary">Gérer les clubs</a>
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
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['nomEvent']); ?></h3>
                                    <span class="event-status status-<?= strtolower(str_replace(' ', '-', $event['status'])) ?>">
                                        <?php echo htmlspecialchars($event['status']); ?>
                                    </span>
                                </div>
                                <p class="event-description"><?php echo htmlspecialchars($event['descriptionEvenement']); ?></p>
                                <div class="event-info">
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?php echo htmlspecialchars($event['lieu']); ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?php echo htmlspecialchars($event['dateDepart']); ?> à <?php echo htmlspecialchars($event['heureDepart']); ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                        </svg>
                                        <?php echo htmlspecialchars($event['clubNom']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

</body>
</html>