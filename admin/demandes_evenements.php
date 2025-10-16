<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";

// Traitement des actions de validation/rejet
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $admin_id = $_SESSION['id'];
    
    try {
        // Handle new event validation/rejection
        if (isset($_POST['event_id']) && in_array($action, ['valider', 'refuser'])) {
            $event_id = $_POST['event_id'];
            
            if ($action === 'valider') {
                $stmt = $conn->prepare("UPDATE evenements SET status = 'Disponible', admin_id = ?, date_validation = NOW() WHERE idEvent = ?");
                $stmt->execute([$admin_id, $event_id]);
                $message = "Événement validé avec succès !";
            } elseif ($action === 'refuser') {
                $stmt = $conn->prepare("UPDATE evenements SET status = 'Rejeté', admin_id = ?, date_validation = NOW() WHERE idEvent = ?");
                $stmt->execute([$admin_id, $event_id]);
                $message = "Événement refusé.";
            }
        }
        
        // Handle modification requests
        if (isset($_POST['event_id']) && in_array($action, ['approuver_modification', 'rejeter_modification'])) {
            $event_id = $_POST['event_id'];
            
            // Get event details
            $stmt = $conn->prepare("SELECT * FROM evenements WHERE idEvent = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event && $event['status'] === 'Modification demandée') {
                if ($action === 'approuver_modification') {
                    // Decode and apply modifications
                    $modificationData = json_decode($event['modification_data'], true);
                    
                    if ($modificationData) {
                        $stmt = $conn->prepare("UPDATE evenements SET 
                            nomEvent = ?, 
                            descriptionEvenement = ?, 
                            categorie = ?, 
                            lieu = ?, 
                            places = ?, 
                            dateDepart = ?, 
                            heureDepart = ?, 
                            dateFin = ?, 
                            heureFin = ?, 
                            image = ?, 
                            status = 'Disponible',
                            motif_demande = NULL,
                            modification_data = NULL
                            WHERE idEvent = ?");
                        $stmt->execute([
                            $modificationData['nomEvent'],
                            $modificationData['descriptionEvenement'],
                            $modificationData['categorie'],
                            $modificationData['lieu'],
                            $modificationData['places'],
                            $modificationData['dateDepart'],
                            $modificationData['heureDepart'],
                            $modificationData['dateFin'],
                            $modificationData['heureFin'],
                            $modificationData['image'],
                            $event_id
                        ]);
                        
                        $message = "Modification approuvée et appliquée avec succès !";
                    }
                } elseif ($action === 'rejeter_modification') {
                    // Clear modification request and restore to Disponible
                    $stmt = $conn->prepare("UPDATE evenements SET status = 'Disponible', motif_demande = NULL, modification_data = NULL WHERE idEvent = ?");
                    $stmt->execute([$event_id]);
                    
                    $message = "Demande de modification rejetée.";
                }
            }
        }
        
        // Handle cancellation requests
        if (isset($_POST['event_id']) && in_array($action, ['approuver_annulation', 'rejeter_annulation'])) {
            $event_id = $_POST['event_id'];
            
            // Get event details
            $stmt = $conn->prepare("SELECT * FROM evenements WHERE idEvent = ?");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($event && $event['status'] === 'Annulation demandée') {
                if ($action === 'approuver_annulation') {
                    // Cancel the event
                    $stmt = $conn->prepare("UPDATE evenements SET status = 'Annulé', motif_demande = NULL WHERE idEvent = ?");
                    $stmt->execute([$event_id]);
                    
                    $message = "Demande d'annulation approuvée. L'événement a été annulé.";
                } elseif ($action === 'rejeter_annulation') {
                    // Restore event to Disponible
                    $stmt = $conn->prepare("UPDATE evenements SET status = 'Disponible', motif_demande = NULL WHERE idEvent = ?");
                    $stmt->execute([$event_id]);
                    
                    $message = "Demande d'annulation rejetée.";
                }
            }
        }
        
        header("Location: demandes_evenements.php?message=" . urlencode($message));
        exit();
    } catch (Exception $e) {
        $error = "Erreur lors du traitement de la demande: " . $e->getMessage();
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

// Récupérer les demandes de modification en attente
function fetchModificationRequests($conn) {
    $stmt = $conn->prepare("SELECT e.*, o.clubNom, u.email as organisateur_email 
        FROM evenements e 
        JOIN organisateur o ON e.organisateur_id = o.id 
        JOIN utilisateurs u ON o.id = u.id 
        WHERE e.status = 'Modification demandée' 
        ORDER BY e.idEvent DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Récupérer les demandes d'annulation en attente
function fetchCancellationRequests($conn) {
    $stmt = $conn->prepare("SELECT e.*, o.clubNom, u.email as organisateur_email 
        FROM evenements e 
        JOIN organisateur o ON e.organisateur_id = o.id 
        JOIN utilisateurs u ON o.id = u.id 
        WHERE e.status = 'Annulation demandée' 
        ORDER BY e.idEvent DESC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

$pending_events = fetchPendingEvents($conn);
$modification_requests = fetchModificationRequests($conn);
$cancellation_requests = fetchCancellationRequests($conn);
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
        
        .request-tab {
            padding: 12px 24px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            font-size: 1rem;
            font-weight: 600;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .request-tab:hover {
            color: #007bff;
            background: #f8f9fa;
        }
        
        .request-tab.active {
            color: #007bff;
            border-bottom-color: #007bff;
            background: #f0f7ff;
        }
        
        .badge {
            background: #007bff;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .badge-warning {
            background: #ffc107;
            color: #856404;
        }
        
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        
        .tab-content {
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>

<div class="tabs">
    <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
    <div class="tab active">Demandes d'événements</div>
    <div class="tab" onclick="navigateTo('evenements.php')">Tous les événements</div>
    <div class="tab" onclick="navigateTo('clubs.php')">Gestion des clubs</div>
    <div class="tab" onclick="navigateTo('utilisateurs.php')">Utilisateurs</div>
    <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
</div>

<div class="events-container">
    <div class="events-header">
        <h2>Gestion des Demandes</h2>
        <p>Validez ou refusez les demandes des clubs</p>
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
    
    <!-- Request Type Tabs -->
    <div class="request-tabs" style="display: flex; gap: 10px; margin-bottom: 30px; border-bottom: 2px solid #e9ecef;">
        <button class="request-tab active" onclick="showTab('nouveaux')" id="tab-nouveaux">
            Nouveaux événements 
            <?php if (count($pending_events) > 0): ?>
                <span class="badge"><?= count($pending_events) ?></span>
            <?php endif; ?>
        </button>
        <button class="request-tab" onclick="showTab('modifications')" id="tab-modifications">
            Demandes de modification 
            <?php if (count($modification_requests) > 0): ?>
                <span class="badge badge-warning"><?= count($modification_requests) ?></span>
            <?php endif; ?>
        </button>
        <button class="request-tab" onclick="showTab('annulations')" id="tab-annulations">
            Demandes d'annulation 
            <?php if (count($cancellation_requests) > 0): ?>
                <span class="badge badge-danger"><?= count($cancellation_requests) ?></span>
            <?php endif; ?>
        </button>
    </div>
    
    <!-- Nouveaux événements -->
    <div id="content-nouveaux" class="tab-content">
        <?php if (empty($pending_events)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-calendar-check"></i>
                <h3>Aucune demande en attente</h3>
                <p>Tous les nouveaux événements ont été traités.</p>
            </div>
        <?php else: ?>
            <div class="events-list">
                <?php foreach ($pending_events as $event): ?>
                <div class="event-card">
                    <div class="event-card-inner">
                        <div class="event-image">
                            <?php if (!empty($event['image']) && file_exists('../' . $event['image'])): ?>
                                <img src="../<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['nomEvent']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                            <?php else: ?>
                                <div class="event-icon"><i class="bi bi-calendar-event"></i></div>
                            <?php endif; ?>
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
                                        <?= $event['places'] ? htmlspecialchars($event['places']) . ' places' : 'Places illimitées' ?>
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
                                        <i class="bi bi-check-circle-fill me-1"></i>Valider
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?= $event['idEvent'] ?>">
                                    <input type="hidden" name="action" value="refuser">
                                    <button type="submit" class="btn-reject" onclick="return confirm('Êtes-vous sûr de vouloir refuser cet événement ?')">
                                        <i class="bi bi-x-circle-fill me-1"></i>Refuser
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

    <!-- Demandes de modification -->
    <div id="content-modifications" class="tab-content" style="display: none;">
        <?php if (empty($modification_requests)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-edit"></i>
                <h3>Aucune demande de modification</h3>
                <p>Toutes les demandes de modification ont été traitées.</p>
            </div>
        <?php else: ?>
            <div class="events-list">
                <?php foreach ($modification_requests as $modif): 
                    $modifData = json_decode($modif['modification_data'], true);
                ?>
                <div class="event-card">
                    <div class="event-card-inner">
                        <div class="event-image" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                            <?php 
                            $newImage = $modifData['image'] ?? $modif['image'];
                            if (!empty($newImage) && file_exists('../' . $newImage)): ?>
                                <img src="../<?= htmlspecialchars($newImage) ?>" alt="<?= htmlspecialchars($modifData['nomEvent'] ?? $modif['nomEvent']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <div class="event-icon"><i class="bi bi-pencil-square"></i></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="event-content">
                            <div>
                                <div class="event-header">
                                    <div>
                                        <h3 class="event-title"><?= htmlspecialchars($modifData['nomEvent'] ?? '') ?></h3>
                                        <small style="color: #6c757d;">Événement original: <?= htmlspecialchars($modif['nomEvent']) ?></small>
                                    </div>
                                    <span class="event-status" style="background: #fff3cd; color: #856404;">MODIFICATION</span>
                                </div>
                                
                                <div style="background: #fff3cd; padding: 10px; border-radius: 8px; margin: 10px 0;">
                                    <strong><i class="bi bi-info-circle"></i> Motif:</strong> <?= htmlspecialchars($modif['motif_demande']) ?>
                                </div>
                                
                                <p class="event-description"><?= htmlspecialchars($modifData['descriptionEvenement'] ?? '') ?></p>
                                
                                <div class="event-info">
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?= htmlspecialchars($modifData['lieu'] ?? '') ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                        </svg>
                                        <?= ($modifData['places'] ?? null) ? htmlspecialchars($modifData['places']) . ' places' : 'Places illimitées' ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?= htmlspecialchars($modifData['dateDepart'] ?? '') ?> à <?= htmlspecialchars($modifData['heureDepart'] ?? '') ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                                        </svg>
                                        Fin: <?= htmlspecialchars($modifData['dateFin'] ?? '') ?> à <?= htmlspecialchars($modifData['heureFin'] ?? '') ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                        </svg>
                                        Club: <?= htmlspecialchars($modif['clubNom']) ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?= htmlspecialchars($modif['organisateur_email']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="event-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?= $modif['idEvent'] ?>">
                                    <input type="hidden" name="action" value="approuver_modification">
                                    <button type="submit" class="btn-validate" onclick="return confirm('Approuver cette modification ? Les changements seront appliqués à l\'événement.')">
                                        <i class="bi bi-check-circle-fill me-1"></i>Approuver
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?= $modif['idEvent'] ?>">
                                    <input type="hidden" name="action" value="rejeter_modification">
                                    <button type="submit" class="btn-reject" onclick="return confirm('Rejeter cette demande de modification ?')">
                                        <i class="bi bi-x-circle-fill me-1"></i>Rejeter
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

    <!-- Demandes d'annulation -->
    <div id="content-annulations" class="tab-content" style="display: none;">
        <?php if (empty($cancellation_requests)): ?>
            <div class="empty-state">
                <i class="fa-regular fa-calendar-times"></i>
                <h3>Aucune demande d'annulation</h3>
                <p>Toutes les demandes d'annulation ont été traitées.</p>
            </div>
        <?php else: ?>
            <div class="events-list">
                <?php foreach ($cancellation_requests as $cancel): ?>
                <div class="event-card">
                    <div class="event-card-inner">
                        <div class="event-image" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
                            <div class="event-icon"><i class="bi bi-x-octagon"></i></div>
                        </div>
                        
                        <div class="event-content">
                            <div>
                                <div class="event-header">
                                    <h3 class="event-title"><?= htmlspecialchars($cancel['nomEvent']) ?></h3>
                                    <span class="event-status" style="background: #f8d7da; color: #721c24;">ANNULATION</span>
                                </div>
                                
                                <div style="background: #f8d7da; padding: 10px; border-radius: 8px; margin: 10px 0; border-left: 4px solid #dc3545;">
                                    <strong><i class="bi bi-exclamation-triangle"></i> Motif d'annulation:</strong><br>
                                    <?= htmlspecialchars($cancel['motif_demande']) ?>
                                </div>
                                
                                <div class="event-info">
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?= htmlspecialchars($cancel['lieu']) ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                        </svg>
                                        <?= $cancel['places'] ? htmlspecialchars($cancel['places']) . ' places' : 'Places illimitées' ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?= htmlspecialchars($cancel['dateDepart']) ?> - <?= htmlspecialchars($cancel['dateFin']) ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3z"></path>
                                        </svg>
                                        Club: <?= htmlspecialchars($cancel['clubNom']) ?>
                                    </div>
                                    <div class="info-item">
                                        <svg class="info-icon" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                                        </svg>
                                        <?= htmlspecialchars($cancel['organisateur_email']) ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="event-actions">
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?= $cancel['idEvent'] ?>">
                                    <input type="hidden" name="action" value="approuver_annulation">
                                    <button type="submit" class="btn-validate" onclick="return confirm('Approuver cette annulation ? L\'événement sera marqué comme Annulé.')">
                                        <i class="bi bi-check-circle-fill me-1"></i>Approuver
                                    </button>
                                </form>
                                
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="event_id" value="<?= $cancel['idEvent'] ?>">
                                    <input type="hidden" name="action" value="rejeter_annulation">
                                    <button type="submit" class="btn-reject" onclick="return confirm('Rejeter cette demande d\'annulation ?')">
                                        <i class="bi bi-x-circle-fill me-1"></i>Rejeter
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
</div>

<script>
function navigateTo(page) {
    window.location.href = page;
}

function showTab(tabName) {
    // Hide all tab contents
    const contents = document.querySelectorAll('.tab-content');
    contents.forEach(content => content.style.display = 'none');
    
    // Remove active class from all tabs
    const tabs = document.querySelectorAll('.request-tab');
    tabs.forEach(tab => tab.classList.remove('active'));
    
    // Show selected tab content
    document.getElementById('content-' + tabName).style.display = 'block';
    
    // Add active class to selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
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