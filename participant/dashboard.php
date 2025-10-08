<?php
session_start();
require_once '../includes/db.php';

// Check if participant is logged in
if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

// Fetch participant info
$stmt = $conn->prepare("SELECT * FROM etudiants WHERE id = ?");
$stmt->execute([$_SESSION['id']]);
$participant = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch all events
$events = $conn->query("SELECT * FROM evenements ORDER BY dateDepart ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch participant's previous participations
$participations_stmt = $conn->prepare("SELECT evenement_id FROM participation WHERE etudiant_id = ?");
$participations_stmt->execute([$_SESSION['id']]);
$participations = $participations_stmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portail Étudiant</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Reset & base */
        * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
        body { background: #f0f4ff; color: #333; }

        /* Header */
        .header {
            display: flex; justify-content: space-between; align-items: center;
            background: #004aad; color: #fff; padding: 20px 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            border-bottom-left-radius: 15px; border-bottom-right-radius: 15px;
        }
        .header h2 { margin-bottom: 5px; font-size: 1.8rem; }
        .header p { font-size: 0.9rem; color: #e0e0e0; }
        .header a { color: #fff; text-decoration: none; font-weight: bold; transition: 0.3s; }
        .header a:hover { color: #ffd700; }

        /* Navigation */
        .nav {
            display: flex; justify-content: center; gap: 15px; background: #eef4ff;
            padding: 12px 0; margin-top: 10px; border-radius: 10px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .nav button {
            background: transparent; border: none; padding: 10px 20px;
            font-size: 0.95rem; cursor: pointer; border-radius: 8px;
            transition: 0.3s; color: #004aad; font-weight: bold;
        }
        .nav button.active, .nav button:hover { background: #004aad; color: #fff; }

        /* Cards layout */
        .cards {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px; padding: 25px 50px;
        }

        /* Card style */
        .card {
            background: #fff; border-radius: 15px; padding: 20px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.08); transition: transform 0.2s;
        }
        .card:hover { transform: translateY(-5px); }

        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; }
        .card-header h3 { font-size: 1.3rem; color: #004aad; }
        .status.green { color: #28a745; font-weight: bold; }
        .status.orange { color: #fd7e14; font-weight: bold; }
        .status.gray { color: #6c757d; font-weight: bold; }

        .card p { margin: 6px 0; line-height: 1.4; }
        .infos p { font-size: 0.9rem; color: #555; }
        .infos i { margin-right: 5px; color: #004aad; }

        /* Footer */
        .footer { display: flex; justify-content: flex-end; margin-top: 15px; }
        .participate-btn, .requested {
            padding: 7px 14px; border: none; border-radius: 8px;
            font-weight: bold; cursor: pointer; font-size: 0.9rem;
            transition: 0.3s;
        }
        .participate-btn { background: #004aad; color: #fff; }
        .participate-btn:hover { background: #00308f; }
        .requested { background: #6c757d; color: #fff; cursor: not-allowed; }

        /* Responsive */
        @media (max-width: 768px) {
            .cards { padding: 20px 15px; grid-template-columns: 1fr; }
            .header { flex-direction: column; align-items: flex-start; gap: 10px; }
        }
    </style>
</head>
<body>

<header class="header">
    <div>
        <h2>Portail Étudiant</h2>
        <p><?= htmlspecialchars($participant['nom'] . ' ' . $participant['prenom']) ?> • <?= htmlspecialchars($participant['annee'] . ' année • ' . $participant['filiere']) ?></p>
    </div>
    <a href="../logout.php"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
</header>

<nav class="nav">
    <button class="active">Tous les événements</button>
    <button>Mes inscriptions</button>
    <button>Mes certificats</button>
    <button>Mon profil</button>
</nav>

<div class="cards">
    <?php foreach ($events as $event): ?>
        <?php $alreadyRequested = in_array($event['id'], $participations); ?>
        <div class="card">
            <div class="card-header">
                <h3><?= htmlspecialchars($event['nom']) ?></h3>
                <?php if ($event['status'] === 'en cours de traitement'): ?>
                    <span class="status orange">En cours</span>
                <?php elseif ($event['status'] === 'terminé'): ?>
                    <span class="status gray">Terminé</span>
                <?php else: ?>
                    <span class="status green"><?= htmlspecialchars($event['status']) ?></span>
                <?php endif; ?>
            </div>

            <p><strong>Catégorie:</strong> <?= htmlspecialchars($event['categorie']) ?></p>
            <p><?= htmlspecialchars($event['description']) ?></p>

            <div class="infos">
                <p><i class="fa-regular fa-calendar"></i>
                    <?= htmlspecialchars($event['dateDepart']) ?> <strong><?= htmlspecialchars($event['heureDepart']) ?></strong>
                    → <?= htmlspecialchars($event['dateFin']) ?> <strong><?= htmlspecialchars($event['heureFin']) ?></strong>
                </p>
                <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($event['lieu']) ?></p>
                <p><i class="fa-solid fa-users"></i> <?= htmlspecialchars($event['places']) ?> places</p>
            </div>

            <div class="footer">
                <?php if ($alreadyRequested): ?>
                    <button class="requested" disabled>Participation demandée</button>
                <?php else: ?>
                    <form method="POST" action="request_participation.php">
                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                        <button type="submit" class="participate-btn">Demander participation</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>
