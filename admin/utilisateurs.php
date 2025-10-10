<?php
session_start();
if (!isset($_SESSION['email']) ) {
    header("Location: ../login.php");
    exit();
}

require_once "../includes/db.php";

// [Le code PHP de gestion des actions reste identique à la version précédente]
// ... (même code de traitement des actions CRUD)

// Récupérer tous les utilisateurs avec leurs rôles et informations
$stmt = $conn->prepare("SELECT u.*,o.description, 
           CASE 
             WHEN a.id IS NOT NULL THEN 'admin'
             WHEN o.id IS NOT NULL THEN 'club' 
             WHEN e.id IS NOT NULL THEN 'etudiant'
             ELSE 'non défini'
           END as role,
           e.prenom, e.nom, e.filiere, e.annee, e.telephone, e.dateNaissance,
           o.clubNom, o.nom_abr, o.logo,
           (SELECT COUNT(*) FROM evenements ev WHERE ev.organisateur_id = u.id) as nb_evenements,
           (SELECT COUNT(*) FROM participation p WHERE p.etudiant_id = u.id) as nb_participations,
           (SELECT COUNT(*) FROM evenements ev WHERE ev.organisateur_id = u.id AND ev.status = 'En attente') as nb_events_en_attente
    FROM utilisateurs u
    LEFT JOIN admin a ON u.id = a.id
    LEFT JOIN organisateur o ON u.id = o.id
    LEFT JOIN etudiants e ON u.id = e.id
    ORDER BY u.id DESC
");
$stmt->execute();
$users = $stmt->fetchAll();

$message = $_GET['message'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Admin</title>
    <link rel="stylesheet" href="../includes/style.css">
    <link rel="stylesheet" href="../includes/style2.css">
    <link rel="stylesheet" href="../includes/style3.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .container-admin {
            max-width: 1800px;
            margin: 0 auto;
            padding: 20px;
        }
        .advanced-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            overflow: hidden;
        }
        .table-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
        }
        .table-responsive {
            border-radius: 0 0 12px 12px;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 0.9rem;
        }
        .role-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-admin { background: #dc3545; color: white; }
        .badge-club { background: #ffc107; color: #000; }
        .badge-etudiant { background: #17a2b8; color: white; }
        .stats-badge {
            font-size: 0.7rem;
            margin: 2px;
        }
        .action-buttons {
            display: flex;
            gap: 6px;
            flex-wrap: nowrap;
        }
        .btn-table {
            padding: 4px 8px;
            font-size: 0.75rem;
            border-radius: 6px;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(102, 126, 234, 0.05);
        }
        .user-info-cell {
            max-width: 200px;
            min-width: 150px;
        }
        .filters-bar {
            background: #f8f9fa;
            padding: 15px 20px;
            border-bottom: 1px solid #dee2e6;
        }
        .search-box {
            max-width: 300px;
        }
        .stats-card {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-left: 4px solid #667eea;
        }
        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #667eea;
        }
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .export-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
        }
        .club-logo-small {
            width: 30px;
            height: 30px;
            border-radius: 6px;
            object-fit: cover;
            margin-right: 8px;
        }
        .status-indicator {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 5px;
        }
        .status-active { background: #28a745; }
        .status-inactive { background: #6c757d; }
        .table th {
            font-weight: 600;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            border-bottom: 2px solid #e9ecef;
        }
        .table td {
            vertical-align: middle;
            font-size: 0.9rem;
            padding: 12px 8px;
        }
        .pagination-container {
            background: white;
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
        }
    </style>
</head>
<body>

<?php include 'admin_header.php'; ?>
<div class="tabs">
    <div class="tab" onclick="navigateTo('dashboard.php')">Tableau de bord</div>
    <div class="tab" onclick="navigateTo('demandes_evenements.php')">Demandes d'événements</div>
    <div class="tab" onclick="navigateTo('evenements.php')">Tous les événements</div>
    <div class="tab" onclick="navigateTo('clubs.php')">Gestion des clubs</div>
    <div class="tab active" onclick="navigateTo('utilisateurs.php')">Utilisateurs</div>
</div>
<div class="container-admin">
    <!-- En-tête avec statistiques -->
    <div class="table-header">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h2 class="mb-0"><i class="fas fa-users me-2"></i>Gestion des Utilisateurs</h2>
                <p class="mb-0 opacity-75">Administration complète de tous les utilisateurs de la plateforme</p>
            </div>
            
        </div>
    </div>

    <!-- Cartes de statistiques -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?= count($users) ?></div>
                <div class="text-muted">Utilisateurs totaux</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?= count(array_filter($users, fn($u) => $u['role'] === 'etudiant')) ?></div>
                <div class="text-muted">Étudiants</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?= count(array_filter($users, fn($u) => $u['role'] === 'club')) ?></div>
                <div class="text-muted">Clubs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="stats-number"><?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></div>
                <div class="text-muted">Administrateurs</div>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Barre de filtres et recherche -->
    <div class="filters-bar">
        <div class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input type="text" id="searchInput" class="form-control search-box" placeholder="Rechercher un utilisateur...">
                </div>
            </div>
            <div class="col-md-3">
                <select id="roleFilter" class="form-select">
                    <option value="">Tous les rôles</option>
                    <option value="admin">Administrateurs</option>
                    <option value="club">Clubs</option>
                    <option value="etudiant">Étudiants</option>
                </select>
            </div>
            <div class="col-md-3">
                <select id="statusFilter" class="form-select">
                    <option value="">Tous les statuts</option>
                    <option value="active">Actifs</option>
                    <option value="inactive">Inactifs</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary w-100" onclick="clearFilters()">
                    <i class="fas fa-refresh me-1"></i>Réinitialiser
                </button>
            </div>
        </div>
    </div>

    <!-- Tableau avancé -->
    <div class="advanced-table">
        <div class="table-responsive">
            <table class="table table-hover mb-0" id="usersTable">
                <thead class="table-light">
                    <tr>
                        <th width="50">ID</th>
                        <th width="80">Avatar</th>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Informations</th>
                        <th width="120">Statistiques</th>
                        <th width="120">Statut</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="user-row" data-role="<?= $user['role'] ?>" data-status="active">
                        <td class="fw-bold text-muted">#<?= $user['id'] ?></td>
                        <td>
                            <?php if ($user['role'] === 'club' && $user['logo']): ?>
                                <img src="../<?= htmlspecialchars($user['logo']) ?>" 
                                     class="club-logo-small" 
                                     alt="Logo"
                                     onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="user-avatar">
                                    <?= strtoupper(substr($user['nom_utilisateur'], 0, 2)) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td class="user-info-cell">
                            <div class="fw-semibold">
                                <?php if ($user['role'] === 'etudiant' && $user['prenom']): ?>
                                    <?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?>
                                <?php elseif ($user['role'] === 'club' && $user['clubNom']): ?>
                                    <?= htmlspecialchars($user['clubNom']) ?>
                                <?php else: ?>
                                    <?= htmlspecialchars($user['nom_utilisateur']) ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small">@<?= htmlspecialchars($user['nom_utilisateur']) ?></div>
                            <div class="text-muted small"><?= htmlspecialchars($user['email']) ?></div>
                        </td>
                        <td>
                            <span class="role-badge badge-<?= $user['role'] ?>">
                                <i class="fas fa-<?= $user['role'] === 'admin' ? 'crown' : ($user['role'] === 'club' ? 'users' : 'user-graduate') ?> me-1"></i>
                                <?= htmlspecialchars($user['role']) ?>
                            </span>
                        </td>
                        <td class="user-info-cell">
                            <?php if ($user['role'] === 'etudiant'): ?>
                                <div class="small">
                                    <i class="fas fa-graduation-cap me-1 text-muted"></i>
                                    <?= htmlspecialchars($user['filiere'] ?? 'Non défini') ?>
                                </div>
                                <div class="small">
                                    <i class="fas fa-calendar me-1 text-muted"></i>
                                    <?= htmlspecialchars($user['annee'] ?? 'Non défini') ?>
                                </div>
                                <?php if ($user['telephone']): ?>
                                <div class="small">
                                    <i class="fas fa-phone me-1 text-muted"></i>
                                    <?= htmlspecialchars($user['telephone']) ?>
                                </div>
                                <?php endif; ?>
                            <?php elseif ($user['role'] === 'club'): ?>
                                <div class="small">
                                    <i class="fas fa-tag me-1 text-muted"></i>
                                    <?= htmlspecialchars($user['nom_abr'] ?? 'Aucune abréviation') ?>
                                </div>
                                <?php if ($user['description']): ?>
                                <div class="small text-truncate" title="<?= htmlspecialchars($user['description']) ?>">
                                    <i class="fas fa-info-circle me-1 text-muted"></i>
                                    <?= htmlspecialchars(substr($user['description'], 0, 50)) ?>
                                    <?= strlen($user['description']) > 50 ? '...' : '' ?>
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="small text-muted">
                                    <i class="fas fa-shield-alt me-1"></i>
                                    Accès administrateur complet
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['role'] === 'club'): ?>
                                <span class="badge bg-primary stats-badge">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?= $user['nb_evenements'] ?> événements
                                </span>
                                <?php if ($user['nb_events_en_attente'] > 0): ?>
                                <span class="badge bg-warning stats-badge">
                                    <?= $user['nb_events_en_attente'] ?> en attente
                                </span>
                                <?php endif; ?>
                            <?php elseif ($user['role'] === 'etudiant'): ?>
                                <span class="badge bg-info stats-badge">
                                    <i class="fas fa-ticket-alt me-1"></i>
                                    <?= $user['nb_participations'] ?> participations
                                </span>
                            <?php else: ?>
                                <span class="badge bg-secondary stats-badge">
                                    <i class="fas fa-infinity me-1"></i>
                                    Accès illimité
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="status-indicator status-active"></span>
                            <span class="small text-success">Actif</span>
                            <div class="small text-muted">
                                <?php if ($user['role'] === 'etudiant' && $user['dateNaissance']): ?>
                                    <?= date('d/m/Y', strtotime($user['dateNaissance'])) ?>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="text-center">
                            <div class="action-buttons justify-content-center">
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="editer">
                                    <button type="submit" class="btn btn-warning btn-table" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </form>
                                
                                <?php if ($user['id'] != $_SESSION['id']): ?>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="action" value="supprimer">
                                    <button type="submit" class="btn btn-danger btn-table" 
                                            title="Supprimer"
                                            onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet utilisateur ?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                <span class="btn btn-outline-secondary btn-table disabled" title="Vous">
                                    <i class="fas fa-user"></i>
                                </span>
                                <?php endif; ?>
                                
                                <button class="btn btn-info btn-table" title="Voir le profil" onclick="viewProfile(<?= $user['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="pagination-container">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <span class="text-muted small">
                        Affichage de <strong><?= count($users) ?></strong> utilisateur(s)
                    </span>
                </div>
                <div class="col-md-6">
                    <nav aria-label="Page navigation">
                        <ul class="pagination pagination-sm justify-content-end mb-0">
                            <li class="page-item disabled"><a class="page-link" href="#">Précédent</a></li>
                            <li class="page-item active"><a class="page-link" href="#">1</a></li>
                            <li class="page-item"><a class="page-link" href="#">2</a></li>
                            <li class="page-item"><a class="page-link" href="#">3</a></li>
                            <li class="page-item"><a class="page-link" href="#">Suivant</a></li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicateur vide -->
    <?php if (empty($users)): ?>
        <div class="text-center py-5">
            <div class="text-muted">
                <i class="fas fa-users fa-4x mb-3 opacity-50"></i>
                <h4>Aucun utilisateur trouvé</h4>
                <p class="mb-4">Commencez par ajouter votre premier utilisateur à la plateforme.</p>
                <button type="button" class="btn btn-success btn-lg" onclick="showAddForm()">
                    <i class="fas fa-plus-circle me-2"></i>Ajouter le premier utilisateur
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Formulaire (identique à la version précédente) -->
<div class="form-container" id="userForm" style="display: none;">
    <!-- ... Le formulaire d'ajout/modification reste identique ... -->
</div>

<script>
// Fonctions d'affichage du formulaire (identiques)
function showAddForm() {
    document.getElementById('userForm').style.display = 'block';
    document.querySelector('html').scrollTo({ top: 0, behavior: 'smooth' });
}

function hideForm() {
    document.getElementById('userForm').style.display = 'none';
}

// Fonctions de filtrage et recherche
function filterTable() {
    const searchText = document.getElementById('searchInput').value.toLowerCase();
    const roleFilter = document.getElementById('roleFilter').value;
    const statusFilter = document.getElementById('statusFilter').value;
    
    document.querySelectorAll('#usersTable .user-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        const role = row.getAttribute('data-role');
        const status = row.getAttribute('data-status');
        
        const matchesSearch = text.includes(searchText);
        const matchesRole = !roleFilter || role === roleFilter;
        const matchesStatus = !statusFilter || status === statusFilter;
        
        row.style.display = (matchesSearch && matchesRole && matchesStatus) ? '' : 'none';
    });
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('roleFilter').value = '';
    document.getElementById('statusFilter').value = '';
    filterTable();
}

function exportTable() {
    // Simulation d'export - À implémenter avec une librairie d'export
    alert('Fonction d\'export à implémenter');
}

function viewProfile(userId) {
    // Simulation de vue profil - À implémenter
    alert('Visualisation du profil utilisateur #' + userId);
}

// Événements
document.addEventListener('DOMContentLoaded', function() {
    // Filtrage en temps réel
    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('roleFilter').addEventListener('change', filterTable);
    document.getElementById('statusFilter').addEventListener('change', filterTable);
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });
    
    // Tri des colonnes (basique)
    document.querySelectorAll('th').forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', () => {
            alert('Tri à implémenter pour: ' + header.textContent);
        });
    });
});

// Fonction pour le formulaire (identique)
function toggleRoleFields() {
    const role = document.getElementById('roleSelect').value;
    document.getElementById('etudiantFields').style.display = role === 'etudiant' ? 'block' : 'none';
    document.getElementById('clubFields').style.display = role === 'club' ? 'block' : 'none';
    document.getElementById('adminFields').style.display = role === 'admin' ? 'block' : 'none';
}
</script>

</body>
</html>