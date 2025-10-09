<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header('Location: login.php');
    exit;
}

$profileStmt = $conn->prepare("SELECT * FROM etudiants natural join utilisateurs WHERE id = ?");
$profileStmt->execute([$_SESSION['id']]);
$profile = $profileStmt->fetch(PDO::FETCH_ASSOC);

$participant_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT * FROM etudiants WHERE id = ?");
$stmt->execute([$participant_id]);
$participant = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mon profil - Portail Étudiant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* ---- Your existing styles (header, nav, etc.) ---- */
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
            background: #004aad;
            color: #fff;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header-left { display: flex; align-items: center; gap: 15px; }
        .logo {
            width: 50px; height: 50px; background: white; border-radius: 12px;
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
        .nav button.active { color: #004aad; border-bottom-color: #004aad; background: #f8fbff; }
        .nav button:hover { background: #f8fbff; color: #004aad; }

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

        /* ---- Profile Form ---- */
        .profile-form {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 6px;
            color: #374151;
        }
        .form-group input,
        .form-group select {
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 0.95rem;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #004aad;
            box-shadow: 0 0 0 2px rgba(0,74,173,0.15);
        }

        .form-row {
            display: flex;
            gap: 20px;
        }
        .form-row .form-group {
            flex: 1;
        }

        .save-btn {
            background: #004aad;
            color: white;
            padding: 12px 18px;
            font-size: 1rem;
            font-weight: 500;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.3s ease;
            align-self: flex-start;
        }
        .save-btn:hover {
            background: #00308f;
        }

        @media (max-width: 600px) {
            .form-row { flex-direction: column; }
        }
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
        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
    </div>
</header>

<nav class="nav">
    <a href="dashboard.php"><button>Tous les événements</button></a>
    <a href="mes_inscriptions.php"><button>Mes inscriptions</button></a>
    <a href="#"><button>Mes certificats</button></a>
    <a href="profile.php"><button class="active">Mon profil</button></a>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Mes informations</h1>
        <p>Vous pouvez consulter vos informations personnelles ci-dessous</p>
    </div>

    <form method="POST" class="profile-form">
        <div class="form-row">
            <div class="form-group">
                <label for="nom">Nom</label>
                <input type="text" id="nom" name="nom" value="<?= htmlspecialchars($profile['nom']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="prenom">Prénom</label>
                <input type="text" id="prenom" name="prenom" value="<?= htmlspecialchars($profile['prenom']) ?>" readonly>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($profile['email']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="telephone">Téléphone</label>
                <input type="text" id="telephone" name="telephone" value="<?= htmlspecialchars($profile['telephone']) ?>" readonly>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="filiere">Filière</label>
                <input type="text" id="filiere" name="filiere" value="<?= htmlspecialchars($profile['filiere']) ?>" readonly>
            </div>
            <div class="form-group">
                <label for="annee">Année</label>
                <input type="text" id="annee" name="annee" value="<?= htmlspecialchars($profile['annee']) ?> annee" readonly>

            </div>
        </div>

<!--        <button type="submit" class="save-btn"><i class="fa fa-save"></i> Enregistrer les modifications</button>-->
    </form>
</div>

</body>
</html>
