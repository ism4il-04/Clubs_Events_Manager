<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";
include "admin_header.php";

require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Get admin email configuration
$adminConfigStmt = $conn->prepare("SELECT email, nom_utilisateur FROM utilisateurs WHERE id = ?");
$adminConfigStmt->execute([$_SESSION['id']]);
$adminConfig = $adminConfigStmt->fetch(PDO::FETCH_ASSOC);

// Get all users for communication
function fetchAllUsers($conn) {
    $stmt = $conn->prepare("
        SELECT u.*, 
               CASE 
                 WHEN a.id IS NOT NULL THEN 'admin'
                 WHEN o.id IS NOT NULL THEN 'club' 
                 WHEN e.id IS NOT NULL THEN 'etudiant'
                 ELSE 'non défini'
               END as role,
               e.prenom, e.nom, e.filiere, e.annee,
               o.clubNom, o.nom_abr
        FROM utilisateurs u
        LEFT JOIN admin a ON u.id = a.id
        LEFT JOIN organisateur o ON u.id = o.id
        LEFT JOIN etudiants e ON u.id = e.id
        ORDER BY u.id ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get all events for filtering
function fetchAllEvents($conn) {
    $stmt = $conn->prepare("SELECT idEvent, nomEvent, dateDepart FROM evenements ORDER BY dateDepart DESC");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get participants for specific event
function fetchEventParticipants($conn, $eventId) {
    $stmt = $conn->prepare("
        SELECT u.*, e.prenom, e.nom, e.filiere, e.annee, p.etat, ev.nomEvent
        FROM utilisateurs u
        JOIN etudiants e ON u.id = e.id
        JOIN participation p ON e.id = p.etudiant_id
        JOIN evenements ev ON p.evenement_id = ev.idEvent
        WHERE p.evenement_id = ? AND p.etat = 'Accepté'
        ORDER BY e.prenom, e.nom
    ");
    $stmt->execute([$eventId]);
    return $stmt->fetchAll();
}

// Get clubs
function fetchClubs($conn) {
    $stmt = $conn->prepare("
        SELECT u.*, o.clubNom, o.nom_abr, o.description
        FROM utilisateurs u
        JOIN organisateur o ON u.id = o.id
        ORDER BY o.clubNom
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Get students
function fetchStudents($conn) {
    $stmt = $conn->prepare("
        SELECT u.*, e.prenom, e.nom, e.filiere, e.annee
        FROM utilisateurs u
        JOIN etudiants e ON u.id = e.id
        ORDER BY e.prenom, e.nom
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

// Handle file upload
function handleFileUpload($file, $uploadDir) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erreur lors du téléchargement du fichier.");
    }
    
    $allowedTypes = [
        'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain', 'application/zip', 'application/x-rar-compressed'
    ];
    
    $maxFileSize = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        throw new Exception("Type de fichier non autorisé.");
    }
    
    if ($file['size'] > $maxFileSize) {
        throw new Exception("Le fichier est trop volumineux. Taille maximale : 10MB.");
    }
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    $fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('attachment_') . '.' . $fileExtension;
    $targetPath = $uploadDir . $fileName;
    
    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        throw new Exception("Erreur lors de l'enregistrement du fichier.");
    }
    
    return 'assets/uploads/communications/' . $fileName;
}

// Send email with attachment
function sendEmailWithAttachment($to, $subject, $body, $attachmentPath = null, $attachmentName = null) {
    global $adminConfig;
    
    $mail = new PHPMailer(true);
    
    try {
        // Basic email settings (you may need to configure SMTP)
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Change to your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = $adminConfig['email'];
        $mail->Password = ''; // You'll need to add password or use app password
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        
        $mail->setFrom($adminConfig['email'], $adminConfig['nom_utilisateur']);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = nl2br($body);
        
        if ($attachmentPath && file_exists('../' . $attachmentPath)) {
            $mail->addAttachment('../' . $attachmentPath, $attachmentName);
        }
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        throw new Exception("Erreur lors de l'envoi de l'email: " . $e->getMessage());
    }
}

// Get filter values
$userTypeFilter = $_GET['user_type'] ?? '';
$eventFilter = $_GET['event'] ?? '';
$searchFilter = $_GET['search'] ?? '';

$allUsers = fetchAllUsers($conn);
$allEvents = fetchAllEvents($conn);
$clubs = fetchClubs($conn);
$students = fetchStudents($conn);

// Handle sending messages
$mail_message = '';
$mail_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_messages') {
    $sujet = trim($_POST['sujet'] ?? '');
    $corps = trim($_POST['corps'] ?? '');
    $recipients = $_POST['recipients'] ?? [];
    $attachmentPath = null;
    $attachmentName = null;
    
    try {
        if (empty($sujet) || empty($corps)) {
            throw new Exception("Veuillez saisir un sujet et un message.");
        }
        
        if (empty($recipients)) {
            throw new Exception("Veuillez sélectionner au moins un destinataire.");
        }
        
        // Handle file upload
        if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
            $attachmentPath = handleFileUpload($_FILES['attachment'], '../assets/uploads/communications/');
            $attachmentName = $_FILES['attachment']['name'];
        }
        
        $sent = 0;
        foreach ($recipients as $email) {
            sendEmailWithAttachment($email, $sujet, $corps, $attachmentPath, $attachmentName);
            $sent++;
        }
        
        $mail_message = $sent . " message(s) envoyé(s) avec succès.";
        
    } catch (Exception $e) {
        $mail_error = $e->getMessage();
    }
}

// Filter users based on criteria
$filteredUsers = $allUsers;

if (!empty($userTypeFilter)) {
    $filteredUsers = array_filter($filteredUsers, function($user) use ($userTypeFilter) {
        return $user['role'] === $userTypeFilter;
    });
}

if (!empty($eventFilter)) {
    $eventParticipants = fetchEventParticipants($conn, $eventFilter);
    $participantEmails = array_column($eventParticipants, 'email');
    $filteredUsers = array_filter($filteredUsers, function($user) use ($participantEmails) {
        return in_array($user['email'], $participantEmails);
    });
}

if (!empty($searchFilter)) {
    $filteredUsers = array_filter($filteredUsers, function($user) use ($searchFilter) {
        $searchTerm = strtolower($searchFilter);
        return strpos(strtolower($user['nom_utilisateur']), $searchTerm) !== false ||
               strpos(strtolower($user['email']), $searchTerm) !== false ||
               (isset($user['prenom']) && strpos(strtolower($user['prenom']), $searchTerm) !== false) ||
               (isset($user['nom']) && strpos(strtolower($user['nom']), $searchTerm) !== false) ||
               (isset($user['clubNom']) && strpos(strtolower($user['clubNom']), $searchTerm) !== false);
    });
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Communications - Admin</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <style>
        .communications-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .filter-section {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .message-form {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .recipients-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
        }
        
        .user-item {
            padding: 8px 0;
            border-bottom: 1px solid #f8f9fa;
        }
        
        .user-item:last-child {
            border-bottom: none;
        }
        
        .role-badge {
            font-size: 0.75rem;
            padding: 4px 8px;
            border-radius: 12px;
        }
        
        .badge-admin { background: #dc3545; color: white; }
        .badge-club { background: #ffc107; color: #000; }
        .badge-etudiant { background: #17a2b8; color: white; }
        
        .attachment-preview {
            margin-top: 10px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 6px;
            display: none;
        }
        
        .file-icon {
            margin-right: 8px;
        }
    </style>
</head>
<body>

<div class="tabs">
    <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
    <div class="tab" onclick="navigateTo('demandes_evenements.php')">Demandes d'événements</div>
    <div class="tab" onclick="navigateTo('evenements.php')">Tous les événements</div>
    <div class="tab" onclick="navigateTo('clubs.php')">Gestion des clubs</div>
    <div class="tab" onclick="navigateTo('utilisateurs.php')">Utilisateurs</div>
    <div class="tab active" onclick="navigateTo('communications.php')">Communications</div>
</div>

<div class="communications-container">
    <div class="events-header">
        <h2><i class="fas fa-envelope me-2"></i>Communications</h2>
        <p>Envoyez des messages à tous les utilisateurs de la plateforme</p>
    </div>

    <?php if (!empty($mail_message)): ?>
        <div class="alert alert-success text-center">
            <i class="bi bi-check-circle-fill me-1"></i><?= htmlspecialchars($mail_message) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($mail_error)): ?>
        <div class="alert alert-danger text-center">
            <i class="bi bi-x-circle-fill me-1"></i><?= htmlspecialchars($mail_error) ?>
        </div>
    <?php endif; ?>

    <!-- Filters Section -->
    <div class="filter-section">
        <h5 class="mb-3"><i class="fas fa-filter me-2"></i>Filtres</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="userTypeFilter" class="form-label">Type d'utilisateur</label>
                <select id="userTypeFilter" name="user_type" class="form-select" onchange="this.form.submit()">
                    <option value="">Tous les utilisateurs</option>
                    <option value="etudiant" <?= $userTypeFilter === 'etudiant' ? 'selected' : '' ?>>Étudiants</option>
                    <option value="club" <?= $userTypeFilter === 'club' ? 'selected' : '' ?>>Clubs</option>
                    <option value="admin" <?= $userTypeFilter === 'admin' ? 'selected' : '' ?>>Administrateurs</option>
                </select>
            </div>
            
            <div class="col-md-3">
                <label for="eventFilter" class="form-label">Événement</label>
                <select id="eventFilter" name="event" class="form-select" onchange="this.form.submit()">
                    <option value="">Tous les événements</option>
                    <?php foreach ($allEvents as $event): ?>
                        <option value="<?= $event['idEvent'] ?>" <?= $eventFilter == $event['idEvent'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($event['nomEvent']) ?> (<?= date('d/m/Y', strtotime($event['dateDepart'])) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-4">
                <label for="searchFilter" class="form-label">Recherche</label>
                <input type="text" id="searchFilter" name="search" class="form-control" 
                       placeholder="Nom, email, club..." value="<?= htmlspecialchars($searchFilter) ?>">
            </div>
            
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Rechercher
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="clearFilters()">
                        <i class="fas fa-times me-1"></i>Effacer
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Message Form -->
    <div class="message-form">
        <h5 class="mb-4"><i class="fas fa-paper-plane me-2"></i>Envoyer un message</h5>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="send_messages" />
            
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">Sujet *</label>
                    <input type="text" class="form-control" name="sujet" placeholder="Sujet du message" required>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Message *</label>
                    <textarea class="form-control" name="corps" rows="5" placeholder="Contenu du message" required></textarea>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Pièce jointe (optionnel)</label>
                    <input type="file" class="form-control" name="attachment" id="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.jpg,.jpeg,.png,.gif,.webp,.zip,.rar">
                    <small class="form-text text-muted">Formats acceptés: PDF, DOC, DOCX, XLS, XLSX, TXT, JPG, PNG, GIF, WEBP, ZIP, RAR (max 10MB)</small>
                    <div class="attachment-preview" id="attachmentPreview">
                        <i class="fas fa-paperclip me-2"></i>
                        <span id="attachmentName"></span>
                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearAttachment()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="col-md-12">
                    <label class="form-label">Destinataires (<?= count($filteredUsers) ?> utilisateur(s) trouvé(s))</label>
                    <div class="recipients-list">
                        <?php if (empty($filteredUsers)): ?>
                            <div class="text-muted text-center">Aucun utilisateur trouvé avec les filtres actuels.</div>
                        <?php else: ?>
                            <div class="mb-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="selectAll()">
                                    <i class="fas fa-check-square me-1"></i>Tout sélectionner
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="deselectAll()">
                                    <i class="fas fa-square me-1"></i>Tout désélectionner
                                </button>
                            </div>
                            
                            <?php foreach ($filteredUsers as $user): ?>
                                <div class="user-item">
                                    <div class="form-check">
                                        <input class="form-check-input recipient-checkbox" type="checkbox" 
                                               name="recipients[]" id="user_<?= $user['id'] ?>" 
                                               value="<?= htmlspecialchars($user['email']) ?>">
                                        <label class="form-check-label" for="user_<?= $user['id'] ?>">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <strong>
                                                        <?php
                                                        if ($user['role'] === 'etudiant') {
                                                            echo htmlspecialchars($user['prenom'] . ' ' . $user['nom']);
                                                        } elseif ($user['role'] === 'club') {
                                                            echo htmlspecialchars($user['clubNom']);
                                                        } else {
                                                            echo htmlspecialchars($user['nom_utilisateur']);
                                                        }
                                                        ?>
                                                    </strong>
                                                    <span class="text-muted ms-2">&lt;<?= htmlspecialchars($user['email']) ?>&gt;</span>
                                                </div>
                                                <span class="role-badge badge-<?= $user['role'] ?>">
                                                    <?= ucfirst($user['role']) ?>
                                                </span>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-md-12 text-center">
                    <button type="submit" class="btn btn-primary btn-lg" <?= empty($filteredUsers) ? 'disabled' : '' ?>>
                        <i class="fas fa-paper-plane me-2"></i>Envoyer le message
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function clearFilters() {
    window.location.href = 'communications.php';
}

function selectAll() {
    document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = true);
}

function deselectAll() {
    document.querySelectorAll('.recipient-checkbox').forEach(cb => cb.checked = false);
}

function clearAttachment() {
    document.getElementById('attachment').value = '';
    document.getElementById('attachmentPreview').style.display = 'none';
}

// Handle file attachment preview
document.getElementById('attachment').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('attachmentPreview');
    const nameSpan = document.getElementById('attachmentName');
    
    if (file) {
        nameSpan.textContent = file.name;
        preview.style.display = 'block';
    } else {
        preview.style.display = 'none';
    }
});

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>

</body>
</html>
