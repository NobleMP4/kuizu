<?php
/**
 * Modèle Answer - Gestion des réponses
 */

require_once ROOT . '/app/core/Model.php';

class Answer extends Model {
    protected $table = 'answers';
    
    /**
     * Crée une nouvelle réponse
     */
    public function createAnswer($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // Détermine l'ordre de la réponse automatiquement
        if (!isset($data['answer_order'])) {
            $sql = "SELECT COALESCE(MAX(answer_order), 0) + 1 FROM {$this->table} WHERE question_id = ?";
            $data['answer_order'] = $this->query($sql, [$data['question_id']])->fetchColumn();
        }
        
        return $this->create($data);
    }
    
    /**
     * Récupère toutes les réponses d'une question
     */
    public function getByQuestionId($questionId) {
        return $this->findAllBy('question_id = ?', [$questionId], 'answer_order', 'ASC');
    }
    
    /**
     * Récupère les réponses correctes d'une question
     */
    public function getCorrectAnswers($questionId) {
        return $this->findAllBy('question_id = ? AND is_correct = 1', [$questionId], 'answer_order', 'ASC');
    }
    
    /**
     * Récupère les réponses incorrectes d'une question
     */
    public function getIncorrectAnswers($questionId) {
        return $this->findAllBy('question_id = ? AND is_correct = 0', [$questionId], 'answer_order', 'ASC');
    }
    
    /**
     * Vérifie si une réponse est correcte
     */
    public function isCorrect($answerId) {
        $answer = $this->findById($answerId);
        return $answer && $answer['is_correct'] == 1;
    }
    
    /**
     * Met à jour l'ordre des réponses après suppression
     */
    public function reorderAnswersAfterDelete($questionId, $deletedOrder) {
        $sql = "UPDATE {$this->table} 
                SET answer_order = answer_order - 1 
                WHERE question_id = ? AND answer_order > ?";
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$questionId, $deletedOrder]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la réorganisation : " . $e->getMessage());
        }
    }
    
    /**
     * Change l'ordre d'une réponse
     */
    public function changeOrder($answerId, $newOrder) {
        $answer = $this->findById($answerId);
        if (!$answer) {
            return false;
        }
        
        $oldOrder = $answer['answer_order'];
        $questionId = $answer['question_id'];
        
        if ($oldOrder == $newOrder) {
            return true;
        }
        
        try {
            $this->db->beginTransaction();
            
            if ($oldOrder < $newOrder) {
                // Déplacer vers le bas
                $sql = "UPDATE {$this->table} 
                        SET answer_order = answer_order - 1 
                        WHERE question_id = ? AND answer_order > ? AND answer_order <= ?";
                $this->query($sql, [$questionId, $oldOrder, $newOrder]);
            } else {
                // Déplacer vers le haut
                $sql = "UPDATE {$this->table} 
                        SET answer_order = answer_order + 1 
                        WHERE question_id = ? AND answer_order >= ? AND answer_order < ?";
                $this->query($sql, [$questionId, $newOrder, $oldOrder]);
            }
            
            // Met à jour la réponse cible
            $this->update($answerId, ['answer_order' => $newOrder]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Supprime une réponse et réorganise les autres
     */
    public function deleteAnswer($answerId) {
        $answer = $this->findById($answerId);
        if (!$answer) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            // Supprime la réponse
            $this->delete($answerId);
            
            // Réorganise les réponses restantes
            $this->reorderAnswersAfterDelete($answer['question_id'], $answer['answer_order']);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Met à jour une réponse comme correcte (et marque les autres comme incorrectes si nécessaire)
     */
    public function setCorrectAnswer($answerId, $exclusive = true) {
        $answer = $this->findById($answerId);
        if (!$answer) {
            return false;
        }
        
        try {
            $this->db->beginTransaction();
            
            if ($exclusive) {
                // Marque toutes les autres réponses de la question comme incorrectes
                $sql = "UPDATE {$this->table} SET is_correct = 0 WHERE question_id = ?";
                $this->query($sql, [$answer['question_id']]);
            }
            
            // Marque cette réponse comme correcte
            $this->update($answerId, ['is_correct' => 1]);
            
            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
    
    /**
     * Compte le nombre de réponses pour une question
     */
    public function countByQuestion($questionId) {
        return $this->count('question_id = ?', [$questionId]);
    }
    
    /**
     * Récupère les statistiques de réponses pour une question dans une session
     */
    public function getAnswerStats($questionId, $sessionId) {
        $sql = "SELECT a.id, a.answer_text, a.is_correct,
                       COUNT(r.id) as response_count,
                       ROUND((COUNT(r.id) * 100.0 / 
                             (SELECT COUNT(*) FROM responses WHERE question_id = ? AND session_id = ?)), 2) as percentage
                FROM answers a
                LEFT JOIN responses r ON a.id = r.answer_id AND r.session_id = ?
                WHERE a.question_id = ?
                GROUP BY a.id, a.answer_text, a.is_correct
                ORDER BY a.answer_order";
        
        return $this->query($sql, [$questionId, $sessionId, $sessionId, $questionId])->fetchAll();
    }
}
?>
