<?php
require_once __DIR__ . '/../config/database.php';

class GameSession {
    private $conn;
    private $table_name = "game_sessions";
    
    public $id;
    public $quiz_id;
    public $admin_id;
    public $session_code;
    public $current_question_id;
    public $status;
    public $started_at;
    public $finished_at;
    public $created_at;

    public function __construct($db = null) {
        if ($db) {
            $this->conn = $db;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    /**
     * Créer une nouvelle session de jeu
     */
    public function create($quiz_id, $admin_id) {
        // Générer un code de session unique
        $session_code = $this->generateSessionCode();
        
        // Vérifier qu'il n'y a pas déjà une session active pour ce quiz
        $activeQuery = "SELECT COUNT(*) as active_count FROM " . $this->table_name . " 
                       WHERE quiz_id = :quiz_id AND status IN ('waiting', 'active', 'paused')";
        $activeStmt = $this->conn->prepare($activeQuery);
        $activeStmt->bindParam(":quiz_id", $quiz_id);
        $activeStmt->execute();
        $activeResult = $activeStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($activeResult['active_count'] > 0) {
            return ['success' => false, 'message' => 'Une session est déjà active pour ce quiz'];
        }

        $query = "INSERT INTO " . $this->table_name . " 
                 (quiz_id, admin_id, session_code) 
                 VALUES (:quiz_id, :admin_id, :session_code)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quiz_id", $quiz_id);
        $stmt->bindParam(":admin_id", $admin_id);
        $stmt->bindParam(":session_code", $session_code);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return ['success' => true, 'session_id' => $this->id, 'session_code' => $session_code];
        }

        return ['success' => false, 'message' => 'Erreur lors de la création de la session'];
    }

    /**
     * Générer un code de session unique
     */
    private function generateSessionCode() {
        do {
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= rand(0, 9);
            }
            
            // Vérifier l'unicité
            $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE session_code = :code";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":code", $code);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } while ($result['count'] > 0);
        
        return $code;
    }

    /**
     * Obtenir une session par ID
     */
    public function getById($id) {
        $query = "SELECT gs.*, q.title as quiz_title, q.description as quiz_description,
                 u.username as admin_username,
                 (SELECT COUNT(*) FROM participants WHERE session_id = gs.id) as participant_count
                 FROM " . $this->table_name . " gs 
                 LEFT JOIN quizzes q ON gs.quiz_id = q.id
                 LEFT JOIN users u ON gs.admin_id = u.id
                 WHERE gs.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    /**
     * Obtenir une session par code
     */
    public function getByCode($code) {
        $query = "SELECT gs.*, q.title as quiz_title, q.description as quiz_description,
                 u.username as admin_username,
                 (SELECT COUNT(*) FROM participants WHERE session_id = gs.id) as participant_count
                 FROM " . $this->table_name . " gs 
                 LEFT JOIN quizzes q ON gs.quiz_id = q.id
                 LEFT JOIN users u ON gs.admin_id = u.id
                 WHERE gs.session_code = :code";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":code", $code);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    /**
     * Démarrer une session
     */
    public function start($session_id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = 'active', started_at = CURRENT_TIMESTAMP
                 WHERE id = :id AND status = 'waiting'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $session_id);

        if ($stmt->execute() && $stmt->rowCount() > 0) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Impossible de démarrer la session'];
    }

    /**
     * Mettre en pause une session
     */
    public function pause($session_id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = 'paused'
                 WHERE id = :id AND status = 'active'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $session_id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erreur lors de la mise en pause'];
    }

    /**
     * Reprendre une session
     */
    public function resume($session_id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET status = 'active'
                 WHERE id = :id AND status = 'paused'";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $session_id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erreur lors de la reprise'];
    }

    /**
     * Terminer une session
     */
    public function finish($session_id) {
        try {
            $this->conn->beginTransaction();

            // Mettre à jour le statut de la session
            $query = "UPDATE " . $this->table_name . " 
                     SET status = 'finished', finished_at = CURRENT_TIMESTAMP
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $session_id);
            $stmt->execute();

            // Créer l'historique pour tous les participants
            $this->createGameHistory($session_id);

            $this->conn->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => 'Erreur lors de la finalisation: ' . $e->getMessage()];
        }
    }

    /**
     * Changer la question courante
     */
    public function setCurrentQuestion($session_id, $question_id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET current_question_id = :question_id
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $session_id);
        $stmt->bindParam(":question_id", $question_id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erreur lors du changement de question'];
    }

    /**
     * Ajouter un participant à la session
     */
    public function addParticipant($session_id, $user_id) {
        // Vérifier que la session existe et n'est pas terminée
        $session = $this->getById($session_id);
        if (!$session) {
            return ['success' => false, 'message' => 'Session non trouvée'];
        }
        
        if ($session['status'] === 'finished') {
            return ['success' => false, 'message' => 'Cette session est terminée'];
        }

        // Vérifier si le participant n'est pas déjà inscrit
        $checkQuery = "SELECT COUNT(*) as count FROM participants 
                      WHERE session_id = :session_id AND user_id = :user_id";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bindParam(":session_id", $session_id);
        $checkStmt->bindParam(":user_id", $user_id);
        $checkStmt->execute();
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);

        if ($checkResult['count'] > 0) {
            return ['success' => false, 'message' => 'Vous participez déjà à cette session'];
        }

        $query = "INSERT INTO participants (session_id, user_id) 
                 VALUES (:session_id, :user_id)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->bindParam(":user_id", $user_id);

        if ($stmt->execute()) {
            return ['success' => true, 'participant_id' => $this->conn->lastInsertId()];
        }

        return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
    }

    /**
     * Obtenir les participants d'une session
     */
    public function getParticipants($session_id) {
        $query = "SELECT p.*, u.username, u.first_name, u.last_name
                 FROM participants p
                 LEFT JOIN users u ON p.user_id = u.id
                 WHERE p.session_id = :session_id
                 ORDER BY p.joined_at ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir le classement des participants
     */
    public function getLeaderboard($session_id) {
        $query = "SELECT p.*, u.username, u.first_name, u.last_name,
                 (SELECT COUNT(*) FROM player_responses pr 
                  JOIN answers a ON pr.answer_id = a.id 
                  WHERE pr.participant_id = p.id AND a.is_correct = 1) as correct_answers,
                 (SELECT COUNT(*) FROM player_responses pr 
                  WHERE pr.participant_id = p.id) as total_answers
                 FROM participants p
                 LEFT JOIN users u ON p.user_id = u.id
                 WHERE p.session_id = :session_id
                 ORDER BY p.total_score DESC, correct_answers DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":session_id", $session_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Créer l'historique de jeu
     */
    private function createGameHistory($session_id) {
        $session = $this->getById($session_id);
        if (!$session) return;

        $participants = $this->getParticipants($session_id);

        foreach ($participants as $participant) {
            // Calculer les statistiques
            $statsQuery = "SELECT 
                          COUNT(*) as total_questions,
                          COUNT(CASE WHEN a.is_correct = 1 THEN 1 END) as correct_answers,
                          SUM(pr.response_time) as total_time
                          FROM player_responses pr
                          JOIN answers a ON pr.answer_id = a.id
                          WHERE pr.participant_id = :participant_id";

            $statsStmt = $this->conn->prepare($statsQuery);
            $statsStmt->bindParam(":participant_id", $participant['id']);
            $statsStmt->execute();
            $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

            // Insérer dans l'historique
            $historyQuery = "INSERT INTO game_history 
                           (user_id, quiz_id, session_id, final_score, total_questions, correct_answers, completion_time)
                           VALUES (:user_id, :quiz_id, :session_id, :final_score, :total_questions, :correct_answers, :completion_time)";

            $historyStmt = $this->conn->prepare($historyQuery);
            $historyStmt->bindParam(":user_id", $participant['user_id']);
            $historyStmt->bindParam(":quiz_id", $session['quiz_id']);
            $historyStmt->bindParam(":session_id", $session_id);
            $historyStmt->bindParam(":final_score", $participant['total_score']);
            $historyStmt->bindParam(":total_questions", $stats['total_questions']);
            $historyStmt->bindParam(":correct_answers", $stats['correct_answers']);
            $historyStmt->bindParam(":completion_time", $stats['total_time']);
            $historyStmt->execute();
        }
    }

    /**
     * Obtenir les sessions d'un admin
     */
    public function getByAdmin($admin_id) {
        $query = "SELECT gs.*, q.title as quiz_title,
                 (SELECT COUNT(*) FROM participants WHERE session_id = gs.id) as participant_count
                 FROM " . $this->table_name . " gs 
                 LEFT JOIN quizzes q ON gs.quiz_id = q.id
                 WHERE gs.admin_id = :admin_id
                 ORDER BY gs.created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":admin_id", $admin_id);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Supprimer une session
     */
    public function delete($session_id) {
        // Vérifier que la session n'est pas active
        $session = $this->getById($session_id);
        if ($session && in_array($session['status'], ['active', 'paused'])) {
            return ['success' => false, 'message' => 'Impossible de supprimer une session active'];
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $session_id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erreur lors de la suppression'];
    }
}
?>
