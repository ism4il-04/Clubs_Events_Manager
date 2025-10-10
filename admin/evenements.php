<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['event_id'])) {
        $action = $_POST['action'];
        $event_id = $_POST['event_id'];
        
        try {
            if ($action === 'supprimer') {
                $stmt = $conn->prepare("DELETE FROM evenements WHERE idEvent = ?");
                $stmt->execute([$event_id]);
                $message = "Événement supprimé avec succès.";
            } elseif ($action === 'changer_statut') {
                $new_status = $_POST['new_status'];
                $stmt = $conn->prepare("UPDATE evenements SET status = ? WHERE idEvent = ?");
                $stmt->execute([$new_status, $event_id]);
                $message = "Statut mis à jour.";
            }
            
            header("Location: evenements.php?message=" . urlencode($message));
            exit();
        } catch (Exception $e) {
            $error = "Erreur : " . $e->getMessage();
        }
    }
}

// Récupérer tous les événements
$stmt = $conn->prepare("
    SELECT e.*, o.clubNom, u.email as organisateur_email 
    FROM evenements e 
    JOIN organisateur o ON e.organisateur_id = o.id 
    JOIN utilisateurs u ON o.id = u.id 
    ORDER BY e.dateDepart DESC
");
$stmt->execute();
$events = $stmt->fetchAll();

$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tous les Événements - Admin</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container-admin {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .table-responsive {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .badge {
            font-size: 0.75em;
        }
        .actions form {
            display: inline-block;
            margin: 2px;
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>
<div class="tabs">
    <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
    <div class="tab" onclick="navigateTo('demandes_evenements.php')">Demandes d'événements</div>
    <div class="tab active" onclick="navigateTo('evenements.php')">Tous les événements</div>
    <div class="tab" onclick="navigateTo('clubs.php')">Gestion des clubs</div>
    <div class="tab" onclick="navigateTo('utilisateurs.php')">Utilisateurs</div>
</div>
<div class="container-admin">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>📋 Tous les Événements</h2>
        <span class="badge bg-primary"><?= count($events) ?> événements</span>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead class="table-dark">
                <tr>
                    <th>Événement</th>
                    <th>Club</th>
                    <th>Date</th>
                    <th>Lieu</th>
                    <th>Places</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($events as $event): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($event['nomEvent']) ?></strong><br>
                        <small class="text-muted"><?= htmlspecialchars($event['descriptionEvenement']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($event['clubNom']) ?></td>
                    <td>
                        <?= htmlspecialchars($event['dateDepart']) ?><br>
                        <small><?= htmlspecialchars($event['heureDepart']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($event['lieu']) ?></td>
                    <td><?= htmlspecialchars($event['places']) ?></td>
                    <td>
                        <span class="badge 
                            <?= $event['status'] == 'Disponible' ? 'bg-success' : 
                               ($event['status'] == 'En attente' ? 'bg-warning' : 'bg-secondary') ?>">
                            <?= htmlspecialchars($event['status']) ?>
                        </span>
                    </td>
                    <td class="actions">
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="event_id" value="<?= $event['idEvent'] ?>">
                            <select name="new_status" onchange="this.form.submit()" class="form-select form-select-sm">
                                <option value="">Changer statut</option>
                                <option value="En attente">En attente</option>
                                <option value="Disponible">Disponible</option>
                                <option value="Annulé">Annulé</option>
                            </select>
                            <input type="hidden" name="action" value="changer_statut">
                        </form>
                        
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="event_id" value="<?= $event['idEvent'] ?>">
                            <input type="hidden" name="action" value="supprimer">
                            <button type="submit" class="btn btn-danger btn-sm" 
                                    onclick="return confirm('Supprimer cet événement ?')">
                                🗑️
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>