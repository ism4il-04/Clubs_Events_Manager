<?php
session_start();
if (!isset($_SESSION['email'])) {
   header("Location: login.php");
   exit();
}
// Dummy data for demonstration
$stats = [
    'total' => 3,
    'pending' => 1,
    'approved' => 1,
    'rejected' => 1
];
$recent_events = [
    [
        'title' => 'Conférence IA et Robotique',
        'date' => '2024-11-15',
        'location' => 'Amphithéâtre A',
        'status' => 'En attente'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Club Dashboard</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    
</head>
<body>

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
                <p>Club Infotech</p>
            </div>
        </div>
        <div class="header-right">
            <div class="user-info">
                <p>Bonjour, <?php echo htmlspecialchars($_SESSION['email']); ?></p>
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

<div class="dashboard-container">
    <!-- Tabs -->
    <div class="tabs">
        <div class="tab active">Tableau de bord</div>
        <div class="tab">Mes événements</div>
        <div class="tab">Participants</div>
        <div class="tab">Communications</div>
        <div class="tab">Certificats</div>
    </div>

    <!-- Stats -->
    <div class="stats-row">
        <div class="stat-card">
            <div class="stat-title">
                Événements totaux
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/></svg>
            </div>
            <div class="stat-value"><?php echo $stats['total']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">
                En attente
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M12 6v6l4 2"/></svg>
            </div>
            <div class="stat-value pending"><?php echo $stats['pending']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">
                Approuvés
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            </div>
            <div class="stat-value approved"><?php echo $stats['approved']; ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-title">
                Rejeté
                <svg xmlns="http://www.w3.org/2000/svg" style="width:18px;height:18px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </div>
            <div class="stat-value rejected"><?php echo $stats['rejected']; ?></div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <div class="action-card">
            <div style="color:#888;font-size:1rem;margin-bottom:8px;">Actions rapides</div>
            <div style="color:#aaa;font-size:0.97rem;margin-bottom:18px;">Gérez vos événements efficacement</div>
            <button class="action-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Créer un nouvel événement
            </button>
        </div>
        <div class="action-card">
            <button class="action-btn" style="background:#fff;color:#222;border:1px solid #ddd;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M15 12H9m12 0A9 9 0 11 3 12a9 9 0 0118 0z"/></svg>
                Voir mes événements
            </button>
        </div>
    </div>

    <!-- Recent Events -->
    <div class="recent-events">
        <div class="recent-title">Événements récents</div>
        <div style="color:#aaa;font-size:0.97rem;margin-bottom:12px;">Vos derniers événements soumis</div>
        <?php foreach ($recent_events as $event): ?>
            <div class="event-card">
                <div class="event-info">
                    <div class="event-title"><?php echo htmlspecialchars($event['title']); ?></div>
                    <div class="event-meta"><?php echo htmlspecialchars($event['date']); ?> &bull; <?php echo htmlspecialchars($event['location']); ?></div>
                </div>
                <div class="event-status pending">
                    <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><circle cx="12" cy="12" r="10" stroke-width="2"/><path stroke-linecap="round" stroke-width="2" d="M12 6v6l4 2"/></svg>
                    <?php echo htmlspecialchars($event['status']); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

</body>
</html>
