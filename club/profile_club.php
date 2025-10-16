<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit;
}

// Get club profile data with email configuration
$profileStmt = $conn->prepare("SELECT * FROM organisateur NATURAL JOIN utilisateurs WHERE id = ?");
$profileStmt->execute([$_SESSION['id']]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

$uploadMessage = '';
$uploadError = '';
$mailMessage = '';
$mailError = '';

// Handle photo upload
if(isset($_POST['upload'])){
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $fileType = $_FILES['file']['type'];

        if (!in_array($fileType, $allowedTypes)) {
            $uploadError = "Type de fichier non autorisé. Utilisez JPG, PNG, GIF ou WEBP.";
        } else {
            // Validate file size (max 5MB)
            $maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if ($_FILES['file']['size'] > $maxSize) {
                $uploadError = "Le fichier est trop volumineux. Taille maximale: 5MB.";
            } else {
                // Set upload directory relative to current file
                $uploadDir = "../assets/photo/";
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }

                // Generate unique filename
                $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
                $filename = uniqid('club_' . $_SESSION['id'] . '_') . '.' . $extension;
                $targetPath = "assets/photo/" . $filename;
                $fullTargetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['file']['tmp_name'], $fullTargetPath)) {
                    // Delete old photo if exists
                    if (!empty($profile['logo']) && file_exists($uploadDir . basename($profile['logo']))) {
                        unlink($uploadDir . basename($profile['logo']));
                    }

                    // Update database
                    $photo = $conn->prepare("UPDATE organisateur SET logo=? WHERE id = ?");
                    if ($photo->execute([$targetPath, $_SESSION['id']])) {
                        $uploadMessage = "Logo téléchargé avec succès !";
                        // Refresh profile data
                        $profileStmt->execute([$_SESSION['id']]);
                        $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
                    } else {
                        $uploadError = "Erreur lors de la mise à jour de la base de données.";
                    }
                } else {
                    $uploadError = "Erreur lors du téléchargement du fichier.";
                }
            }
        }
    } else {
        $uploadError = "Aucun fichier sélectionné ou erreur lors du téléchargement.";
    }
}

// Handle photo removal
if(isset($_POST['remove_photo'])) {
    if (!empty($profile['logo'])) {
        $uploadDir = "../assets/photo/";
        // Extract just the filename from the stored path
        $filename = basename($profile['logo']);
        // Delete file from server
        if (file_exists($uploadDir . $filename)) {
            unlink($uploadDir . $filename);
        }

        // Update database
        $removePhoto = $conn->prepare("UPDATE organisateur SET logo=NULL WHERE id = ?");
        if ($removePhoto->execute([$_SESSION['id']])) {
            $uploadMessage = "Logo supprimé avec succès !";
            // Refresh profile data
            $profileStmt->execute([$_SESSION['id']]);
            $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $uploadError = "Erreur lors de la suppression du logo.";
        }
    }
}

// Handle mailing configuration update
if(isset($_POST['update_mail_config'])) {
    $serveur_smtp = trim($_POST['mail_host']);
    $port_smtp = trim($_POST['mail_port']);
    $nom_smtp = trim($_POST['mail_username']);
    $api_key = trim($_POST['mail_password']);
    $nom_expediteur = trim($_POST['mail_sender_name']);

    if (empty($serveur_smtp) || empty($port_smtp) || empty($nom_smtp) || empty($nom_expediteur)) {
        $mailError = "Les champs obligatoires doivent être remplis.";
    } else {
        // Update database
        $mailConfig = $conn->prepare("UPDATE organisateur SET serveur_smtp=?, port_smtp=?, nom_smtp=?, api_key=?, nom_expediteur=? WHERE id = ?");
        if ($mailConfig->execute([$serveur_smtp, $port_smtp, $nom_smtp, $api_key, $nom_expediteur, $_SESSION['id']])) {
            $mailMessage = "Configuration email mise à jour avec succès !";
            // Refresh profile data
            $profileStmt->execute([$_SESSION['id']]);
            $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $mailError = "Erreur lors de la mise à jour de la base de données.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil - Portail Club</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
        }
        body {
            background: #f5f7fa;
            color: #333;
            min-height: 100vh;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .logo {
            width: 50px; height: 50px;
            display: flex; align-items: center; justify-content: center;
            font-size: 24px; color: #004aad;
        }
        .header-info h2 { font-size: 1.5rem; font-weight: 600; margin-bottom: 3px; }
        .header-info p { font-size: 0.85rem; color: #c5d9f5; }
        .header-right { display: flex; align-items: center; gap: 20px; }
        .header-right a { color: #fff; text-decoration: none; font-weight: 500; display: flex; gap: 8px; }
        .header-right a:hover { color: #ffd700; }

        nav.nav {
            display: flex;
            justify-content: center;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .nav a { flex: 1; max-width: 250px; text-decoration: none; }
        .nav button {
            background: transparent;
            border: none;
            padding: 18px 30px;
            width: 100%;
            font-size: 0.95rem;
            cursor: pointer;
            color: #666;
            font-weight: 500;
            border-bottom: 3px solid transparent;
        }
        .nav button.active { color: #1f3c88; border-bottom-color: #1f3c88; background: #f0f3ff; }
        .nav button:hover { background: #f0f3ff; color: #1f3c88; }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 40px 30px;
        }
        .page-header {
            margin-bottom: 30px;
        }
        .page-header h1 {
            font-size: 1.8rem;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .page-header p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .profile-form {
            background: #fff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08), 0 2px 8px rgba(0,0,0,0.04);
            border: 1px solid #e5e7eb;
            display: flex;
            flex-direction: column;
            gap: 32px;
            position: relative;
            overflow: hidden;
        }

        .profile-form::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea 0%, #764ba2 100%);
        }

        .form-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            margin-bottom: 8px;
        }

        .form-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .form-section-title i {
            color: #667eea;
            font-size: 1.2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            color: #374151;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            background: #fff;
            transition: all 0.3s ease;
            color: #1f2937;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: #fefefe;
        }

        .form-group input:read-only {
            background: #f9fafb;
            color: #6b7280;
            cursor: not-allowed;
        }

        .form-row {
            display: flex;
            gap: 24px;
            margin-bottom: 8px;
        }

        .form-row .form-group {
            flex: 1;
            margin-bottom: 0;
        }

        .photo-upload-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            padding: 24px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .photo-upload-section:hover {
            border-color: #667eea;
            background: linear-gradient(135deg, #fefefe 0%, #f0f4ff 100%);
        }

        .profile-photo-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .profile-photo {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #667eea;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .profile-photo-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 3rem;
            border: 4px solid #e2e8f0;
        }

        .upload-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
            margin-top: 20px;
        }

        .file-input-wrapper {
            position: relative;
            width: 100%;
            max-width: 300px;
        }

        .file-input-wrapper input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 2px solid #d1d5db;
            border-radius: 8px;
            background: #fff;
            cursor: pointer;
            font-size: 0.9rem;
        }

        .file-input-wrapper input[type="file"]::file-selector-button {
            padding: 8px 16px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            margin-right: 10px;
            font-weight: 500;
        }

        .btn-upload {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
        }

        .btn-upload:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
        }

        .btn-remove {
            background: #ef4444;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.3s ease;
            margin-top: 15px;
        }

        .btn-remove:hover {
            background: #dc2626;
            transform: translateY(-1px);
        }

        .btn-save {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .photo-info {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 10px;
            line-height: 1.5;
        }

        .form-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 24px 0;
        }

        .mail-config-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .mail-config-grid .form-group {
            margin-bottom: 0;
        }

        .mail-status {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .mail-status h4 {
            color: #0369a1;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .mail-status p {
            color: #0c4a6e;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .profile-form {
                padding: 24px;
                margin: 0 16px;
            }

            .form-row {
                flex-direction: column;
                gap: 16px;
            }

            .mail-config-grid {
                grid-template-columns: 1fr;
            }
        }
        .img{
            width: 80px;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-left">
        <div class="logo"><img class="img" src="../Circle_BLACK_Logo-removebg-preview.png" alt="logo"></div>
        <div class="header-info">
            <h2>Portail Club</h2>
            <p>ENSA Tétouan - École Nationale des Sciences Appliquées</p>
        </div>
    </div>
    <div class="header-right">
        <span><?= htmlspecialchars($profile['clubNom']) ?></span>
        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
    </div>
</header>

<nav class="nav">
    <a href="dashboard.php"><button>Tableau de bord</button></a>
    <a href="evenements_clubs.php"><button>Mes événements</button></a>
    <a href="ajouter_evenement.php"><button>Ajouter un événement</button></a>
    <a href="demandes_participants.php"><button>Participants</button></a>
    <a href="communications.php"><button>Communications</button></a>
    <a href="certificats.php"><button>Certificats</button></a>
    <a href="profile_club.php"><button class="active">Mon Profile</button></a>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Mon profil club</h1>
        <p>Gérez les informations de votre club et la configuration email</p>
    </div>

    <?php if ($uploadMessage): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($uploadMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($uploadError): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($uploadError) ?>
        </div>
    <?php endif; ?>

    <?php if ($mailMessage): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($mailMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($mailError): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($mailError) ?>
        </div>
    <?php endif; ?>

    <div class="profile-form">
        <!-- Logo Upload Section -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-image"></i>
                Logo du club
            </div>
            <div class="photo-upload-section">
                <div class="profile-photo-container">
                    <?php if (!empty($profile['logo'])): ?>
                        <img src="../<?= htmlspecialchars($profile['logo']) ?>"
                             alt="Logo du club"
                             class="profile-photo"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="profile-photo-placeholder" style="display: none;">
                            <i class="fas fa-building"></i>
                        </div>
                    <?php else: ?>
                        <div class="profile-photo-placeholder">
                            <i class="fas fa-building"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($profile['logo'])): ?>
                    <p class="photo-info">
                        <i class="fas fa-info-circle"></i> Logo actuel: <?= htmlspecialchars($profile['logo']) ?>
                    </p>
                <?php else: ?>
                    <p class="photo-info">
                        <i class="fas fa-info-circle"></i> Aucun logo. Téléchargez-en un maintenant !
                    </p>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="upload-form">
                    <div class="file-input-wrapper">
                        <input type="file" name="file" id="logo" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    </div>
                    <button type="submit" name="upload" class="btn-upload">
                        <i class="fas fa-upload"></i> Télécharger le logo
                    </button>
                    <p class="photo-info" style="margin-top: 10px;">
                        Formats acceptés: JPG, PNG, GIF, WEBP (max 5MB)
                    </p>
                </form>

                <?php if (!empty($profile['logo'])): ?>
                    <form method="post" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                        <button type="submit" name="remove_photo" class="btn-remove"
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer le logo du club ?')">
                            <i class="fas fa-trash"></i> Supprimer le logo
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Club Information Section -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-building"></i>
                Informations du club
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="clubNom">Nom du club</label>
                    <input type="text" id="clubNom" name="clubNom" value="<?= htmlspecialchars($profile['clubNom']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="nom_abr">Nom abrégé</label>
                    <input type="text" id="nom_abr" name="nom_abr" value="<?= htmlspecialchars($profile['nom_abr']) ?>" readonly>
                </div>
            </div>
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="3" readonly><?= htmlspecialchars($profile['description']) ?></textarea>
            </div>
        </div>

        <!-- Contact Information Section -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-address-book"></i>
                Informations de contact
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Adresse e-mail</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" readonly>
                </div>
                <div class="form-group">
                    <label for="nom_utilisateur">Nom d'utilisateur</label>
                    <input type="text" id="nom_utilisateur" name="nom_utilisateur" value="<?= htmlspecialchars($profile['nom_utilisateur']) ?>" readonly>
                </div>
            </div>
        </div>

        <!-- Mailing Configuration Section -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-envelope"></i>
                Configuration email
            </div>
            
            <div class="mail-status">
                <h4><i class="fas fa-info-circle"></i> Configuration actuelle</h4>
                <p>Serveur: <?= htmlspecialchars($profile['serveur_smtp'] ?? 'Non configuré') ?> | Port: <?= htmlspecialchars($profile['port_smtp'] ?? 'Non configuré') ?> | Expéditeur: <?= htmlspecialchars($profile['email'] ?? 'Non configuré') ?></p>
            </div>

            <form method="post">
                <div class="mail-config-grid">
                    <div class="form-group">
                        <label for="mail_host">Serveur SMTP *</label>
                        <input type="text" id="mail_host" name="mail_host" value="<?= htmlspecialchars($profile['serveur_smtp'] ?? '') ?>" placeholder="smtp.gmail.com" required>
                    </div>
                    <div class="form-group">
                        <label for="mail_port">Port SMTP *</label>
                        <input type="number" id="mail_port" name="mail_port" value="<?= htmlspecialchars($profile['port_smtp'] ?? '') ?>" placeholder="587" required>
                    </div>
                    <div class="form-group">
                        <label for="mail_username">Nom d'utilisateur SMTP *</label>
                        <input type="text" id="mail_username" name="mail_username" value="<?= htmlspecialchars($profile['nom_smtp'] ?? '') ?>" placeholder="votre-email@gmail.com" required>
                        <small style="color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i> Adresse email utilisée pour l'authentification SMTP
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="mail_password">Mot de passe/API Key *</label>
                        <input type="password" id="mail_password" name="mail_password" value="<?= htmlspecialchars($profile['api_key'] ?? '') ?>" placeholder="Votre mot de passe ou clé API" required>
                        <small style="color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i> Mot de passe ou clé API de l'adresse SMTP
                        </small>
                    </div>
                </div>
                
                <div class="form-section" style="background: #f0f9ff; border: 1px solid #0ea5e9; margin-top: 20px;">
                    <h4 style="color: #0369a1; margin-bottom: 10px;"><i class="fas fa-info-circle"></i> Informations d'expédition</h4>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Adresse expéditeur (automatique)</label>
                            <input type="text" value="<?= htmlspecialchars($profile['email']) ?>" readonly style="background: #f9fafb; color: #6b7280;">
                        </div>
                        <div class="form-group">
                            <label for="mail_sender_name">Nom expéditeur *</label>
                            <input type="text" id="mail_sender_name" name="mail_sender_name" 
                                   value="<?= htmlspecialchars($profile['nom_expediteur'] ?? $profile['clubNom']) ?>" 
                                   placeholder="<?= htmlspecialchars($profile['clubNom']) ?>" required>
                            <small style="color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: block;">
                                <i class="fas fa-info-circle"></i> Nom qui apparaîtra comme expéditeur des emails
                            </small>
                        </div>
                    </div>
                    <p style="color: #0c4a6e; font-size: 0.9rem; margin-top: 10px;">
                        <i class="fas fa-lightbulb"></i> L'adresse expéditeur est automatique. Le nom expéditeur est modifiable et par défaut utilise le nom du club.
                    </p>
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <button type="submit" name="update_mail_config" class="btn-save">
                        <i class="fas fa-save"></i> Sauvegarder la configuration email
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>