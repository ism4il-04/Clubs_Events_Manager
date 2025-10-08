<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
require_once "../includes/db.php";
include "../includes/header.php";

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['email'])) {
    $action = $_POST['action'];
    $email = $_POST['email'];
    
    try {
        if ($action === 'valider') {
            $stmt = $conn->prepare("UPDATE participation SET status = 'Accepté' WHERE etudiant_id = (SELECT id FROM etudiants WHERE email = ?)");
            $stmt->execute([$email]);
            $message = "Demande validée avec succès !";
        } elseif ($action === 'refuser') {
            $stmt = $conn->prepare("UPDATE participation SET status = 'Refusé' WHERE etudiant_id = (SELECT id FROM etudiants WHERE email = ?)");
            $stmt->execute([$email]);
            $message = "Demande refusée.";
        }
        
        // Redirect to prevent form resubmission
        header("Location: demandes_participants.php?message=" . urlencode($message));
        exit();
    } catch (Exception $e) {
        $error = "Erreur lors du traitement de la demande.";
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
    
    $sql .= " ORDER BY date_demande";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchEvents($conn) {
    $stmt = $conn->prepare("SELECT nomEvent FROM evenements JOIN organisateur ON organisateur.id = evenements.organisateur_id WHERE organisateur_id=?" );
    $stmt->execute([$_SESSION['id']]);
    return $stmt->fetchAll();
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
        <div class="tab" onclick="navigateTo('ajouter_evenement.php')">Ajouter un événement</div>
        <div class="tab active">Participants</div>
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
                                        <img src="../pic.jpg" class="photo-thumb" alt="Photo par défaut">
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
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($demande['email']) ?>">
                                        <input type="hidden" name="action" value="valider">
                                        <button type="submit" class="btn btn-validate" onclick="return confirm('Êtes-vous sûr de vouloir valider cette demande ?')">Valider</button>
                                    </form>
                                    
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="email" value="<?= htmlspecialchars($demande['email']) ?>">
                                        <input type="hidden" name="action" value="refuser">
                                        <button type="submit" class="btn btn-reject" onclick="return confirm('Êtes-vous sûr de vouloir refuser cette demande ?')">Refuser</button>
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
                                    <button class="btn btn-validate" onclick="handleAction('valider', '<?= htmlspecialchars($demande['email']) ?>')">Valider la demande</button>
                                    <button class="btn btn-reject" onclick="handleAction('refuser', '<?= htmlspecialchars($demande['email']) ?>')">Refuser la demande</button>
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