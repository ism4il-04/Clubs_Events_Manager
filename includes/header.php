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
     *{
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    min-height: 100vh;
    background: linear-gradient(to bottom right, #f8fafc, #eff6ff, #e0e7ff);
    font-family: Arial, Helvetica, sans-serif;
    color: #1f2937;
}

header {
    background: rgba(255, 255, 255, 0.8);
    border-bottom: 1px solid rgba(229, 231, 235, 0.5);
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.header-container {
    max-width: 1120px;
    margin: 0 auto;
    padding: 0 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    height: 72px;
}

.header-left {
    display: flex;
    align-items: center;
}

.logo-box {
    width: 40px;
    height: 40px;
    background: linear-gradient(to bottom right, #2563eb, #4338ca);
    border-radius: 12px;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 1rem;
}

.logo-box svg {
    width: 24px;
    height: 24px;
    color: #fff;
}

.header-left h1 {
    font-size: 1.25rem;
    font-weight: 700;
    color: #111827;
}

.header-left p {
    font-size: 0.875rem;
    color: #4b5563;
}

.header-right {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-info {
    text-align: right;
}

.user-info p:first-child {
    font-size: 0.875rem;
    font-weight: 500;
    color: #111827;
}

.user-info p:last-child {
    font-size: 0.75rem;
    color: #6b7280;
}

.logout-button {
    display: flex;
    align-items: center;
    border: 1px solid #e5e7eb;
    background: transparent;
    padding: 0.4rem 0.75rem;
    border-radius: 12px;
    font-size: 0.875rem;
    cursor: pointer;
    transition: background 0.2s;
}

.logout-button:hover {
    background: #f9fafb;
}

.logout-button svg {
    width: 16px;
    height: 16px;
    margin-right: 0.4rem;
}
</style>
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
                <p>InfoTech</p>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <p>Bonjour, </p>
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
