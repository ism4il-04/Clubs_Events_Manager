<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../auth/login.php");
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
                throw new Exception("La suppression des événements n'est pas autorisée.");
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        .container-admin {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        .table-responsive {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
            overflow: hidden;
        }
        .table {
            margin-bottom: 0;
        }
        .table thead th {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            border: none;
            font-weight: 600;
            padding: 1rem;
        }
        .table tbody td {
            padding: 1rem;
            vertical-align: middle;
        }
        .table tbody tr:hover {
            background-color: #f8f9fa;
        }
        .badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .badge.bg-success {
            background: linear-gradient(135deg, #10b981, #059669) !important;
        }
        .badge.bg-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        }
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563) !important;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            color: white;
        }
        .form-select {
            background: white;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        .form-select:focus {
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
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
    <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
</div>
<div class="container-admin">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-clipboard-check me-2"></i>Tous les Événements</h2>
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
                    <td><?= $event['places'] ? htmlspecialchars($event['places']) : 'Illimité' ?></td>
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
                        
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>