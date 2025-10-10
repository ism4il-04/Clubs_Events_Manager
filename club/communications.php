<?php

session_start();
if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}
require_once "../includes/db.php";
include "../includes/header.php";


// Get filter values
$eventFilter = $_GET['event'] ?? '';
$participantFilter = $_GET['participant'] ?? '';

$events = fetchEvents($conn);
$participants = fetchParticipants($conn,$eventFilter);


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
    
</div>


<script>
    function clearFilters() {
        window.location.href = 'demandes_participants.php';
    }
</script>
</body>
</html>