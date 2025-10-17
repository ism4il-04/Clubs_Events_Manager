<?php
session_start();
if(!isset($_SESSION["email"])){
    header("Location: ../login.php");
    exit;
}

include "../includes/db.php";
$participant_id = $_SESSION['id'];
$stmt = $conn->prepare("SELECT * FROM etudiants WHERE id = ?");
$stmt->execute([$participant_id]);
$participant = $stmt->fetch(PDO::FETCH_ASSOC);

$attestation = $conn->prepare("SELECT * FROM participation p JOIN evenements e on p.evenement_id=e.idEvent  WHERE p.etudiant_id = ? AND p.attestation is NOT NULL");
$attestation->execute(array($participant_id));
$attestation = $attestation->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Mes inscriptions - Portail Étudiant</title>
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Reset & base */
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

        /* Header */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            padding: 20px 40px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logo {
            width: 50px;
            height: 50px;
            /*background: white;*/
            /*border-radius: 12px;*/
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #004aad;
        }

        .header-info h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 3px;
        }

        .header-info p {
            font-size: 0.85rem;
            color: #c5d9f5;
            font-weight: 400;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .header-right a {
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            transition: 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .header-right a:hover {
            color: #ffd700;
        }

        /* Navigation */
        .nav {
            display: flex;
            justify-content: center;
            gap: 0;
            background: #fff;
            padding: 0;
            margin: 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .nav a {
            text-decoration: none;
            flex: 1;
            max-width: 250px;
        }

        .nav button {
            background: transparent;
            border: none;
            padding: 18px 30px;
            width: 100%;
            font-size: 0.95rem;
            cursor: pointer;
            transition: 0.3s;
            color: #666;
            font-weight: 500;
            border-bottom: 3px solid transparent;
            position: relative;
        }

        .nav button.active { color: #1f3c88; border-bottom-color: #1f3c88; background: #f0f3ff; }
        .nav button:hover { background: #f0f3ff; color: #1f3c88; }

        /* Main container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 30px;
        }

        /* Page header */
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

        /* Event cards */
        .events-list {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            padding: 24px 28px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e5e7eb;
        }

        .event-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.12);
            transform: translateY(-2px);
        }

        .event-card-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 18px;
        }

        .event-title-section h3 {
            font-size: 1.25rem;
            color: #1a1a1a;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .event-club {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .status-badge {
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            text-transform: capitalize;
        }

        .status-badge.inscrit {
            background: #dbeafe;
            color: #1e40af;
        }

        .status-badge.valide {
            background: #dcfce7;
            color: #166534;
        }

        .status-badge.en-attente {
            background: #fef3c7;
            color: #92400e;
        }

        .status-badge.refuse {
            background: #fee2e2;
            color: #991b1b;
        }

        .event-details {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
            margin-bottom: 0;
        }

        .event-detail-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #4b5563;
            font-size: 0.9rem;
        }

        .event-detail-item i {
            color: #6b7280;
            width: 18px;
        }

        .event-detail-item strong {
            color: #1a1a1a;
            font-weight: 500;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }

        .empty-state i {
            font-size: 64px;
            color: #d1d5db;
            margin-bottom: 20px;
        }

        .empty-state h3 {
            font-size: 1.3rem;
            color: #4b5563;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .empty-state p {
            color: #6b7280;
            font-size: 0.95rem;
            margin-bottom: 25px;
        }

        .empty-state a {
            display: inline-block;
            padding: 12px 24px;
            background: #004aad;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: 0.3s;
        }

        .empty-state a:hover {
            background: #00308f;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
                padding: 20px;
            }

            .header-left {
                width: 100%;
            }

            .header-right {
                width: 100%;
                justify-content: flex-end;
            }

            .nav {
                flex-direction: column;
            }

            .nav a {
                max-width: 100%;
            }

            .nav button {
                text-align: left;
                border-bottom: 1px solid #e5e7eb;
                border-left: 3px solid transparent;
            }

            .nav button.active {
                border-bottom-color: #e5e7eb;
                border-left-color: #004aad;
            }

            .container {
                padding: 25px 15px;
            }

            .event-card {
                padding: 20px;
            }

            .event-card-header {
                flex-direction: column;
                gap: 12px;
            }

            .event-details {
                flex-direction: column;
                gap: 12px;
            }
        }
        .img{
            width: 80px;
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
        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
    </div>
</header>

<nav class="nav">
    <a href="dashboard.php"><button>Tous les événements</button></a>
    <a href="mes_inscriptions.php"><button>Mes inscriptions</button></a>
    <a href="mes_certificats.php"><button class="active">Mes certificats</button></a>
    <a href="profile.php"><button>Mon profil</button></a>
</nav>

<div class="container">
    <div class="page-header">
        <h1>Mes certificats</h1>
        <p>Vos certificats</p>
    </div>

    <?php if (count($attestation) > 0): ?>
        <div class="events-list">
            <?php foreach ($attestation as $att): ?>
                <div class="event-card">
                    <div class="event-card-header">
                        <div class="event-title-section">
                            <h3><?= htmlspecialchars($att['nomEvent']) ?></h3>
                            <p class="event-club"><?= htmlspecialchars($att['categorie']) ?></p>
                        </div>
                        <span class="status-badge">
                            <a href="download_certificate.php?id=<?= urlencode($att['attestation']) ?>" style="color: inherit; text-decoration: none;">
                                <i class="fa-solid fa-download"></i> Télécharger
                            </a>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fa-regular fa-calendar-xmark"></i>
            <h3>Aucun certificat</h3>
            <p>Vous n'avez aucun certificat pour le moment.</p>
            <a href="dashboard.php">
                <i class="fa-solid fa-magnifying-glass"></i> Découvrir les événements
            </a>
        </div>
    <?php endif; ?>
</div>

</body>
</html>