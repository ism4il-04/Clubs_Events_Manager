<?php

require_once "db.php";
function fetchInformations ($conn) {
    $stmt = $conn->prepare('SELECT * from utilisateurs NATURAL JOIN organisateur WHERE email = ?');
    $stmt->execute(array($_SESSION['email']));
    return $stmt->fetchAll();
}
$club = fetchInformations($conn)[0];

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
        <span><?= htmlspecialchars($club['clubNom']) ?></span>
        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i> Déconnexion
        </a>
    </div>
</header>

