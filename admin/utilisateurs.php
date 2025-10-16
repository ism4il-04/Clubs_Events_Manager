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
    ORDER BY u.id ASC
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .container-admin {
            max-width: 1800px;
            margin: 0 auto;
            padding: 20px;
        }
        .advanced-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
            overflow: hidden;
        }
        .table-header {
            background: linear-gradient(135deg, #007bff, #0056b3);
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
            background: linear-gradient(135deg, #007bff, #0056b3);
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
        .badge-admin {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }
        .badge-club {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }
        .badge-etudiant {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            color: white;
        }
        .stats-badge {
            font-size: 0.7rem;
            margin: 2px;
        }
        .badge.bg-primary {
            background: linear-gradient(135deg, #007bff, #0056b3) !important;
        }
        .badge.bg-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706) !important;
        }
        .badge.bg-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb) !important;
        }
        .badge.bg-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563) !important;
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
        .btn-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-warning:hover {
            background: linear-gradient(135deg, #d97706, #b45309);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.3);
            color: white;
        }
        .btn-danger {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-danger:hover {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            color: white;
        }
        .btn-info {
            background: linear-gradient(135deg, #3b82f6, #2563eb);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-info:hover {
            background: linear-gradient(135deg, #2563eb, #1d4ed8);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
            color: white;
        }
        .btn-outline-secondary {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .btn-outline-secondary:hover {
            background: linear-gradient(135deg, #4b5563, #374151);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(107, 114, 128, 0.3);
            color: white;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
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
            border-radius: 12px;
            padding: 15px;
            box-shadow: 0 4px 12px rgba(0, 123, 255, 0.1);
            border-left: 4px solid #007bff;
        }
        .stats-number {
            font-size: 1.8rem;
            font-weight: 700;
            color: #007bff;
        }
        .quick-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .export-btn {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .export-btn:hover {
            background: linear-gradient(135deg, #059669, #047857);
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            color: white;
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
        .status-active { background: #10b981; }
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
        .badge-admin { background: #dc3545 !important; color: white; }
        .badge-club { background: #ffc107 !important; color: #000; }
        .badge-etudiant { background: #17a2b8 !important; color: white; }
        .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .modal-header .btn-close {
            filter: invert(1);
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
            <div class="col-md-5">
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
                        <th width="80">Avatar</th>
                        <th>Utilisateur</th>
                        <th>Rôle</th>
                        <th>Informations</th>
                        <th width="120">Statistiques</th>
                        <th width="150" class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr class="user-row" data-role="<?= $user['role'] ?>" data-status="active">
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
                                    <?= htmlspecialchars($user['annee']." année ".$user['filiere'] ?? 'Non défini') ?>
                                </div>
                                <?php if ($user['telephone']): ?>
                                <div class="small">
                                    <i class="fas fa-phone me-1 text-muted"></i>
                                    <?= htmlspecialchars($user['telephone']) ?>
                                </div>
                                <?php endif; ?>
                                <?php if ($user['email']): ?>
                                <div class="small">
                                    <i class="bi bi-envelope-at me-1 text-muted"></i>
                                    <?= htmlspecialchars($user['email']) ?>
                                </div>
                                <?php endif; ?>
                            <?php elseif ($user['role'] === 'club'): ?>
                                <div class="small">
                                    <i class="fas fa-tag me-1 text-muted"></i>
                                    <?= htmlspecialchars($user['nom_abr'] ?? 'Aucune abréviation') ?>
                                </div>
                                <?php if ($user['email']): ?>
                                <div class="small">
                                    <i class="bi bi-envelope-at me-1 text-muted"></i>
                                    <?= htmlspecialchars($user['email']) ?>
                                </div>
                                <?php endif; ?>
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
                        <td class="text-center">
                            <div class="action-buttons justify-content-center">
                                <button class="btn btn-info btn-table" title="Voir les détails" onclick="viewUserDetails(<?= $user['id'] ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
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
                    <i class="bi bi-plus-circle me-2"></i>Ajouter le premier utilisateur
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
    
    document.querySelectorAll('#usersTable .user-row').forEach(row => {
        const text = row.textContent.toLowerCase();
        const role = row.getAttribute('data-role');
        
        const matchesSearch = text.includes(searchText);
        const matchesRole = !roleFilter || role === roleFilter;
        
        row.style.display = (matchesSearch && matchesRole) ? '' : 'none';
    });
}

function clearFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('roleFilter').value = '';
    filterTable();
}

function exportTable() {
    // Simulation d'export - À implémenter avec une librairie d'export
    alert('Fonction d\'export à implémenter');
}


function viewUserDetails(userId) {
    // Récupérer les données de l'utilisateur et afficher dans une modal
    fetch(`get_user_details.php?id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showUserDetailsModal(data.user);
            } else {
                alert('Erreur lors du chargement des détails: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des détails de l\'utilisateur');
        });
}

function showUserDetailsModal(user) {
    // Créer le contenu de la modal
    const modalContent = `
        <div class="modal fade" id="userDetailsModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-user me-2"></i>Détails de l'utilisateur
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 text-center">
                                ${user.avatar ?
                                    `<img src="../${user.avatar}" class="img-fluid rounded-circle mb-3" style="width: 120px; height: 120px; object-fit: cover;" alt="Avatar">` :
                                    `<div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 120px; height: 120px; font-size: 2rem;">
                                        ${user.nom_utilisateur ? user.nom_utilisateur.substring(0, 2).toUpperCase() : 'U'}
                                    </div>`
                                }
                                <h4>${user.display_name || user.nom_utilisateur}</h4>
                                <span class="badge badge-${user.role}">${user.role}</span>
                            </div>
                            <div class="col-md-8">
                                <h6>Informations générales</h6>
                                <table class="table table-sm">
                                    <tr><td><strong>ID:</strong></td><td>#${user.id}</td></tr>
                                    <tr><td><strong>Nom d'utilisateur:</strong></td><td>${user.nom_utilisateur}</td></tr>
                                    <tr><td><strong>Email:</strong></td><td>${user.email}</td></tr>
                                    ${user.role === 'etudiant' ? `
                                        <tr><td><strong>Nom complet:</strong></td><td>${user.prenom} ${user.nom}</td></tr>
                                        <tr><td><strong>Filière:</strong></td><td>${user.filiere || 'Non défini'}</td></tr>
                                        <tr><td><strong>Année:</strong></td><td>${user.annee || 'Non défini'}</td></tr>
                                        <tr><td><strong>Téléphone:</strong></td><td>${user.telephone || 'Non défini'}</td></tr>
                                    ` : ''}
                                    ${user.role === 'club' ? `
                                        <tr><td><strong>Nom du club:</strong></td><td>${user.clubNom}</td></tr>
                                        <tr><td><strong>Nom abrégé:</strong></td><td>${user.nom_abr || 'Non défini'}</td></tr>
                                        <tr><td><strong>Description:</strong></td><td>${user.description || 'Aucune description'}</td></tr>
                                    ` : ''}
                                </table>

                                <h6>Statistiques</h6>
                                <div class="row">
                                    ${user.role === 'club' ? `
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="h4 text-primary">${user.nb_evenements || 0}</div>
                                                <small>Événements</small>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="h4 text-warning">${user.nb_events_en_attente || 0}</div>
                                                <small>En attente</small>
                                            </div>
                                        </div>
                                    ` : ''}
                                    ${user.role === 'etudiant' ? `
                                        <div class="col-6">
                                            <div class="text-center p-2 bg-light rounded">
                                                <div class="h4 text-info">${user.nb_participations || 0}</div>
                                                <small>Participations</small>
                                            </div>
                                        </div>
                                    ` : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Supprimer l'ancienne modal si elle existe
    const existingModal = document.getElementById('userDetailsModal');
    if (existingModal) {
        existingModal.remove();
    }

    // Ajouter la nouvelle modal au DOM
    document.body.insertAdjacentHTML('beforeend', modalContent);

    // Afficher la modal
    const modal = new bootstrap.Modal(document.getElementById('userDetailsModal'));
    modal.show();
}

// Événements
document.addEventListener('DOMContentLoaded', function() {
    // Filtrage en temps réel
    document.getElementById('searchInput').addEventListener('input', filterTable);
    document.getElementById('roleFilter').addEventListener('change', filterTable);
    
    // Auto-hide alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
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