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
require_once __DIR__ . '/../classes/GameSession.php';
require_once __DIR__ . '/../classes/Question.php';

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

    $gameSession = new GameSession();
    $question = new Question();

    switch ($action) {
        case 'create':
            $quiz_id = $input['quiz_id'] ?? null;
            
            if (!$quiz_id) {
                throw new Exception('ID du quiz manquant');
            }
            
            if (!User::isAdmin()) {
                throw new Exception('Seuls les administrateurs peuvent créer des sessions');
            }
            
            $result = $gameSession->create($quiz_id, $current_user['id']);
            break;
            
        case 'start':
            $session_id = $input['session_id'] ?? null;
            
            if (!$session_id) {
                throw new Exception('ID de session manquant');
            }
            
            // Vérifier les permissions
            $session = $gameSession->getById($session_id);
            if (!$session || (!User::isAdmin() || $session['admin_id'] != $current_user['id'])) {
                throw new Exception('Accès non autorisé');
            }
            
            $result = $gameSession->start($session_id);
            break;
            
        case 'pause':
            $session_id = $input['session_id'] ?? null;
            
            if (!$session_id) {
                throw new Exception('ID de session manquant');
            }
            
            // Vérifier les permissions
            $session = $gameSession->getById($session_id);
            if (!$session || (!User::isAdmin() || $session['admin_id'] != $current_user['id'])) {
                throw new Exception('Accès non autorisé');
            }
            
            $result = $gameSession->pause($session_id);
            break;
            
        case 'resume':
            $session_id = $input['session_id'] ?? null;
            
            if (!$session_id) {
                throw new Exception('ID de session manquant');
            }
            
            // Vérifier les permissions
            $session = $gameSession->getById($session_id);
            if (!$session || (!User::isAdmin() || $session['admin_id'] != $current_user['id'])) {
                throw new Exception('Accès non autorisé');
            }
            
            $result = $gameSession->resume($session_id);
            break;
            
        case 'finish':
            $session_id = $input['session_id'] ?? null;
            
            if (!$session_id) {
                throw new Exception('ID de session manquant');
            }
            
            // Vérifier les permissions
            $session = $gameSession->getById($session_id);
            if (!$session || (!User::isAdmin() || $session['admin_id'] != $current_user['id'])) {
                throw new Exception('Accès non autorisé');
            }
            
            $result = $gameSession->finish($session_id);
            break;
            
        case 'set_question':
            $session_id = $input['session_id'] ?? null;
            $question_id = $input['question_id'] ?? null;
            
            if (!$session_id || !$question_id) {
                throw new Exception('ID de session ou de question manquant');
            }
            
            // Vérifier les permissions
            $session = $gameSession->getById($session_id);
            if (!$session || (!User::isAdmin() || $session['admin_id'] != $current_user['id'])) {
                throw new Exception('Accès non autorisé');
            }
            
            $result = $gameSession->setCurrentQuestion($session_id, $question_id);
            break;
            
        case 'join':
            $session_code = $input['session_code'] ?? null;
            
            if (!$session_code) {
                throw new Exception('Code de session manquant');
            }
            
            // Trouver la session par code
            $session = $gameSession->getByCode($session_code);
            if (!$session) {
                throw new Exception('Session non trouvée');
            }
            
            $result = $gameSession->addParticipant($session['id'], $current_user['id']);
            if ($result['success']) {
                $result['session'] = $session;
            }
            break;
            
        case 'get_session':
            $session_id = $input['session_id'] ?? null;
            $session_code = $input['session_code'] ?? null;
            
            if ($session_id) {
                $session = $gameSession->getById($session_id);
            } elseif ($session_code) {
                $session = $gameSession->getByCode($session_code);
            } else {
                throw new Exception('ID ou code de session manquant');
            }
            
            if (!$session) {
                throw new Exception('Session non trouvée');
            }
            
            $result = ['success' => true, 'session' => $session];
            break;
            
        case 'get_participants':
            $session_id = $input['session_id'] ?? null;
            
            if (!$session_id) {
                throw new Exception('ID de session manquant');
            }
            
            // Vérifier les permissions (admin ou participant)
            $session = $gameSession->getById($session_id);
            if (!$session) {
                throw new Exception('Session non trouvée');
            }
            
            $isAdmin = User::isAdmin() && $session['admin_id'] == $current_user['id'];
            $isParticipant = false;
            
            if (!$isAdmin) {
                // Vérifier si l'utilisateur est participant
                $participants = $gameSession->getParticipants($session_id);
                foreach ($participants as $participant) {
                    if ($participant['user_id'] == $current_user['id']) {
                        $isParticipant = true;
                        break;
                    }
                }
            }
            
            if (!$isAdmin && !$isParticipant) {
                throw new Exception('Accès non autorisé');
            }
            
            $participants = $gameSession->getParticipants($session_id);
            $result = ['success' => true, 'participants' => $participants];
            break;
            
        case 'get_leaderboard':
            $session_id = $input['session_id'] ?? null;
            
            if (!$session_id) {
                throw new Exception('ID de session manquant');
            }
            
            $leaderboard = $gameSession->getLeaderboard($session_id);
            $result = ['success' => true, 'leaderboard' => $leaderboard];
            break;
            
        case 'get_current_question':
            $session_id = $input['session_id'] ?? null;
            
            if (!$session_id) {
                throw new Exception('ID de session manquant');
            }
            
            $session = $gameSession->getById($session_id);
            if (!$session) {
                throw new Exception('Session non trouvée');
            }
            
            $current_question = null;
            if ($session['current_question_id']) {
                $current_question = $question->getById($session['current_question_id']);
                
                // Pour les joueurs, ne pas inclure les réponses correctes
                if (!User::isAdmin() || $session['admin_id'] != $current_user['id']) {
                    foreach ($current_question['answers'] as &$answer) {
                        unset($answer['is_correct']);
                    }
                }
            }
            
            $result = [
                'success' => true, 
                'question' => $current_question,
                'session_status' => $session['status']
            ];
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
