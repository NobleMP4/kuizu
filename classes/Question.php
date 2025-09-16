<?php
require_once __DIR__ . '/../config/database.php';

class Question {
    private $conn;
    private $table_name = "questions";
    
    public $id;
    public $quiz_id;
    public $question_text;
    public $question_type;
    public $time_limit;
    public $points;
    public $question_order;
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
     * Créer une nouvelle question
     */
    public function create($quiz_id, $question_text, $question_type, $time_limit, $points, $answers) {
        try {
            $this->conn->beginTransaction();

            // Obtenir le prochain ordre de question
            $orderQuery = "SELECT COALESCE(MAX(question_order), 0) + 1 as next_order FROM " . $this->table_name . " WHERE quiz_id = :quiz_id";
            $orderStmt = $this->conn->prepare($orderQuery);
            $orderStmt->bindParam(":quiz_id", $quiz_id);
            $orderStmt->execute();
            $next_order = $orderStmt->fetch(PDO::FETCH_ASSOC)['next_order'];

            // Créer la question
            $query = "INSERT INTO " . $this->table_name . " 
                     (quiz_id, question_text, question_type, time_limit, points, question_order) 
                     VALUES (:quiz_id, :question_text, :question_type, :time_limit, :points, :question_order)";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":quiz_id", $quiz_id);
            $stmt->bindParam(":question_text", $question_text);
            $stmt->bindParam(":question_type", $question_type);
            $stmt->bindParam(":time_limit", $time_limit);
            $stmt->bindParam(":points", $points);
            $stmt->bindParam(":question_order", $next_order);

            if (!$stmt->execute()) {
                throw new Exception('Erreur lors de la création de la question');
            }

            $question_id = $this->conn->lastInsertId();

            // Créer les réponses
            $answerQuery = "INSERT INTO answers (question_id, answer_text, is_correct, answer_order) 
                           VALUES (:question_id, :answer_text, :is_correct, :answer_order)";
            $answerStmt = $this->conn->prepare($answerQuery);

            foreach ($answers as $index => $answer) {
                $answer_order = $index + 1;
                $is_correct = $answer['is_correct'] ? 1 : 0; // Convertir en entier
                
                $answerStmt->bindParam(":question_id", $question_id);
                $answerStmt->bindParam(":answer_text", $answer['text']);
                $answerStmt->bindParam(":is_correct", $is_correct);
                $answerStmt->bindParam(":answer_order", $answer_order);

                if (!$answerStmt->execute()) {
                    throw new Exception('Erreur lors de la création des réponses');
                }
            }

            $this->conn->commit();
            return ['success' => true, 'question_id' => $question_id];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Obtenir toutes les questions d'un quiz
     */
    public function getByQuizId($quiz_id) {
        $query = "SELECT q.*, 
                 GROUP_CONCAT(
                     JSON_OBJECT(
                         'id', a.id,
                         'text', a.answer_text,
                         'is_correct', a.is_correct,
                         'order', a.answer_order
                     ) ORDER BY a.answer_order
                 ) as answers
                 FROM " . $this->table_name . " q 
                 LEFT JOIN answers a ON q.id = a.question_id
                 WHERE q.quiz_id = :quiz_id 
                 GROUP BY q.id
                 ORDER BY q.question_order";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quiz_id", $quiz_id);
        $stmt->execute();

        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Parser les réponses JSON
        foreach ($questions as &$question) {
            if ($question['answers']) {
                $answers_string = '[' . $question['answers'] . ']';
                $question['answers'] = json_decode($answers_string, true);
            } else {
                $question['answers'] = [];
            }
        }

        return $questions;
    }

    /**
     * Obtenir une question par ID avec ses réponses
     */
    public function getById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtenir les réponses
            $answerQuery = "SELECT * FROM answers WHERE question_id = :question_id ORDER BY answer_order";
            $answerStmt = $this->conn->prepare($answerQuery);
            $answerStmt->bindParam(":question_id", $id);
            $answerStmt->execute();
            
            $question['answers'] = $answerStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $question;
        }
        
        return null;
    }

    /**
     * Mettre à jour une question
     */
    public function update($id, $question_text, $question_type, $time_limit, $points, $answers) {
        try {
            $this->conn->beginTransaction();

            // Mettre à jour la question
            $query = "UPDATE " . $this->table_name . " 
                     SET question_text = :question_text, question_type = :question_type, 
                         time_limit = :time_limit, points = :points
                     WHERE id = :id";

            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(":id", $id);
            $stmt->bindParam(":question_text", $question_text);
            $stmt->bindParam(":question_type", $question_type);
            $stmt->bindParam(":time_limit", $time_limit);
            $stmt->bindParam(":points", $points);

            if (!$stmt->execute()) {
                throw new Exception('Erreur lors de la mise à jour de la question');
            }

            // Supprimer les anciennes réponses
            $deleteQuery = "DELETE FROM answers WHERE question_id = :question_id";
            $deleteStmt = $this->conn->prepare($deleteQuery);
            $deleteStmt->bindParam(":question_id", $id);
            $deleteStmt->execute();

            // Créer les nouvelles réponses
            $answerQuery = "INSERT INTO answers (question_id, answer_text, is_correct, answer_order) 
                           VALUES (:question_id, :answer_text, :is_correct, :answer_order)";
            $answerStmt = $this->conn->prepare($answerQuery);

            foreach ($answers as $index => $answer) {
                $answer_order = $index + 1;
                $is_correct = $answer['is_correct'] ? 1 : 0; // Convertir en entier
                
                $answerStmt->bindParam(":question_id", $id);
                $answerStmt->bindParam(":answer_text", $answer['text']);
                $answerStmt->bindParam(":is_correct", $is_correct);
                $answerStmt->bindParam(":answer_order", $answer_order);

                if (!$answerStmt->execute()) {
                    throw new Exception('Erreur lors de la mise à jour des réponses');
                }
            }

            $this->conn->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Supprimer une question
     */
    public function delete($id) {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);

        if ($stmt->execute()) {
            return ['success' => true];
        }

        return ['success' => false, 'message' => 'Erreur lors de la suppression de la question'];
    }

    /**
     * Réorganiser l'ordre des questions
     */
    public function reorder($quiz_id, $question_orders) {
        try {
            $this->conn->beginTransaction();

            $query = "UPDATE " . $this->table_name . " SET question_order = :order WHERE id = :id";
            $stmt = $this->conn->prepare($query);

            foreach ($question_orders as $question_id => $order) {
                $stmt->bindParam(":id", $question_id);
                $stmt->bindParam(":order", $order);
                
                if (!$stmt->execute()) {
                    throw new Exception('Erreur lors de la réorganisation');
                }
            }

            $this->conn->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $this->conn->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Obtenir la question suivante dans l'ordre
     */
    public function getNextQuestion($quiz_id, $current_order = 0) {
        $query = "SELECT * FROM " . $this->table_name . " 
                 WHERE quiz_id = :quiz_id AND question_order > :current_order 
                 ORDER BY question_order ASC LIMIT 1";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quiz_id", $quiz_id);
        $stmt->bindParam(":current_order", $current_order);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $question = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Obtenir les réponses
            $answerQuery = "SELECT * FROM answers WHERE question_id = :question_id ORDER BY answer_order";
            $answerStmt = $this->conn->prepare($answerQuery);
            $answerStmt->bindParam(":question_id", $question['id']);
            $answerStmt->execute();
            
            $question['answers'] = $answerStmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $question;
        }
        
        return null;
    }

    /**
     * Obtenir le nombre total de questions d'un quiz
     */
    public function getQuestionCount($quiz_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table_name . " WHERE quiz_id = :quiz_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":quiz_id", $quiz_id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    }

    /**
     * Vérifier si une réponse est correcte
     */
    public function checkAnswer($question_id, $answer_id) {
        $query = "SELECT is_correct FROM answers WHERE id = :answer_id AND question_id = :question_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":answer_id", $answer_id);
        $stmt->bindParam(":question_id", $question_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['is_correct'] == 1;
        }
        
        return false;
    }
}
?>
