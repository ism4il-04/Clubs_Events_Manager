<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accueil - Plateforme Événements</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        header {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            color: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-size: 1.8rem;
            font-weight: 600;
        }

        nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            font-weight: 500;
            transition: opacity 0.3s ease;
        }

        nav a:hover {
            opacity: 0.8;
        }

        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            padding: 40px 20px;
            color: white;
        }

        .hero h2 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            text-shadow: 2px 2px 8px rgba(0,0,0,0.2);
        }

        .hero p {
            font-size: 1.2rem;
            max-width: 700px;
            line-height: 1.6;
            margin-bottom: 30px;
        }

        .cta-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .cta-btn {
            background: white;
            color: #667eea;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: bold;
            text-decoration: none;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .cta-btn:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(255,255,255,0.3);
        }

        .roles-section {
            background: white;
            color: #333;
            padding: 60px 20px;
        }

        .roles-section h3 {
            text-align: center;
            font-size: 2rem;
            margin-bottom: 40px;
            color: #667eea;
        }

        .roles-grid {
            max-width: 1100px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 30px;
        }

        .role-card {
            background: #f9f9f9;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
        }

        .role-card:hover {
            transform: translateY(-8px);
        }

        .role-icon {
            font-size: 2.5rem;
            margin-bottom: 15px;
            color: #667eea;
        }

        .role-title {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .role-description {
            font-size: 0.95rem;
            color: #555;
            line-height: 1.5;
        }

        footer {
            background: rgba(0,0,0,0.2);
            color: white;
            text-align: center;
            padding: 15px 10px;
            font-size: 0.9rem;
        }

        @media (max-width: 768px) {
            .hero h2 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>Plateforme Événements ENSA</h1>
    <nav>
        <a href="login.php">Connexion</a>
        <a href="signup.php">Inscription Participant</a>
    </nav>
</header>

<section class="hero">
    <h2>Bienvenue sur la plateforme officielle des événements 🗓️</h2>
    <p>Une solution simple et intuitive pour gérer, organiser et participer aux événements au sein de votre établissement. Que vous soyez étudiant, club ou administrateur, cette plateforme est faite pour vous.</p>

    <div class="cta-buttons">
        <a href="signup.php" class="cta-btn">S'inscrire comme Participant</a>
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
                Consultez les événements disponibles, inscrivez-vous facilement et restez informé en temps réel.
            </div>
        </div>
        <div class="role-card">
            <div class="role-icon">🏫</div>
            <div class="role-title">Club Admin</div>
            <div class="role-description">
                Proposez et gérez les événements de votre club, suivez les inscriptions et les validations administratives.
            </div>
        </div>
        <div class="role-card">
            <div class="role-icon">🛠️</div>
            <div class="role-title">Administrateur</div>
            <div class="role-description">
                Validez les demandes d'événements, supervisez la plateforme et assurez une bonne organisation générale.
            </div>
        </div>
    </div>
</section>

<footer>
    © <?php echo date('Y'); ?> Plateforme Événements ENSA - Tous droits réservés
</footer>

</body>
</html>
