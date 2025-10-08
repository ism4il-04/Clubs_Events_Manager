<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";
include "../includes/header.php";
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nomEvent = $_POST['nomEvent'];
        $descriptionEvenement = $_POST['descriptionEvenement'];
        $lieu = $_POST['lieu'];
        $places = (int)$_POST['places'];
        $dateDepart = $_POST['dateDepart'];
        $heureDepart = $_POST['heureDepart'];
        $dateFin = $_POST['dateFin'];
        $heureFin = $_POST['heureFin'];
        
        // Validate required fields
        if (empty($nomEvent) || empty($descriptionEvenement) || empty($lieu) || 
            empty($places) || empty($dateDepart) || empty($heureDepart) || 
            empty($dateFin) || empty($heureFin)) {
            throw new Exception("Tous les champs sont obligatoires.");
        }
        
        // Validate dates
        $startDateTime = new DateTime($dateDepart . ' ' . $heureDepart);
        $endDateTime = new DateTime($dateFin . ' ' . $heureFin);
        
        if ($endDateTime <= $startDateTime) {
            throw new Exception("La date de fin doit être postérieure à la date de début.");
        }
        
        // Insert event into database
        $stmt = $conn->prepare("INSERT INTO evenements (nomEvent, descriptionEvenement, lieu, places, dateDepart, heureDepart, dateFin, heureFin, status, organisateur_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'En attente', ?)");
        $stmt->execute([$nomEvent, $descriptionEvenement, $lieu, $places, $dateDepart, $heureDepart, $dateFin, $heureFin, $_SESSION['id']]);
        
        $success_message = "Événement créé avec succès ! Il est en attente d'approbation.";
        
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
    <title>Ajouter un événement</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/script.js"></script>
    
    <style>
        .form-container {
            max-width: 800px;
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
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .form-header p {
            color: #6c757d;
            margin: 0;
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
            border-color: #007bff;
            box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.25);
        }
        
        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #007bff, #0056b3);
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
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        
        .btn-cancel {
            background: #6c757d;
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.3s ease;
            width: 100%;
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
        
        .datetime-row {
            display: flex;
            gap: 1rem;
        }
        
        .datetime-row .form-group {
            flex: 1;
        }
        
        @media (max-width: 768px) {
            .datetime-row {
                flex-direction: column;
                gap: 0;
            }
        }
    </style>
</head>
<body>
    <div class="tabs">
        <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
        <div class="tab" onclick="navigateTo('evenements_clubs.php')">Mes événements</div>
        <div class="tab active" onclick="navigateTo('ajouter_evenements.php')">Ajouter un événement</div>
        <div class="tab" onclick="navigateTo('demandes_participants.php')">Participants</div>
        <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
        <div class="tab" onclick="navigateTo('certificats.php')">Certificats</div>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h1>📅 Créer un nouvel événement</h1>
            <p>Remplissez les informations ci-dessous pour créer votre événement</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <strong>✅ Succès !</strong> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <strong>❌ Erreur :</strong> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="nomEvent" class="form-label">Nom de l'événement *</label>
                        <input type="text" class="form-control" id="nomEvent" name="nomEvent" 
                               value="<?= htmlspecialchars($_POST['nomEvent'] ?? '') ?>" required>
                        <small class="form-text">Donnez un nom attractif à votre événement</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="descriptionEvenement" class="form-label">Description *</label>
                        <textarea class="form-control" id="descriptionEvenement" name="descriptionEvenement" 
                                  rows="4" required><?= htmlspecialchars($_POST['descriptionEvenement'] ?? '') ?></textarea>
                        <small class="form-text">Décrivez votre événement en détail</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="lieu" class="form-label">Lieu *</label>
                        <input type="text" class="form-control" id="lieu" name="lieu" 
                               value="<?= htmlspecialchars($_POST['lieu'] ?? '') ?>" required>
                        <small class="form-text">Ex: Amphithéâtre A, Salle de conférence, etc.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="places" class="form-label">Nombre de places *</label>
                        <input type="number" class="form-control" id="places" name="places" 
                               value="<?= htmlspecialchars($_POST['places'] ?? '') ?>" min="1" required>
                        <small class="form-text">Capacité maximale</small>
                    </div>
                </div>
            </div>

            <div class="datetime-row">
                <div class="form-group">
                    <label for="dateDepart" class="form-label">Date de début *</label>
                    <input type="date" class="form-control" id="dateDepart" name="dateDepart" 
                           value="<?= htmlspecialchars($_POST['dateDepart'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="heureDepart" class="form-label">Heure de début *</label>
                    <input type="time" class="form-control" id="heureDepart" name="heureDepart" 
                           value="<?= htmlspecialchars($_POST['heureDepart'] ?? '') ?>" required>
                </div>
            </div>

            <div class="datetime-row">
                <div class="form-group">
                    <label for="dateFin" class="form-label">Date de fin *</label>
                    <input type="date" class="form-control" id="dateFin" name="dateFin" 
                           value="<?= htmlspecialchars($_POST['dateFin'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="heureFin" class="form-label">Heure de fin *</label>
                    <input type="time" class="form-control" id="heureFin" name="heureFin" 
                           value="<?= htmlspecialchars($_POST['heureFin'] ?? '') ?>" required>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-submit">
                    🚀 Créer l'événement
                </button>
                <a href="evenements_clubs.php" class="btn-cancel">
                    ❌ Annuler
                </a>
            </div>
        </form>
    </div>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const dateDepart = document.getElementById('dateDepart');
            const dateFin = document.getElementById('dateFin');
            const heureDepart = document.getElementById('heureDepart');
            const heureFin = document.getElementById('heureFin');

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            dateDepart.min = today;
            dateFin.min = today;

            // Update dateFin minimum when dateDepart changes
            dateDepart.addEventListener('change', function() {
                dateFin.min = this.value;
                if (dateFin.value && dateFin.value < this.value) {
                    dateFin.value = this.value;
                }
            });

            // Validate date and time combination
            function validateDateTime() {
                if (dateDepart.value && dateFin.value && heureDepart.value && heureFin.value) {
                    const startDateTime = new Date(dateDepart.value + 'T' + heureDepart.value);
                    const endDateTime = new Date(dateFin.value + 'T' + heureFin.value);
                    
                    if (endDateTime <= startDateTime) {
                        heureFin.setCustomValidity('L\'heure de fin doit être postérieure à l\'heure de début');
                    } else {
                        heureFin.setCustomValidity('');
                    }
                }
            }

            dateDepart.addEventListener('change', validateDateTime);
            dateFin.addEventListener('change', validateDateTime);
            heureDepart.addEventListener('change', validateDateTime);
            heureFin.addEventListener('change', validateDateTime);

            // Form submission validation
            form.addEventListener('submit', function(e) {
                const requiredFields = ['nomEvent', 'descriptionEvenement', 'lieu', 'places', 'dateDepart', 'heureDepart', 'dateFin', 'heureFin'];
                let isValid = true;

                requiredFields.forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (!field.value.trim()) {
                        field.style.borderColor = '#dc3545';
                        isValid = false;
                    } else {
                        field.style.borderColor = '#e9ecef';
                    }
                });

                validateDateTime();

                if (!isValid) {
                    e.preventDefault();
                    alert('Veuillez remplir tous les champs obligatoires.');
                }
            });
        });
    </script>
</body>
</html>
