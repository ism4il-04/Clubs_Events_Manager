<?php
session_start();
include '../includes/db.php';
include 'admin_header.php';

if (!isset($_SESSION['id'])) {
    header('Location: ../login.php');
    exit;
}

// Load PHPMailer classes
require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get admin profile data with email configuration
$profileStmt = $conn->prepare("SELECT u.*, a.photo, a.serveur_smtp, a.port_smtp, a.nom_smtp, a.api_key_encrypted, a.nom_expediteur FROM utilisateurs u LEFT JOIN admin a ON u.id = a.id WHERE u.id = ?");
$profileStmt->execute([$_SESSION['id']]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

$uploadMessage = '';
$uploadError = '';
$mailMessage = '';
$mailError = '';
$testMessage = '';
$testError = '';
$adminMessage = '';
$adminError = '';

// Decrypt API key function
function decrypt_api_key($encrypted_api_key) {
    if (empty($encrypted_api_key)) return '';
    $key = hash('sha256', 'clubs_events_manager_secret_key_2024', true);
    $data = base64_decode($encrypted_api_key);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
}

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

// Handle admin profile update
if(isset($_POST['update_admin_info'])) {
    $nom_utilisateur = trim($_POST['nom_utilisateur']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    
    if (empty($nom_utilisateur) || empty($email)) {
        $adminError = "Le nom d'utilisateur et l'email sont obligatoires.";
    } else {
        // Check if email is already used by another user
        $checkEmail = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
        $checkEmail->execute([$email, $_SESSION['id']]);
        
        if ($checkEmail->fetch()) {
            $adminError = "Cet email est déjà utilisé par un autre utilisateur.";
        } else {
            try {
                $conn->beginTransaction();
                
                // Update user information
                if (!empty($password)) {
                    $updateStmt = $conn->prepare("UPDATE utilisateurs SET nom_utilisateur = ?, email = ?, password = ? WHERE id = ?");
                    $updateStmt->execute([$nom_utilisateur, $email, $password, $_SESSION['id']]);
                } else {
                    $updateStmt = $conn->prepare("UPDATE utilisateurs SET nom_utilisateur = ?, email = ? WHERE id = ?");
                    $updateStmt->execute([$nom_utilisateur, $email, $_SESSION['id']]);
                }
                
                $conn->commit();
                $adminMessage = "Profil administrateur mis à jour avec succès !";
                
                // Refresh profile data
                $profileStmt->execute([$_SESSION['id']]);
                $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
                
            } catch (Exception $e) {
                $conn->rollBack();
                $adminError = "Erreur lors de la mise à jour du profil: " . $e->getMessage();
            }
        }
    }
}

// Handle email configuration update
if(isset($_POST['update_mail_config'])) {
    $serveur_smtp = trim($_POST['mail_host']);
    $port_smtp = (int)$_POST['mail_port'];
    $nom_smtp = trim($_POST['mail_username']);
    $api_key = trim($_POST['mail_password']);
    $nom_expediteur = trim($_POST['mail_sender_name']);
    
    if (empty($serveur_smtp) || empty($port_smtp) || empty($nom_smtp) || empty($nom_expediteur)) {
        $mailError = "Tous les champs de configuration email sont obligatoires.";
    } else {
        try {
            $conn->beginTransaction();
            
            // Check if admin record exists
            $checkAdmin = $conn->prepare("SELECT id FROM admin WHERE id = ?");
            $checkAdmin->execute([$_SESSION['id']]);
            
            if ($checkAdmin->fetch()) {
                // Update existing admin record
                if (!empty($api_key)) {
                    // Encrypt API key
                    $key = hash('sha256', 'clubs_events_manager_secret_key_2024', true);
                    $iv = random_bytes(16);
                    $encrypted = openssl_encrypt($api_key, 'AES-256-CBC', $key, 0, $iv);
                    $api_key_encrypted = base64_encode($iv . $encrypted);
                    
                    $updateEmail = $conn->prepare("UPDATE admin SET serveur_smtp = ?, port_smtp = ?, nom_smtp = ?, api_key_encrypted = ?, nom_expediteur = ? WHERE id = ?");
                    $updateEmail->execute([$serveur_smtp, $port_smtp, $nom_smtp, $api_key_encrypted, $nom_expediteur, $_SESSION['id']]);
                } else {
                    // Keep existing API key
                    $updateEmail = $conn->prepare("UPDATE admin SET serveur_smtp = ?, port_smtp = ?, nom_smtp = ?, nom_expediteur = ? WHERE id = ?");
                    $updateEmail->execute([$serveur_smtp, $port_smtp, $nom_smtp, $nom_expediteur, $_SESSION['id']]);
                }
            } else {
                // Insert new admin record
                if (!empty($api_key)) {
                    // Encrypt API key
                    $key = hash('sha256', 'clubs_events_manager_secret_key_2024', true);
                    $iv = random_bytes(16);
                    $encrypted = openssl_encrypt($api_key, 'AES-256-CBC', $key, 0, $iv);
                    $api_key_encrypted = base64_encode($iv . $encrypted);
                    
                    $insertEmail = $conn->prepare("INSERT INTO admin (id, serveur_smtp, port_smtp, nom_smtp, api_key_encrypted, nom_expediteur) VALUES (?, ?, ?, ?, ?, ?)");
                    $insertEmail->execute([$_SESSION['id'], $serveur_smtp, $port_smtp, $nom_smtp, $api_key_encrypted, $nom_expediteur]);
                } else {
                    $insertEmail = $conn->prepare("INSERT INTO admin (id, serveur_smtp, port_smtp, nom_smtp, nom_expediteur) VALUES (?, ?, ?, ?, ?)");
                    $insertEmail->execute([$_SESSION['id'], $serveur_smtp, $port_smtp, $nom_smtp, $nom_expediteur]);
                }
            }
            
            $conn->commit();
            $mailMessage = "Configuration email mise à jour avec succès !";
            // Refresh profile data
            $profileStmt->execute([$_SESSION['id']]);
            $profile = $profileStmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            $conn->rollBack();
            $mailError = "Erreur lors de la mise à jour de la configuration email: " . $e->getMessage();
        }
    }
}

// Handle test email sending
if(isset($_POST['test_email'])) {
    $test_email = trim($_POST['test_email_address']);
    
    if (empty($test_email)) {
        $testError = "Veuillez saisir une adresse email de test.";
    } elseif (!filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        $testError = "Adresse email invalide.";
    } else {
        // Check if email configuration is complete
        if (empty($profile['serveur_smtp']) || empty($profile['port_smtp']) || empty($profile['nom_smtp']) || empty($profile['api_key_encrypted'])) {
            $testError = "Configuration email incomplète. Veuillez d'abord configurer tous les paramètres email.";
        } else {
            // Send test email
            try {
                $mail = new PHPMailer(true);
                
                // Server settings
                $mail->isSMTP();
                $mail->Host = $profile['serveur_smtp'];
                $mail->SMTPAuth = true;
                $mail->SMTPSecure = 'tls';
                $mail->Username = $profile['nom_smtp'];
                $mail->Password = decrypt_api_key($profile['api_key_encrypted']);
                $mail->Port = $profile['port_smtp'];
                $mail->From = $profile['email'];
                $mail->FromName = $profile['nom_expediteur'] ?? 'Portail Administrateur';
                $mail->addReplyTo($profile['email']);
                $mail->addAddress($test_email);
                
                // Content
                $mail->isHTML(true);
                $mail->Subject = 'Test de configuration email - Portail Administrateur';
                $mail->Body = '
                    <h2>Test de configuration email</h2>
                    <p>Bonjour,</p>
                    <p>Ceci est un email de test pour vérifier que la configuration email du <strong>Portail Administrateur</strong> fonctionne correctement.</p>
                    <p><strong>Détails de la configuration :</strong></p>
                    <ul>
                        <li>Serveur SMTP : ' . htmlspecialchars($profile['serveur_smtp']) . '</li>
                        <li>Port : ' . htmlspecialchars($profile['port_smtp']) . '</li>
                        <li>Nom d\'utilisateur SMTP : ' . htmlspecialchars($profile['nom_smtp']) . '</li>
                        <li>Expéditeur : ' . htmlspecialchars($profile['email']) . '</li>
                        <li>Nom expéditeur : ' . htmlspecialchars($profile['nom_expediteur'] ?? 'Portail Administrateur') . '</li>
                    </ul>
                    <p>Si vous recevez cet email, la configuration est correcte !</p>
                    <p>Cordialement,<br>L\'équipe du Portail Administrateur</p>
                ';
                
                $mail->send();
                $testMessage = "Email de test envoyé avec succès à " . htmlspecialchars($test_email) . " !";
                
            } catch (Exception $e) {
                $testError = "Erreur lors de l'envoi de l'email de test : " . $mail->ErrorInfo;
            }
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
            transition: all 0.3s ease;
        }
        .nav button:hover, .nav button.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background: #f8faff;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        .page-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 10px;
        }

        .page-header p {
            font-size: 1.1rem;
            color: #6b7280;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
        }

        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }

        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        .profile-form {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            overflow: hidden;
        }

        .form-section {
            padding: 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .form-section:last-child {
            border-bottom: none;
        }

        .form-section-title {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.25rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 25px;
        }

        .form-section-title i {
            color: #667eea;
            font-size: 1.1rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
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
            width: 100%;
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
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .file-input-label {
            display: block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
        }

        .file-input-label:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
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
            width: 100%;
            max-width: 200px;
        }

        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
        }

        .btn-test {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
            width: 100%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-test:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }

        .photo-info {
            font-size: 0.85rem;
            color: #6b7280;
            margin-top: 10px;
            line-height: 1.5;
        }

        .mail-status {
            background: #f0f9ff;
            border: 1px solid #0ea5e9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .mail-status h4 {
            color: #0369a1;
            margin-bottom: 8px;
            font-size: 1rem;
        }

        .mail-status p {
            color: #0c4a6e;
            font-size: 0.9rem;
            margin: 0;
        }

        .mail-config-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        @media (max-width: 768px) {
            .form-row,
            .mail-config-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .container {
                padding: 20px 15px;
            }
            
            .header {
                padding: 15px 20px;
            }
            
            .nav {
                flex-wrap: wrap;
            }
            
            .nav a {
                flex: 1 1 50%;
                min-width: 120px;
            }
        }
    </style>
</head>
<body>
<nav class="nav">
    <a href="dashboard.php"><button>Tableau de bord</button></a>
    <a href="demandes_evenements.php"><button>Demandes d'événements</button></a>
    <a href="evenements.php"><button>Tous les événements</button></a>
    <a href="clubs.php"><button>Gestion des clubs</button></a>
    <a href="utilisateurs.php"><button>Utilisateurs</button></a>
    <a href="communications.php"><button>Communications</button></a>
</nav>


<div class="container">
    <div class="page-header">
        <h1>Mon profil administrateur</h1>
        <p>Gérez vos informations personnelles et la configuration email</p>
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

    <?php if ($testMessage): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($testMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($testError): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($testError) ?>
        </div>
    <?php endif; ?>

    <?php if ($adminMessage): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($adminMessage) ?>
        </div>
    <?php endif; ?>

    <?php if ($adminError): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($adminError) ?>
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
                        <i class="fas fa-info-circle"></i> Photo actuelle : <?= htmlspecialchars(basename($profile['photo'])) ?>
                    </p>
                <?php endif; ?>

                <form method="post" enctype="multipart/form-data" class="upload-form">
                    <div class="file-input-wrapper">
                        <input type="file" name="file" id="photo" accept="image/jpeg,image/png,image/gif,image/webp" required>
                        <label for="photo" class="file-input-label">
                            <i class="fas fa-upload"></i> Choisir une photo
                        </label>
                    </div>
                    <button type="submit" name="upload" class="btn-upload">
                        <i class="fas fa-upload"></i> Télécharger la photo
                    </button>
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

        <!-- Admin Information Section -->
        <div class="form-section">
            <div class="form-section-title">
                <i class="fas fa-user-shield"></i>
                Informations du compte
            </div>
            
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label for="nom_utilisateur">Nom d'utilisateur *</label>
                        <input type="text" id="nom_utilisateur" name="nom_utilisateur" 
                               value="<?= htmlspecialchars($profile['nom_utilisateur']) ?>" required>
                        <small style="color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i> Nom d'utilisateur pour la connexion
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="email">Adresse e-mail *</label>
                        <input type="email" id="email" name="email" 
                               value="<?= htmlspecialchars($profile['email']) ?>" required>
                        <small style="color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i> Cette adresse sera utilisée pour l'expédition des emails
                        </small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                    <input type="password" id="password" name="password" 
                           placeholder="Entrez un nouveau mot de passe">
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <button type="submit" name="update_admin_info" class="btn-save">
                        <i class="fas fa-save"></i> Sauvegarder les informations du compte
                    </button>
                </div>
            </form>
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
            
            <div style="background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px; margin-bottom: 20px;">
                <p style="color: #92400e; font-size: 0.9rem; margin: 0;">
                    <i class="fas fa-info-circle"></i> <strong>Note :</strong> Remplissez seulement les champs que vous souhaitez modifier. 
                    L'API key ne sera mise à jour que si vous en saisissez une nouvelle.
                </p>
            </div>

            <form method="post">
                <div class="mail-config-grid">
                    <div class="form-group">
                        <label for="mail_host">Serveur SMTP</label>
                        <input type="text" id="mail_host" name="mail_host" value="<?= htmlspecialchars($profile['serveur_smtp'] ?? '') ?>" placeholder="smtp.gmail.com">
                    </div>
                    <div class="form-group">
                        <label for="mail_port">Port SMTP</label>
                        <input type="number" id="mail_port" name="mail_port" value="<?= htmlspecialchars($profile['port_smtp'] ?? '') ?>" placeholder="587">
                    </div>
                    <div class="form-group">
                        <label for="mail_username">Nom d'utilisateur SMTP</label>
                        <input type="text" id="mail_username" name="mail_username" value="<?= htmlspecialchars($profile['nom_smtp'] ?? '') ?>" placeholder="votre-email@gmail.com">
                        <small style="color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i> Adresse email utilisée pour l'authentification SMTP
                        </small>
                    </div>
                    <div class="form-group">
                        <label for="mail_password">Mot de passe/API Key</label>
                        <input type="password" id="mail_password" name="mail_password" value="" placeholder="Votre mot de passe ou clé API">
                        <small style="color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: block;">
                            <i class="fas fa-info-circle"></i> Mot de passe ou clé API de l'adresse SMTP
                            <?php if (!empty($profile['api_key_encrypted'])): ?>
                                <br><i class="fas fa-lock"></i> Une clé API est déjà configurée. Saisissez une nouvelle clé pour la remplacer.
                            <?php else: ?>
                                <br><i class="fas fa-info-circle"></i> Laissez vide pour conserver la clé actuelle.
                            <?php endif; ?>
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
                            <label for="mail_sender_name">Nom expéditeur</label>
                            <input type="text" id="mail_sender_name" name="mail_sender_name" 
                                   value="<?= htmlspecialchars($profile['nom_expediteur'] ?? 'Portail Administrateur') ?>" 
                                   placeholder="Portail Administrateur">
                            <small style="color: #6b7280; font-size: 0.8rem; margin-top: 5px; display: block;">
                                <i class="fas fa-info-circle"></i> Nom qui apparaîtra comme expéditeur des emails
                            </small>
                        </div>
                    </div>
                    <p style="color: #0c4a6e; font-size: 0.9rem; margin-top: 10px;">
                        <i class="fas fa-lightbulb"></i> L'adresse expéditeur est automatique. Le nom expéditeur est modifiable et par défaut utilise "Portail Administrateur".
                    </p>
                </div>
                
                <div style="margin-top: 20px; text-align: center;">
                    <button type="submit" name="update_mail_config" class="btn-save">
                        <i class="fas fa-save"></i> Sauvegarder la configuration email
                    </button>
                </div>
            </form>
        </div>

        <!-- Test Email Section -->
        <div class="form-section" style="background: #f0fdf4; border: 1px solid #22c55e; margin-top: 20px;">
            <div class="form-section-title" style="color: #166534;">
                <i class="fas fa-paper-plane"></i>
                Tester la configuration email
            </div>
            <p style="color: #166534; margin-bottom: 20px; font-size: 0.9rem;">
                <i class="fas fa-info-circle"></i> Envoyez un email de test pour vérifier que votre configuration fonctionne correctement.
            </p>
            
            <form method="post" style="display: flex; gap: 15px; align-items: end;">
                <div class="form-group" style="flex: 1; margin-bottom: 0;">
                    <label for="test_email_address">Adresse email de test *</label>
                    <input type="email" id="test_email_address" name="test_email_address" 
                           placeholder="test@example.com" required 
                           style="margin-bottom: 0;">
                </div>
                <div>
                    <button type="submit" name="test_email" class="btn-test" 
                            style="background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%); 
                                   color: white; padding: 12px 24px; border: none; border-radius: 8px; 
                                   cursor: pointer; font-weight: 600; transition: all 0.3s ease;
                                   box-shadow: 0 2px 8px rgba(34, 197, 94, 0.3);">
                        <i class="fas fa-paper-plane"></i> Envoyer un test
                    </button>
                </div>
            </form>
            
            <div style="margin-top: 15px; padding: 12px; background: #f0f9ff; border: 1px solid #0ea5e9; border-radius: 6px;">
                <p style="color: #0c4a6e; font-size: 0.85rem; margin: 0;">
                    <i class="fas fa-lightbulb"></i> <strong>Conseil :</strong> Utilisez votre propre adresse email pour recevoir le test et vérifier que tout fonctionne correctement.
                </p>
            </div>
        </div>
    </div>
</div>

</body>
</html>