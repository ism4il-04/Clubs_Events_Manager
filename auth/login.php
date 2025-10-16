<?php
session_start();

require_once '../vendor/autoload.php';
include '../includes/db.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');$dotenv->load();
$secretKey = $_ENV['SECRET_KEY'];
$siteKey = $_ENV['SITE_KEY'];
// --- INSCRIPTION (participer) ---
//if (isset($_POST['participer'])) {
//    $nom = $_POST['nom'];
//    $prenom = $_POST['prenom'];
//    $nom_utilisateur = $_POST['nom_utilisateur'];
//    $email = $_POST['email'];
//    $password = $_POST['password'];
//    $tel = $_POST['telephone'];
//    $date_naissance = $_POST['date_naissance'];
//    $annee = $_POST['annee'];
//    $filiere = $_POST['filiere'];
//
//    $hashed_password=password_hash($password, PASSWORD_DEFAULT);
//    $user = $conn->prepare("INSERT INTO utilisateurs(email, password, nom_utilisateur) VALUES (?, ?, ?)");
//    $user->execute([$email, $hashed_password, $nom_utilisateur]);
//
//    $participant = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ? AND nom_utilisateur = ?");
//    $participant->execute([$email, $nom_utilisateur]);
//    $u = $participant->fetch(PDO::FETCH_ASSOC);
//    $user_id = $u['id'] ?? null;
//
//    if ($user_id) {
//        $fin = $conn->prepare("INSERT INTO etudiants (id, filiere, annee, dateNaissance, prenom, nom, telephone) VALUES (?, ?, ?, ?, ?, ?, ?)");
//        $fin->execute([$user_id, $filiere, $annee, $date_naissance, $prenom, $nom, $tel]);
//    }
//}

// --- LOGIN ---
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = trim($_POST['password']);
    $recaptcha = $_POST['g-recaptcha-response'];
    $secret_key = $secretKey;

    // Hitting request to the URL, Google will
    // respond with success or error scenario
    $url = 'https://www.google.com/recaptcha/api/siteverify?secret='
        . $secret_key . '&response=' . $recaptcha;

    // Making request to verify captcha
    $response = file_get_contents($url);

    // Response return by google is in
    // JSON format, so we have to parse
    // that json
    $response = json_decode($response);

    // Checking, if response is true or not
    if ($response->success == true) {
//        echo '<script>alert("Google reCAPTCHA verified")</script>';

        $select = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ?");
        $select->execute([$email]);
        $data = $select->fetch();

        if ($data) {
            if ($data['active'] == 0) {
                echo "<script>alert('Votre compte n'est pas encore activé. Veuillez vérifier votre email pour activer votre compte.');</script>";
            } elseif (password_verify($password, $data['password'])) {

                $_SESSION['id'] = $data['id'];
                $_SESSION['email'] = $data['email'];
                $_SESSION['nom_utilisateur'] = $data['nom_utilisateur'];

                $adminCheck = $conn->prepare("SELECT id FROM admin WHERE id = ?");
                $adminCheck->execute([$_SESSION['id']]);
                $isAdmin = $adminCheck->fetch();

                // Check if user is admin
                if ($isAdmin) {
                    header("Location: ../admin/dashboard.php");
                    exit;
                }

                // Check if user is club
                $clubCheck = $conn->prepare("SELECT id FROM organisateur WHERE id = ?");
                $clubCheck->execute([$_SESSION['id']]);
                $isClub = $clubCheck->fetch();

                if ($isClub) {
                    header("Location: ../club/dashboard.php");
                    exit;
                }

                // Else, it's a participant
                header("Location: ../participant/dashboard.php");
                exit;

            } else {
                echo "<script>alert('Email ou mot de passe incorrect.');</script>";
            }
        } else {
            echo "<script>alert('Aucun compte trouvé avec cet email.');</script>";
        }

    } else {
        echo '<script>alert("Error in Google reCAPTCHA")</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Clubs Events</title>
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer>
    </script>
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .login-container {
            background: white;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 380px;
            text-align: center;
        }
        .logo {
            display: block;
            align-items: center;
            justify-content: center;
            gap: 10px;
            margin-bottom: 20px;
        }
        .logo i {
            font-size: 35px;
            color: #4f46e5;
        }
        .logo h1 {
            font-size: 22px;
            margin: 0;
            color: #333;
        }
        label {
            font-size: 14px;
            margin-bottom: 6px;
            display: block;
            color: #555;
            text-align: left;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            margin-bottom: 15px;
            font-size: 14px;
        }
        input:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 5px rgba(74,144,226,0.4);
        }
        .btnn {
            width: 100%;
            background: #4a90e2;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 10px;
        }
        .btnn:hover {
            background: #357ab8;
        }
        .extra-links {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
        }
        .extra-links a {
            color: #4a90e2;
            text-decoration: none;
        }
        .extra-links a:hover {
            text-decoration: underline;
        }
        .back-home {
            display: inline-block;
            margin-top: 20px;
            font-size: 14px;
            color: #4f46e5;
            text-decoration: none;
        }
        .back-home i {
            margin-right: 5px;
        }
        .back-home:hover {
            text-decoration: underline;
        }
        .img{
            width: 300px;
            align-items: center;
        }
        /*.g-recaptcha {*/
        /*    margin-left: 513px;*/
        /*}*/
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo">
        <img class="img" src="../Horizontal_Logo-removebg-preview.png" alt="logo">
    </div>

    <form method="POST" action="login.php">
        <label for="email">Email</label>
        <input type="text" name="email" id="email" placeholder="Entrez votre email" required>

        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" placeholder="Entrez votre mot de passe" required>

        <div class="g-recaptcha"
             data-sitekey=<?= $siteKey; ?>>
        </div>
        <button class="btnn" type="submit" name="login">Se connecter</button>
    </form>
    <p><a href="forgot-password.php">Mot de passe oublié ?</a></p>

    <div class="extra-links">
        <p>Pas encore de compte ? <a href="signup.php">S'inscrire</a></p>
    </div>

    <a href="landing.php" class="back-home">
        <i class="fa-solid fa-arrow-left"></i> Retour à l'accueil
    </a>
</div>

</body>
</html>
