<?php
require_once '../connexion.php';




//session_start();
//require_once 'connexion.php';

// Optional: You can add authentication here (e.g., check if user is logged in)

// Fetch all events
$events = $conn->query("SELECT * FROM evenements")->fetchAll(PDO::FETCH_ASSOC);

// Fetch participant info
$participant = $conn->query("SELECT * FROM etudiants where id=$user_id")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Portail Étudiant</title>
    <link rel="stylesheet" href="dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>

<!-- Header -->
<header class="header">
    <div class="logo">
        <i class="fa-solid fa-graduation-cap"></i>
        <div>
            <h2>Portail Étudiant</h2>
            <p>ENSA Tétouan - École Nationale des Sciences Appliquées</p>
        </div>
    </div>
    <div class="user">
        <span>Bonjour, <strong>Abderrahim Sadiki</strong></span>
        <span>3ème année • Génie Informatique</span>
        <a href="logout.php" class="logout"><i class="fa-solid fa-right-from-bracket"></i> Déconnexion</a>
    </div>
</header>

<!-- Navigation -->
<nav class="nav">
    <button class="active">Tous les événements</button>
    <button>Mes inscriptions</button>
    <button>Mes certificats</button>
    <button>Mon profil</button>
</nav>

<!-- Filters -->
<div class="filters">
    <div class="search">
        <input type="text" placeholder="Nom d'événement ou club...">
    </div>
    <select>
        <option>Toutes les catégories</option>
        <option>Hackathon</option>
        <option>Atelier</option>
        <option>Conférence</option>
    </select>
    <select>
        <option>Tous les événements</option>
        <option>Terminé</option>
        <option>En cours</option>
        <option>À venir</option>
    </select>
</div>

<!-- Event Cards -->
<div class="cards">
    <?php foreach ($events as $event): ?>
        <div class="card">
            <!-- En-tête -->
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

            <!-- Catégorie -->
            <p class="club"><?= htmlspecialchars($event['categorie']) ?></p>

            <!-- Description -->
            <p class="description"><?= htmlspecialchars($event['description']) ?></p>

            <!-- Infos principales -->
            <div class="infos">
                <p>
                    <i class="fa-regular fa-calendar"></i>
                    <?= htmlspecialchars($event['dateDepart']) ?>
                    <strong><?= htmlspecialchars($event['heureDepart']) ?></strong>
                    &nbsp;→&nbsp;
                    <?= htmlspecialchars($event['dateFin']) ?>
                    <strong><?= htmlspecialchars($event['heureFin']) ?></strong>
                </p>
                <p><i class="fa-solid fa-location-dot"></i> <?= htmlspecialchars($event['lieu']) ?></p>
                <p><i class="fa-solid fa-users"></i> <?= htmlspecialchars($event['places']) ?> places</p>
                <?php if (!empty($event['date_validation'])): ?>
                    <p><i class="fa-regular fa-circle-check"></i> Validé le <?= htmlspecialchars($event['date_validation']) ?></p>
                <?php endif; ?>
            </div>

            <!-- Pied de carte -->
            <div class="footer">
                <span class="category"><?= htmlspecialchars($event['categorie']) ?></span>
                <div class="actions">
                    <a href="details.php?id=<?= $event['id'] ?>" class="details-btn">Voir détails</a>
                    <a href="edit_event.php?id=<?= $event['id'] ?>" class="edit-btn">Éditer</a>
                    <a href="delete_event.php?id=<?= $event['id'] ?>" class="delete-btn" onclick="return confirm('Supprimer cet événement ?');">Supprimer</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

</body>
</html>


