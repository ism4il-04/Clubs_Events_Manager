<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

// Get admin profile data
$profileStmt = $conn->prepare("SELECT u.*, a.photo FROM utilisateurs u LEFT JOIN admin a ON u.id = a.id WHERE u.id = ?");
$profileStmt->execute([$_SESSION['id']]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

$uploadMessage = '';
$uploadError = '';

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
                $filename = uniqid('admin_' . $_SESSION['id'] . '_') . '.' . $extension;
                $targetPath = "assets/photo/" . $filename;
                $fullTargetPath = $uploadDir . $filename;

                if (move_uploaded_file($_FILES['file']['tmp_name'], $fullTargetPath)) {
                    // Delete old photo if exists
                    if (!empty($profile['photo']) && file_exists($uploadDir . basename($profile['photo']))) {
                        unlink($uploadDir . basename($profile['photo']));
                    }

                    // Update database - first check if admin record exists
                    $checkAdmin = $conn->prepare("SELECT id FROM admin WHERE id = ?");
                    $checkAdmin->execute([$_SESSION['id']]);
                    
                    if ($checkAdmin->fetch()) {
                        // Update existing admin record
                        $photo = $conn->prepare("UPDATE admin SET photo=? WHERE id = ?");
                    } else {
                        // Insert new admin record
                        $photo = $conn->prepare("INSERT INTO admin (id, photo) VALUES (?, ?)");
                    }
                    
                    if ($photo->execute([$_SESSION['id'], $targetPath])) {
                        $uploadMessage = "Photo téléchargée avec succès !";
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
    if (!empty($profile['photo'])) {
        $uploadDir = "../assets/photo/";
        // Extract just the filename from the stored path
        $filename = basename($profile['photo']);
        // Delete file from server
        if (file_exists($uploadDir . $filename)) {
            unlink($uploadDir . $filename);
        }

        // Update database
        $removePhoto = $conn->prepare("UPDATE admin SET photo=NULL WHERE id = ?");
        if ($removePhoto->execute([$_SESSION['id']])) {
            $uploadMessage = "Photo supprimée avec succès !";
            // Refresh profile data
            $profileStmt->execute([$_SESSION['id']]);
            $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
        } else {
            $uploadError = "Erreur lors de la suppression de la photo.";
        }
    }
}

// Handle profile update
if(isset($_POST['update_profile'])) {
    $nom_utilisateur = trim($_POST['nom_utilisateur']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($nom_utilisateur) || empty($email)) {
        $uploadError = "Le nom d'utilisateur et l'email sont obligatoires.";
    } else {
        try {
            // Check if email is already used by another user
            $checkEmail = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
            $checkEmail->execute([$email, $_SESSION['id']]);
            
            if ($checkEmail->fetch()) {
                $uploadError = "Cet email est déjà utilisé par un autre utilisateur.";
            } else {
                // Update user information
                if (!empty($password)) {
                    $updateStmt = $conn->prepare("UPDATE utilisateurs SET nom_utilisateur = ?, email = ?, password = ? WHERE id = ?");
                    $updateStmt->execute([$nom_utilisateur, $email, $password, $_SESSION['id']]);
                } else {
                    $updateStmt = $conn->prepare("UPDATE utilisateurs SET nom_utilisateur = ?, email = ? WHERE id = ?");
                    $updateStmt->execute([$nom_utilisateur, $email, $_SESSION['id']]);
                }
                
                $uploadMessage = "Profil mis à jour avec succès !";
                // Refresh profile data
                $profileStmt->execute([$_SESSION['id']]);
                $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (Exception $e) {
            $uploadError = "Erreur lors de la mise à jour: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil - Portail Administrateur</title>
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
            max-width: 800px;
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
        .form-group select {
            padding: 14px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            background: #fff;
            transition: all 0.3s ease;
            color: #1f2937;
        }

        .form-group input:focus,
        .form-group select:focus {
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

        .btn-update {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
            width: 100%;
            max-width: 200px;
        }

        .btn-update:hover {
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

        @media (max-width: 768px) {
            .profile-form {
                padding: 24px;
                margin: 0 16px;
            }

            .form-row {
                flex-direction: column;
                gap: 16px;
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
            <h2>Portail Administrateur</h2>
            <p>ENSA Tétouan - École Nationale des Sciences Appliquées</p>
        </div>
    </div>
    <div class="header-right">
        <span><?= htmlspecialchars($profile['nom_utilisateur']) ?></span>
        <a href="../auth/logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
    </div>
</header>

<nav class="nav">
    <a href="dashboard.php"><button>Tableau de bord</button></a>
    <a href="demandes_evenements.php"><button>Demandes d'événements</button></a>
    <a href="evenements.php"><button>Tous les événements</button></a>
    <a href="clubs.php"><button>Gestion des clubs</button></a>
    <a href="utilisateurs.php"><button>Utilisateurs</button></a>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Mon profil administrateur</h1>
        <p>Vous pouvez consulter et modifier vos informations personnelles ci-dessous</p>
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

    <div class="profile-form">
        <!-- Photo Upload Section -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-camera"></i>
                Photo de profil
            </div>
            <div class="photo-upload-section">
                <div class="profile-photo-container">
                    <?php if (!empty($profile['photo'])): ?>
                        <img src="../<?= htmlspecialchars($profile['photo']) ?>"
                             alt="Photo de profil"
                             class="profile-photo"
                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                        <div class="profile-photo-placeholder" style="display: none;">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    <?php else: ?>
                        <div class="profile-photo-placeholder">
                            <i class="fas fa-user-shield"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($profile['photo'])): ?>
                    <p class="photo-info">
                        <i class="fas fa-info-circle"></i> Photo actuelle: <?= htmlspecialchars(basename($profile['photo'])) ?>
                    </p>
                <?php else: ?>
                    <p class="photo-info">
                        <i class="fas fa-info-circle"></i> Aucune photo de profil. Téléchargez-en une maintenant !
                    </p>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="upload-form">
                    <div class="file-input-wrapper">
                        <input type="file" name="file" id="photo" accept="image/jpeg,image/png,image/gif,image/webp" required>
                    </div>
                    <button type="submit" name="upload" class="btn-upload">
                        <i class="fas fa-upload"></i> Télécharger la photo
                    </button>
                    <p class="photo-info" style="margin-top: 10px;">
                        Formats acceptés: JPG, PNG, GIF, WEBP (max 5MB)
                    </p>
                </form>

                <?php if (!empty($profile['photo'])): ?>
                    <form method="post" style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e2e8f0;">
                        <button type="submit" name="remove_photo" class="btn-remove"
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer votre photo de profil ?')">
                            <i class="fas fa-trash"></i> Supprimer la photo
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <!-- Profile Information Section -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-user-shield"></i>
                Informations du compte
            </div>
            
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom_utilisateur">Nom d'utilisateur</label>
                        <input type="text" id="nom_utilisateur" name="nom_utilisateur" 
                               value="<?= htmlspecialchars($profile['nom_utilisateur']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Adresse e-mail</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($profile['email']) ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Entrez un nouveau mot de passe">
                </div>
                
                <div style="text-align: center; margin-top: 20px;">
                    <button type="submit" name="update_profile" class="btn-update">
                        <i class="fas fa-save"></i> Mettre à jour le profil
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
