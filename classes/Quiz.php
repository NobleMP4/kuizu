<?php
require_once __DIR__ . '/../config/database.php';

class Quiz {
    private $conn;
    private $table_name = "quizzes";
    
    public $id;
    public $title;
    public $description;
    public $created_by;
    public $is_active;
    public $is_locked;
    public $qr_code;
    public $created_at;
    public $updated_at;

    public function __construct($db = null) {
        if ($db) {
            $this->conn = $db;
        } else {
            $database = new Database();
            $this->conn = $database->getConnection();
        }
    }

    /**
     * Créer un nouveau quiz
     */
    public function create($title, $description, $created_by) {
        $query = "INSERT INTO " . $this->table_name . " 
                 (title, description, created_by) 
                 VALUES (:title, :description, :created_by)";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":created_by", $created_by);

        if ($stmt->execute()) {
            $this->id = $this->conn->lastInsertId();
            return ['success' => true, 'quiz_id' => $this->id];
        }

        return ['success' => false, 'message' => 'Erreur lors de la création du quiz'];
    }

    /**
     * Obtenir tous les quiz
     */
    public function getAll($created_by = null) {
        $query = "SELECT q.*, u.username as creator_name,
                 (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                 FROM " . $this->table_name . " q 
                 LEFT JOIN users u ON q.created_by = u.id";
        
        if ($created_by) {
            $query .= " WHERE q.created_by = :created_by";
        }
        
        $query .= " ORDER BY q.updated_at DESC";

        $stmt = $this->conn->prepare($query);
        
        if ($created_by) {
            $stmt->bindParam(":created_by", $created_by);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtenir un quiz par ID
     */
    public function getById($id) {
        $query = "SELECT q.*, u.username as creator_name,
                 (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                 FROM " . $this->table_name . " q 
                 LEFT JOIN users u ON q.created_by = u.id
                 WHERE q.id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    /**
     * Mettre à jour un quiz
     */
    public function update($id, $title, $description) {
        $query = "UPDATE " . $this->table_name . " 
                 SET title = :title, description = :description, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erreur lors de la mise à jour du quiz'];
    }

    /**
     * Supprimer un quiz
     */
    public function delete($id) {
        // Vérifier si le quiz a des sessions actives
        $sessionQuery = "SELECT COUNT(*) as active_sessions FROM game_sessions 
                        WHERE quiz_id = :id AND status IN ('waiting', 'active', 'paused')";
        $sessionStmt = $this->conn->prepare($sessionQuery);
        $sessionStmt->bindParam(":id", $id);
        $sessionStmt->execute();
        $sessionResult = $sessionStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sessionResult['active_sessions'] > 0) {
            return ['success' => false, 'message' => 'Impossible de supprimer un quiz avec des sessions actives'];
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erreur lors de la suppression du quiz'];
    }

    /**
     * Activer/Désactiver un quiz
     */
    public function toggleActive($id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET is_active = NOT is_active, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erreur lors du changement de statut'];
    }

    /**
     * Verrouiller/Déverrouiller un quiz
     */
    public function toggleLock($id) {
        $query = "UPDATE " . $this->table_name . " 
                 SET is_locked = NOT is_locked, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erreur lors du changement de verrouillage'];
    }

    /**
     * Générer et sauvegarder un QR code pour le quiz
     */
    public function generateQRCode($id) {
        $quiz = $this->getById($id);
        if (!$quiz) {
            return ['success' => false, 'message' => 'Quiz non trouvé'];
        }

        // URL du quiz pour les joueurs
        $quiz_url = "http://localhost:8888/kuizu/auth/login.php?quiz=" . $id;
        
        // Utiliser une API de génération de QR code (QR Server)
        $qr_code_url = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=" . urlencode($quiz_url);
        
        // Sauvegarder l'URL du QR code
        $query = "UPDATE " . $this->table_name . " 
                 SET qr_code = :qr_code, updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":qr_code", $qr_code_url);

        if ($stmt->execute()) {
            return ['success' => true, 'qr_code_url' => $qr_code_url, 'quiz_url' => $quiz_url];
        }

        return ['success' => false, 'message' => 'Erreur lors de la génération du QR code'];
    }

    /**
     * Obtenir les quiz accessibles (non verrouillés et actifs)
     */
    public function getAccessibleQuizzes() {
        $query = "SELECT q.*, u.username as creator_name,
                 (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                 FROM " . $this->table_name . " q 
                 LEFT JOIN users u ON q.created_by = u.id
                 WHERE q.is_active = 1 AND q.is_locked = 0
                 ORDER BY q.updated_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Vérifier si un quiz est accessible pour un joueur
     */
    public function isAccessible($id) {
        $query = "SELECT is_active, is_locked FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $quiz = $stmt->fetch(PDO::FETCH_ASSOC);
            return $quiz['is_active'] == 1 && $quiz['is_locked'] == 0;
        }
        
        return false;
    }

    /**
     * Obtenir les statistiques d'un quiz
     */
    public function getStats($id) {
        $stats = [];

        // Nombre total de parties jouées
        $query = "SELECT COUNT(*) as total_games FROM game_history WHERE quiz_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $stats['total_games'] = $stmt->fetch(PDO::FETCH_ASSOC)['total_games'];

        // Nombre de joueurs uniques
        $query = "SELECT COUNT(DISTINCT user_id) as unique_players FROM game_history WHERE quiz_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $stats['unique_players'] = $stmt->fetch(PDO::FETCH_ASSOC)['unique_players'];

        // Score moyen
        $query = "SELECT AVG(final_score) as average_score FROM game_history WHERE quiz_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['average_score'] = $result['average_score'] ? round($result['average_score'], 1) : 0;

        // Taux de réussite moyen
        $query = "SELECT AVG(correct_answers * 100.0 / total_questions) as success_rate FROM game_history WHERE quiz_id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['success_rate'] = $result['success_rate'] ? round($result['success_rate'], 1) : 0;

        return $stats;
    }
}
?>
