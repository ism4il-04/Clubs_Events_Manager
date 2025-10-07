<?php
session_start();


$conn = new PDO('mysql:host=localhost; dbname=clubs_events; charset=utf8', 'root', '');

if(isset($_POST['participer'])){
    $nom = $_POST['nom'];
    $prenom = $_POST['prenom'];
    $nom_utilisateur = $_POST['nom_utilisateur'];
    $email = $_POST['email'];
    $password= $_POST['password'];
    $tel = $_POST['telephone'];
    $date_naissance = $_POST['date_naissance'];
    $annee = $_POST['annee'];
    $filiere = $_POST['filiere'];


    $user=$conn->prepare("insert into utilisateurs(email,password,nom_utilisateur) values (?,?,?)");
    $user->execute(array($email,$password,$nom_utilisateur));

    $participant = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ? AND nom_utilisateur = ?");
    $participant->execute([$email, $nom_utilisateur]);
    $u = $participant->fetch(PDO::FETCH_ASSOC);
    $user_id = $u['id'] ?? null;

// Insert into participants
    if ($user_id) {
        $fin = $conn->prepare("INSERT INTO etudiants (id, filiere, annee, dateNaissance, prenom, nom, telephone) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $fin->execute([$user_id, $filiere, $annee, $date_naissance, $prenom, $nom, $tel]);
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>

    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">
    <!--Stylesheet-->
    <style media="screen">
        *,
        *:before,
        *:after{
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }
        body{
            background-color: #080710;
        }
        .background{
            width: 430px;
            height: 520px;
            position: absolute;
            transform: translate(-50%,-50%);
            left: 50%;
            top: 50%;
        }
        .background .shape{
            height: 200px;
            width: 200px;
            position: absolute;
            border-radius: 50%;
        }
        .shape:first-child{
            background: linear-gradient(
                    #1845ad,
                    #23a2f6
            );
            left: -80px;
            top: -80px;
        }
        .shape:last-child{
            background: linear-gradient(
                    to right,
                    #ff512f,
                    #f09819
            );
            right: -30px;
            bottom: -80px;
        }
        form{
            height: 550px;
            width: 400px;
            background-color: rgba(255,255,255,0.13);
            position: absolute;
            transform: translate(-50%,-50%);
            top: 100px;
            left: 185px;
            border-radius: 10px;
            backdrop-filter: blur(10px);
            border: 2px solid rgba(255,255,255,0.1);
            box-shadow: 0 0 40px rgba(8,7,16,0.6);
            padding: 50px 35px;
        }
        form *{
            font-family: 'Poppins',sans-serif;
            color: #ffffff;
            letter-spacing: 0.5px;
            outline: none;
            border: none;
        }
        form h3{
            font-size: 32px;
            font-weight: 500;
            line-height: 42px;
            text-align: center;
        }

        label{
            display: block;
            margin-top: 30px;
            font-size: 16px;
            font-weight: 500;
        }
        input{
            display: block;
            height: 50px;
            width: 100%;
            background-color: rgba(255,255,255,0.07);
            border-radius: 3px;
            padding: 0 10px;
            margin-top: 8px;
            font-size: 14px;
            font-weight: 300;
        }
        ::placeholder{
            color: #e5e5e5;
        }
        .btnn{
            margin-top: 50px;
            width: 100%;
            background-color: #ffffff;
            color: #080710;
            padding: 15px 0;
            font-size: 18px;
            font-weight: 600;
            border-radius: 5px;
            cursor: pointer;
        }
        .hotel_login{
            width: 320px;
        }


    </style>
</head>
<body>
<div class="background">
    <div class="shape"></div>
    <div class="shape"></div>
</div>
<form style="margin-left:550px ; margin-top:300px;" class="form-container" method="POST" action="login.php">
    <img Class="hotel_login" src="logo.png" alt="hotel_logo">
    <label for="email"></label>
    <input type="text" placeholder="email" name="email"  id="email" required><br>
    <label for="password"></label>
    <input type="password" placeholder="password" name="password" id="password" required><br>
    <input class="btnn" type="submit" name="login" value="Connexion">
</form>
<?php
if(isset($_POST['login'])){
    $email = $_POST['email'];
    $password=$_POST['password'];

    $select = $conn->prepare( "SELECT * FROM utilisateurs WHERE email=? AND password=?");
    $select->execute(array($email,$password));
    $data = $select->fetch();

    $_SESSION['id'] = $data['id'];
    $_SESSION['email'] = $data['email'];
    $_SESSION['password'] = $data['password'];
    $_SESSION['nom_utilisateur'] = $data['nom_utilisateur'];
    }


// else{
//     echo '<script type="text/javascript">';
//     echo 'alert("invalid username or password");';
//     echo 'window.location.href="login.php"';
//     echo '</script>';

// }
if(isset($_SESSION['email'])){
    if($_SESSION['email'] == 'admin@gmail.com'){
    header("Location:./admin/dashboard.php");
}elseif ($_SESSION['email'] == 'infotech@gmail.com'){
        header("Location:./club/dashboard.php");
    }
}

?>
</body>
</html>