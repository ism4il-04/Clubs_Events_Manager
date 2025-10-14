<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
require_once "../includes/db.php";
include "../includes/header.php";

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    try {
        traiterDemande($conn,$_POST["idEtu"],$_POST['idEve'], $_POST["action"]);
        $message = $action === 'Accepté' ? "Demande validée avec succès." : "Demande refusée avec succès.";
        // Redirect to prevent form resubmission
        header("Location: demandes_participants.php?message=" . urlencode($message." ".$_POST["idEtu"]." ".$_POST["idEve"]." ".$_POST["action"]));
        exit();
    } catch (Exception $e) {
        $error = "Erreur lors du traitement de la demande.";
        echo $e->getMessage();
    }
}

function fetchDemandes($conn, $statusFilter = '', $eventFilter = '') {
    $sql = "SELECT * FROM utilisateurs NATURAL JOIN etudiants JOIN participation ON etudiant_id = etudiants.id JOIN evenements ON evenement_id=evenements.idEvent WHERE organisateur_id=?";
    $params = [$_SESSION['id']];
    
    if (!empty($statusFilter)) {
        $sql .= " AND participation.etat = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($eventFilter)) {
        $sql .= " AND evenements.nomEvent = ?";
        $params[] = $eventFilter;
    }
    
    $sql .= " ORDER BY date_demande DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchEvents($conn) {
    $stmt = $conn->prepare("SELECT nomEvent FROM evenements JOIN organisateur ON organisateur.id = evenements.organisateur_id WHERE organisateur_id=?" );
    $stmt->execute([$_SESSION['id']]);
    return $stmt->fetchAll();
}

function traiterDemande($conn, $etu, $event, $rep) {
    // Update participation status
    $stmt = $conn->prepare("UPDATE participation SET etat = ? WHERE etudiant_id = ? AND evenement_id = ?");
    $stmt->execute([$rep, $etu, $event]);
    
    // Get event details
    $stmt = $conn->prepare("SELECT places, status FROM evenements WHERE idEvent = ?");
    $stmt->execute([$event]);
    $eventData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Only check if places is defined (not unlimited)
    if ($eventData && !empty($eventData['places'])) {
        // Count accepted participants
        $stmt = $conn->prepare("SELECT COUNT(*) as accepted FROM participation WHERE evenement_id = ? AND etat = 'Accepté'");
        $stmt->execute([$event]);
        $acceptedCount = $stmt->fetch(PDO::FETCH_ASSOC)['accepted'];
        
        if ($rep === 'Accepté') {
            // If full, update status to Sold out
            if ($acceptedCount >= $eventData['places']) {
                $stmt = $conn->prepare("UPDATE evenements SET status = 'Sold out' WHERE idEvent = ?");
                $stmt->execute([$event]);
            }
        } elseif ($rep === 'Refusé') {
            // If event was sold out and now has free places, make it available again
            if ($eventData['status'] === 'Sold out' && $acceptedCount < $eventData['places']) {
                $stmt = $conn->prepare("UPDATE evenements SET status = 'Disponible' WHERE idEvent = ?");
                $stmt->execute([$event]);
            }
        }
    }
}

// Get filter values
$statusFilter = $_GET['status'] ?? '';
$eventFilter = $_GET['event'] ?? '';
$message = $_GET['message'] ?? '';

$events = fetchEvents($conn);
$demandes = fetchDemandes($conn, $statusFilter, $eventFilter);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/script.js"></script>
    <title>Demandes des Participants</title>
    
    <style>
        .btn-validate {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-validate:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            color: white;
        }
        
        .btn-reject {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-reject:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            color: white;
        }
        
        .btn-view {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn-view:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            color: white;
        }
    </style>
</head>
<body>
<div>
    <div class="tabs">
        <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
        <div class="tab" onclick="navigateTo('evenements_clubs.php')">Mes événements</div>
        <div class="tab" onclick="navigateTo('ajouter_evenement.php')">Ajouter un événement</div>
        <div class="tab active" onclick="navigateTo('demandes_participants.php')">Participants</div>
        <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
        <div class="tab" onclick="navigateTo('certificats.php')">Certificats</div>
    </div>
    
    <div class="events-container">
        <div class="events-header">
            <h2>Demandes des Participants</h2>
            <p>Gérez les demandes de participation à vos événements</p>
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
        
        <form method="GET" class="filters mb-4">
            <div class="row g-2 align-items-center">
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Filtrer par statut</label>
                    <select id="statusFilter" name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous</option>
                        <option value="En attente" <?= $statusFilter === 'En attente' ? 'selected' : '' ?>>En attente</option>
                        <option value="Accepté" <?= $statusFilter === 'Accepté' ? 'selected' : '' ?>>Accepté</option>
                        <option value="Refusé" <?= $statusFilter === 'Refusé' ? 'selected' : '' ?>>Refusé</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="eventFilter" class="form-label">Nom de l'événement</label>
                    <select id="eventFilter" name="event" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les événements</option>
                        <?php foreach ($events as $event): ?>
                        <option value="<?= htmlspecialchars($event['nomEvent']) ?>" <?= $eventFilter === $event['nomEvent'] ? 'selected' : '' ?>><?= htmlspecialchars($event['nomEvent']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-secondary w-100" onclick="clearFilters()">Effacer</button>
                </div>
            </div>
        </form>

        <div class="table-container">
            <?php if (empty($demandes)): ?>
                <div class="text-center py-5">
                    <h4>Aucune demande pour le moment</h4>
                    <p class="text-muted">Les demandes de participation apparaîtront ici</p>
                </div>
            <?php else: ?>
                <table class="participants-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Nom Complet</th>
                            <th>Événement</th>
                            <th>État</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($demandes as $demande): ?>
                            <tr data-status="<?= htmlspecialchars($demande['status'] ?? 'En attente') ?>" data-event="<?= htmlspecialchars($demande['nomEvent'] ?? '') ?>">
                                <td class="photo-cell">
                                    <?php if (isset($demande['photo']) && !empty($demande['photo'])): ?>
                                        <img src="data:image/jpeg;base64,<?= $demande['photo'] ?>" class="photo-thumb" alt="Photo">
                                    <?php else: ?>
                                        <img src="../assets/photo/default.jpg" class="photo-thumb" alt="Photo par défaut">
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($demande['prenom'].' '.$demande['nom']) ?></td>
                                <td><?= htmlspecialchars($demande['nomEvent'] ?? 'Événement') ?></td>
                                <td>
                                    <?php 
                                    $status = $demande['etat'];
                                    $statusClass = '';
                                    switch($status) {
                                        case 'Accepté':
                                            $statusClass = 'accepted';
                                            break;
                                        case 'Refusé':
                                            $statusClass = 'rejected';
                                            break;
                                        default:
                                            $statusClass = 'pending';
                                    }
                                    ?>
                                    <span class="status-badge <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                                </td>
                                <td class="actions">
                                    <button class="btn btn-view" data-bs-toggle="modal" data-bs-target="#participantModal<?= $demande['id'] ?>">Voir détails</button>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="idEtu" value="<?= $demande['etudiant_id'] ?>">
                                        <input type="hidden" name="idEve" value="<?= $demande['evenement_id'] ?>">
                                        <input type="hidden" name="action" value="Accepté">
                                        <button type="submit" class="btn btn-validate" onclick="return confirm('Êtes-vous sûr de vouloir valider cette demande ?')">Valider</button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="idEtu" value="<?= $demande['etudiant_id'] ?>">
                                        <input type="hidden" name="idEve" value="<?= $demande['evenement_id'] ?>">
                                        <input type="hidden" name="action" value="Refusé">
                                        <button type="submit" class="btn btn-reject" >Refuser</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Participant Details Modal -->
                <?php foreach ($demandes as $demande): ?>
                    <div class="modal fade participant-modal" id="participantModal<?= $demande['id']?>" tabindex="-1" aria-labelledby="participantModalLabel<?= $demande['id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="participantModalLabel<?= $demande['id']?>">
                                        <?= htmlspecialchars($demande['prenom'] . " " . $demande['nom']) ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="participant-detail-section">
                                                <h6>Informations personnelles</h6>
                                                <div class="detail-item">
                                                    <span class="detail-label">Nom complet:</span>
                                                    <span class="detail-value"><?= htmlspecialchars($demande['prenom'] . " " . $demande['nom']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Email:</span>
                                                    <span class="detail-value"><?= htmlspecialchars($demande['email'] ?? 'Non disponible') ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Téléphone:</span>
                                                    <span class="detail-value"><?= htmlspecialchars($demande['telephone'] ?? 'Non disponible') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="participant-detail-section">
                                                <h6>Informations académiques</h6>
                                                <div class="detail-item">
                                                    <span class="detail-label">Filière:</span>
                                                    <span class="detail-value"><?= htmlspecialchars($demande['annee'] ." année ".$demande['filiere'] ?? 'Non disponible') ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Date de demande:</span>
                                                    <span class="detail-value"><?= htmlspecialchars($demande['date_demande'] ?? 'Non disponible') ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Nom de l'événement:</span>
                                                    <span class="detail-value"><?= htmlspecialchars($demande['nomEvent'] ?? 'Non disponible') ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <?php 
                                                        $status = $demande['etat'];
                                                        $statusClass = '';
                                                        switch($status) {
                                                            case 'Accepté':
                                                                $statusClass = 'accepted';
                                                                break;
                                                            case 'Refusé':
                                                                $statusClass = 'rejected';
                                                                break;
                                                            default:
                                                                $statusClass = 'pending';
                                                        }
                                                        ?>
                                                    <span class="detail-label">Statut:</span>
                                                    <span class="detail-value">
                                                        <span class="status-badge <?= $statusClass ?>"> <?= htmlspecialchars($demande['etat'] ?? 'Non disponible') ?> </span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="idEtu" value="<?= $demande['etudiant_id'] ?>">
                                        <input type="hidden" name="idEve" value="<?= $demande['evenement_id'] ?>">
                                        <input type="hidden" name="action" value="Accepté">
                                        <button type="submit" class="btn btn-validate" onclick="return confirm('Êtes-vous sûr de vouloir valider cette demande ?')">Valider la demande</button>
                                    </form>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="idEtu" value="<?= $demande['etudiant_id'] ?>">
                                        <input type="hidden" name="idEve" value="<?= $demande['evenement_id'] ?>">
                                        <input type="hidden" name="action" value="Refusé">
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Êtes-vous sûr de vouloir refuser cette demande ?')">Refuser la demande</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function clearFilters() {
    window.location.href = 'demandes_participants.php';
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