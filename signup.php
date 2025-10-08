<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription Étudiant</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;500;600&display=swap" rel="stylesheet">

    <style>
        /* RESET */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #954fa5 0%, #a138b1 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .signup-container {
            background: #fff;
            max-width: 650px;
            width: 100%;
            border-radius: 16px;
            padding: 30px 35px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            border: 1px solid #e5e7eb;
        }

        .header {
            text-align: center;
            margin-bottom: 25px;
        }

        .header h1 {
            font-size: 1.9rem;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .header p {
            color: #64748b;
            font-size: 0.95rem;
        }

        fieldset {
            border: none;
            margin-bottom: 20px;
        }

        legend {
            font-weight: 600;
            margin-bottom: 10px;
            color: #374151;
            font-size: 1.1rem;
        }

        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-row div {
            flex: 1 1 45%;
            display: flex;
            flex-direction: column;
        }

        label {
            font-size: 14px;
            margin-bottom: 5px;
            color: #374151;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        input[type="date"] {
            padding: 10px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.2s ease;
            background: #f9fafb;
        }

        input:focus {
            border-color: #6366f1;
            outline: none;
            background: #fff;
        }

        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 5px;
        }

        .radio-group label {
            display: flex;
            align-items: center;
            gap: 5px;
            background: #f8fafc;
            padding: 5px 10px;
            border-radius: 20px;
            cursor: pointer;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }

        .radio-group label:hover {
            background: #eef2ff;
            border-color: #6366f1;
        }

        input[type="radio"] {
            accent-color: #6366f1;
            cursor: pointer;
        }

        .actions {
            text-align: center;
            margin-top: 25px;
        }

        .actions button {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: white;
            border: none;
            padding: 12px 35px;
            border-radius: 25px;
            font-size: 1rem;
            cursor: pointer;
            font-weight: bold;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .actions button:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(99, 102, 241, 0.4);
        }

        .password-error {
            color: red;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }

        @media (max-width: 600px) {
            .form-row div {
                flex: 1 1 100%;
            }
        }

        .extra-links {
            text-align: center;
            margin-top: 15px;
            font-size: 13px;
        }

        .extra-links a {
            color: #4f46e5;
            text-decoration: none;
            font-weight: 500;
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

<div class="signup-container">
    <div class="header">
        <h1>Créer un compte étudiant</h1>
        <p>Inscrivez-vous pour accéder aux événements et fonctionnalités</p>
    </div>

    <form action="login.php" method="post" enctype="multipart/form-data" onsubmit="return checkPasswords();">
        <!-- Renseignements personnels -->
        <fieldset>
            <legend>Renseignements personnels</legend>
            <div class="form-row">
                <div>
                    <label>Nom</label>
                    <input type="text" name="nom" required>
                </div>
                <div>
                    <label>Prénom</label>
                    <input type="text" name="prenom" required>
                </div>
                <div>
                    <label>Date de naissance</label>
                    <input type="date" name="date_naissance" required>
                </div>
                <div>
                    <label>Téléphone</label>
                    <input type="tel" name="telephone" required>
                </div>
            </div>
        </fieldset>

        <!-- Renseignements académiques -->
        <fieldset>
            <legend>Renseignements académiques</legend>
            <label>Filière :</label>
            <div class="radio-group">
                <label><input type="radio" name="filiere" value="2AP" required> 2AP</label>
                <label><input type="radio" name="filiere" value="GSTR"> GSTR</label>
                <label><input type="radio" name="filiere" value="GI"> GI</label>
                <label><input type="radio" name="filiere" value="SCM"> SCM</label>
                <label><input type="radio" name="filiere" value="GC"> GC</label>
                <label><input type="radio" name="filiere" value="GCSE"> GCSE</label>
                <label><input type="radio" name="filiere" value="BDIA"> BDIA</label>
            </div>

            <label style="margin-top:10px;">Année :</label>
            <div class="radio-group" id="annee-container"></div>
        </fieldset>

        <!-- Identifiants -->
        <fieldset>
            <legend>Informations d'identification</legend>
            <div class="form-row">
                <div>
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div>
                    <label>Nom d'utilisateur</label>
                    <input type="text" name="nom_utilisateur" required>
                </div>
                <div>
                    <label>Mot de passe</label>
                    <input type="password" name="password" id="password" required minlength="6">
                </div>
                <div>
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
                    <div class="password-error" id="password-error">Les mots de passe ne correspondent pas.</div>
                </div>
            </div>
        </fieldset>

        <div class="actions">
            <button type="submit" name="participer">Créer mon compte</button>
        </div>
    </form>

    <div class="extra-links">
        <p>Déjà inscrit ? <a href="login.php">Se connecter</a></p>
    </div>
    <a href="index.php" class="back-home">
        <i class="fa-solid fa-arrow-left"></i> Retour à l'accueil
    </a>
</div>

<script>
    function checkPasswords() {
        const pwd = document.getElementById('password').value;
        const confirmPwd = document.getElementById('confirm_password').value;
        const errorMsg = document.getElementById('password-error');

        if (pwd !== confirmPwd) {
            errorMsg.style.display = 'block';
            return false;
        } else {
            errorMsg.style.display = 'none';
            return true;
        }
    }

    const filiereRadios = document.querySelectorAll('input[name="filiere"]');
    const anneeContainer = document.getElementById('annee-container');

    const filiereYears = {
        '2AP': ['1er', '2eme'],
        'GSTR': ['1er', '2eme', '3eme'],
        'GI': ['1er', '2eme', '3eme'],
        'SCM': ['1er', '2eme', '3eme'],
        'GC': ['1er', '2eme', '3eme'],
        'GCSE': ['1er', '2eme', '3eme'],
        'BDIA': ['1er', '2eme', '3eme']
    };

    function updateYears(selectedFiliere) {
        anneeContainer.innerHTML = '';
        filiereYears[selectedFiliere].forEach(year => {
            const label = document.createElement('label');
            label.classList.add('year-label');
            const input = document.createElement('input');
            input.type = 'radio';
            input.name = 'annee';
            input.value = year;
            input.required = true;
            label.appendChild(input);
            label.appendChild(document.createTextNode(year === '1er' ? '1ère année' : `${year} année`));
            anneeContainer.appendChild(label);
        });
    }

    filiereRadios.forEach(radio => {
        radio.addEventListener('change', (e) => {
            updateYears(e.target.value);
        });
    });
</script>

</body>
</html>
