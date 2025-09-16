<?php
/**
 * Modèle Question - Gestion des questions
 */

require_once ROOT . '/app/core/Model.php';

class Question extends Model {
    protected $table = 'questions';
    
    /**
     * Crée une nouvelle question
     */
    public function createQuestion($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Détermine l'ordre de la question automatiquement
        if (!isset($data['question_order'])) {
            $sql = "SELECT COALESCE(MAX(question_order), 0) + 1 FROM {$this->table} WHERE quiz_id = ?";
            $data['question_order'] = $this->query($sql, [$data['quiz_id']])->fetchColumn();
        }
        
        return $this->create($data);
    }
    
    /**
     * Récupère toutes les questions d'un quiz
     */
    public function getByQuizId($quizId) {
        return $this->findAllBy('quiz_id = ?', [$quizId], 'question_order', 'ASC');
    }
    
    /**
     * Récupère une question avec ses réponses
     */
    public function getQuestionWithAnswers($questionId) {
        $question = $this->findById($questionId);
        if (!$question) {
            return null;
        }
        
        $sql = "SELECT * FROM answers WHERE question_id = ? ORDER BY answer_order ASC";
        $question['answers'] = $this->query($sql, [$questionId])->fetchAll();
        
        return $question;
    }
    
    /**
     * Récupère la première question d'un quiz
     */
    public function getFirstQuestion($quizId) {
        return $this->findBy('quiz_id = ? ORDER BY question_order ASC LIMIT 1', [$quizId]);
    }
    
    /**
     * Récupère la question suivante
     */
    public function getNextQuestion($quizId, $currentOrder) {
        return $this->findBy(
            'quiz_id = ? AND question_order > ? ORDER BY question_order ASC LIMIT 1',
            [$quizId, $currentOrder]
        );
    }
    
    /**
     * Récupère la question précédente
     */
    public function getPreviousQuestion($quizId, $currentOrder) {
        return $this->findBy(
            'quiz_id = ? AND question_order < ? ORDER BY question_order DESC LIMIT 1',
            [$quizId, $currentOrder]
        );
    }
    
    /**
     * Met à jour l'ordre des questions après suppression
     */
    public function reorderQuestionsAfterDelete($quizId, $deletedOrder) {
        $sql = "UPDATE {$this->table} 
                SET question_order = question_order - 1 
                WHERE quiz_id = ? AND question_order > ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$quizId, $deletedOrder]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la réorganisation : " . $e->getMessage());
        }
    }
    
    /**
     * Change l'ordre d'une question
     */
    public function changeOrder($questionId, $newOrder) {
        $question = $this->findById($questionId);
        if (!$question) {
            return false;
        }
        
        $oldOrder = $question['question_order'];
        $quizId = $question['quiz_id'];
        
        if ($oldOrder == $newOrder) {
            return true;
        }
        
        try {
            $this->db->beginTransaction();
            
            if ($oldOrder < $newOrder) {
                // Déplacer vers le bas - décrémente les questions entre oldOrder et newOrder
                $sql = "UPDATE {$this->table} 
                        SET question_order = question_order - 1 
                        WHERE quiz_id = ? AND question_order > ? AND question_order <= ?";
                $this->query($sql, [$quizId, $oldOrder, $newOrder]);
            } else {
                // Déplacer vers le haut - incrémente les questions entre newOrder et oldOrder
                $sql = "UPDATE {$this->table} 
                        SET question_order = question_order + 1 
                        WHERE quiz_id = ? AND question_order >= ? AND question_order < ?";
                $this->query($sql, [$quizId, $newOrder, $oldOrder]);
            }
            
            // Met à jour la question cible
            $this->update($questionId, ['question_order' => $newOrder]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Supprime une question et réorganise les autres
     */
    public function deleteQuestion($questionId) {
        $question = $this->findById($questionId);
        if (!$question) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Supprime la question
            $this->delete($questionId);
            
            // Réorganise les questions restantes
            $this->reorderQuestionsAfterDelete($question['quiz_id'], $question['question_order']);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Compte le nombre de réponses correctes pour une question
     */
    public function getCorrectAnswersCount($questionId) {
        $sql = "SELECT COUNT(*) FROM answers WHERE question_id = ? AND is_correct = 1";
        return $this->query($sql, [$questionId])->fetchColumn();
    }
    
    /**
     * Vérifie si une question a au moins une réponse correcte
     */
    public function hasCorrectAnswer($questionId) {
        return $this->getCorrectAnswersCount($questionId) > 0;
    }
}
?>
