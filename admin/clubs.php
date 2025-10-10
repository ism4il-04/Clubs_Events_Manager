<?php
session_start();
if (!isset($_SESSION['email'])) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";

// Variables pour le formulaire
$form_action = 'ajouter';
$editing_club_id = null;
$club_data = [
    'clubNom' => '',
    'nom_abr' => '',
    'description' => '',
    'logo' => '',
    'email' => '',
    'nom_utilisateur' => '',
    'password' => ''
];

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        try {
            // ACTION AJOUTER
            if ($action === 'ajouter') {
                $clubNom = trim($_POST['clubNom']);
                $nom_abr = trim($_POST['nom_abr']);
                $description = trim($_POST['description']);
                $logo = trim($_POST['logo']);
                $email = trim($_POST['email']);
                $nom_utilisateur = trim($_POST['nom_utilisateur']);
                $password = trim($_POST['password']);

                // Vérifications
                if (empty($clubNom) || empty($nom_abr) || empty($email) || empty($nom_utilisateur) || empty($password)) {
                    throw new Exception("Tous les champs obligatoires doivent être remplis.");
                }

                // Vérifier si l'email existe déjà
                $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    throw new Exception("Cet email est déjà utilisé.");
                }

                $conn->beginTransaction();

                // Insérer dans utilisateurs
                $stmt = $conn->prepare("INSERT INTO utilisateurs (email, password, nom_utilisateur) VALUES (?, ?, ?)");
                $stmt->execute([$email, $password, $nom_utilisateur]);
                $user_id = $conn->lastInsertId();

                // Insérer dans organisateur
                $stmt = $conn->prepare("INSERT INTO organisateur (id, clubNom, nom_abr, logo, description) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $clubNom, $nom_abr, $logo, $description]);

                $conn->commit();
                $message = "Club ajouté avec succès !";

            // ACTION MODIFIER - CORRIGÉE
            } elseif ($action === 'modifier' && isset($_POST['club_id'])) {
                $club_id = $_POST['club_id'];
                $clubNom = trim($_POST['clubNom']);
                $nom_abr = trim($_POST['nom_abr']);
                $description = trim($_POST['description']);
                $logo = trim($_POST['logo']);
                $email = trim($_POST['email']);
                $nom_utilisateur = trim($_POST['nom_utilisateur']);
                $password = trim($_POST['password']);

                // Vérifications
                if (empty($clubNom) || empty($nom_abr) || empty($email) || empty($nom_utilisateur)) {
                    throw new Exception("Tous les champs obligatoires doivent être remplis.");
                }

                // Vérifier si l'email existe déjà pour un autre utilisateur
                $stmt = $conn->prepare("SELECT id FROM utilisateurs WHERE email = ? AND id != ?");
                $stmt->execute([$email, $club_id]);
                if ($stmt->fetch()) {
                    throw new Exception("Cet email est déjà utilisé par un autre utilisateur.");
                }

                $conn->beginTransaction();

                // Mettre à jour utilisateurs
                if (!empty($password)) {
                    $stmt = $conn->prepare("UPDATE utilisateurs SET email = ?, nom_utilisateur = ?, password = ? WHERE id = ?");
                    $stmt->execute([$email, $nom_utilisateur, $password, $club_id]);
                } else {
                    $stmt = $conn->prepare("UPDATE utilisateurs SET email = ?, nom_utilisateur = ? WHERE id = ?");
                    $stmt->execute([$email, $nom_utilisateur, $club_id]);
                }

                // Mettre à jour organisateur
                $stmt = $conn->prepare("UPDATE organisateur SET clubNom = ?, nom_abr = ?, logo = ?, description = ? WHERE id = ?");
                $stmt->execute([$clubNom, $nom_abr, $logo, $description, $club_id]);

                $conn->commit();
                $message = "Club modifié avec succès !";

            // ACTION SUPPRIMER
            } elseif ($action === 'supprimer' && isset($_POST['club_id'])) {
                $club_id = $_POST['club_id'];
                
                // Vérifier si le club a des événements
                $stmt = $conn->prepare("SELECT COUNT(*) as nb_events FROM evenements WHERE organisateur_id = ?");
                $stmt->execute([$club_id]);
                $result = $stmt->fetch();
                
                if ($result['nb_events'] > 0) {
                    throw new Exception("Impossible de supprimer ce club : il a des événements associés.");
                }

                $conn->beginTransaction();
                
                // Supprimer d'abord l'organisateur puis l'utilisateur
                $stmt = $conn->prepare("DELETE FROM organisateur WHERE id = ?");
                $stmt->execute([$club_id]);
                
                $stmt = $conn->prepare("DELETE FROM utilisateurs WHERE id = ?");
                $stmt->execute([$club_id]);
                
                $conn->commit();
                $message = "Club supprimé avec succès.";

            // ACTION EDITOR - Préparation de l'édition
            } elseif ($action === 'editer' && isset($_POST['club_id'])) {
                // Préparer les données pour l'édition
                $club_id = $_POST['club_id'];
                $form_action = 'modifier';
                $editing_club_id = $club_id;
                
                // Récupérer les données du club
                $stmt = $conn->prepare("
                    SELECT o.*, u.email, u.nom_utilisateur 
                    FROM organisateur o 
                    JOIN utilisateurs u ON o.id = u.id 
                    WHERE o.id = ?
                ");
                $stmt->execute([$club_id]);
                $club_data_result = $stmt->fetch();
                
                if ($club_data_result) {
                    $club_data = $club_data_result;
                } else {
                    throw new Exception("Club non trouvé.");
                }
            }
            
            // Redirection seulement si pas d'erreur et pas d'action 'editer'
            if (!isset($error) && $action !== 'editer') {
                header("Location: clubs.php?message=" . urlencode($message ?? ''));
                exit();
            }
            
        } catch (Exception $e) {
            if (isset($conn) && $conn->inTransaction()) {
                $conn->rollBack();
            }
            $error = "Erreur : " . $e->getMessage();
            
            // Debug: Afficher l'erreur SQL si disponible
            error_log("Erreur clubs.php: " . $e->getMessage());
        }
    }
}

// Récupérer tous les clubs avec le nombre d'événements (reste identique)
$stmt = $conn->prepare("
    SELECT o.*, u.email, u.nom_utilisateur, 
           COUNT(e.idEvent) as nb_evenements
    FROM organisateur o 
    JOIN utilisateurs u ON o.id = u.id
    LEFT JOIN evenements e ON o.id = e.organisateur_id
    GROUP BY o.id
    ORDER BY o.clubNom
");
$stmt->execute();
$clubs = $stmt->fetchAll();

$message = $_GET['message'] ?? '';
?>

<!-- Le reste du code HTML reste identique -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Clubs - Admin</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .container-admin {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        .club-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .club-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .club-logo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 10px;
            border: 2px solid #e9ecef;
        }
        .form-container {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .stats-badge {
            font-size: 0.7em;
            margin-left: 5px;
        }
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .btn-sm {
            padding: 4px 8px;
            font-size: 0.8rem;
        }
        .debug-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 5px;
            padding: 10px;
            margin: 10px 0;
            font-size: 0.9em;
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>
<div class="tabs">
    <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
    <div class="tab" onclick="navigateTo('demandes_evenements.php')">Demandes d'événements</div>
    <div class="tab" onclick="navigateTo('evenements.php')">Tous les événements</div>
    <div class="tab active" onclick="navigateTo('clubs.php')">Gestion des clubs</div>
    <div class="tab" onclick="navigateTo('utilisateurs.php')">Utilisateurs</div>
</div>
<div class="container-admin">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>🏫 Gestion des Clubs</h2>
        <div>
            <span class="badge bg-primary"><?= count($clubs) ?> clubs</span>
            <button type="button" class="btn btn-success ms-2" onclick="showAddForm()">
                ➕ Ajouter un club
            </button>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success">✅ <?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger">❌ <?= htmlspecialchars($error) ?></div>
        
        <!-- Debug info (à retirer en production) -->
        <div class="debug-info">
            <strong>Debug Info:</strong><br>
            Action: <?= $_POST['action'] ?? 'N/A' ?><br>
            Club ID: <?= $_POST['club_id'] ?? 'N/A' ?><br>
            Form Action: <?= $form_action ?><br>
            Editing Club ID: <?= $editing_club_id ?? 'N/A' ?>
        </div>
    <?php endif; ?>

    <!-- Formulaire d'ajout/modification -->
    <div class="form-container" id="clubForm" style="<?= ($form_action === 'ajouter' || $form_action === 'modifier') ? '' : 'display: none;' ?>">
        <h4>
            <?= $form_action === 'ajouter' ? '➕ Ajouter un club' : '✏️ Modifier le club' ?>
            <?php if ($form_action === 'modifier'): ?>
                <small class="text-muted">(ID: <?= $editing_club_id ?>)</small>
            <?php endif; ?>
        </h4>
        
        <form method="POST" action="">
            <input type="hidden" name="action" value="<?= $form_action ?>">
            <?php if ($editing_club_id): ?>
                <input type="hidden" name="club_id" value="<?= $editing_club_id ?>">
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Nom du club *</label>
                        <input type="text" class="form-control" name="clubNom" 
                               value="<?= htmlspecialchars($club_data['clubNom']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nom abrégé *</label>
                        <input type="text" class="form-control" name="nom_abr" 
                               value="<?= htmlspecialchars($club_data['nom_abr']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Logo (URL)</label>
                        <input type="text" class="form-control" name="logo" 
                               value="<?= htmlspecialchars($club_data['logo']) ?>"
                               placeholder="ex: includes/logo-club.png">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="mb-3">
                        <label class="form-label">Email *</label>
                        <input type="email" class="form-control" name="email" 
                               value="<?= htmlspecialchars($club_data['email']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Nom d'utilisateur *</label>
                        <input type="text" class="form-control" name="nom_utilisateur" 
                               value="<?= htmlspecialchars($club_data['nom_utilisateur']) ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">
                            Mot de passe <?= $form_action === 'ajouter' ? '*' : '(laisser vide pour ne pas changer)' ?>
                        </label>
                        <input type="password" class="form-control" name="password" 
                               <?= $form_action === 'ajouter' ? 'required' : '' ?>
                               placeholder="<?= $form_action === 'modifier' ? 'Laisser vide pour garder l\'ancien' : '' ?>">
                    </div>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea class="form-control" name="description" rows="3" 
                          placeholder="Description du club..."><?= htmlspecialchars($club_data['description']) ?></textarea>
            </div>
            
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <?= $form_action === 'ajouter' ? '➕ Ajouter' : '💾 Sauvegarder' ?>
                </button>
                <button type="button" class="btn btn-secondary" onclick="hideForm()">Annuler</button>
                
                <?php if ($form_action === 'modifier'): ?>
                <button type="button" class="btn btn-info" onclick="resetForm()">
                    🔄 Réinitialiser
                </button>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Liste des clubs (reste identique) -->
    <div class="row">
        <?php foreach ($clubs as $club): ?>
        <div class="col-md-6 col-lg-4">
            <div class="club-card">
                <div class="d-flex align-items-start mb-3">
                    <?php if ($club['logo']): ?>
                        <img src="../<?= htmlspecialchars($club['logo']) ?>" 
                             class="club-logo me-3" 
                             alt="Logo <?= htmlspecialchars($club['clubNom']) ?>"
                             onerror="this.style.display='none'">
                    <?php endif; ?>
                    <div class="flex-grow-1">
                        <h5 class="mb-1"><?= htmlspecialchars($club['clubNom']) ?></h5>
                        <p class="text-muted mb-1">
                            <strong>Nom abrégé:</strong> <?= htmlspecialchars($club['nom_abr']) ?>
                        </p>
                        <p class="text-muted mb-1">
                            <strong>Email:</strong> <?= htmlspecialchars($club['email']) ?>
                        </p>
                        <p class="text-muted mb-2">
                            <strong>Utilisateur:</strong> <?= htmlspecialchars($club['nom_utilisateur']) ?>
                        </p>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-info">
                                📅 <?= $club['nb_evenements'] ?> événement(s)
                            </span>
                            <small class="text-muted">ID: <?= $club['id'] ?></small>
                        </div>
                    </div>
                </div>
                
                <?php if ($club['description']): ?>
                <p class="text-muted small mb-3">
                    <?= htmlspecialchars(substr($club['description'], 0, 150)) ?>
                    <?= strlen($club['description']) > 150 ? '...' : '' ?>
                </p>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                        <input type="hidden" name="action" value="editer">
                        <button type="submit" class="btn btn-warning btn-sm">
                            ✏️ Modifier
                        </button>
                    </form>
                    
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
                        <input type="hidden" name="action" value="supprimer">
                        <button type="submit" class="btn btn-danger btn-sm" 
                                onclick="return confirm('Êtes-vous sûr de vouloir supprimer le club <?= addslashes($club['clubNom']) ?> ?')">
                            🗑️ Supprimer
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($clubs)): ?>
        <div class="text-center py-5">
            <div class="text-muted">
                <i class="fas fa-users fa-3x mb-3"></i>
                <h4>Aucun club trouvé</h4>
                <p>Commencez par ajouter votre premier club.</p>
                <button type="button" class="btn btn-success" onclick="showAddForm()">
                    ➕ Ajouter un club
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
function showAddForm() {
    document.getElementById('clubForm').style.display = 'block';
    // Réinitialiser le formulaire pour l'ajout
    document.querySelector('form').reset();
    document.querySelector('input[name="action"]').value = 'ajouter';
    document.querySelector('h4').textContent = '➕ Ajouter un club';
    
    // Supprimer le champ club_id s'il existe
    const clubIdField = document.querySelector('input[name="club_id"]');
    if (clubIdField) clubIdField.remove();
    
    document.querySelector('html').scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

function hideForm() {
    document.getElementById('clubForm').style.display = 'none';
}

function resetForm() {
    if (confirm('Voulez-vous réinitialiser le formulaire ?')) {
        document.querySelector('form').reset();
    }
}

// Afficher le formulaire si on est en mode édition ou ajout
<?php if ($form_action === 'modifier' || $form_action === 'ajouter'): ?>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('clubForm').style.display = 'block';
    document.querySelector('html').scrollTo({
        top: 0,
        behavior: 'smooth'
    });
});
<?php endif; ?>

// Auto-hide alerts
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
});
</script>

</body>
</html>