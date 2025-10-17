<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM etudiants NATURAL JOIN utilisateurs WHERE id = ?");
$stmt->execute([$_SESSION['id']]);
$participant = $stmt->fetch(PDO::FETCH_ASSOC);

// Get events - ensure no duplicates
$events = $conn->query("SELECT DISTINCT * FROM evenements WHERE status='Disponible' ORDER BY dateDepart ASC")->fetchAll(PDO::FETCH_ASSOC);

// Get registration counts and categories
$registrationCounts = [];
$categories = [];
foreach ($events as $event) {
    $countStmt = $conn->prepare("SELECT COUNT(*) as registered FROM participation WHERE evenement_id = ? AND etat = 'Accepté'");
    $countStmt->execute([$event['idEvent']]);
    $registrationCounts[$event['idEvent']] = $countStmt->fetch()['registered'];
    $categories[] = $event['categorie'] ?? 'Non spécifiée';
}
$categories = array_unique($categories);

$participations_stmt = $conn->prepare("SELECT evenement_id FROM participation WHERE etudiant_id = ?");
$participations_stmt->execute([$_SESSION['id']]);
$participations = $participations_stmt->fetchAll(PDO::FETCH_COLUMN);

// Prepare user information for modal
$userInfo = [
    'nom' => htmlspecialchars($participant['nom']),
    'prenom' => htmlspecialchars($participant['prenom']),
    'email' => htmlspecialchars($participant['email'] ?? 'N/A'),
    'matricule' => htmlspecialchars($participant['nom_utilisateur'] ?? 'N/A'),
    'filiere' => htmlspecialchars($participant['filiere'] ?? 'N/A')
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portail Étudiant</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Base */
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        body { background: #f5f7ff; color: #333; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px 40px; border-radius: 0 0 15px 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .logo { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #1f3c88; }
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

        /* Cards Container */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
            gap: 32px;
            padding: 40px 60px;
        }

        /* Enhanced Card Styles */
        .card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border: 1px solid rgba(255, 255, 255, 0.8);
            position: relative;
        }

        .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
        }

        .card:hover {
            transform: translateY(-8px);
            box-shadow: 0 16px 40px rgba(102, 126, 234, 0.15);
        }

        .card:hover::before {
            opacity: 1;
        }

        .card-image {
            width: 100%;
            height: 220px;
            overflow: hidden;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            transition: transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), filter 0.3s ease;
        }

        .card-image::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.1);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 1;
        }

        .card:hover .card-image::before {
            opacity: 1;
        }

        .card:hover .card-image img {
            transform: scale(1.08);
            filter: brightness(1.1);
        }

        /* Fallback for missing images */
        .card-image.no-image {
            background: linear-gradient(135deg, #a8b8c8 0%, #7c8aa0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 2rem;
            font-weight: bold;
        }

        .card-image.no-image::after {
            content: '📷';
        }

        .card-header {
            position: absolute;
            top: 220px;
            left: 0;
            right: 0;
            transform: translateY(-50%);
            padding: 0 24px;
            z-index: 10;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
        }

        .card-header h3 {
            background: #fff;
            padding: 12px 18px;
            border-radius: 12px;
            font-size: 1.15rem;
            font-weight: 700;
            color: #1f3c88;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            flex: 1;
            line-height: 1.4;
            max-width: calc(100% - 100px);
        }

        .status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            white-space: nowrap;
            backdrop-filter: blur(10px);
        }

        .status.green {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: #fff;
        }

        .status.orange {
            background: linear-gradient(135deg, #fd7e14 0%, #ffc107 100%);
            color: #fff;
        }

        .status.gray {
            background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%);
            color: #fff;
        }

        .card-content {
            padding: 40px 24px 20px;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        .card-content .category {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, #eef1f7 0%, #e3e8f0 100%);
            color: #1f3c88;
            font-size: 0.8rem;
            font-weight: 700;
            padding: 6px 14px;
            border-radius: 20px;
            align-self: flex-start;
            border: 1.5px solid #d5dce8;
        }

        .card-content .category::before {
            content: '•';
            font-size: 1.2rem;
        }

        .card-content > p {
            color: #555;
            font-size: 0.93rem;
            line-height: 1.6;
            margin: 0;
        }

        .infos {
            margin-top: auto;
            display: flex;
            flex-direction: column;
            gap: 10px;
            padding-top: 16px;
            border-top: 1px solid #f0f0f0;
        }

        .infos p {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
            font-size: 0.88rem;
            line-height: 1.5;
            margin: 0;
        }

        .infos i {
            color: #667eea;
            font-size: 1rem;
            min-width: 20px;
            text-align: center;
        }

        .places-info {
            font-weight: 600;
        }

        .footer {
            padding: 20px 24px;
            background: linear-gradient(180deg, transparent 0%, #fafbff 100%);
            border-top: 1px solid #f0f3f8;
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .participation-status {
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
            color: #fff;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .participation-status::before {
            content: '✓';
            font-size: 1rem;
            font-weight: bold;
        }

        .participate-btn, .requested {
            padding: 12px 24px;
            border: none;
            border-radius: 12px;
            font-weight: 700;
            cursor: pointer;
            font-size: 0.9rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .participate-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .participate-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s ease;
        }

        .participate-btn:hover::before {
            left: 100%;
        }

        .participate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .participate-btn:active {
            transform: translateY(0);
        }

        .requested {
            background: #adb5bd;
            color: #fff;
            cursor: not-allowed;
        }

        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); animation: fadeIn 0.3s; }
        @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }
        .modal-content { background: #fff; margin: 3% auto; padding: 0; border-radius: 15px; width: 90%; max-width: 600px; box-shadow: 0 10px 40px rgba(0,0,0,0.2); animation: slideDown 0.3s; max-height: 90vh; overflow-y: auto; }
        @keyframes slideDown { from {transform: translateY(-50px); opacity:0;} to {transform: translateY(0); opacity:1;} }
        .modal-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px 25px; border-radius: 15px 15px 0 0; display: flex; justify-content: space-between; align-items: center; }
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
        .modal-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 20px 25px; background: #f8f9fa; border-radius: 0 0 15px 15px; }
        .btn-cancel, .btn-submit, .btn-annuler { padding: 10px 25px; border: none; border-radius: 8px; font-weight: bold; cursor: pointer; font-size: 0.95rem; transition: 0.3s; }
        .btn-cancel { background: #6c757d; color: #fff; }
        .btn-cancel:hover { background: #5a6268; }
        .btn-submit { background: #1f3c88; color: #fff; }
        .btn-submit:hover { background: #15306b; }
        .btn-annuler { background: #dc3545; color: #fff; }
        .btn-annuler:hover { background: #c82333; }

        /* Alerts */
        .alert {
            padding: 15px 20px;
            margin: 20px 60px;
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

        /* Responsive */
        @media (max-width: 768px) {
            .cards { padding: 20px 15px; grid-template-columns: 1fr; }
            .modal-content { width: 95%; margin: 10% auto; }
            .modal-info-row { grid-template-columns: 1fr; }
            .card-header h3 {
                font-size: 1rem;
                padding: 10px 14px;
            }
            .status {
                font-size: 0.7rem;
                padding: 6px 12px;
            }
        }
        .img{ width: 80px; }

        /* Filter Bar */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            padding: 20px 60px;
            background: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
            margin-top: 10px;
            border-radius: 12px;
        }

        .filter-bar input,
        .filter-bar select {
            padding: 10px 15px;
            font-size: 0.95rem;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.3s;
            flex: 1;
            min-width: 200px;
        }

        .filter-bar input:focus,
        .filter-bar select:focus {
            border-color: #1f3c88;
        }
    </style>
</head>
<body>

<header class="header">
    <div class="header-left">
        <div class="logo"><img class="img" src="../Circle_BLACK_Logo-removebg-preview.png" alt="logo"></div>
        <div class="header-info">
            <h2>Portail Étudiant</h2>
            <p>ENSA Tétouan - École Nationale des Sciences Appliquées</p>
        </div>
    </div>
    <div class="header-right">
        <span><?= htmlspecialchars($participant['nom'] . ' ' . $participant['prenom']) ?></span>
        <a href="../auth/logout.php"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
    </div>
</header>

<nav class="nav">
    <a href="dashboard.php"><button class="active">Tous les événements</button></a>
    <a href="mes_inscriptions.php"><button>Mes inscriptions</button></a>
    <a href="mes_certificats.php"><button>Mes certificats</button></a>
    <a href="profile.php"><button>Mon profil</button></a>
</nav>

<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Votre demande de participation a été envoyée avec succès !</div>
<?php endif; ?>

<?php if (isset($_GET['cancelled'])): ?>
    <div class="alert alert-success">Votre demande de participation a été annulée avec succès.</div>
<?php endif; ?>

<?php if (isset($_GET['error'])): ?>
    <div class="alert alert-error">
        <?php
        switch ($_GET['error']) {
            case 'already_requested':
                echo 'Vous avez déjà demandé à participer à cet événement.';
                break;
            case 'insert_failed':
                echo 'Erreur lors de l\'envoi de votre demande. Veuillez réessayer.';
                break;
            case 'cancel_failed':
                echo 'Erreur lors de l\'annulation. Veuillez réessayer.';
                break;
            case 'database_error':
                echo 'Erreur de base de données. Veuillez réessayer plus tard.';
                break;
            default:
                echo 'Une erreur est survenue.';
        }
        ?>
    </div>
<?php endif; ?>

<!-- Filter Bar -->
<div class="filter-bar">
    <input type="text" id="searchInput" placeholder="Rechercher un événement...">
    <select id="statusFilter">
        <option value="">Tous les statuts</option>
        <option value="Disponible">Disponible</option>
        <option value="terminé">Terminé</option>
        <option value="sold out">sold out</option>
    </select>
    <select id="categoryFilter">
        <option value="">Toutes les catégories</option>
        <option value="Conférence">Conférence</option>
        <option value="Formation">Formation</option>
        <option value="Sortie pédagogique">Sortie pédagogique</option>
        <option value="Sortie">Sortie</option>
        <option value="Séminaire">Séminaire</option>
        <option value="Hackathon">Hackathon</option>
        <option value="Table ronde/ Débat">Table ronde/ Débat</option>
        <option value="Compétition">Compétition</option>
        <option value="Sportif">Sportif</option>
        <option value="Autre">Autre</option>
    </select>
</div>

<div class="cards">
    <?php
    $renderedIds = [];
    foreach ($events as $event):
        // Check for duplicate rendering
        if (in_array($event['idEvent'], $renderedIds)) {
            continue; // Skip duplicates
        }
        $renderedIds[] = $event['idEvent'];

        $alreadyRequested = in_array($event['idEvent'], $participations);
        $event['registeredCount'] = $registrationCounts[$event['idEvent']] ?? 0;
        ?>
        <div class="card" data-category="<?= htmlspecialchars($event['categorie'] ?? 'Non spécifiée') ?>" data-event-id="<?= $event['idEvent'] ?>">
            <?php if (!empty($event['image'])): ?>
                <div class="card-image">
                    <img src="../<?= htmlspecialchars($event['image']) ?>" alt="<?= htmlspecialchars($event['nomEvent']) ?>" loading="lazy">
                </div>
            <?php else: ?>
                <div class="card-image no-image"></div>
            <?php endif; ?>

            <div class="card-header">
                <h3><?= htmlspecialchars($event['nomEvent']) ?></h3>
                <?php if ($event['status'] === 'Disponible'): ?>
                    <span class="status green">Disponible</span>
                <?php elseif ($event['status'] === 'terminé'): ?>
                    <span class="status gray">Terminé</span>
                <?php else: ?>
                    <span class="status orange"><?= htmlspecialchars($event['status']) ?></span>
                <?php endif; ?>
            </div>

            <div class="card-content">
                <span class="category"><?= htmlspecialchars($event['categorie'] ?? 'Non spécifiée') ?></span>
                <p><?= htmlspecialchars($event['descriptionEvenement']) ?></p>

                <div class="infos">
                    <p><i class="fa-regular fa-calendar"></i>
                        <?php if ($event['dateDepart'] === $event['dateFin']): ?>
                            <?= htmlspecialchars($event['dateDepart']) ?>
                            <strong><?= htmlspecialchars($event['heureDepart']) ?></strong> → <strong><?= htmlspecialchars($event['heureFin']) ?></strong>
                        <?php else: ?>
                            <?= htmlspecialchars($event['dateDepart']) ?>
                            <strong><?= htmlspecialchars($event['heureDepart']) ?></strong> → <?= htmlspecialchars($event['dateFin']) ?>
                            <strong><?= htmlspecialchars($event['heureFin']) ?></strong>
                        <?php endif; ?>
                    </p>
                    <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($event['lieu']) ?></p>
                    <p><i class="fa-solid fa-users"></i>
                        <?php
                        if ($event['places'] === null):
                            ?>
                            <span class="places-info" style="color: #28a745; font-weight: bold;">
                                Nombre de places non définit
                            </span>
                        <?php else:
                            $registered = $registrationCounts[$event['idEvent']] ?? 0;
                            $available = max(0, $event['places'] - $registered);
                            $isFull = $available <= 0;
                            ?>
                            <span class="places-info">
                                <?php if ($isFull): ?>
                                    <span style="color: #dc3545; font-weight: bold;">Complet</span>
                                    (<?= $event['places'] ?>/<?= $event['places'] ?>)
                                <?php else: ?>
                                    <?= $available ?> disponibles / <?= $event['places'] ?> total
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <div class="footer">
                <div class="footer-content">
                    <?php if ($alreadyRequested): ?>
                        <span class="participation-status">Participation demandée</span>
                    <?php endif; ?>
                    <button type="button" class="participate-btn" onclick="openModal(<?= htmlspecialchars(json_encode($event)) ?>, <?= $alreadyRequested ? 'true' : 'false' ?>)">Voir détails</button>
                </div>
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
                <!-- User Information Section -->
                <div class="modal-section">
                    <h3>Vos Informations</h3>
                    <div class="modal-info-row">
                        <div class="modal-info-item">
                            <strong>Nom Complet</strong>
                            <p id="modalUserName"><?= $userInfo['nom'] . ' ' . $userInfo['prenom'] ?></p>
                        </div>
                        <div class="modal-info-item">
                            <strong>Email</strong>
                            <p id="modalUserEmail"><?= $userInfo['email'] ?></p>
                        </div>
                        <div class="modal-info-item">
                            <strong>Nom utilisateur</strong>
                            <p id="modalUserMatricule"><?= $userInfo['matricule'] ?></p>
                        </div>
                        <div class="modal-info-item">
                            <strong>Filière</strong>
                            <p id="modalUserFiliere"><?= $userInfo['filiere'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- Event Information Section -->
                <div class="modal-section">
                    <h3>Catégorie</h3>
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
                    <div class="modal-info-item">
                        <strong>Capacité</strong>
                        <p id="modalCapacityInfo">
                            <span id="modalCapacity"></span> places disponibles sur <span id="modalMaxPlaces"></span> total
                        </p>
                    </div>
                </div>
                <div class="modal-section participation-form" style="display: none;">
                    <h3>S'inscrire à cet événement</h3>
                    <input type="hidden" name="event_id" id="modalEventId">
                    <input type="hidden" name="submit_participation" value="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn-cancel" onclick="closeModal()">Fermer</button>
                <button type="button" class="btn-annuler" onclick="cancelParticipation()" style="display: none;">Annuler la demande</button>
                <button type="submit" name="participer" class="btn-submit" id="submitBtn" style="display: none;">Demander participation</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('eventModal');
        const submitBtn = document.getElementById('submitBtn');

        if (!modal || !submitBtn) {
            console.error('Modal elements not found!');
            return;
        }

        window.openModal = function(event, alreadyRequested) {
            console.log('openModal called with:', { event, alreadyRequested });

            const modalTitle = document.getElementById('modalTitle');
            const modalClub = document.getElementById('modalClub');
            const modalDescription = document.getElementById('modalDescription');
            const modalPeriod = document.getElementById('modalPeriod');
            const modalLieu = document.getElementById('modalLieu');
            const modalEventId = document.getElementById('modalEventId');
            const modalCapacityInfo = document.getElementById('modalCapacityInfo');
            const modalUserName = document.getElementById('modalUserName');
            const modalUserEmail = document.getElementById('modalUserEmail');
            const modalUserMatricule = document.getElementById('modalUserMatricule');
            const modalUserFiliere = document.getElementById('modalUserFiliere');

            if (!modalTitle || !modalClub || !modalDescription || !modalPeriod || !modalLieu ||
                !modalEventId || !modalCapacityInfo || !modalUserName || !modalUserEmail ||
                !modalUserMatricule || !modalUserFiliere) {
                console.error('Modal form elements not found!');
                return;
            }

            const participationForm = document.getElementById('participationForm');
            if (participationForm) {
                participationForm.reset();
            }

            modalTitle.textContent = event.nomEvent;
            modalClub.textContent = event.categorie ? event.categorie : 'Non spécifiée';
            modalDescription.textContent = event.descriptionEvenement;
            
            // Format date display for modal
            if (event.dateDepart === event.dateFin) {
                modalPeriod.innerHTML = `${event.dateDepart}<br>${event.heureDepart} → ${event.heureFin}`;
            } else {
                modalPeriod.innerHTML = `${event.dateDepart} au ${event.dateFin}<br>${event.heureDepart} → ${event.heureFin}`;
            }
            
            modalLieu.textContent = event.lieu;
            modalEventId.value = event.idEvent;

            modalUserName.textContent = '<?= $userInfo['nom'] . ' ' . $userInfo['prenom'] ?>';
            modalUserEmail.textContent = '<?= $userInfo['email'] ?>';
            modalUserMatricule.textContent = '<?= $userInfo['matricule'] ?>';
            modalUserFiliere.textContent = '<?= $userInfo['filiere'] ?>';

            const registeredCount = event.registeredCount || 0;
            const maxPlaces = event.places;

            if (maxPlaces === null || maxPlaces === undefined || maxPlaces === '') {
                modalCapacityInfo.innerHTML = `<span style="color: #28a745; font-weight: bold;">Nombre de places non définit</span><br>(${registeredCount} participant${registeredCount > 1 ? 's' : ''} inscrit${registeredCount > 1 ? 's' : ''})`;
            } else {
                const availablePlaces = Math.max(0, maxPlaces - registeredCount);
                if (availablePlaces <= 0) {
                    modalCapacityInfo.innerHTML = `<span style="color: #dc3545; font-weight: bold;">Événement complet</span><br>(${maxPlaces}/${maxPlaces} places occupées)`;
                } else {
                    modalCapacityInfo.innerHTML = `${availablePlaces} places disponibles sur ${maxPlaces} total<br>(${registeredCount} places déjà occupées)`;
                }
            }

            const participationFormSection = document.querySelector('.participation-form');
            const cancelBtn = document.querySelector('.btn-annuler');

            if (!participationFormSection || !cancelBtn) {
                console.error('Participation form elements not found!');
                return;
            }

            const hasAlreadyRequested = alreadyRequested === true || alreadyRequested === 1 || alreadyRequested === "true";

            if (hasAlreadyRequested) {
                participationFormSection.style.display = 'none';
                submitBtn.style.display = 'none';
                cancelBtn.style.display = 'inline-block';
            } else {
                // Check if event is full (only if maxPlaces is defined and not null/unlimited)
                const isEventFull = (maxPlaces !== null && maxPlaces !== undefined && maxPlaces !== '') && (registeredCount >= maxPlaces);

                if (isEventFull) {
                    participationFormSection.style.display = 'none';
                    submitBtn.style.display = 'none';
                    cancelBtn.style.display = 'none';

                    const fullMessage = document.createElement('div');
                    fullMessage.className = 'alert alert-error';
                    fullMessage.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Cet événement est complet. Vous ne pouvez plus vous inscrire.';
                    participationFormSection.parentNode.insertBefore(fullMessage, participationFormSection);
                } else {
                    participationFormSection.style.display = 'block';
                    submitBtn.style.display = 'inline-block';
                    cancelBtn.style.display = 'none';
                }
            }

            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        };

        window.closeModal = function() {
            console.log('closeModal called');
            if (modal) {
                modal.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        };

        window.onclick = function(event) {
            if (event.target == modal) {
                console.log('Modal clicked outside, closing');
                window.closeModal();
            }
        };

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape' && modal && modal.style.display === 'block') {
                console.log('Escape key pressed, closing modal');
                window.closeModal();
            }
        });

        window.cancelParticipation = function() {
            const eventId = document.getElementById('modalEventId');
            if (!eventId) {
                console.error('Modal event ID element not found!');
                return;
            }

            if (confirm('Êtes-vous sûr de vouloir annuler votre demande de participation ?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'mes_demandes.php';

                const eventInput = document.createElement('input');
                eventInput.type = 'hidden';
                eventInput.name = 'event_id';
                eventInput.value = eventId.value;

                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'annuler';
                actionInput.value = '1';

                form.appendChild(eventInput);
                form.appendChild(actionInput);
                document.body.appendChild(form);
                form.submit();
            }
        };

        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const categoryFilter = document.getElementById('categoryFilter');
        const cardsContainer = document.querySelector('.cards');

        // Only select divs with class 'card'
        const cards = cardsContainer ? Array.from(cardsContainer.querySelectorAll('.card')) : [];

        if (searchInput && statusFilter && categoryFilter && cards.length > 0) {
            const filterEvents = function() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const selectedStatus = statusFilter.value.toLowerCase();
                let selectedCategory = categoryFilter.value.toLowerCase();

                if (selectedCategory === 'non spécifiée') {
                    selectedCategory = '';
                }

                cards.forEach(card => {
                    const title = card.querySelector('.card-header h3')?.textContent.toLowerCase() || '';
                    const statusEl = card.querySelector('.status');
                    const status = statusEl ? statusEl.textContent.toLowerCase() : '';
                    const categoryEl = card.querySelector('.category');
                    const category = categoryEl ? categoryEl.textContent.toLowerCase() : '';

                    const matchesSearch = !searchTerm || title.includes(searchTerm);
                    const matchesStatus = !selectedStatus || status.includes(selectedStatus);
                    const matchesCategory = !selectedCategory || category === selectedCategory;

                    if (matchesSearch && matchesStatus && matchesCategory) {
                        card.style.display = 'flex';
                    } else {
                        card.style.display = 'none';
                    }
                });
            };

            searchInput.addEventListener('input', filterEvents);
            statusFilter.addEventListener('change', filterEvents);
            categoryFilter.addEventListener('change', filterEvents);
            filterEvents();
        }
    });
</script>
</body>
</html>