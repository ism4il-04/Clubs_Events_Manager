<?php
session_start();

include './includes/db.php';
// --- INSCRIPTION (participer) ---
if (isset($_POST['participer'])) {
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $nom_utilisateur = $_POST['nom_utilisateur'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $tel = $_POST['telephone'];
    $date_naissance = $_POST['date_naissance'];
    $annee = $_POST['annee'];
    $filiere = $_POST['filiere'];

    $user = $conn->prepare("INSERT INTO utilisateurs(email, password, nom_utilisateur) VALUES (?, ?, ?)");
    $user->execute([$email, $password, $nom_utilisateur]);

    $participant = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ? AND nom_utilisateur = ?");
    $participant->execute([$email, $nom_utilisateur]);
    $u = $participant->fetch(PDO::FETCH_ASSOC);
    $user_id = $u['id'] ?? null;

    if ($user_id) {
        $fin = $conn->prepare("INSERT INTO etudiants (id, filiere, annee, dateNaissance, prenom, nom, telephone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $fin->execute([$user_id, $filiere, $annee, $date_naissance, $prenom, $nom, $tel]);
    }
}

// --- LOGIN ---
if (isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $select = $conn->prepare("SELECT * FROM utilisateurs WHERE email = ? AND password = ?");
    $select->execute([$email, $password]);
    $data = $select->fetch();

    if ($data) {
        $_SESSION['id'] = $data['id'];
        $_SESSION['email'] = $data['email'];
        $_SESSION['password'] = $data['password'];
        $_SESSION['nom_utilisateur'] = $data['nom_utilisateur'];

        if ($_SESSION['email'] == 'admin@gmail.com') {
            header("Location: ./admin/dashboard.php");
            exit;
        } elseif ($_SESSION['email'] == 'infotech@gmail.com') {
            header("Location: ./club/dashboard.php");
            exit;
        } else {
            header("Location: ./participant/dashboard.php");
            exit;
        }
    } else {
        echo "<script>alert('Email ou mot de passe incorrect.');</script>";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            box-sizing: border-box;
        }
        body {
            margin: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(to right, #f0f4ff, #dfe9f3);
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
            display: flex;
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
    </style>
</head>
<body>

<div class="login-container">
    <div class="logo">
        <i class="fa-solid fa-graduation-cap"></i>
        <h1>Clubs Events</h1>
    </div>

    <form method="POST" action="login.php">
        <label for="email">Email</label>
        <input type="text" name="email" id="email" placeholder="Entrez votre email" required>

        <label for="password">Mot de passe</label>
        <input type="password" name="password" id="password" placeholder="Entrez votre mot de passe" required>

        <button class="btnn" type="submit" name="login">Se connecter</button>
    </form>

    <div class="extra-links">
        <p>Pas encore de compte ? <a href="signup.php">S'inscrire</a></p>
    </div>

    <a href="index.php" class="back-home">
        <i class="fa-solid fa-arrow-left"></i> Retour à l'accueil
    </a>
</div>

</body>
</html>
