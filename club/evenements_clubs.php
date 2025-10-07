<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

require_once "../includes/db.php";
function fetchEvents($conn) {
    $stmt = $conn->prepare("SELECT * FROM evenements JOIN organisateur where organisateur.id = evenements.organisateur_id");
    $stmt->execute();
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
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Optional JS for modals, dropdowns, etc. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <style>
        .event-card {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 15px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
        .status {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 0.9em;
        }
        .status.waiting { background-color: #f0ad4e; color: white; }
        .status.approved { background-color: #5cb85c; color: white; }
        .status.rejected { background-color: #d9534f; color: white; }

        .actions a {
            margin-right: 10px;
            text-decoration: none;
        }
        .container {
  max-width: 1140px; /* varies depending on screen size */
  margin: 0 auto;    /* centers the content */
  padding-left: 15px;
  padding-right: 15px;
}
.card:hover {
    transform: translateY(-5px);
    transition: 0.2s ease;
}

    </style>
    <title>My évents</title>
</head>
<body>
    <div>
        <div class="tabs">
        <div class="tab">Tableau de bord</div>
        <div class="tab active">Mes événements</div>
        <div class="tab">Participants</div>
        <div class="tab">Communications</div>
        <div class="tab">Certificats</div>
    </div>
    
    <div class="container mt-4">
    <h3 class="mb-3">Mes événements</h3>
    <div class="row">
        <?php foreach ($events as $event): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="card shadow-sm h-100">

                    <!-- Club Header -->
                    <div class="card-header d-flex align-items-center">
                        <img src="<?= htmlspecialchars($event['logo']) ?>" alt="Logo Club" 
                             class="me-2 rounded-circle" width="40" height="40">
                        <strong><?= htmlspecialchars($event['clubNom']) ?></strong>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($event['nom']) ?></h5>
                        <p class="card-text small text-muted"><?= htmlspecialchars($event['descriptionEvenement']) ?></p>

                        <ul class="list-unstyled mb-3">
                            <li><strong>Lieu:</strong> <?= htmlspecialchars($event['lieu']) ?></li>
                            <li><strong>Places:</strong> <?= htmlspecialchars($event['places']) ?></li>
                            <li><strong>Début:</strong> <?= htmlspecialchars($event['dateDepart']) ?> à <?= htmlspecialchars($event['heureDepart']) ?></li>
                            <li><strong>Fin:</strong> <?= htmlspecialchars($event['dateFin']) ?> à <?= htmlspecialchars($event['heureFin']) ?></li>
                        </ul>

                        <!-- Status -->
                        <?php
                            $status = $event['status'];
                            $badgeClass = [
                                'En attente' => 'bg-secondary',
                                'Rejeté' => 'bg-danger',
                                'Disponible' => 'bg-success',
                                'Sold out' => 'bg-warning text-dark',
                                'En cours' => 'bg-primary',
                                'Terminé' => 'bg-dark',
                                'Annulé' => 'bg-danger'
                            ][$status] ?? 'bg-secondary';
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= htmlspecialchars($status) ?></span>
                    </div>

                    <!-- Card Footer (actions) -->
                    <div class="card-footer d-flex justify-content-between">
                        <?php if (in_array($status, ['En attente'])): ?>
                            <a href="edit_event.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-primary">Modifier</a>
                            <a href="cancel_event.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-danger">Annuler</a>

                        <?php elseif (in_array($status, ['Disponible', 'Sold out'])): ?>
                            <a href="cancel_event.php?id=<?= $event['id'] ?>" class="btn btn-sm btn-outline-danger">Annuler</a>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>Aucune action</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>


    
</body>
</html>