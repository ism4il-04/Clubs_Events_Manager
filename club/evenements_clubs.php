<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

require_once "../includes/db.php";
function fetchEvents($conn) {
    $stmt = $conn->prepare("SELECT * FROM evenements JOIN organisateur ON organisateur.id = evenements.organisateur_id WHERE organisateur_id=?");
    $stmt->execute([$_SESSION['id']]);
    return $stmt->fetchAll();
}

$events = fetchEvents($conn, $_SESSION['email']);
include "../includes/header.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Optional JS for modals, dropdowns, etc. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/script.js"></script>

    <style>
        .events-actions {
            margin-top: 1rem;
        }
        
        .events-actions .btn {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        
        .events-actions .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
            color: white;
            text-decoration: none;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>

    <title>My évents</title>
</head>
<body>
<div>
    <div class="tabs">
        <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
        <div class="tab active">Mes événements</div>
        <div class="tab" onclick="navigateTo('ajouter_evenement.php')">Ajouter un événement</div>
        <div class="tab" onclick="navigateTo('demandes_participants.php')">Participants</div>
        <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
        <div class="tab" onclick="navigateTo('certificats.php')">Certificats</div>
        <div class="tab" onclick="navigateTo('profile_club.php')">Mon Profile</div>
    </div>
    
    <div class="events-container">
        <div class="events-header">
            <h2>Mes Événements</h2>
            <p>Gérez et suivez vos événements en temps réel</p>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success" style="margin-top: 1rem;">
                    <i class="bi bi-check-circle-fill"></i> <?= htmlspecialchars($_SESSION['success_message']) ?>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger" style="margin-top: 1rem;">
                    <i class="bi bi-x-circle-fill"></i> <?= htmlspecialchars($_SESSION['error_message']) ?>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>
            
            <div class="events-actions">
                <a href="ajouter_evenement.php" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Ajouter un événement
                </a>
            </div>
        </div>
        
        <div class="events-list">
        <?php foreach ($events as $event): ?>
            <div class="event-card">
                <div class="event-card-inner">
                    <!-- Event Image/Icon -->
                    <div class="event-image">
                        <?php if (!empty($event['image']) && file_exists('../' . $event['image'])): ?>
                            <img src="../<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['nomEvent']) ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                        <?php else: ?>
                            <div class="event-icon"><i class="bi bi-bullseye"></i></div>
                        <?php endif; ?>
                </div>

                    <!-- Event Content -->
                    <div class="event-content">
                        <div>
                            <div class="event-header">
                                <h3 class="event-title"><?= htmlspecialchars($event['nomEvent']) ?></h3>
                    <?php
                        // Determine status based on conditions
                        $currentDateTime = date('Y-m-d H:i:s');
                        $eventEndDateTime = $event['dateFin'] . ' ' . $event['heureFin'];
                        
                        // Count registered participants
                        $countStmt = $conn->prepare("SELECT COUNT(*) as registered FROM participation WHERE evenement_id = ? AND etat = 'Accepté'");
                        $countStmt->execute([$event['idEvent']]);
                        $registeredCount = $countStmt->fetch()['registered'];
                        
                        // Determine new status
                        $newStatus = $event['status'];
                        
                        // Check if event date has passed
                        if (strtotime($eventEndDateTime) < strtotime($currentDateTime)) {
                            $newStatus = 'Terminé';
                        }
                        // Check if no available places (count >= max places)
                        elseif (!empty($event['places']) && $registeredCount >= $event['places']) {
                            $newStatus = 'Sold out';
                        }
                        
                        // Update database if status has changed
                        if ($newStatus !== $event['status']) {
                            $updateStmt = $conn->prepare("UPDATE evenements SET status = ? WHERE idEvent = ?");
                            $updateStmt->execute([$newStatus, $event['idEvent']]);
                            $status = $newStatus;
                        } else {
                            $status = $event['status'];
                        }
                        
                        $statusClass = [
                            'En attente' => 'status-pending',
                            'Rejeté' => 'status-rejected',
                            'Disponible' => 'status-available',
                            'Sold out' => 'status-soldout',
                            'En cours' => 'status-ongoing',
                            'Terminé' => 'status-completed',
                            'Annulé' => 'status-cancelled'
                        ][$status] ?? 'status-pending';
                    ?>
                                <span class="event-status <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
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
                                    <?php
                                    if ($event['places']):
                                        echo htmlspecialchars($registeredCount) . ' / ' . htmlspecialchars($event['places']) . ' places';
                                    else:
                                        echo 'Places non définis';
                                    endif;
                                    ?>
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
                            </div>
                        </div>

                        <div class="event-actions">
                            <button type="button" class="btn-details" data-bs-toggle="modal" data-bs-target="#eventModal<?= $event['idEvent'] ?>">
                                <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                </svg>
                                Détails
                            </button>
                            
                            <?php if ($status === 'En attente'): ?>
                                <!-- En attente: Can modify and cancel directly (no admin verification) -->
                                <a href="modifier_evenement.php?id=<?= $event['idEvent'] ?>" class="btn-action btn-edit">Modifier</a>
                                <a href="annuler_evenement.php?id=<?= $event['idEvent'] ?>" class="btn-action btn-cancel" onclick="return confirm('Êtes-vous sûr de vouloir annuler cet événement ?');">Annuler</a>
                            <?php elseif ($status === 'Rejeté'): ?>
                                <!-- Rejeté: Can modify and cancel directly -->
                                <a href="modifier_evenement.php?id=<?= $event['idEvent'] ?>" class="btn-action btn-edit">Modifier</a>
                                <a href="annuler_evenement.php?id=<?= $event['idEvent'] ?>" class="btn-action btn-cancel" onclick="return confirm('Êtes-vous sûr de vouloir annuler cet événement ?');">Annuler</a>
                            <?php elseif (in_array($status, ['Disponible', 'Sold out'])): ?>
                                <!-- Disponible/Sold out: Request modification or cancellation (requires admin approval) -->
                                <a href="demande_modification.php?id=<?= $event['idEvent'] ?>" class="btn-action btn-edit">Demander modification</a>
                                <a href="demande_annulation.php?id=<?= $event['idEvent'] ?>" class="btn-action btn-cancel" onclick="return confirm('Envoyer une demande d\'annulation à l\'administrateur ?');">Demander annulation</a>
                            <?php elseif (in_array($status, ['En cours', 'Terminé', 'Annulé'])): ?>
                                <!-- En cours/Terminé/Annulé: No actions available -->
                                <button class="btn-action btn-secondary" disabled>Aucune action disponible</button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Event Details Modal -->
            <div class="modal fade" id="eventModal<?= $event['idEvent'] ?>" tabindex="-1" aria-labelledby="eventModalLabel<?= $event['idEvent'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="eventModalLabel<?= $event['idEvent'] ?>"><?= htmlspecialchars($event['nomEvent']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Informations générales</h6>
                                    <div class="mb-3">
                                        <strong>Organisateur: </strong> <?= htmlspecialchars($event['clubNom']) ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Description:</strong><br>
                                        <p class="mt-1"><?= htmlspecialchars($event['descriptionEvenement']) ?></p>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Lieu:</strong> <?= htmlspecialchars($event['lieu']) ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Places disponibles:</strong> <?= $event['places'] ? htmlspecialchars($event['places']) : 'non définis' ?>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-muted mb-3">Planning</h6>
                                    <div class="mb-3">
                                        <strong>Date de début:</strong> <?= htmlspecialchars($event['dateDepart']) ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Heure de début:</strong> <?= htmlspecialchars($event['heureDepart']) ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Date de fin:</strong> <?= htmlspecialchars($event['dateFin']) ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Heure de fin:</strong> <?= htmlspecialchars($event['heureFin']) ?>
                                    </div>
                                    <div class="mb-3">
                                        <strong>Statut:</strong> 
                                        <span class="event-status <?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                            <?php if (in_array($status, ['En attente', 'Rejeté'])): ?>
                                <a href="modifier_evenement.php?id=<?= $event['idEvent'] ?>" class="btn btn-primary">Modifier</a>
                            <?php elseif (in_array($status, ['Disponible', 'Sold out'])): ?>
                                <a href="demande_modification.php?id=<?= $event['idEvent'] ?>" class="btn btn-warning">Demander modification</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


    
</body>
</html>