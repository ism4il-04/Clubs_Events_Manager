<?php

require_once "db.php";
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<?php
function fetchInformations ($conn) {
    $stmt = $conn->prepare('SELECT o.*, u.email, u.nom_utilisateur FROM organisateur o JOIN utilisateurs u ON o.id = u.id WHERE o.id = ?');
    $stmt->execute(array($_SESSION['id']));
    return $stmt->fetchAll();
}

$informations = fetchInformations($conn);
$club = !empty($informations) ? $informations[0] : null;

?>
<style>
    * { box-sizing: border-box; font-family: 'Inter', sans-serif; margin: 0; padding: 0; }
    body { background: #f5f7ff; color: #333; }

    /* Header */
    .header { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px 40px; border-radius: 0 0 15px 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .header-left { display: flex; align-items: center; gap: 15px; }
    .logo { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #1f3c88; }
    .header-info h2 { font-size: 1.6rem; font-weight: 600; margin-bottom: 3px; }
    .header-info p { font-size: 0.85rem; color: #ffffff; font-weight: 400; }
    .header-right { display: flex; align-items: center; gap: 20px; }
    .header-right a { color: #fff; text-decoration: none; font-weight: 500; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
    .header-right a:hover { color: #ffd700; }
    
    .club-info {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(255, 255, 255, 0.1);
        padding: 8px 15px;
        border-radius: 25px;
        backdrop-filter: blur(10px);
    }
    
    .club-logo-header {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.3);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .club-name {
        font-weight: 600;
        font-size: 0.9rem;
        color: #fff;
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    .img{
        width: 80px;
    }
</style>
<header class="header">
    <div class="header-left">
        <div class="logo"><img class="img" src="../Circle_BLACK_Logo-removebg-preview.png" alt="logo"></div>
        <div class="header-info">
            <h2>Portail Club</h2>
            <p>ENSA Tétouan - École Nationale des Sciences Appliquées</p>
        </div>
    </div>
    <div class="header-right">
        <a href="profile_club.php">
            <i class="fa-solid fa-user-circle"></i> Mon profil
        </a>
        <div class="club-info">
            <?php if (!empty($club['logo'])): ?>
                <img src="../<?= htmlspecialchars($club['logo']) ?>" 
                     class="club-logo-header" 
                     alt="Logo du club"
                     onerror="this.style.display='none'">
            <?php endif; ?>
            <span class="club-name"><?= htmlspecialchars($club['clubNom'] ?? $club['nom_utilisateur'] ?? 'Utilisateur') ?></span>
        </div>
        <a href="../auth/logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
    </div>
</header>

