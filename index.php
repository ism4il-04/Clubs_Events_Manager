<?php
// Redirect to the new landing page
header("Location: landing.php");
exit();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Plateforme Événements</title>
    <style>
        /* === RESET === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: "Segoe UI", Roboto, sans-serif;
            background-color: #f7f8fa;
            color: #333;
            line-height: 1.6;
        }

        /* === HEADER === */
        header {
            background: #ffffffcc;
            backdrop-filter: blur(10px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 15px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        header h1 {
            font-size: 1.6rem;
            font-weight: 600;
            color: #4a4a4a;
        }

        nav a {
            color: #4a4a4a;
            text-decoration: none;
            margin-left: 25px;
            font-weight: 500;
            transition: color 0.3s ease;
        }

        nav a:hover {
            color: #5a67d8;
        }

        /* === HERO SECTION === */
        .hero {
            background: linear-gradient(135deg, #5a67d8 0%, #764ba2 100%);
            color: white;
            text-align: center;
            padding: 100px 20px 80px;
        }

        .hero h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 20px;
        }

        .hero p {
            max-width: 700px;
            margin: 0 auto 35px;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .cta-btn {
            background: white;
            color: #5a67d8;
            padding: 12px 30px;
            border-radius: 30px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 5px 10px rgba(0,0,0,0.1);
        }

        .cta-btn:hover {
            background: #edf2f7;
            transform: translateY(-4px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        /* === ROLES SECTION === */
        .roles-section {
            background: #ffffff;
            padding: 80px 20px;
        }

        .roles-section h3 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 50px;
            color: #4a4a4a;
            font-weight: 600;
        }

        .roles-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .role-card {
            background: #f9fafb;
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            box-shadow: 0 6px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .role-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
        }

        .role-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #5a67d8;
        }

        .role-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 10px;
            color: #333;
        }

        .role-description {
            font-size: 0.95rem;
            color: #555;
            line-height: 1.5;
        }

        /* === FOOTER === */
        footer {
            background: #2d3748;
            color: #e2e8f0;
            text-align: center;
            padding: 20px 10px;
            font-size: 0.9rem;
        }

        footer a {
            color: #c3dafc;
            text-decoration: none;
            margin-left: 5px;
        }

        footer a:hover {
            text-decoration: underline;
        }

        /* === RESPONSIVE === */
        @media (max-width: 768px) {
            .hero h2 {
                font-size: 2rem;
            }

            header {
                flex-direction: column;
                align-items: flex-start;
            }

            nav {
                margin-top: 10px;
            }

            nav a {
                margin-left: 0;
                margin-right: 15px;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>Plateforme Événements ENSA</h1>
    <nav>
        <a href="login.php">Connexion</a>
        <a href="signup.php">Inscription</a>
    </nav>
</header>

<section class="hero">
    <h2>Bienvenue sur la plateforme officielle des événements 🗓️</h2>
    <p>Gérez, organisez et participez facilement aux événements de votre établissement. Que vous soyez étudiant, membre d’un club ou administrateur, cette plateforme vous simplifie la vie.</p>
    <div class="cta-buttons">
        <a href="signup.php" class="cta-btn">S'inscrire</a>
        <a href="login.php" class="cta-btn">Se Connecter</a>
    </div>
</section>

<section class="roles-section">
    <h3>Les rôles dans l'application</h3>
    <div class="roles-grid">
        <div class="role-card">
            <div class="role-icon">👨‍🎓</div>
            <div class="role-title">Participant</div>
            <div class="role-description">
                Consultez les événements disponibles, inscrivez-vous facilement et suivez vos participations en temps réel.
            </div>
        </div>
        <div class="role-card">
            <div class="role-icon">🏫</div>
            <div class="role-title">Club Admin</div>
            <div class="role-description">
                Créez et gérez les événements de votre club, suivez les inscriptions et les validations administratives.
            </div>
        </div>
        <div class="role-card">
            <div class="role-icon">🛠️</div>
            <div class="role-title">Administrateur</div>
            <div class="role-description">
                Validez les demandes d'événements, supervisez la plateforme et assurez une organisation fluide et efficace.
            </div>
        </div>
    </div>
</section>

<footer>
    © <?php echo date('Y'); ?> Plateforme Événements ENSA — Tous droits réservés.
</footer>

</body>
</html>
