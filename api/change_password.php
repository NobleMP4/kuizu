<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/User.php';

// Vérifier les permissions (encadrants et admins)
requireQuizManager();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit();
}

if (!isset($_POST['user_id']) || !isset($_POST['new_password'])) {
    echo json_encode(['success' => false, 'message' => 'Données manquantes']);
    exit();
}

$user_id = intval($_POST['user_id']);
$new_password = $_POST['new_password'];

// Validation du mot de passe
if (strlen($new_password) < 6) {
    echo json_encode(['success' => false, 'message' => 'Le mot de passe doit contenir au moins 6 caractères']);
    exit();
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Vérifier que l'utilisateur à modifier existe et est un joueur
    $checkQuery = "SELECT id, role FROM users WHERE id = :user_id AND role = 'player'";
    $checkStmt = $conn->prepare($checkQuery);
    $checkStmt->bindParam(':user_id', $user_id);
    $checkStmt->execute();
    $user = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Joueur non trouvé']);
        exit();
    }
    
    // Hasher le nouveau mot de passe
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Mettre à jour le mot de passe
    $updateQuery = "UPDATE users SET password = :password, updated_at = NOW() WHERE id = :user_id";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bindParam(':password', $hashed_password);
    $updateStmt->bindParam(':user_id', $user_id);
    
    if ($updateStmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Mot de passe changé avec succès'
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => 'Erreur lors de la mise à jour du mot de passe'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
?>
