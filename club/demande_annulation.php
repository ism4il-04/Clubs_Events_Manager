<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";
include "../includes/header.php";

// Get event ID
$eventId = $_GET['id'] ?? null;

if (!$eventId) {
    header("Location: evenements_clubs.php");
    exit();
}

// Fetch event details
$stmt = $conn->prepare("SELECT * FROM evenements WHERE idEvent = ? AND organisateur_id = ?");
$stmt->execute([$eventId, $_SESSION['id']]);
$event = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$event) {
    header("Location: evenements_clubs.php");
    exit();
}

// Check if event is Disponible or Sold out
if (!in_array($event['status'], ['Disponible', 'Sold out'])) {
    $_SESSION['error_message'] = "Cet événement ne nécessite pas de demande d'annulation.";
    header("Location: evenements_clubs.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $motif = $_POST['motif'];
        
        // Validate required field
        if (empty($motif)) {
            throw new Exception("Le motif d'annulation est obligatoire.");
        }
        
        // Store cancellation request in demandes_annulations table
        // First, check if table exists, if not create it
        $conn->exec("CREATE TABLE IF NOT EXISTS demandes_annulations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            evenement_id INT NOT NULL,
            organisateur_id INT NOT NULL,
            motif TEXT NOT NULL,
            status ENUM('En attente', 'Approuvé', 'Rejeté') DEFAULT 'En attente',
            date_demande TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (evenement_id) REFERENCES evenements(idEvent) ON DELETE CASCADE
        )");
        
        // Insert cancellation request
        $stmt = $conn->prepare("INSERT INTO demandes_annulations (evenement_id, organisateur_id, motif) VALUES (?, ?, ?)");
        $stmt->execute([$eventId, $_SESSION['id'], $motif]);
        
        $_SESSION['success_message'] = "Demande d'annulation envoyée avec succès ! Elle sera examinée par l'administrateur.";
        header("Location: evenements_clubs.php");
        exit();
        
    } catch (Exception $e) {
        $error_message = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <title>Demande d'annulation</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/script.js"></script>
    
    <style>
        .form-container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .form-header h1 {
            color: #dc3545;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #6c757d;
            margin: 0;
        }
        
        .event-info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .event-info-box h5 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .event-info-box .info-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .event-info-box .info-row:last-child {
            border-bottom: none;
        }
        
        .event-info-box .info-label {
            font-weight: 600;
            color: #495057;
        }
        
        .event-info-box .info-value {
            color: #6c757d;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #856404;
        }
        
        .warning-box i {
            margin-right: 0.5rem;
        }
        
        .danger-box {
            background: #f8d7da;
            border: 1px solid #dc3545;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
            color: #721c24;
        }
        
        .danger-box i {
            margin-right: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            transition: border-color 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
        
        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #dc3545, #c82333);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: transform 0.2s ease;
            width: 100%;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }
        
        .btn-cancel {
            background: #6c757d;
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            text-decoration: none;
            display: block;
            text-align: center;
            transition: background-color 0.3s ease;
            margin-top: 1rem;
        }
        
        .btn-cancel:hover {
            background: #545b62;
            color: white;
        }
        
        .alert {
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .alert-danger {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="tabs">
        <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
        <div class="tab" onclick="navigateTo('evenements_clubs.php')">Mes événements</div>
        <div class="tab" onclick="navigateTo('ajouter_evenement.php')">Ajouter un événement</div>
        <div class="tab" onclick="navigateTo('demandes_participants.php')">Participants</div>
        <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
        <div class="tab" onclick="navigateTo('certificats.php')">Certificats</div>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h1><i class="bi bi-x-octagon me-2"></i>Demande d'annulation</h1>
            <p>Soumettez une demande d'annulation pour votre événement</p>
        </div>

        <div class="event-info-box">
            <h5>Informations de l'événement</h5>
            <div class="info-row">
                <span class="info-label">Nom :</span>
                <span class="info-value"><?= htmlspecialchars($event['nomEvent']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date :</span>
                <span class="info-value"><?= htmlspecialchars($event['dateDepart']) ?> - <?= htmlspecialchars($event['dateFin']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Lieu :</span>
                <span class="info-value"><?= htmlspecialchars($event['lieu']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Statut :</span>
                <span class="info-value"><?= htmlspecialchars($event['status']) ?></span>
            </div>
            <?php
            // Count participants
            $stmt = $conn->prepare("SELECT COUNT(*) as count FROM participation WHERE evenement_id = ? AND etat = 'Accepté'");
            $stmt->execute([$eventId]);
            $participantCount = $stmt->fetch()['count'];
            ?>
            <div class="info-row">
                <span class="info-label">Participants acceptés :</span>
                <span class="info-value"><?= $participantCount ?></span>
            </div>
        </div>

        <div class="warning-box">
            <i class="bi bi-info-circle-fill"></i>
            <strong>Important :</strong> Votre événement est actuellement <strong><?= htmlspecialchars($event['status']) ?></strong>. 
            Toute annulation nécessite l'approbation de l'administrateur.
        </div>

        <?php if ($participantCount > 0): ?>
        <div class="danger-box">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <strong>Attention :</strong> Cet événement a déjà <strong><?= $participantCount ?> participant(s) accepté(s)</strong>. 
            Ils seront notifiés en cas d'annulation.
        </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <strong><i class="bi bi-x-circle-fill me-1"></i>Erreur :</strong> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="motif" class="form-label">Motif de l'annulation *</label>
                <textarea class="form-control" id="motif" name="motif" rows="5" required 
                          placeholder="Expliquez en détail pourquoi vous souhaitez annuler cet événement. Ce motif sera examiné par l'administrateur et communiqué aux participants..."><?= htmlspecialchars($_POST['motif'] ?? '') ?></textarea>
                <small class="form-text">Soyez précis et professionnel dans votre explication</small>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-submit" onclick="return confirm('Êtes-vous sûr de vouloir envoyer cette demande d\'annulation ?');">
                    <i class="bi bi-send me-1"></i>Envoyer la demande d'annulation
                </button>
                <a href="evenements_clubs.php" class="btn-cancel">
                    <i class="bi bi-arrow-left me-1"></i>Retour aux événements
                </a>
            </div>
        </form>
    </div>
</body>
</html>

