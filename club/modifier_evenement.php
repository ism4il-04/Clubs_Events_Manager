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

// Check if event can be modified (allow all statuses except special ones)
if (in_array($event['status'], ['Modification demandée', 'Annulation demandée', 'Annulé'])) {
    $_SESSION['error_message'] = "Cet événement ne peut pas être modifié dans son état actuel.";
    header("Location: evenements_clubs.php");
    exit();
}

// Debug: Show current event status
echo "<!-- DEBUG: Event status: " . $event['status'] . " -->";

// Get current number of registered participants for validation
$registeredCount = 0;
if (!empty($event['places'])) {
    $countStmt = $conn->prepare("SELECT COUNT(*) as registered FROM participation WHERE evenement_id = ? AND etat = 'Accepté'");
    $countStmt->execute([$eventId]);
    $registeredCount = $countStmt->fetch()['registered'];
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
        
        // Validate required fields
        if (empty($nomEvent) || empty($descriptionEvenement) || empty($lieu) || 
            empty($dateDepart) || empty($heureDepart) || 
            empty($dateFin) || empty($heureFin)) {
            throw new Exception("Tous les champs sont obligatoires.");
        }
        
        // Validate places if not unlimited
        if (!$placesIllimitees && (empty($places) || $places < 1)) {
            throw new Exception("Veuillez entrer un nombre de places valide ou cocher 'Places illimitées'.");
        }
        
        // Check current number of registered participants
        if (!$placesIllimitees && !empty($places)) {
            $countStmt = $conn->prepare("SELECT COUNT(*) as registered FROM participation WHERE evenement_id = ? AND etat = 'Accepté'");
            $countStmt->execute([$eventId]);
            $registeredCount = $countStmt->fetch()['registered'];
            
            if ($places < $registeredCount) {
                throw new Exception("Impossible de réduire le nombre de places à $places. Il y a actuellement $registeredCount participants inscrits. Le nombre de places ne peut pas être inférieur au nombre de participants déjà inscrits.");
            }
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
                // Delete old image if exists
                if (!empty($event['image']) && file_exists('../' . $event['image'])) {
                    unlink('../' . $event['image']);
                }
                $imagePath = 'assets/uploads/events/' . $fileName;
            } else {
                throw new Exception("Erreur lors du téléchargement de l'image.");
            }
        }
        
        // Update event in database (reset status to "En attente" for re-approval)
        $stmt = $conn->prepare("UPDATE evenements SET nomEvent = ?, descriptionEvenement = ?, categorie = ?, lieu = ?, places = ?, dateDepart = ?, heureDepart = ?, dateFin = ?, heureFin = ?, image = ?, status = 'En attente' WHERE idEvent = ? AND organisateur_id = ?");
        $stmt->execute([$nomEvent, $descriptionEvenement, $categorie, $lieu, $places, $dateDepart, $heureDepart, $dateFin, $heureFin, $imagePath, $eventId, $_SESSION['id']]);
        
        $success_message = "Événement modifié avec succès ! Il est à nouveau en attente d'approbation.";
        
        // Refresh event data
        $stmt = $conn->prepare("SELECT * FROM evenements WHERE idEvent = ? AND organisateur_id = ?");
        $stmt->execute([$eventId, $_SESSION['id']]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
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

    <title>Modifier un événement</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
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
            display: grid;
            align-items: center;
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
            <h1><i class="bi bi-pencil-square me-2"></i>Modifier l'événement</h1>
            <p>Modifiez les informations de votre événement</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <strong><i class="bi bi-check-circle-fill me-1"></i>Succès !</strong> <?= htmlspecialchars($success_message) ?>
            </div>
        <?php endif; ?>

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
                        <small class="form-text">Donnez un nom attractif à votre événement</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="descriptionEvenement" class="form-label">Description *</label>
                        <textarea class="form-control" id="descriptionEvenement" name="descriptionEvenement" 
                                  rows="4" required><?= htmlspecialchars($_POST['descriptionEvenement'] ?? $event['descriptionEvenement']) ?></textarea>
                        <small class="form-text">Décrivez votre événement en détail</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="categorie" class="form-label">Catégorie *</label>
                        <select class="form-control" id="categorie" name="categorie" required>
                            <option value="">-- Sélectionnez une catégorie --</option>
                            <?php
                            $categories = ['Conférence', 'Atelier', 'Formation', 'Sortie Pédagogique', 'Sportif', 'Hackathon', 'Séminaire', 'Table ronde/ Débat', 'Sortie', 'Compétition', 'Autre'];
                            $selectedCategorie = $_POST['categorie'] ?? $event['categorie'];
                            foreach ($categories as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($selectedCategorie == $cat) ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text">Choisissez la catégorie qui correspond le mieux à votre événement</small>
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
                                <p class="form-text">Image actuelle (laissez vide pour conserver cette image)</p>
                            </div>
                        <?php endif; ?>
                        <input type="file" class="form-control" id="eventImage" name="eventImage" accept="image/jpeg,image/jpg,image/png,image/gif,image/webp">
                        <small class="form-text">Format accepté : JPG, PNG, GIF, WEBP. Taille max : 5MB (Optionnel)</small>
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
                        <small class="form-text">Ex: Amphithéâtre A, Salle de conférence, etc.</small>
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
                                Places illimitées / non contrôlées
                            </label>
                        </div>
                        <small class="form-text text-info d-block">
                            <i class="bi bi-info-circle me-1"></i>
                            Actuellement <?= $registeredCount ?> participant(s) inscrit(s). Le nombre de places ne peut pas être inférieur à ce nombre.
                        </small>
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

            <div class="form-group">
                <button type="submit" class="btn-submit">
                    <i class="bi bi-check-circle me-1"></i>Enregistrer les modifications
                </button>
                <a href="evenements_clubs.php" class="btn-cancel">
                    <i class="bi bi-x-circle me-1"></i>Annuler
                </a>
            </div>
        </form>
    </div>

    <script>
        // Form validation (same as ajouter_evenement.php)
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const dateDepart = document.getElementById('dateDepart');
            const dateFin = document.getElementById('dateFin');
            const heureDepart = document.getElementById('heureDepart');
            const heureFin = document.getElementById('heureFin');
            const placesInput = document.getElementById('places');
            const placesIllimiteesCheckbox = document.getElementById('placesIllimitees');
            const imageInput = document.getElementById('eventImage');
            const imagePreview = document.getElementById('imagePreview');
            const previewImg = document.getElementById('previewImg');

            // Image preview functionality
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (file.size > 5 * 1024 * 1024) {
                        alert('Le fichier est trop volumineux. Taille maximale : 5MB');
                        imageInput.value = '';
                        imagePreview.style.display = 'none';
                        return;
                    }
                    
                    const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
                    if (!allowedTypes.includes(file.type)) {
                        alert('Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.');
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
                } else {
                    imagePreview.style.display = 'none';
                }
            });

            // Set minimum date to today
            const today = new Date().toISOString().split('T')[0];
            dateDepart.min = today;
            dateFin.min = today;

            // Handle unlimited places checkbox
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

            dateDepart.addEventListener('change', function() {
                dateFin.min = this.value;
                if (dateFin.value && dateFin.value < this.value) {
                    dateFin.value = this.value;
                }
            });

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

            // Validate places input
            const placesInput = document.getElementById('places');
            const registeredCount = <?= json_encode($registeredCount) ?>;
            
            placesInput.addEventListener('input', function() {
                const places = parseInt(this.value);
                if (!isNaN(places) && places < registeredCount) {
                    this.setCustomValidity(`Le nombre de places ne peut pas être inférieur à ${registeredCount} (participants déjà inscrits)`);
                    this.style.borderColor = '#dc3545';
                } else {
                    this.setCustomValidity('');
                    this.style.borderColor = '#e9ecef';
                }
            });

            dateDepart.addEventListener('change', validateDateTime);
            dateFin.addEventListener('change', validateDateTime);
            heureDepart.addEventListener('change', validateDateTime);
            heureFin.addEventListener('change', validateDateTime);

            form.addEventListener('submit', function(e) {
                const requiredFields = ['nomEvent', 'descriptionEvenement', 'categorie', 'lieu', 'dateDepart', 'heureDepart', 'dateFin', 'heureFin'];
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

                if (!placesIllimiteesCheckbox.checked) {
                    if (!placesInput.value || placesInput.value < 1) {
                        placesInput.style.borderColor = '#dc3545';
                        isValid = false;
                    } else {
                        placesInput.style.borderColor = '#e9ecef';
                    }
                }

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

