<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM etudiants natural join utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['id']]);
$participant = $stmt->fetch(PDO::FETCH_ASSOC);
$events = $conn->query("SELECT * FROM evenements ORDER BY dateDepart ASC")->fetchAll(PDO::FETCH_ASSOC);

$participations_stmt = $conn->prepare("SELECT evenement_id FROM participation WHERE etudiant_id = ?");
$participations_stmt->execute([$_SESSION['id']]);
$participations = $participations_stmt->fetchAll(PDO::FETCH_COLUMN);

// Handle participation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['event_id']) && isset($_POST['submit_participation'])) {
    $event_id = $_POST['event_id'];
    $commentaire = $_POST['commentaire'] ?? '';

    $insert_stmt = $conn->prepare("INSERT INTO participation (etudiant_id, evenement_id, commentaire, date_demande) VALUES (?, ?, ?, NOW())");
    if ($insert_stmt->execute([$_SESSION['id'], $event_id, $commentaire])) {
        header('Location: dashboard.php?success=1');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portail Étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Base */
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        body { background: #f5f7ff; color: #333; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; background: #1f3c88; color: #fff; padding: 20px 40px; border-radius: 0 0 15px 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .logo { width: 50px; height: 50px; background: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #1f3c88; }
        .header-info h2 { font-size: 1.6rem; font-weight: 600; margin-bottom: 3px; }
        .header-info p { font-size: 0.85rem; color: #c5d9f5; font-weight: 400; }
        .header-right { display: flex; align-items: center; gap: 20px; }
        .header-right a { color: #fff; text-decoration: none; font-weight: 500; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
        .header-right a:hover { color: #ffd700; }

        /* Nav */
        .nav { display: flex; justify-content: center; background: #fff; padding: 0; box-shadow: 0 2px 6px rgba(0,0,0,0.05); }
        .nav a { flex: 1; max-width: 250px; text-decoration: none; }
        .nav button { background: transparent; border: none; padding: 18px 30px; width: 100%; font-size: 0.95rem; cursor: pointer; transition: 0.3s; color: #666; font-weight: 500; border-bottom: 3px solid transparent; }
        .nav button.active { color: #1f3c88; border-bottom-color: #1f3c88; background: #f0f3ff; }
        .nav button:hover { background: #f0f3ff; color: #1f3c88; }

        /* Cards */
        .cards { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; padding: 25px 50px; }
        .card { background: #fff; border-radius: 15px; padding: 20px; box-shadow: 0 10px 20px rgba(0,0,0,0.06); transition: transform 0.25s, box-shadow 0.25s; display: flex; flex-direction: column; justify-content: space-between; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 15px 25px rgba(0,0,0,0.1); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .card-header h3 { font-size: 1.3rem; color: #1f3c88; }
        .status.green { color: #28a745; font-weight: bold; }
        .status.orange { color: #fd7e14; font-weight: bold; }
        .status.gray { color: #6c757d; font-weight: bold; }
        .card p { margin: 6px 0; line-height: 1.4; }
        .infos p { font-size: 0.9rem; color: #555; }
        .infos i { margin-right: 5px; color: #1f3c88; }
        .footer { display: flex; justify-content: flex-end; margin-top: 15px; }
        .participate-btn, .requested { padding: 8px 16px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 0.9rem; transition: 0.3s; }
        .participate-btn { background: #1f3c88; color: #fff; }
        .participate-btn:hover { background: #15306b; }
        .requested { background: #6c757d; color: #fff; cursor: not-allowed; }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); animation: fadeIn 0.3s; }
        @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
        .modal-content { background: #fff; margin: 3% auto; padding: 0; border-radius: 15px; width: 90%; max-width: 600px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); animation: slideDown 0.3s; max-height: 90vh; overflow-y: auto; }
        @keyframes slideDown { from {transform: translateY(-50px); opacity:0;} to {transform: translateY(0); opacity:1;} }
        .modal-header { background: #1f3c88; color: #fff; padding: 20px 25px; border-radius: 15px 15px 0 0; display: flex; justify-content: space-between; align-items: center; }
        .modal-header h2 { font-size: 1.5rem; margin: 0; }
        .close { color: #fff; font-size: 28px; font-weight: bold; cursor: pointer; transition: 0.3s; }
        .close:hover { color: #ffd700; }
        .modal-body { padding: 25px; }
        .modal-section { margin-bottom: 20px; }
        .modal-section h3 { color: #1f3c88; font-size: 1.1rem; margin-bottom: 10px; border-bottom: 2px solid #eef4ff; padding-bottom: 8px; }
        .modal-section p { margin: 8px 0; line-height: 1.6; }
        .modal-info-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px; }
        .modal-info-item { background: #f8f9fa; padding: 12px; border-radius: 8px; }
        .modal-info-item strong { display: block; color: #1f3c88; margin-bottom: 5px; font-size: 0.9rem; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; color: #333; }
        .form-group textarea { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 8px; font-size: 0.95rem; resize: vertical; min-height: 100px; transition: 0.3s; }
        .form-group textarea:focus { outline: none; border-color: #1f3c88; }
        .form-group textarea::placeholder { color: #aaa; }
        .checkbox-group { display: flex; align-items: start; gap: 10px; margin-bottom: 20px; }
        .checkbox-group input[type="checkbox"] { margin-top: 4px; width: 18px; height: 18px; cursor: pointer; }
        .checkbox-group label { cursor: pointer; line-height: 1.5; }
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 20px 25px; background: #f8f9fa; border-radius: 0 0 15px 15px; }
        .btn-cancel, .btn-submit { padding: 10px 25px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 0.95rem; transition: 0.3s; }
        .btn-cancel { background: #6c757d; color: #fff; }
        .btn-cancel:hover { background: #5a6268; }
        .btn-submit { background: #1f3c88; color: #fff; }
        .btn-submit:hover { background: #15306b; }
        .btn-submit:disabled { background: #ccc; cursor: not-allowed; }

        /* Responsive */
        @media (max-width: 768px) { .cards { padding: 20px 15px; grid-template-columns: 1fr; } .modal-content { width: 95%; margin: 10% auto; } .modal-info-row { grid-template-columns: 1fr; } }
    </style>
</head>
<body>

<header class="header">
    <div class="header-left">
        <div class="logo"><i class="fas fa-graduation-cap"></i></div>
        <div class="header-info">
            <h2>Portail Étudiant</h2>
            <p>ENSA Tétouan - École Nationale des Sciences Appliquées</p>
        </div>
    </div>
    <div class="header-right">
        <span><?= htmlspecialchars($participant['nom'] . ' ' . $participant['prenom']) ?></span>
        <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
    </div>
</header>

<nav class="nav">
    <a href="dashboard.php"><button class="active">Tous les événements</button></a>
    <a href="mes_inscriptions.php"><button>Mes inscriptions</button></a>
    <a href="#"><button>Mes certificats</button></a>
    <a href="profile.php"><button>Mon profil</button></a>
</nav>

<div class="cards">
    <?php foreach ($events as $event): ?>
        <?php $alreadyRequested = in_array($event['idEvent'], $participations); ?>
        <div class="card">
            <div class="card-header">
                <h3><?= htmlspecialchars($event['nomEvent']) ?></h3>
                <?php if ($event['status'] === 'en cours de traitement'): ?>
                    <span class="status orange">En cours</span>
                <?php elseif ($event['status'] === 'terminé'): ?>
                    <span class="status gray">Terminé</span>
                <?php else: ?>
                    <span class="status green"><?= htmlspecialchars($event['status']) ?></span>
                <?php endif; ?>
            </div>
            <p><strong>Catégorie:</strong> <?= htmlspecialchars($event['categorie']) ?></p>
            <p><?= htmlspecialchars($event['descriptionEvenement']) ?></p>
            <div class="infos">
                <p><i class="fa-regular fa-calendar"></i><?= htmlspecialchars($event['dateDepart']) ?> <strong><?= htmlspecialchars($event['heureDepart']) ?></strong> → <?= htmlspecialchars($event['dateFin']) ?> <strong><?= htmlspecialchars($event['heureFin']) ?></strong></p>
                <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($event['lieu']) ?></p>
                <p><i class="fa-solid fa-users"></i> <?= htmlspecialchars($event['places']) ?> places</p>
            </div>
            <div class="footer">
                <?php if ($alreadyRequested): ?>
                    <button class="requested" disabled>Participation demandée</button>
                <?php else: ?>
                    <button type="button" class="participate-btn" onclick="openModal(<?= htmlspecialchars(json_encode($event)) ?>)">Voir détails</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Modal -->
<div id="eventModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2 id="modalTitle"></h2>
            <span class="close" onclick="closeModal()">&times;</span>
        </div>
        <form method="POST" id="participationForm" action="mes_demandes.php">
            <div class="modal-body">
                <div class="modal-section">
                    <p id="modalClub" style="color:#666;font-style:italic;"></p>
                </div>
                <div class="modal-section">
                    <h3>Description</h3>
                    <p id="modalDescription"></p>
                </div>
                <div class="modal-info-row">
                    <div class="modal-info-item"><strong>Période</strong><p id="modalPeriod"></p></div>
                    <div class="modal-info-item"><strong>Lieu</strong><p id="modalLieu"></p></div>
                </div>
                <div class="modal-section" style="margin-top: 15px;">
                    <div class="modal-info-item"><strong>Places</strong><p id="modalPlaces"></p></div>
                </div>
                <div class="modal-section">
                    <h3>Informations Étudiant</h3>
                    <div class="modal-info-item"><strong>Nom & Prénom</strong><p><?= htmlspecialchars($participant['nom'] . ' ' . $participant['prenom']) ?></p></div>
                    <div class="modal-info-item"><strong>Email</strong><p><?= htmlspecialchars($participant['email']) ?></p></div>
                    <div class="modal-info-item"><strong>Filière</strong><p><?= htmlspecialchars($participant['filiere'] ?? '-') ?></p></div>
                </div>
                <div class="modal-section">
                    <h3>S'inscrire à cet événement</h3>
                    <div class="form-group">
                        <label>Commentaire (optionnel)</label>
                        <textarea name="commentaire" placeholder="Pourquoi souhaitez-vous participer à cet événement ?"></textarea>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" id="acceptTerms" required>
                        <label for="acceptTerms">J'accepte les conditions de participation et le règlement de l'événement</label>
                    </div>
                </div>
                <input type="hidden" name="event_id" id="modalEventId">
                <input type="hidden" name="submit_participation" value="1">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Annuler</button>
                <button type="submit" name="participer" class="btn-submit" id="submitBtn" disabled>Demander participation</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById('eventModal');
    const submitBtn = document.getElementById('submitBtn');
    const acceptTerms = document.getElementById('acceptTerms');

    acceptTerms.addEventListener('change', function() {
        submitBtn.disabled = !this.checked;
    });

    function openModal(event) {
        document.getElementById('modalTitle').textContent = event.nomEvent;
        document.getElementById('modalClub').textContent = event.categorie;
        document.getElementById('modalDescription').textContent = event.descriptionEvenement;
        document.getElementById('modalPeriod').innerHTML = `${event.dateDepart} au ${event.dateFin}<br>${event.heureDepart} - ${event.heureFin}`;
        document.getElementById('modalLieu').textContent = event.lieu;
        document.getElementById('modalPlaces').textContent = event.places;
        document.getElementById('modalEventId').value = event.idEvent;

        document.getElementById('participationForm').reset();
        submitBtn.disabled = true;

        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    window.onclick = function(event) {
        if (event.target == modal) { closeModal(); }
    }
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.style.display === 'block') { closeModal(); }
    });
</script>
</body>
</html>
