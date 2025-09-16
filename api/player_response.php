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
    $database = new Database();
    $conn = $database->getConnection();
    
    // Récupérer les données de la requête
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    switch ($action) {
        case 'submit_answer':
            $session_id = $input['session_id'] ?? null;
            $question_id = $input['question_id'] ?? null;
            $answer_id = $input['answer_id'] ?? null;
            $response_time = $input['response_time'] ?? 0; // en millisecondes
            
            if (!$session_id || !$question_id || !$answer_id) {
                throw new Exception('Données manquantes');
            }
            
            // Vérifier que l'utilisateur participe à cette session
            $participantQuery = "SELECT id FROM participants 
                               WHERE session_id = :session_id AND user_id = :user_id";
            $participantStmt = $conn->prepare($participantQuery);
            $participantStmt->bindParam(":session_id", $session_id);
            $participantStmt->bindParam(":user_id", $current_user['id']);
            $participantStmt->execute();
            
            if ($participantStmt->rowCount() === 0) {
                throw new Exception('Vous ne participez pas à cette session');
            }
            
            $participant = $participantStmt->fetch(PDO::FETCH_ASSOC);
            $participant_id = $participant['id'];
            
            // Vérifier que la réponse n'a pas déjà été soumise
            $existingQuery = "SELECT COUNT(*) as count FROM player_responses 
                            WHERE participant_id = :participant_id AND question_id = :question_id";
            $existingStmt = $conn->prepare($existingQuery);
            $existingStmt->bindParam(":participant_id", $participant_id);
            $existingStmt->bindParam(":question_id", $question_id);
            $existingStmt->execute();
            $existingResult = $existingStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingResult['count'] > 0) {
                throw new Exception('Vous avez déjà répondu à cette question');
            }
            
            // Vérifier si la réponse est correcte et calculer les points
            $question = new Question();
            $is_correct = $question->checkAnswer($question_id, $answer_id);
            
            $points_earned = 0;
            if ($is_correct) {
                // Récupérer les points de la question
                $questionQuery = "SELECT points, time_limit FROM questions WHERE id = :question_id";
                $questionStmt = $conn->prepare($questionQuery);
                $questionStmt->bindParam(":question_id", $question_id);
                $questionStmt->execute();
                $questionData = $questionStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($questionData) {
                    $base_points = $questionData['points'];
                    $time_limit_ms = $questionData['time_limit'] * 1000; // Convertir en millisecondes
                    
                    // Bonus de vitesse : plus on répond vite, plus on gagne de points
                    $speed_bonus = max(0, 1 - ($response_time / $time_limit_ms)) * 0.5; // Jusqu'à 50% de bonus
                    $points_earned = intval($base_points * (1 + $speed_bonus));
                }
            }
            
            // Enregistrer la réponse
            $responseQuery = "INSERT INTO player_responses 
                            (participant_id, question_id, answer_id, response_time, points_earned)
                            VALUES (:participant_id, :question_id, :answer_id, :response_time, :points_earned)";
            $responseStmt = $conn->prepare($responseQuery);
            $responseStmt->bindParam(":participant_id", $participant_id);
            $responseStmt->bindParam(":question_id", $question_id);
            $responseStmt->bindParam(":answer_id", $answer_id);
            $responseStmt->bindParam(":response_time", $response_time);
            $responseStmt->bindParam(":points_earned", $points_earned);
            $responseStmt->execute();
            
            // Mettre à jour le score total du participant
            $updateScoreQuery = "UPDATE participants 
                               SET total_score = total_score + :points_earned
                               WHERE id = :participant_id";
            $updateScoreStmt = $conn->prepare($updateScoreQuery);
            $updateScoreStmt->bindParam(":points_earned", $points_earned);
            $updateScoreStmt->bindParam(":participant_id", $participant_id);
            $updateScoreStmt->execute();
            
            $result = [
                'success' => true,
                'is_correct' => $is_correct,
                'points_earned' => $points_earned
            ];
            break;
            
        case 'get_responses':
            $session_id = $input['session_id'] ?? null;
            $question_id = $input['question_id'] ?? null;
            
            if (!$session_id) {
                throw new Exception('ID de session manquant');
            }
            
            // Vérifier les permissions (admin de la session)
            $gameSession = new GameSession();
            $session = $gameSession->getById($session_id);
            if (!$session || (!User::isAdmin() || $session['admin_id'] != $current_user['id'])) {
                throw new Exception('Accès non autorisé');
            }
            
            $responseQuery = "SELECT pr.*, u.username, u.first_name, u.last_name, 
                            a.answer_text, a.is_correct,
                            q.question_text, q.points as max_points
                            FROM player_responses pr
                            JOIN participants p ON pr.participant_id = p.id
                            JOIN users u ON p.user_id = u.id
                            JOIN answers a ON pr.answer_id = a.id
                            JOIN questions q ON pr.question_id = q.id
                            WHERE p.session_id = :session_id";
            
            $params = [':session_id' => $session_id];
            
            if ($question_id) {
                $responseQuery .= " AND pr.question_id = :question_id";
                $params[':question_id'] = $question_id;
            }
            
            $responseQuery .= " ORDER BY pr.answered_at ASC";
            
            $responseStmt = $conn->prepare($responseQuery);
            foreach ($params as $key => $value) {
                $responseStmt->bindValue($key, $value);
            }
            $responseStmt->execute();
            
            $responses = $responseStmt->fetchAll(PDO::FETCH_ASSOC);
            $result = ['success' => true, 'responses' => $responses];
            break;
            
        case 'get_my_responses':
            $session_id = $input['session_id'] ?? null;
            
            if (!$session_id) {
                throw new Exception('ID de session manquant');
            }
            
            // Récupérer les réponses de l'utilisateur connecté
            $responseQuery = "SELECT pr.*, a.answer_text, a.is_correct,
                            q.question_text, q.points as max_points
                            FROM player_responses pr
                            JOIN participants p ON pr.participant_id = p.id
                            JOIN answers a ON pr.answer_id = a.id
                            JOIN questions q ON pr.question_id = q.id
                            WHERE p.session_id = :session_id AND p.user_id = :user_id
                            ORDER BY pr.answered_at ASC";
            
            $responseStmt = $conn->prepare($responseQuery);
            $responseStmt->bindParam(":session_id", $session_id);
            $responseStmt->bindParam(":user_id", $current_user['id']);
            $responseStmt->execute();
            
            $responses = $responseStmt->fetchAll(PDO::FETCH_ASSOC);
            $result = ['success' => true, 'responses' => $responses];
            break;
            
        case 'get_question_stats':
            $session_id = $input['session_id'] ?? null;
            $question_id = $input['question_id'] ?? null;
            
            if (!$session_id || !$question_id) {
                throw new Exception('ID de session ou de question manquant');
            }
            
            // Vérifier les permissions (admin de la session)
            $gameSession = new GameSession();
            $session = $gameSession->getById($session_id);
            if (!$session || (!User::isAdmin() || $session['admin_id'] != $current_user['id'])) {
                throw new Exception('Accès non autorisé');
            }
            
            // Statistiques par réponse
            $statsQuery = "SELECT a.id, a.answer_text, a.is_correct,
                         COUNT(pr.id) as response_count,
                         AVG(pr.response_time) as avg_response_time
                         FROM answers a
                         LEFT JOIN player_responses pr ON a.id = pr.answer_id 
                         AND pr.participant_id IN (SELECT id FROM participants WHERE session_id = :session_id)
                         WHERE a.question_id = :question_id
                         GROUP BY a.id
                         ORDER BY a.answer_order";
            
            $statsStmt = $conn->prepare($statsQuery);
            $statsStmt->bindParam(":session_id", $session_id);
            $statsStmt->bindParam(":question_id", $question_id);
            $statsStmt->execute();
            
            $stats = $statsStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Nombre total de participants ayant répondu
            $totalQuery = "SELECT COUNT(DISTINCT pr.participant_id) as total_responses
                          FROM player_responses pr
                          JOIN participants p ON pr.participant_id = p.id
                          WHERE p.session_id = :session_id AND pr.question_id = :question_id";
            
            $totalStmt = $conn->prepare($totalQuery);
            $totalStmt->bindParam(":session_id", $session_id);
            $totalStmt->bindParam(":question_id", $question_id);
            $totalStmt->execute();
            $totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
            
            $result = [
                'success' => true,
                'stats' => $stats,
                'total_responses' => $totalResult['total_responses']
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
