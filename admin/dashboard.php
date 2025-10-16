<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../auth/login.php");
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
            transition: all 0.3s ease;
            display: block;
        }

        .stat-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.2);
        }

        .stat-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .stat-icon i {
            font-size: 1.4rem;
            color: white;
        }

        .stat-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 8px;
        }

        .stat-title {
            color: #718096;
            font-size: 0.95rem;
            margin: 0;
            font-weight: 600;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
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
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
            transition: all 0.3s ease;
        }

        .action-card-modern:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.2);
        }

        .action-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .action-icon i {
            font-size: 1.3rem;
            color: white;
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
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
        }

        .btn-action-modern:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }

        .events-list {
            display: grid;
            gap: 20px;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 123, 255, 0.2);
        }

        .event-card-inner {
            display: flex;
            min-height: 200px;
        }

        .event-image {
            width: 200px;
            background: linear-gradient(135deg, #007bff, #0056b3);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .event-icon {
            font-size: 3rem;
            color: white;
        }

        .event-content {
            flex: 1;
            padding: 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .event-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 16px;
        }

        .event-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a202c;
            margin: 0;
        }

        .event-status {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-en-attente {
            background: #fef3cd;
            color: #856404;
        }

        .status-disponible {
            background: #d4edda;
            color: #155724;
        }

        .status-en-cours {
            background: #cce7ff;
            color: #0066cc;
        }

        .status-termine {
            background: #e2e3e5;
            color: #383d41;
        }

        .status-annule {
            background: #f8d7da;
            color: #721c24;
        }

        .status-rejete {
            background: #f8d7da;
            color: #721c24;
        }

        .status-sold-out {
            background: #fff3cd;
            color: #856404;
        }

        .event-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4a5568;
            font-size: 0.95rem;
        }

        .info-icon {
            width: 16px;
            height: 16px;
            color: #007bff;
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

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
            <div class="stat-header">
                <p class="stat-title">Événements totaux</p>
                <div class="stat-icon"><i class="bi bi-bar-chart-fill"></i></div>
            </div>
            <div class="stat-value"><?php echo $stats['total_events']; ?></div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-header">
                <p class="stat-title">En attente de validation</p>
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
            </div>
            <div class="stat-value"><?php echo $stats['pending_events']; ?></div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-header">
                <p class="stat-title">Clubs actifs</p>
                <div class="stat-icon"><i class="bi bi-building"></i></div>
            </div>
            <div class="stat-value"><?php echo $stats['total_clubs']; ?></div>
        </div>
        <div class="stat-card-modern">
            <div class="stat-header">
                <p class="stat-title">Participants inscrits</p>
                <div class="stat-icon"><i class="bi bi-people-fill"></i></div>
            </div>
            <div class="stat-value"><?php echo $stats['total_participants']; ?></div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="quick-actions-modern">
        <div class="action-card-modern">
            <div class="action-icon"><i class="bi bi-clipboard-check"></i></div>
            <div class="action-content">
                <h4>Gérer les demandes</h4>
                <p>Validez ou refusez les événements proposés par les clubs</p>
                <a href="demandes_evenements.php" class="btn-action-modern primary">Voir les demandes</a>
            </div>
        </div>
        <div class="action-card-modern">
            <div class="action-icon"><i class="bi bi-calendar-event"></i></div>
            <div class="action-content">
                <h4>Tous les événements</h4>
                <p>Consultez et gérez tous les événements de la plateforme</p>
                <a href="evenements.php" class="btn-action-modern primary">Voir les événements</a>
            </div>
        </div>
        <div class="action-card-modern">
            <div class="action-icon"><i class="bi bi-building"></i></div>
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
                            <div class="event-icon"><i class="bi bi-calendar-event"></i></div>
                        </div>
                        <div class="event-content">
                            <div>
                                <div class="event-header">
                                    <h3 class="event-title"><?php echo htmlspecialchars($event['nomEvent']); ?></h3>
                                    <?php
                                    $statusClass = [
                                        'En attente' => 'status-en-attente',
                                        'Disponible' => 'status-disponible',
                                        'En cours' => 'status-en-cours',
                                        'Terminé' => 'status-termine',
                                        'Annulé' => 'status-annule',
                                        'Rejeté' => 'status-rejete',
                                        'Sold out' => 'status-sold-out'
                                    ][$event['status']] ?? 'status-en-attente';
                                    ?>
                                    <span class="event-status <?= $statusClass ?>">
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