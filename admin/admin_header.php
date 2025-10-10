<?php
// admin_header.php - Header commun pour toutes les pages admin
?>
<header>
    <div class="header-container">
        <div class="header-left">
            <div class="logo-box">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3L2 9l10 6 10-6-10-6z" />
                </svg>
            </div>
            <div>
                <h1>Portail Administration</h1>
                <p>ENSA Tétouan - École Nationale des Sciences Appliquées</p>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <p>Bonjour, <?php echo htmlspecialchars($_SESSION['email']); ?></p>
                <p>Administrateur</p>
            </div>
            <form action="../logout.php" method="post">
                <button type="submit" class="logout-button">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5" />
                    </svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </div>
</header>



<script>
function navigateTo(page) {
    window.location.href = page;
}
</script>