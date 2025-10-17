<?php
require_once "../includes/db.php";


require_once '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
if(isset($_POST["email"]) && !empty($_POST["email"])) {
    $error = '';
    $email = filter_var($_POST["email"], FILTER_SANITIZE_EMAIL);
    $email = filter_var($email, FILTER_VALIDATE_EMAIL);

    if (!$email) {
        $error .= "<p>Adresse e-mail invalide, veuillez saisir une adresse e-mail valide !</p>";
    } else {
        // PDO prepared statement
        $stmt = $conn->prepare("SELECT * FROM utilisateurs WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $rowCount = $stmt->rowCount();

        if ($rowCount == 0) {
            $error .= "<p>Aucun utilisateur n'est enregistré avec cette adresse e-mail !</p>";
        }
    }

    if ($error != "") {
        echo "<div class='error'>".$error."</div>
        <br /><a href='javascript:history.go(-1)'>Retour</a>";
    } else {
        // Generate reset key and expiration
        $expFormat = mktime(
            date("H"), date("i"), date("s"), date("m"), date("d")+1, date("Y")
        );
        $expDate = date("Y-m-d H:i:s", $expFormat);
        $key =$key = md5(2418*2 . $email);
        $addKey = substr(md5(uniqid(rand(),1)),3,10);
        $key = $key . $addKey;

        // Insert into password_reset_temp table using PDO
        $insertStmt = $conn->prepare(
            "INSERT INTO password_reset_temp (email, token, expDate, type) 
             VALUES (:email, :key, :expDate, 'reset')"
        );
        $insertStmt->bindParam(':email', $email);
        $insertStmt->bindParam(':key', $key);
        $insertStmt->bindParam(':expDate', $expDate);
        $insertStmt->execute();

        // Prepare email
        $resetLink = "https://clubseventsmanager.fwh.is/Clubs_Events_Manager/auth/reset-password.php?key=$key&email=$email&action=reset";

        $body = "
            <p>Cher utilisateur,</p>
            <p>Veuillez cliquer sur le lien suivant pour réinitialiser votre mot de passe :</p>
            <p>-------------------------------------------------------------</p>
            <p><a href='$resetLink' target='_blank'>$resetLink</a></p>
            <p>-------------------------------------------------------------</p>
            <p>Le lien expirera après 1 jour pour des raisons de sécurité.</p>
            <p>Si vous n'avez pas demandé cela, aucune action n'est requise.</p>
            <p>Cordialement,<br>Équipe Clubs & Événements Manager</p>
        ";
        $subject = "Récupération de mot de passe - Clubs & Événements Manager";

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();                                            //Send using SMTP
            $mail->isHTML(true);
            $mail->Host       = $_ENV["HOST"];                     //Set the SMTP server to send through
            $mail->SMTPAuth   = true;                                   //Enable SMTP authentication
            $mail->SMTPSecure = 'tls';
            $mail->Username   = $_ENV["USERNAME"];                     //SMTP username
            $mail->Password   = $_ENV["API_KEY"];                              //SMTP password
            $mail->Port       = $_ENV["PORT"];
            $mail->From       = $_ENV["FROM"];
            $mail->FromName   = $_ENV["FROM_NAME"];
            $mail->addReplyTo($_ENV["REPLY_TO"]); //l'adresse à répondre
            $mail->addAddress($email);
            $mail->Body    = $body;
            $mail->Subject = $subject;

            $mail->send();
            echo "<div class='error'>
                <p>Un e-mail vous a été envoyé avec les instructions pour réinitialiser votre mot de passe.</p>
            </div><br /><br /><br />";
        } catch (Exception $e) {
            echo "Mailer Error: " . $mail->ErrorInfo;
        }
    }
} else {
    ?>
    <style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
    .container { width: 50%; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    label { display: block; margin-bottom: 10px; font-weight: bold; }
    input[type="email"], input[type="submit"] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; }
    input[type="submit"] { background-color: #007bff; color: white; border: none; cursor: pointer; }
    input[type="submit"]:hover { background-color: #0056b3; }
    .error { color: red; margin-bottom: 20px; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
<div class="container">
    <form method="post" action="" name="reset">
        <label><strong>Saisissez votre adresse e-mail :</strong></label>
        <input type="email" name="email" placeholder="utilisateur@exemple.com" required />
        <input type="submit" value="Réinitialiser le mot de passe"/>
    </form>
</div>
    <?php
}
?>
