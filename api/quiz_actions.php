<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Gérer les requêtes OPTIONS (CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/Quiz.php';

// Vérifier l'authentification
if (!User::isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit();
}

$current_user = User::getCurrentUser();

try {
    // Récupérer les données de la requête
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $quiz_id = $input['quiz_id'] ?? null;

    if (!$quiz_id) {
        throw new Exception('ID du quiz manquant');
    }

    $quiz = new Quiz();
    
    // Vérifier que l'utilisateur a le droit de modifier ce quiz
    $quiz_data = $quiz->getById($quiz_id);
    if (!$quiz_data) {
        throw new Exception('Quiz non trouvé');
    }
    
    if (!User::canManageQuizzes() || $quiz_data['created_by'] != $current_user['id']) {
        throw new Exception('Accès non autorisé');
    }

    switch ($action) {
        case 'toggle_active':
            $result = $quiz->toggleActive($quiz_id);
            break;
            
        case 'toggle_lock':
            $result = $quiz->toggleLock($quiz_id);
            break;
            
        case 'generate_qr':
            $result = $quiz->generateQRCode($quiz_id);
            break;
            
        case 'get_stats':
            $stats = $quiz->getStats($quiz_id);
            $result = ['success' => true, 'stats' => $stats];
            break;
            
        case 'delete':
            $result = $quiz->delete($quiz_id);
            break;
            
        default:
            throw new Exception('Action non reconnue');
    }

    echo json_encode($result);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
