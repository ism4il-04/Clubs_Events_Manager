<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Formulaire étudiant</title>
    <link rel="stylesheet" href="index.css">
    <link rel="icon" type="image/png" sizes="16x16" href="../pigeon2-removebg-preview.png">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #f0f4ff, #dfe9f3);
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 700px;
            margin: 40px auto;
            background: white;
            padding: 20px 30px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        fieldset {
            border: 1px solid #ccc;
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 5px;
        }
        legend {
            font-weight: bold;
            color: #444;
        }
        .row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .row div {
            flex: 1 1 45%;
            display: flex;
            flex-direction: column;
        }
        label {
            font-size: 14px;
            margin-bottom: 5px;
        }
        input[type="text"],
        input[type="date"],
        input[type="tel"],
        input[type="email"],
        input[type="password"],
        input[type="file"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="radio"] {
            margin-right: 5px;
        }
        .actions {
            text-align: center;
        }
        .actions button {
            background: #4a90e2;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
        }
        .actions button:hover {
            background: #357ab8;
        }
        .password-error {
            color: red;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Formulaire étudiant</h1>

    <form action="../login.php" method="post" enctype="multipart/form-data" onsubmit="return checkPasswords();">

        <!-- Renseignements personnels -->
        <fieldset>
            <legend>Renseignements personnels</legend>
            <div class="row">
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


<!--                <div>-->
<!--                    <label>Photo</label>-->
<!--                    <input type="file" name="file" id="file">-->
<!--                </div>-->
            </div>
        </fieldset>

        <!-- Renseignements académiques -->
        <fieldset>
            <legend>Renseignements académiques</legend>

            <p><strong>Filière :</strong></p>
            <label><input type="radio" name="filiere" value="2AP" required> 2AP</label>
            <label><input type="radio" name="filiere" value="GSTR"> GSTR</label>
            <label><input type="radio" name="filiere" value="GI"> GI</label>
            <label><input type="radio" name="filiere" value="SCM"> SCM</label>
            <label><input type="radio" name="filiere" value="GC"> GC</label>
            <label><input type="radio" name="filiere" value="GCSE"> GCSE</label>
            <label><input type="radio" name="filiere" value="BDIA"> BDIA</label>


            <p><strong>Année :</strong></p>
            <div id="annee-container">
                <!-- Years will be populated here dynamically -->
            </div>
        </fieldset>


        <!-- Mot de passe -->
        <fieldset>
            <legend>Informations d'identification</legend>
            <div class="row">
                <div>
                    <label>Email</label>
                    <input type="email" name="email" required>
                </div>
                <div>
                    <label>Nom utilisateur</label>
                    <input type="text" name="nom_utilisateur" required>
                </div>
                <div>
                    <label>Mot de passe</label>
                    <input type="password" name="password" id="password" required minlength="6">
                </div>
                <div>
                    <label>Confirmer le mot de passe</label>
                    <input type="password" name="confirm_password" id="confirm_password" required minlength="6">
<!--                    <div class="password-error" id="password-error">Les mots de passe ne correspondent pas.</div>-->
                </div>
            </div>
        </fieldset>

        <div class="actions">
            <button type="submit" name="participer">Valider et accéder à l'espace</button>
        </div>

    </form>
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
        'MS': ['1er', '2eme', '3eme'],
        'GCSE': ['1er', '2eme', '3eme'],
        'BDIA': ['1er', '2eme', '3eme']

    };

    function updateYears(selectedFiliere) {
        anneeContainer.innerHTML = '';

        filiereYears[selectedFiliere].forEach(year => {
            const label = document.createElement('label');
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
