<?php
require_once "../includes/db.php";

if (isset($_GET['token'], $_GET['email'])) {
    $token = $_GET['token'];
    $email = $_GET['email'];
    $curDate = date("Y-m-d H:i:s");

    // Check if token exists and is valid in password_reset_temp
    $stmt = $conn->prepare("SELECT * FROM password_reset_temp WHERE token = :token AND email = :email AND type = 'signup'");
    $stmt->bindParam(':token', $token);
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    $rowCount = $stmt->rowCount();

    if ($rowCount == 0) {
        echo "<div style='text-align: center; margin-top: 50px; color: red;'>Lien invalide ou expiré.</div>";
    } else {
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $expDate = $row['expDate'];

        if ($expDate >= $curDate) {
            // Move data to utilisateurs table
            $insertStmt = $conn->prepare(
                "INSERT INTO utilisateurs (email, nom_utilisateur, password, active) 
                 VALUES (:email, :nom_utilisateur, :password, 1)"
            );
            $insertStmt->bindParam(':email', $row['email']);
            $insertStmt->bindParam(':nom_utilisateur', $row['nom_utilisateur']);
            $insertStmt->bindParam(':password', $row['password']);
            $insertStmt->execute();

            // Get the inserted user ID
            $user_id = $conn->lastInsertId();

            // Insert into etudiants table
            if ($user_id) {
                $etudiantStmt = $conn->prepare(
                    "INSERT INTO etudiants (id, filiere, annee, dateNaissance, prenom, nom, telephone) 
                     VALUES (:id, :filiere, :annee, :date_naissance, :prenom, :nom, :telephone)"
                );
                $etudiantStmt->bindParam(':id', $user_id);
                $etudiantStmt->bindParam(':filiere', $row['filiere']);
                $etudiantStmt->bindParam(':annee', $row['annee']);
                $etudiantStmt->bindParam(':date_naissance', $row['date_naissance']);
                $etudiantStmt->bindParam(':prenom', $row['prenom']);
                $etudiantStmt->bindParam(':nom', $row['nom']);
                $etudiantStmt->bindParam(':telephone', $row['telephone']);
                $etudiantStmt->execute();
            }

            // Delete from password_reset_temp
            $deleteStmt = $conn->prepare("DELETE FROM password_reset_temp WHERE token = :token AND email = :email AND type = 'signup'");
            $deleteStmt->bindParam(':token', $token);
            $deleteStmt->bindParam(':email', $email);
            $deleteStmt->execute();

            echo "<div style='text-align: center; margin-top: 50px; color: green;'>Votre compte a été activé avec succès ! Vous pouvez maintenant vous connecter.</div>";
            echo "<div style='text-align: center; margin-top: 20px;'><a href='login.php'>Aller à la connexion</a></div>";
        } else {
            echo "<div style='text-align: center; margin-top: 50px; color: red;'>Le lien a expiré. Veuillez vous réinscrire.</div>";
        }
    }
} else {
    echo "<div style='text-align: center; margin-top: 50px; color: red;'>Paramètres manquants.</div>";
}
?>