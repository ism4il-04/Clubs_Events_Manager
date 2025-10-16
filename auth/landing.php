<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clubs & Events Manager - ENSA Tétouan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        /* === RESET === */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        ::-webkit-scrollbar {
            display: none;
        }


        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: #333;
            line-height: 1.6;
            overflow-x: hidden;
            overflow: auto; /* still scrollable */
            -ms-overflow-style: none;  /* IE and Edge */
            scrollbar-width: none;   
        }

        /* === ANIMATED BACKGROUND === */
        .bg-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .bg-animation::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            animation: float 20s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(1deg); }
        }

        /* === HEADER === */
        header {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo-section {
            display: flex;
            padding: 0px;
            align-items: center;
            gap: 0rem;
        }

        .logo {
            width: 320;
            height: 90px;
            border-radius: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
        }

        .logo img {
            width: 320px;
            height: 180px;
            object-fit: contain;
        }

        .brand-text h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.25rem;
        }

        .brand-text p {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 500;
        }

        .auth-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-auth {
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            border: 2px solid transparent;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-login {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border-color: rgba(255, 255, 255, 0.3);
        }

        .btn-login:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-signup {
            background: white;
            color: #667eea;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .btn-signup:hover {
            background: #f8fafc;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        /* === MAIN CONTENT === */
        .main-content {
            min-height: 100vh;
            display: flex;
            margin-top: 20px !important; /* 👈 force push below header */
            align-items: center;
            justify-content: center;
            padding: 0 2rem;
            position: relative;
        }

        .hero-container {
            max-width: 1200px;
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .hero-content {
            color: white;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 8px 16px;
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-bottom: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
        }

        .hero-badge span {
            width: 8px;
            height: 8px;
            background: #4ade80;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            line-height: 1.1;
            margin-bottom: 1.5rem;
            margin-top: 0px;
            background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.25rem;
            font-weight: 400;
            margin-bottom: 1.5rem;
            opacity: 0.9;
            line-height: 1.6;
        }

        .hero-features {
            list-style: none;
            margin-bottom: 1.5rem;
        }

        .hero-features li {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
            font-size: 1rem;
            opacity: 0.9;
        }

        .hero-features li::before {
            content: '✓';
            width: 20px;
            height: 20px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .cta-section {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .btn-primary {
            background: white;
            color: #667eea;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: #f8fafc;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 16px 32px;
            border-radius: 12px;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        /* === HERO VISUAL === */
        .hero-visual {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
        }

        .floating-cards {
            position: relative;
            width: 100%;
            height: 380px;
        }

        .card {
            position: absolute;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            animation: float-card 6s ease-in-out infinite;
        }

        .card:nth-child(1) {
            top: 20px;
            left: 20px;
            width: 200px;
            animation-delay: 0s;
        }

        .card:nth-child(2) {
            top: 80px;
            right: 20px;
            width: 180px;
            animation-delay: 2s;
        }

        .card:nth-child(3) {
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 160px;
            animation-delay: 4s;
        }

        @keyframes float-card {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-10px) rotate(1deg); }
        }

        .card:nth-child(3) {
            animation: float-card 6s ease-in-out infinite 4s, center-float 8s ease-in-out infinite;
        }

        @keyframes center-float {
            0%, 100% { transform: translateX(-50%) translateY(0px); }
            50% { transform: translateX(-50%) translateY(-10px); }
        }

        .card-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }

        .card h4 {
            font-size: 1rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 0.5rem;
        }

        .card p {
            font-size: 0.85rem;
            color: #6b7280;
            line-height: 1.4;
        }

        /* === STATS SECTION === */
        .stats-section {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(20px);
            padding: 1.5rem 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .stat-item {
            text-align: center;
            color: white;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        /* === RESPONSIVE === */
        @media (max-width: 968px) {
            .hero-container {
                grid-template-columns: 1fr;
                gap: 3rem;
                text-align: center;
            }

            .hero-title {
                font-size: 2.5rem;
            }

            .stats-section {
                position: relative;
                bottom: auto;
                left: auto;
                transform: none;
                margin-top: 3rem;
                flex-wrap: wrap;
                gap: 1.5rem;
            }
        }

        @media (max-width: 768px) {
            .header-container {
                padding: 0 1rem;
            }

            .brand-text h1 {
                font-size: 1.2rem;
            }

            .auth-buttons {
                gap: 0.5rem;
            }

            .btn-auth {
                padding: 10px 16px;
                font-size: 0.9rem;
            }

            .main-content {
                padding: 0 1rem;
            }

            .hero-title {
                font-size: 2rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .cta-section {
                justify-content: center;
            }

            .floating-cards {
                height: 300px;
            }

            .card {
                padding: 1.5rem;
            }

            .card:nth-child(1) {
                width: 150px;
            }

            .card:nth-child(2) {
                width: 140px;
            }

            .card:nth-child(3) {
                width: 120px;
            }

            .stats-section {
                flex-direction: column;
                gap: 1rem;
            }
        }

        /* === SCROLL EFFECTS === */
        .scroll-reveal {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .scroll-reveal.revealed {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="bg-animation"></div>

    <!-- Header -->
    <header>
        <div class="header-container">
            <div class="logo-section">
                <div class="logo">
                    <img src="../Horizontal_Logo-removebg-preview.png" alt="InfoTech Logo">
                </div>
            </div>
            <div class="auth-buttons">
                <a href="login.php" class="btn-auth btn-login">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 8a3 3 0 1 0 0-6 3 3 0 0 0 0 6zm2-3a2 2 0 1 1-4 0 2 2 0 0 1 4 0zm4 8c0 1-1 1-1 1H3s-1 0-1-1 1-4 6-4 6 3 6 4zm-1-.004c-.001-.246-.154-.986-.832-1.664C11.516 10.68 10.289 10 8 10c-2.29 0-3.516.68-4.168 1.332-.678.678-.83 1.418-.832 1.664h10z"/>
                    </svg>
                    Se connecter
                </a>
                <a href="signup.php" class="btn-auth btn-signup">
                    <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M6 12v-2h3v2h5v2H1v-2h5zM11 3v2H8V3H3V1h10v2h-2z"/>
                    </svg>
                    S'inscrire comme participant
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="hero-container">
            <div class="hero-content scroll-reveal">
                <h1 class="hero-title">
                    Gérez vos événements 
                    <span style="background: linear-gradient(135deg, #4ade80, #22c55e); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                        facilement
                    </span>
                </h1>
                <p class="hero-subtitle">
                    La solution complète pour organiser, gérer et participer aux événements de votre établissement. 
                    Connectez étudiants, clubs et administration dans une plateforme moderne.
                </p>
                <ul class="hero-features">
                    <li>Création et gestion d'événements simplifiée</li>
                    <li>Inscription en ligne pour les participants</li>
                    <li>Suivi en temps réel des participations</li>
                </ul>
                <div class="cta-section">
                    <a href="login.php" class="btn-primary">
                        <svg width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 0a8 8 0 1 1 0 16A8 8 0 0 1 8 0zM4.5 7.5a.5.5 0 0 0 0 1h5.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3a.5.5 0 0 0 0-.708l-3-3a.5.5 0 1 0-.708.708L10.293 7.5H4.5z"/>
                        </svg>
                        Commencer maintenant
                    </a>
                </div>
            </div>
            
            <div class="hero-visual scroll-reveal">
                <div class="floating-cards">
                    <div class="card">
                        <div class="card-icon">📅</div>
                        <h4>Événements</h4>
                        <p>Créez et gérez vos événements avec facilité</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">👥</div>
                        <h4>Participants</h4>
                        <p>Suivez les inscriptions en temps réel</p>
                    </div>
                    <div class="card">
                        <div class="card-icon">✔️</div>
                        <h4>Validation</h4>
                        <p>Processus d'approbation simplifié</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-section scroll-reveal">
            <div class="stat-item">
                <div class="stat-number">20+</div>
                <div class="stat-label">Événements par mois</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">1K+</div>
                <div class="stat-label">Participants actifs</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">10+</div>
                <div class="stat-label">Clubs & Assosciations partenaires</div>
            </div>
            <div class="stat-item">
                <div class="stat-number">98%</div>
                <div class="stat-label">Satisfaction</div>
            </div>
        </div>
    </main>

    <script>
        // Scroll reveal animation
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('revealed');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.scroll-reveal').forEach(el => {
            observer.observe(el);
        });

        // Header scroll effect
        let lastScrollY = window.scrollY;
        const header = document.querySelector('header');

        window.addEventListener('scroll', () => {
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.backdropFilter = 'blur(20px)';
                header.style.borderBottom = '1px solid rgba(0, 0, 0, 0.1)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.1)';
                header.style.backdropFilter = 'blur(20px)';
                header.style.borderBottom = '1px solid rgba(255, 255, 255, 0.2)';
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
