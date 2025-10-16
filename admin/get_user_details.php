<?php
session_start();
if (!isset($_SESSION['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit;
}

require_once "../includes/db.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur invalide']);
    exit;
}

$user_id = (int)$_GET['id'];

try {
    // Récupérer les détails complets de l'utilisateur
    $stmt = $conn->prepare("
        SELECT u.*, o.description, o.clubNom, o.nom_abr, o.logo,
               e.prenom, e.nom, e.filiere, e.annee, e.telephone, e.dateNaissance, e.photo,
               a.photo as admin_photo,
               CASE 
                 WHEN a.id IS NOT NULL THEN 'admin'
                 WHEN o.id IS NOT NULL THEN 'club' 
                 WHEN e.id IS NOT NULL THEN 'etudiant'
                 ELSE 'non défini'
               END as role,
               (SELECT COUNT(*) FROM evenements ev WHERE ev.organisateur_id = u.id) as nb_evenements,
               (SELECT COUNT(*) FROM participation p WHERE p.etudiant_id = u.id) as nb_participations,
               (SELECT COUNT(*) FROM evenements ev WHERE ev.organisateur_id = u.id AND ev.status = 'En attente') as nb_events_en_attente
        FROM utilisateurs u
        LEFT JOIN admin a ON u.id = a.id
        LEFT JOIN organisateur o ON u.id = o.id
        LEFT JOIN etudiants e ON u.id = e.id
        WHERE u.id = ?
    ");
    
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        exit;
    }
    
    // Préparer les données pour l'affichage
    $user_data = [
        'id' => $user['id'],
        'nom_utilisateur' => $user['nom_utilisateur'],
        'email' => $user['email'],
        'role' => $user['role'],
        'display_name' => '',
        'avatar' => null,
        'nb_evenements' => $user['nb_evenements'],
        'nb_participations' => $user['nb_participations'],
        'nb_events_en_attente' => $user['nb_events_en_attente']
    ];
    
    // Déterminer le nom d'affichage et l'avatar selon le rôle
    if ($user['role'] === 'etudiant') {
        $user_data['display_name'] = trim($user['prenom'] . ' ' . $user['nom']);
        $user_data['avatar'] = $user['photo'];
        $user_data['prenom'] = $user['prenom'];
        $user_data['nom'] = $user['nom'];
        $user_data['filiere'] = $user['filiere'];
        $user_data['annee'] = $user['annee'];
        $user_data['telephone'] = $user['telephone'];
        $user_data['dateNaissance'] = $user['dateNaissance'];
    } elseif ($user['role'] === 'club') {
        $user_data['display_name'] = $user['clubNom'];
        $user_data['avatar'] = $user['logo'];
        $user_data['clubNom'] = $user['clubNom'];
        $user_data['nom_abr'] = $user['nom_abr'];
        $user_data['description'] = $user['description'];
    } elseif ($user['role'] === 'admin') {
        $user_data['display_name'] = $user['nom_utilisateur'];
        $user_data['avatar'] = $user['admin_photo'];
    }
    
    echo json_encode(['success' => true, 'user' => $user_data]);
    
} catch (Exception $e) {
    error_log("Erreur get_user_details.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur lors de la récupération des données']);
}
?>
