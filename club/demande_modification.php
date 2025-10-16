<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../auth/login.php");
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
    $_SESSION['error_message'] = "Cet événement ne nécessite pas de demande de modification.";
    header("Location: evenements_clubs.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $nomEvent = $_POST['nomEvent'];
        $descriptionEvenement = $_POST['descriptionEvenement'];
        $categorie = $_POST['categorie'] ?? null;
        $lieu = $_POST['lieu'];
        $placesIllimitees = isset($_POST['placesIllimitees']);
        $places = $placesIllimitees ? null : (int)$_POST['places'];
        $dateDepart = $_POST['dateDepart'];
        $heureDepart = $_POST['heureDepart'];
        $dateFin = $_POST['dateFin'];
        $heureFin = $_POST['heureFin'];
        $motif = $_POST['motif'];
        
        // Validate required fields
        if (empty($nomEvent) || empty($descriptionEvenement) || empty($lieu) || 
            empty($dateDepart) || empty($heureDepart) || 
            empty($dateFin) || empty($heureFin) || empty($motif)) {
            throw new Exception("Tous les champs sont obligatoires.");
        }
        
        // Validate places if not unlimited
        if (!$placesIllimitees && (empty($places) || $places < 1)) {
            throw new Exception("Veuillez entrer un nombre de places valide ou cocher 'Places illimitées'.");
        }
        
        // Validate dates
        $startDateTime = new DateTime($dateDepart . ' ' . $heureDepart);
        $endDateTime = new DateTime($dateFin . ' ' . $heureFin);
        
        if ($endDateTime <= $startDateTime) {
            throw new Exception("La date de fin doit être postérieure à la date de début.");
        }
        
        // Handle image upload
        $imagePath = $event['image']; // Keep existing image by default
        if (isset($_FILES['eventImage']) && $_FILES['eventImage']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            
            $fileType = $_FILES['eventImage']['type'];
            $fileSize = $_FILES['eventImage']['size'];
            
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.");
            }
            
            if ($fileSize > $maxFileSize) {
                throw new Exception("Le fichier est trop volumineux. Taille maximale : 5MB.");
            }
            
            $uploadDir = '../assets/uploads/events/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }
            
            $fileExtension = pathinfo($_FILES['eventImage']['name'], PATHINFO_EXTENSION);
            $fileName = uniqid('event_') . '.' . $fileExtension;
            $targetPath = $uploadDir . $fileName;
            
            if (move_uploaded_file($_FILES['eventImage']['tmp_name'], $targetPath)) {
                $imagePath = 'assets/uploads/events/' . $fileName;
            } else {
                throw new Exception("Erreur lors du téléchargement de l'image.");
            }
        }
        
        // Store modification data as JSON
        $modificationData = json_encode([
            'nomEvent' => $nomEvent,
            'descriptionEvenement' => $descriptionEvenement,
            'categorie' => $categorie,
            'lieu' => $lieu,
            'places' => $places,
            'dateDepart' => $dateDepart,
            'heureDepart' => $heureDepart,
            'dateFin' => $dateFin,
            'heureFin' => $heureFin,
            'image' => $imagePath
        ]);
        
        // Update event with modification request
        $stmt = $conn->prepare("UPDATE evenements SET status = 'Modification demandée', motif_demande = ?, modification_data = ? WHERE idEvent = ?");
        $stmt->execute([$motif, $modificationData, $eventId]);
        
        $_SESSION['success_message'] = "Demande de modification envoyée avec succès ! Elle sera examinée par l'administrateur.";
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

    <title>Demande de modification</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }
        
        .form-text {
            color: #6c757d;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            border: none;
            color: white;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-size: 1.1rem;
            font-weight: 600;
            transition: transform 0.2s ease;
        }
        
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(255, 193, 7, 0.3);
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
            display: inline-block;
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
        
        .datetime-row {
            display: flex;
            gap: 1rem;
        }
        
        .datetime-row .form-group {
            flex: 1;
        }
        
        .current-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 8px;
            border: 2px solid #e9ecef;
            margin-bottom: 10px;
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
        <div class="tab" onclick="navigateTo('ajouter_evenement.php')">Ajouter un événement</div>
        <div class="tab" onclick="navigateTo('demandes_participants.php')">Participants</div>
        <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
        <div class="tab" onclick="navigateTo('certificats.php')">Certificats</div>
    </div>

    <div class="form-container">
        <div class="form-header">
            <h1><i class="bi bi-exclamation-triangle me-2"></i>Demande de modification</h1>
            <p>Soumettez une demande de modification pour votre événement</p>
        </div>

        <div class="warning-box">
            <i class="bi bi-info-circle-fill"></i>
            <strong>Important :</strong> Votre événement est actuellement <strong><?= htmlspecialchars($event['status']) ?></strong>. 
            Toute modification nécessite l'approbation de l'administrateur avant d'être appliquée.
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <strong><i class="bi bi-x-circle-fill me-1"></i>Erreur :</strong> <?= htmlspecialchars($error_message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="nomEvent" class="form-label">Nom de l'événement *</label>
                        <input type="text" class="form-control" id="nomEvent" name="nomEvent" 
                               value="<?= htmlspecialchars($_POST['nomEvent'] ?? $event['nomEvent']) ?>" required>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="descriptionEvenement" class="form-label">Description *</label>
                        <textarea class="form-control" id="descriptionEvenement" name="descriptionEvenement" 
                                  rows="4" required><?= htmlspecialchars($_POST['descriptionEvenement'] ?? $event['descriptionEvenement']) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="categorie" class="form-label">Catégorie *</label>
                        <select class="form-control" id="categorie" name="categorie" required>
                            <?php
                            $categories = ['Conférence', 'Atelier', 'Formation', 'Sortie Pédagogique', 'Sportif', 'Hackathon', 'Séminaire', 'Table ronde/ Débat', 'Sortie', 'Compétition', 'Autre'];
                            $selectedCategorie = $_POST['categorie'] ?? $event['categorie'];
                            foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($selectedCategorie == $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="eventImage" class="form-label">Image de l'événement</label>
                        <?php if (!empty($event['image']) && file_exists('../' . $event['image'])): ?>
                            <div>
                                <img src="../<?= htmlspecialchars($event['image']) ?>" alt="Image actuelle" class="current-image">
                                <p class="form-text">Image actuelle</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="eventImage" name="eventImage" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small class="form-text">Laissez vide pour conserver l'image actuelle</small>
                        <div id="imagePreview" style="margin-top: 10px; display: none;">
                            <img id="previewImg" src="" alt="Aperçu" style="max-width: 200px; max-height: 200px; border-radius: 8px; border: 2px solid #e9ecef;">
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="lieu" class="form-label">Lieu *</label>
                        <input type="text" class="form-control" id="lieu" name="lieu" 
                               value="<?= htmlspecialchars($_POST['lieu'] ?? $event['lieu']) ?>" required>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="places" class="form-label">Nombre de places</label>
                        <input type="number" class="form-control" id="places" name="places" 
                               value="<?= htmlspecialchars($_POST['places'] ?? $event['places']) ?>" min="1">
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="checkbox" id="placesIllimitees" 
                                   name="placesIllimitees" <?= (isset($_POST['placesIllimitees']) || empty($event['places'])) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="placesIllimitees">
                                Places illimitées
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="datetime-row">
                <div class="form-group">
                    <label for="dateDepart" class="form-label">Date de début *</label>
                    <input type="date" class="form-control" id="dateDepart" name="dateDepart" 
                           value="<?= htmlspecialchars($_POST['dateDepart'] ?? $event['dateDepart']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="heureDepart" class="form-label">Heure de début *</label>
                    <input type="time" class="form-control" id="heureDepart" name="heureDepart" 
                           value="<?= htmlspecialchars($_POST['heureDepart'] ?? $event['heureDepart']) ?>" required>
                </div>
            </div>

            <div class="datetime-row">
                <div class="form-group">
                    <label for="dateFin" class="form-label">Date de fin *</label>
                    <input type="date" class="form-control" id="dateFin" name="dateFin" 
                           value="<?= htmlspecialchars($_POST['dateFin'] ?? $event['dateFin']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="heureFin" class="form-label">Heure de fin *</label>
                    <input type="time" class="form-control" id="heureFin" name="heureFin" 
                           value="<?= htmlspecialchars($_POST['heureFin'] ?? $event['heureFin']) ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="motif" class="form-label">Motif de la modification *</label>
                        <textarea class="form-control" id="motif" name="motif" rows="3" required 
                                  placeholder="Expliquez pourquoi vous souhaitez modifier cet événement..."><?= htmlspecialchars($_POST['motif'] ?? '') ?></textarea>
                        <small class="form-text">Ce motif sera examiné par l'administrateur</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-submit">
                    <i class="bi bi-send me-1"></i>Envoyer la demande
                </button>
                <a href="evenements_clubs.php" class="btn-cancel">
                    <i class="bi bi-x-circle me-1"></i>Annuler
                </a>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const placesInput = document.getElementById('places');
            const placesIllimiteesCheckbox = document.getElementById('placesIllimitees');
            const imageInput = document.getElementById('eventImage');
            const imagePreview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');

            // Image preview
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Fichier trop volumineux. Max: 5MB');
                        imageInput.value = '';
                        imagePreview.style.display = 'none';
                        return;
                    }
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        previewImg.src = event.target.result;
                        imagePreview.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Handle unlimited places
            function togglePlacesInput() {
                if (placesIllimiteesCheckbox.checked) {
                    placesInput.value = '';
                    placesInput.disabled = true;
                    placesInput.removeAttribute('required');
                    placesInput.style.backgroundColor = '#e9ecef';
                } else {
                    placesInput.disabled = false;
                    placesInput.setAttribute('required', 'required');
                    placesInput.style.backgroundColor = '';
                }
            }
            togglePlacesInput();
            placesIllimiteesCheckbox.addEventListener('change', togglePlacesInput);
        });
    </script>
</body>
</html>

