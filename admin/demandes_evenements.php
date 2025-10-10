<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";

// Traitement des actions de validation/rejet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['event_id'])) {
    $action = $_POST['action'];
    $event_id = $_POST['event_id'];
    $admin_id = $_SESSION['id'];
    
    try {
        if ($action === 'valider') {
            $stmt = $conn->prepare("UPDATE evenements SET status = 'Disponible', admin_id = ?, date_validation = NOW() WHERE idEvent = ?");
            $stmt->execute([$admin_id, $event_id]);
            $message = "Événement validé avec succès !";
        } elseif ($action === 'refuser') {
            $stmt = $conn->prepare("UPDATE evenements SET status = 'Rejeté', admin_id = ?, date_validation = NOW() WHERE idEvent = ?");
            $stmt->execute([$admin_id, $event_id]);
            $message = "Événement refusé.";
        }
        
        header("Location: demandes_evenements.php?message=" . urlencode($message));
        exit();
    } catch (Exception $e) {
        $error = "Erreur lors du traitement de la demande.";
    }
}

// Récupérer les événements en attente
function fetchPendingEvents($conn) {
    $stmt = $conn->prepare("SELECT e.*, o.clubNom, u.email as organisateur_email 
        FROM evenements e 
        JOIN organisateur o ON e.organisateur_id = o.id 
        JOIN utilisateurs u ON o.id = u.id 
        WHERE e.status = 'En attente' 
        ORDER BY e.dateDepart ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

$pending_events = fetchPendingEvents($conn);
$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes d'Événements - Administration</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .events-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .events-header {
            text-align: center;
            margin-bottom: 40px;
        }
        
        .events-header h2 {
            font-size: 2.5rem;
            color: #1a202c;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .events-header p {
            color: #718096;
            font-size: 1.1rem;
        }
        
        .event-card {
            background: white;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .event-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.15);
        }
        
        .event-card-inner {
            display: flex;
            min-height: 200px;
        }
        
        .event-image {
            width: 200px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        
        .status-pending {
            background: #fef3cd;
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
            color: #667eea;
        }
        
        .event-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }
        
        .btn-validate, .btn-reject {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-validate {
            background: #10b981;
            color: white;
        }
        
        .btn-validate:hover {
            background: #059669;
            transform: translateY(-2px);
        }
        
        .btn-reject {
            background: #ef4444;
            color: white;
        }
        
        .btn-reject:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        
        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            font-size: 1.3rem;
            color: #4b5563;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .empty-state p {
            color: #6b7280;
            font-size: 0.95rem;
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
    <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
    <div class="tab active">Demandes d'événements</div>
    <div class="tab" onclick="navigateTo('evenements.php')">Tous les événements</div>
    <div class="tab" onclick="navigateTo('clubs.php')">Gestion des clubs</div>
    <div class="tab" onclick="navigateTo('utilisateurs.php')">Utilisateurs</div>
</div>

<div class="events-container">
    <div class="events-header">
        <h2>Demandes d'Événements</h2>
        <p>Validez ou refusez les événements proposés par les clubs</p>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="alert alert-success" role="alert">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger" role="alert">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (empty($pending_events)): ?>
        <div class="empty-state">
            <i class="fa-regular fa-calendar-check"></i>
            <h3>Aucune demande en attente</h3>
            <p>Tous les événements ont été traités.</p>
        </div>
    <?php else: ?>
        <div class="events-list">
            <?php foreach ($pending_events as $event): ?>
                <div class="event-card">
                    <div class="event-card-inner">
                        <div class="event-image">
                            <div class="event-icon">📅</div>
                        </div>
                        
                        <div class="event-content">
                            <div>
                                <div class="event-header">
                                    <h3 class="event-title"><?= htmlspecialchars($event['nomEvent']) ?></h3>
                                    <span class="event-status status-pending"><?= htmlspecialchars($event['status']) ?></span>
                                </div>
                                
                                <p class="event-description"><?= htmlspecialchars($event['descriptionEvenement']) ?></p>
                                
                                <div class="event-info">
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?= htmlspecialchars($event['lieu']) ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                        </svg>
                                        <?= htmlspecialchars($event['places']) ?> places
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?= htmlspecialchars($event['dateDepart']) ?> à <?= htmlspecialchars($event['heureDepart']) ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Fin: <?= htmlspecialchars($event['dateFin']) ?> à <?= htmlspecialchars($event['heureFin']) ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                        </svg>
                                        Club: <?= htmlspecialchars($event['clubNom']) ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                        Organisateur: <?= htmlspecialchars($event['organisateur_email']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="event-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?= $event['idEvent'] ?>">
                                    <input type="hidden" name="action" value="valider">
                                    <button type="submit" class="btn-validate" onclick="return confirm('Êtes-vous sûr de vouloir valider cet événement ?')">
                                        ✅ Valider
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?= $event['idEvent'] ?>">
                                    <input type="hidden" name="action" value="refuser">
                                    <button type="submit" class="btn-reject" onclick="return confirm('Êtes-vous sûr de vouloir refuser cet événement ?')">
                                        ❌ Refuser
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function navigateTo(page) {
    window.location.href = page;
}

// Auto-hide alerts after 5 seconds
document.addEventListener("DOMContentLoaded", () => {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 500);
        }, 5000);
    });
});
</script>

</body>
</html>