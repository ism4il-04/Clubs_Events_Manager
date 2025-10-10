<?php

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
require_once "../includes/db.php";
include "../includes/header.php";

require_once '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

function envoyerMail ($email,$resultat,$nom,$prenom,$sujetParam = null,$corpsParam = null){

    $mail = new PHPMailer(true);
        // Use custom subject/body when provided, otherwise fall back to legacy templates
        if ($sujetParam !== null && $corpsParam !== null) {
            $sujet = $sujetParam;
            $corps = $corpsParam;
        } else {
            $sujet="Reponse a votre candidature de stage - Entreprise";
            if ($resultat == true){ //si le cv est accepté resultat = true
                $corps="Bonjour {$prenom} {$nom},\n\nAprès consultation de votre candidature et de votre CV, nous avons le plaisir de vous informer que votre profil a été retenu pour un stage au sein de notre organisation.\nNous reviendrons vers vous prochainement pour vous communiquer les détails pratiques concernant le déroulement du stage.\n\nCordialement,\nL'équipe Recrutement";
            } else { //si le cv n'est accepté resultat = false
                $corps ="Bonjour {$prenom} {$nom},\n\nAprès consultation de votre candidature et de votre CV, nous regrettons de vous informer que votre profil n'a pas été retenu pour le stage proposé.\nNous vous remercions pour l'intérêt porté à notre organisation et vous souhaitons beaucoup de succès dans vos recherches futures.\n\nCordialement,\nL'équipe Recrutement";
            }
        }
        try {
            //Server settings
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host       = $_ENV["HOST"];                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->SMTPSecure = 'tls';   
            $mail->Username   = $_ENV["USERNAME"];                     //SMTP username
            $mail->Password   = $_ENV["API_KEY"];                              //SMTP password
            $mail->Port       = $_ENV["PORT"];
            $mail->From       = $_ENV["FROM"]; 
            $mail->FromName   = $_ENV["FROM_NAME"];
            $mail->addReplyTo($_ENV["REPLY_TO"]); //l'adresse à répondre
            $mail->addAddress($email);
            $mail->Body    = $corps;
            $mail->Subject = $sujet;
            $mail->send();
        } catch(Exception $e){
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }

}


// Get filter values
$eventFilter = $_GET['event'] ?? '';
$participantFilter = $_GET['participant'] ?? '';

$events = fetchEvents($conn);
$participants = fetchParticipants($conn,$eventFilter);
// Load demandes to show eligible recipients (default Accepté)
$demandes = fetchDemandes($conn, 'Accepté', $eventFilter, $participantFilter);

// Handle sending messages
$mail_message = '';
$mail_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'send_messages')) {
    $sujet = trim($_POST['sujet'] ?? '');
    $corps = trim($_POST['corps'] ?? '');
    $recipients = $_POST['recipients'] ?? [];

    if (empty($sujet) || empty($corps)) {
        $mail_error = "Veuillez saisir un sujet et un message.";
    } elseif (empty($recipients)) {
        $mail_error = "Veuillez sélectionner au moins un destinataire.";
    } else {
        $sent = 0;
        foreach ($recipients as $email) {
            // We don't need nom/prenom for custom messages; pass empty strings
            envoyerMail($email, true, '', '', $sujet, $corps);
            $sent++;
        }
        $mail_message = $sent . " message(s) envoyé(s) avec succès.";
    }
}


function fetchDemandes($conn, $statusFilter = 'Accepté', $eventFilter = '', $participantFilter = '') {
    $sql = "SELECT * FROM utilisateurs NATURAL JOIN etudiants JOIN participation ON etudiant_id = etudiants.id JOIN evenements ON evenement_id=evenements.idEvent WHERE organisateur_id=?";
    $params = [$_SESSION['id']];
    
    if (!empty($statusFilter)) {
        $sql .= " AND participation.etat = ?";
        $params[] = $statusFilter;
    }
    
    if (!empty($eventFilter)) {
        $sql .= " AND evenements.nomEvent = ?";
        $params[] = $eventFilter;
    }

    if (!empty($participantFilter)) {
        $sql .= " AND (utilisateurs.id =?)";
        $params[] = $participantFilter;
    }
    
    $sql .= " ORDER BY date_demande";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetchEvents($conn) {
    $stmt = $conn->prepare("SELECT nomEvent FROM evenements JOIN organisateur ON organisateur.id = evenements.organisateur_id WHERE organisateur_id=?" );
    $stmt->execute([$_SESSION['id']]);
    return $stmt->fetchAll();
}

function fetchParticipants($conn,$eventFilter='') {
    $sql = "SELECT * FROM utilisateurs NATURAL JOIN etudiants JOIN participation ON etudiants.id = participation.etudiant_id JOIN evenements ON idEvent = evenement_id WHERE organisateur_id=? AND participation.etat='Accepté'"; ;
    $params = [$_SESSION['id']];

    if (!empty($eventFilter)) {
        $sql .= " AND evenements.nomEvent = ?";
        $params[] = $eventFilter;
    }
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/script.js"></script>
    <title>Communication</title>
</head>
<body>
    
<div>
    <div class="tabs">
        <div class="tab">Tableau de bord</div>
        <div class="tab" onclick="navigateTo('evenements_clubs.php')">Mes événements</div>
        <div class="tab" onclick="navigateTo('evenements_clubs.php')">Ajouter un événement</div>
        <div class="tab" onclick="navigateTo('demandes_participants.php')">Participants</div>
        <div class="tab active" onclick="navigateTo('communications.php')">Communications</div>
        <div class="tab" onclick="navigateTo('certificats.php')">Certificats</div>
    </div>

    <div class="events-container">
        <div class="events-header">
            <h2>Communication</h2>
            <p>Communication avec les participants dans un événement</p>
        </div>
        <?php if (!empty($mail_message)): ?>
            <div class="alert alert-success text-center"><i class="bi bi-check-circle-fill me-1"></i><?= htmlspecialchars($mail_message) ?></div>
        <?php endif; ?>
        <?php if (!empty($mail_error)): ?>
            <div class="alert alert-danger text-center"><i class="bi bi-x-circle-fill me-1"></i><?= htmlspecialchars($mail_error) ?></div>
        <?php endif; ?>
    </div>

    <form method="GET" class="filters mb-4">
            <div class="row g-2 align-items-center">

                <div class="col-md-3">
                    <label for="eventFilter" class="form-label">Nom de l'événement</label>
                    <select id="eventFilter" name="event" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les événements</option>
                        <?php foreach ($events as $event): ?>
                        <option value="<?= htmlspecialchars($event['nomEvent']) ?>" <?= $eventFilter === $event['nomEvent'] ? 'selected' : '' ?>><?= htmlspecialchars($event['nomEvent']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="participantFilter" class="form-label">Nom de participant</label>
                    <select id="participantFilter" name="participant" class="form-select" onchange="this.form.submit()">
                        <option value="">Tous les participants</option>
                        <?php foreach ($participants as $participant): ?>
                        <option value="<?= htmlspecialchars($participant['id']) ?>" <?= $participantFilter == $participant['id'] ? 'selected' : '' ?>><?= htmlspecialchars($participant['prenom']." ".$participant['nom']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-secondary w-100" onclick="clearFilters()">Effacer</button>
                </div>
            </div>
        </form>
        <div class="d-flex justify-content-center">
        <div class="card mt-4" style="max-width: 860px; width: 100%;">
            <div class="card-body">
                <h5 class="card-title mb-3">Envoyer un message aux participants</h5>
                <form method="POST">
                    <input type="hidden" name="action" value="send_messages" />
                    <?php if (empty($eventFilter)): ?>
                        <div class="alert alert-info mb-3 text-center"><i class="bi bi-info-circle me-1"></i>Veuillez d'abord sélectionner un événement pour pouvoir envoyer des messages.</div>
                    <?php endif; ?>
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label">Sujet</label>
                            <input type="text" class="form-control" name="sujet" placeholder="Sujet du message" <?= empty($eventFilter) ? 'disabled' : 'required' ?>>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Message</label>
                            <textarea class="form-control" name="corps" rows="5" placeholder="Contenu du message" <?= empty($eventFilter) ? 'disabled' : 'required' ?>></textarea>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Destinataires (Acceptés<?= $eventFilter ? ' - ' . htmlspecialchars($eventFilter) : '' ?>)</label>
                            <div class="border rounded p-3" style="max-height: 220px; overflow:auto;">
                                <?php if (empty($demandes)): ?>
                                    <div class="text-muted">Aucun participant accepté pour les filtres actuels.</div>
                                <?php else: ?>
                                    <?php foreach ($demandes as $demande): ?>
                                        <div class="form-check">
                                            <input class="form-check-input rec-checkbox" type="checkbox" name="recipients[]" id="rec_<?= htmlspecialchars($demande['email']) ?>" value="<?= htmlspecialchars($demande['email']) ?>" <?= empty($eventFilter) ? 'disabled' : '' ?>>
                                            <label class="form-check-label" for="rec_<?= htmlspecialchars($demande['email']) ?>">
                                                <?= htmlspecialchars($demande['prenom'] . ' ' . $demande['nom']) ?>
                                                <span class="text-muted">&lt;<?= htmlspecialchars($demande['email'] ?? '') ?>&gt;</span>
                                                <?php if (!empty($demande['nomEvent'])): ?>
                                                    <span class="badge bg-light text-dark ms-2"><i class="bi bi-calendar-event me-1"></i><?= htmlspecialchars($demande['nomEvent']) ?></span>
                                                <?php endif; ?>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-12 d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-outline-secondary" onclick="document.querySelectorAll('.rec-checkbox').forEach(cb=>cb.checked=true)" <?= empty($eventFilter) ? 'disabled' : '' ?>><i class="bi bi-check2-square me-1"></i>Sélectionner tout</button>
                            <button type="button" class="btn btn-outline-secondary" onclick="document.querySelectorAll('.rec-checkbox').forEach(cb=>cb.checked=false)"><i class="bi bi-x-circle me-1"></i>Désélectionner tout</button>
                            <button type="submit" class="btn btn-primary" <?= empty($eventFilter) ? 'disabled' : '' ?>><i class="bi bi-send me-1"></i>Envoyer</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        </div>
    
</div>


<script>
    function clearFilters() {
        window.location.href = 'demandes_participants.php';
    }
</script>
</body>
</html>