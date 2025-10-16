<?php
// admin_header.php - Header commun pour toutes les pages admin
?>
<style>
    .header { display: flex; justify-content: space-between; align-items: center; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 20px 40px; border-radius: 0 0 15px 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.08); }
    .header-left { display: flex; align-items: center; gap: 15px; }
    .logo { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #1f3c88; }
    .header-info h2 { font-size: 1.6rem; font-weight: 600; margin-bottom: 3px; }
    .header-info p { font-size: 0.85rem; color: #ffffff; font-weight: 400; }
    .header-right { display: flex; align-items: center; gap: 20px; }
    .header-right a { color: #fff; text-decoration: none; font-weight: 500; transition: 0.3s; display: flex; align-items: center; gap: 8px; }
    .header-right a:hover { color: #ffd700; }
    .img { width: 80px; }
</style>
<header class="header">
    <div class="header-left">
        <div class="logo"><img class="img" src="../Circle_BLACK_Logo-removebg-preview.png" alt="logo"></div>
        <div class="header-info">
            <h2>Portail Administrateur</h2>
            <p>ENSA Tétouan - École Nationale des Sciences Appliquées</p>
        </div>
    </div>
    <div class="header-right">
        <a href="admin_profile.php">
            <i class="fa-solid fa-user-shield"></i> Mon profil
        </a>
        <a href="../logout.php">
            <i class="fa-solid fa-right-from-bracket"></i>Déconnexion</a>
    </div>
</header>

<script>
function navigateTo(page) {
    window.location.href = page;
}
</script>