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
require_once __DIR__ . '/../classes/Question.php';
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

    $question = new Question();
    $quiz = new Quiz();

    switch ($action) {
        case 'reorder':
            $question_orders = $input['question_orders'] ?? [];
            
            if (empty($question_orders)) {
                throw new Exception('Ordre des questions manquant');
            }
            
            // Vérifier que l'utilisateur a le droit de modifier ces questions
            foreach ($question_orders as $question_id => $order) {
                $question_data = $question->getById($question_id);
                if (!$question_data) {
                    throw new Exception('Question non trouvée: ' . $question_id);
                }
                
                $quiz_data = $quiz->getById($question_data['quiz_id']);
                if (!$quiz_data || (!User::canManageQuizzes() || $quiz_data['created_by'] != $current_user['id'])) {
                    throw new Exception('Accès non autorisé');
                }
            }
            
            // Effectuer la réorganisation
            $result = $question->reorder(null, $question_orders);
            break;
            
        case 'delete':
            $question_id = $input['question_id'] ?? null;
            
            if (!$question_id) {
                throw new Exception('ID de la question manquant');
            }
            
            // Vérifier les permissions
            $question_data = $question->getById($question_id);
            if (!$question_data) {
                throw new Exception('Question non trouvée');
            }
            
            $quiz_data = $quiz->getById($question_data['quiz_id']);
            if (!$quiz_data || (!User::canManageQuizzes() || $quiz_data['created_by'] != $current_user['id'])) {
                throw new Exception('Accès non autorisé');
            }
            
            $result = $question->delete($question_id);
            break;
            
        case 'get_by_quiz':
            $quiz_id = $input['quiz_id'] ?? null;
            
            if (!$quiz_id) {
                throw new Exception('ID du quiz manquant');
            }
            
            // Vérifier les permissions
            $quiz_data = $quiz->getById($quiz_id);
            if (!$quiz_data) {
                throw new Exception('Quiz non trouvé');
            }
            
            if (!User::canManageQuizzes() || $quiz_data['created_by'] != $current_user['id']) {
                // Pour les joueurs, vérifier que le quiz est accessible
                if (!$quiz->isAccessible($quiz_id)) {
                    throw new Exception('Quiz non accessible');
                }
            }
            
            $questions = $question->getByQuizId($quiz_id);
            $result = ['success' => true, 'questions' => $questions];
            break;
            
        case 'get_next':
            $quiz_id = $input['quiz_id'] ?? null;
            $current_order = $input['current_order'] ?? 0;
            
            if (!$quiz_id) {
                throw new Exception('ID du quiz manquant');
            }
            
            // Vérifier que le quiz est accessible
            if (!$quiz->isAccessible($quiz_id)) {
                throw new Exception('Quiz non accessible');
            }
            
            $next_question = $question->getNextQuestion($quiz_id, $current_order);
            $result = ['success' => true, 'question' => $next_question];
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
