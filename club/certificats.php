<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";
include "../includes/header.php";
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;


// Get organizer's events sorted by end date
$stmt = $conn->prepare("
    SELECT e.*
    FROM evenements e
    WHERE e.organisateur_id = ?
    ORDER BY e.dateFin DESC
");
$stmt->execute([$_SESSION['id']]);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

function generateCertificate($conn, $participantId, $event, $logoClub, $logoEcole) {
    // Fetch full participant data from database
    $stmt = $conn->prepare("SELECT * FROM etudiants WHERE id = ?");
    $stmt->execute([$participantId]);
    $participantData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$participantData) {
        return false;
    }
    
    // Get organizer name
    $stmt = $conn->prepare("SELECT clubNom FROM organisateur WHERE id = ?");
    $stmt->execute([$event['organisateur_id']]);
    $organisateurName = $stmt->fetchColumn();
    
    // Convert logos to base64 for embedding in PDF
    $logoEcoleBase64 = '';
    if (!empty($logoEcole)) {
        $logoPath = file_exists('../' . $logoEcole) ? '../' . $logoEcole : $logoEcole;
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $base64 = base64_encode($imageData);
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoEcoleBase64 = 'data:image/' . $ext . ';base64,' . $base64;
        }
    }
    
    $logoClubBase64 = '';
    if (!empty($logoClub)) {
        $logoPath = file_exists('../' . $logoClub) ? '../' . $logoClub : $logoClub;
        if (file_exists($logoPath)) {
            $imageData = file_get_contents($logoPath);
            $base64 = base64_encode($imageData);
            $ext = pathinfo($logoPath, PATHINFO_EXTENSION);
            $logoClubBase64 = 'data:image/' . $ext . ';base64,' . $base64;
        }
    }
    
    // Instantiate dompdf
    $dompdf = new Dompdf();
    
    // Capture the template output
    ob_start();
    include "certificat_themplate.php"; 
    $html = ob_get_clean();
    
    $dompdf->loadHtml($html);
    
    // Set paper size to A4 landscape with reduced margins
    $dompdf->setPaper([0, 0, 375, 750], 'landscape'); // Smaller than A4
    
    // Render the PDF
    $dompdf->render();
    
    // Get PDF output
    $pdf = $dompdf->output();
    
    // Create directory if doesn't exist
    $uploadDir = '../assets/uploads/certificates/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }
    
    // Generate filename
    $fileName = 'certificate_' . $event['idEvent'] . '_' . $participantId . '_' . time() . '.pdf';
    $filePath = $uploadDir . $fileName;
    $relativePath = 'assets/uploads/certificates/' . $fileName;
    
    // Save PDF to file
    file_put_contents($filePath, $pdf);
    
    // Update database with certificate path
    $stmt = $conn->prepare("UPDATE participation SET attestation = ?, certified = 1 WHERE evenement_id = ? AND etudiant_id = ?");
    $stmt->execute([$relativePath, $event['idEvent'], $participantId]);
    
    
    return true;
}

// Handle form submission for certificate generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_certificates') {
    // Your POST treatment here
    $event_id = $_POST['event_id'] ?? null;
    $selected_participants = $_POST['participants'] ?? [];
    $stmt = $conn->prepare("SELECT logo FROM organisateur WHERE id = ?;");
    $stmt->execute([$_SESSION['id']]);
    $logo = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $conn->prepare("SELECT photo FROM admin WHERE id = 1;");
    $stmt->execute();
    $logoEcole = $stmt->fetch(PDO::FETCH_ASSOC);
    $stmt = $conn->prepare("SELECT * FROM evenements WHERE idEvent = ? AND organisateur_id = ?;");
    $stmt->execute([$event_id, $_SESSION['id']]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    foreach ($selected_participants as $participant) {
        generateCertificate($conn, $participant, $event, $logo['logo'], $logoEcole['photo']);
    }

    header("Location: certificats.php");
    exit();
    // You can access the data here and do your processing
    // $event_id contains the event ID
    // $selected_participants is an array of student IDs
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../includes/script.js"></script>
    <title>Gestion des Certificats</title>
    
    <style>
        .certificates-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
        }
        
        .event-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: box-shadow 0.3s ease;
        }
        
        .event-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .event-info {
            flex: 1;
        }
        
        .event-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0 0 0.5rem 0;
        }
        
        .event-details {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .event-details i {
            margin-right: 0.25rem;
            color: #007bff;
        }
        
        .event-details span {
            margin-right: 1.5rem;
        }
        
        .btn-open-modal {
            background: linear-gradient(135deg, #007bff, #0056b3);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .btn-open-modal:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.3);
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .modal-content {
            background-color: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 700px;
            max-height: 80vh;
            display: flex;
            flex-direction: column;
        }
        
        .modal-header {
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            margin: 0;
            color: #2c3e50;
            font-size: 1.25rem;
        }
        
        .close {
            font-size: 1.75rem;
            font-weight: bold;
            color: #6c757d;
            cursor: pointer;
            line-height: 1;
        }
        
        .close:hover {
            color: #000;
        }
        
        .modal-body {
            padding: 1rem;
            overflow-y: auto;
            flex: 1;
            max-height: 400px;
        }
        
        .modal-footer {
            padding: 0.75rem 1rem;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 0.75rem;
            justify-content: space-between;
        }
        
        .participant-item {
            padding: 0.5rem;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            margin-bottom: 0.4rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .participant-item input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            flex-shrink: 0;
        }
        
        .participant-item input[type="checkbox"]:disabled {
            cursor: not-allowed;
        }
        
        .participant-item.certified {
            background: #f8f9fa;
        }
        
        .participant-name {
            flex: 1;
            font-weight: 500;
            font-size: 0.9rem;
        }
        
        .certified-badge {
            background: #28a745;
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 10px;
            font-size: 0.75rem;
            white-space: nowrap;
        }
        
        .btn-select-all, .btn-unselect-all {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .btn-select-all:hover, .btn-unselect-all:hover {
            background: #5a6268;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .btn-submit:hover {
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }
        
        .btn-close {
            background: #6c757d;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.875rem;
        }
        
        .btn-close:hover {
            background: #5a6268;
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem;
            color: #6c757d;
        }
        
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
    </style>
</head>
<body>
<div>
    <div class="tabs">
        <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
        <div class="tab" onclick="navigateTo('evenements_clubs.php')">Mes événements</div>
        <div class="tab" onclick="navigateTo('ajouter_evenement.php')">Ajouter un événement</div>
        <div class="tab" onclick="navigateTo('demandes_participants.php')">Participants</div>
        <div class="tab" onclick="navigateTo('communications.php')">Communications</div>
        <div class="tab active" onclick="navigateTo('certificats.php')">Certificats</div>
    </div>

    <div class="certificates-container">
        <div class="events-header">
            <h2><i class="bi bi-award me-2"></i>Gestion des Certificats</h2>
            <p>Générer les certificats de participation pour vos événements</p>
        </div>
        
        <?php if (empty($events)): ?>
            <div class="empty-state">
                <i class="bi bi-calendar-x"></i>
                <h4>Aucun événement</h4>
                <p>Vous n'avez pas encore organisé d'événements.</p>
            </div>
        <?php else: ?>
            <?php foreach ($events as $event): ?>
                <div class="event-card">
                    <div class="event-info">
                        <h3 class="event-title"><?= htmlspecialchars($event['nomEvent']) ?></h3>
                        <div class="event-details">
                            <span><i class="bi bi-calendar3"></i><?= date('d/m/Y', strtotime($event['dateDepart'])) ?> - <?= date('d/m/Y', strtotime($event['dateFin'])) ?></span>
                            <span><i class="bi bi-geo-alt-fill"></i><?= htmlspecialchars($event['lieu']) ?></span>
                            <span><i class="bi bi-tag-fill"></i><?= htmlspecialchars($event['categorie'] ?? 'N/A') ?></span>
                        </div>
                    </div>
                    <button type="button" class="btn-open-modal" onclick="openParticipantsModal(<?= $event['idEvent'] ?>, '<?= htmlspecialchars($event['nomEvent'], ENT_QUOTES) ?>')">
                        <i class="bi bi-people-fill"></i> Gérer les certificats
                    </button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Modal for participants -->
    <div id="participantsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="modalEventName">Événement</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form method="POST" action="" id="certificateForm">
                <input type="hidden" name="action" value="generate_certificates">
                <input type="hidden" name="event_id" id="modalEventId">
                
                <div class="modal-body" id="participantsList">
                    <!-- Participants will be loaded here via JavaScript -->
                </div>
                
                <div class="modal-footer">
                    <div>
                        <button type="button" class="btn-select-all" onclick="selectAll()">Tout sélectionner</button>
                        <button type="button" class="btn-unselect-all" onclick="unselectAll()">Tout désélectionner</button>
                    </div>
                    <div>
                        <button type="button" class="btn-close" onclick="closeModal()">Fermer</button>
                        <button type="submit" class="btn-submit">Générer les certificats</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Store participants data for the modal
        const participantsData = <?= json_encode(
            array_reduce($events, function($carry, $event) use ($conn) {
                $stmt = $conn->prepare("
                    SELECT p.attestation, p.certified, e.nom, e.prenom, e.id as student_id, e.filiere, e.annee
                    FROM participation p
                    JOIN etudiants e ON p.etudiant_id = e.id
                    WHERE p.evenement_id = ? AND p.etat = 'Accepté'
                    ORDER BY e.nom, e.prenom
                ");
                $stmt->execute([$event['idEvent']]);
                $carry[$event['idEvent']] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return $carry;
            }, [])
        ) ?>;
        
        function openParticipantsModal(eventId, eventName) {
            const modal = document.getElementById('participantsModal');
            const modalEventName = document.getElementById('modalEventName');
            const modalEventId = document.getElementById('modalEventId');
            const participantsList = document.getElementById('participantsList');
            
            modalEventName.textContent = eventName;
            modalEventId.value = eventId;
            
            // Load participants for this event
            const participants = participantsData[eventId] || [];
            
            if (participants.length === 0) {
                participantsList.innerHTML = '<p style="text-align: center; color: #6c757d;">Aucun participant accepté pour cet événement.</p>';
            } else {
                let html = '';
                participants.forEach(participant => {
                    const isCertified = participant.certified == 1;
                    const disabled = isCertified ? 'disabled checked' : '';
                    const certifiedClass = isCertified ? 'certified' : '';
                    
                    html += `
                        <div class="participant-item ${certifiedClass}">
                            <input type="checkbox" name="participants[]" value="${participant.student_id}" ${disabled}>
                            <span class="participant-name">${participant.prenom} ${participant.nom}</span>
                            ${isCertified ? '<span class="certified-badge">Déjà certifié</span>' : ''}
                        </div>
                    `;
                });
                participantsList.innerHTML = html;
            }
            
            modal.style.display = 'block';
        }
        
        function closeModal() {
            document.getElementById('participantsModal').style.display = 'none';
        }
        
        function selectAll() {
            const checkboxes = document.querySelectorAll('#participantsList input[type="checkbox"]:not(:disabled)');
            checkboxes.forEach(cb => cb.checked = true);
        }
        
        function unselectAll() {
            const checkboxes = document.querySelectorAll('#participantsList input[type="checkbox"]:not(:disabled)');
            checkboxes.forEach(cb => cb.checked = false);
        }
        
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('participantsModal');
            if (event.target == modal) {
                closeModal();
            }
        }
    </script>
    
</div>
</body>
</html>