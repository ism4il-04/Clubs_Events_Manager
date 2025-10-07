<?php

require_once "db.php";
function fetchInformations ($conn) {
    $stmt = $conn->prepare('SELECT * from utilisateurs NATURAL JOIN organisateur WHERE email = ?');
    $stmt->execute(array($_SESSION['email']));
    return $stmt->fetchAll();
}
$club = fetchInformations($conn)[0];

?>
<header>
    <div class="header-container">
        <div class="header-left">
            <div class="logo-box">
                <!-- School icon (example SVG) -->
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3L2 9l10 6 10-6-10-6z" />
                </svg>
            </div>
            <div>
                <h1>Portail Club</h1>
                <p><?= htmlspecialchars($club["nom_abr"]) ?></p>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <p>Bonjour, <?= htmlspecialchars($club["clubNom"]); ?></p>
                <p>Club</p>
            </div>
            <form action="../logout.php" method="post">
                <button type="submit" class="logout-button">
                    <!-- Logout icon (example SVG) -->
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1m0-10V5" />
                    </svg>
                    Déconnexion
                </button>
            </form>
        </div>
    </div>
</header>
