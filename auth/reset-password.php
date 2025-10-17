<?php
require_once "../includes/db.php";

if (isset($_GET["key"], $_GET["email"], $_GET["action"]) && $_GET["action"] === "reset" && !isset($_POST["action"])) {
    $token = $_GET["key"];
    $email = $_GET["email"];
    $curDate = date("Y-m-d H:i:s");
    $error = '';

    // Check if reset request exists and is valid
    $stmt = $conn->prepare("SELECT * FROM password_reset_temp WHERE token = :token AND email = :email AND type = 'reset'");
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $rowCount = $stmt->rowCount();

    if ($rowCount == 0) {
        $error .= '<h2>Lien invalide</h2>
<p>Le lien est invalide ou expiré. Soit vous n avez pas copié le bon lien depuis l e-mail, soit vous avez déjà utilisé le jeton, ce qui l a désactivé.</p>
<p><a href="forgot-password.php">Cliquez ici</a> pour réinitialiser le mot de passe.</p>';
    } else {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $expDate = $row['expDate'];

        if ($expDate >= $curDate) {
            // Show reset password form
            ?>
            <style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; }
    .container { width: 50%; margin: 50px auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    label { display: block; margin-bottom: 10px; font-weight: bold; }
    input[type="password"], input[type="submit"] { width: 100%; padding: 10px; margin-bottom: 20px; border: 1px solid #ccc; border-radius: 4px; }
    input[type="submit"] { background-color: #007bff; color: white; border: none; cursor: pointer; }
    input[type="submit"]:hover { background-color: #0056b3; }
    .error { color: red; margin-bottom: 20px; }
    a { color: #007bff; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
<div class="container">
    <form method="post" action="" name="update">
        <input type="hidden" name="action" value="update" />
        <label><strong>Saisissez le nouveau mot de passe :</strong></label>
        <input type="password" name="pass1" maxlength="50" required />
        <label><strong>Confirmez le nouveau mot de passe :</strong></label>
        <input type="password" name="pass2" maxlength="50" required />
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>"/>
        <input type="submit" value="Réinitialiser le mot de passe" />
    </form>
</div>
            <?php
        } else {
            $error .= "<h2>Lien expiré</h2>
<p>Le lien est expiré. Vous essayez d utiliser un lien qui n était valide que 24 heures après la demande.<br /><br /></p>";
        }
    }

    if ($error != '') {
        echo "<div class='error'>$error</div><br />";
    }
}

// Handle password update
if (isset($_POST["email"], $_POST["action"]) && $_POST["action"] === "update") {
    $error = '';
    $pass1 = $_POST["pass1"];
    $pass2 = $_POST["pass2"];
    $email = $_POST["email"];
    $curDate = date("Y-m-d H:i:s");

    if ($pass1 !== $pass2) {
        $error .= "<p>Les mots de passe ne correspondent pas. Les deux mots de passe doivent être identiques.<br /><br /></p>";
    }

    if ($error != '') {
        echo "<div class='error'>$error</div><br />";
    } else {
        // Hash password securely
        $hashedPassword = password_hash($pass1, PASSWORD_DEFAULT);

        // Update user's password
        $updateStmt = $conn->prepare("UPDATE utilisateurs SET password = :password WHERE email = :email");
        $updateStmt->bindParam(':password', $hashedPassword);
        $updateStmt->bindParam(':email', $email);
        $updateStmt->execute();

        // Delete the reset key from temp table
        $deleteStmt = $conn->prepare("DELETE FROM password_reset_temp WHERE email = :email AND type = 'reset'");
        $deleteStmt->bindParam(':email', $email);
        $deleteStmt->execute();

        echo '<div class="error">
<p>Félicitations ! Votre mot de passe a été mis à jour avec succès.</p>
<p><a href="login.php">Cliquez ici</a> pour vous connecter.</p></div><br />';
    }
}
?>
