<?php
/**
 * Modèle Quiz - Gestion des quiz
 */

require_once ROOT . '/app/core/Model.php';

class Quiz extends Model {
    protected $table = 'quizzes';
    
    /**
     * Crée un nouveau quiz
     */
    public function createQuiz($data) {
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }
    
    /**
     * Récupère tous les quiz avec les informations du créateur
     */
    public function getAllWithCreator() {
        $sql = "SELECT q.*, u.username as creator_name, u.first_name, u.last_name,
                       (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                FROM {$this->table} q 
                LEFT JOIN users u ON q.created_by = u.id 
                WHERE q.is_active = 1
                ORDER BY q.updated_at DESC";
        return $this->query($sql)->fetchAll();
    }
    
    /**
     * Récupère un quiz avec ses questions et réponses
     */
    public function getQuizWithQuestions($quizId) {
        // Quiz principal
        $quiz = $this->findById($quizId);
        if (!$quiz) {
            return null;
        }
        
        // Questions du quiz
        $sql = "SELECT q.*, 
                       (SELECT COUNT(*) FROM answers WHERE question_id = q.id) as answer_count
                FROM questions q 
                WHERE q.quiz_id = ? 
                ORDER BY q.question_order ASC";
        $questions = $this->query($sql, [$quizId])->fetchAll();
        
        // Réponses pour chaque question
        foreach ($questions as &$question) {
            $sql = "SELECT * FROM answers WHERE question_id = ? ORDER BY answer_order ASC";
            $question['answers'] = $this->query($sql, [$question['id']])->fetchAll();
        }
        
        $quiz['questions'] = $questions;
        return $quiz;
    }
    
    /**
     * Récupère les quiz créés par un utilisateur
     */
    public function getByCreator($userId) {
        return $this->findAllBy('created_by = ? AND is_active = 1', [$userId], 'updated_at', 'DESC');
    }
    
    /**
     * Récupère les quiz débloqués (accessibles aux joueurs)
     */
    public function getUnlockedQuizzes() {
        $sql = "SELECT q.*, u.username as creator_name,
                       (SELECT COUNT(*) FROM questions WHERE quiz_id = q.id) as question_count
                FROM {$this->table} q 
                LEFT JOIN users u ON q.created_by = u.id 
                WHERE q.is_active = 1 AND q.is_locked = 0
                ORDER BY q.title ASC";
        return $this->query($sql)->fetchAll();
    }
    
    /**
     * Verrouille un quiz
     */
    public function lockQuiz($quizId) {
        return $this->update($quizId, ['is_locked' => 1, 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Déverrouille un quiz
     */
    public function unlockQuiz($quizId) {
        return $this->update($quizId, ['is_locked' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Vérifie si un quiz est accessible (déverrouillé et actif)
     */
    public function isAccessible($quizId) {
        $quiz = $this->findById($quizId);
        return $quiz && $quiz['is_active'] && !$quiz['is_locked'];
    }
    
    /**
     * Met à jour un quiz
     */
    public function updateQuiz($quizId, $data) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->update($quizId, $data);
    }
    
    /**
     * Désactive un quiz (suppression logique)
     */
    public function deactivateQuiz($quizId) {
        return $this->update($quizId, [
            'is_active' => 0, 
            'updated_at' => date('Y-m-d H:i:s')
        ]);
    }
    
    /**
     * Compte le nombre de questions d'un quiz
     */
    public function getQuestionCount($quizId) {
        $sql = "SELECT COUNT(*) FROM questions WHERE quiz_id = ?";
        return $this->query($sql, [$quizId])->fetchColumn();
    }
    
    /**
     * Récupère les statistiques d'un quiz
     */
    public function getQuizStats($quizId) {
        $sql = "SELECT 
                    COUNT(DISTINCT s.id) as total_sessions,
                    COUNT(DISTINCT p.user_id) as total_participants,
                    AVG(p.total_score) as avg_score,
                    MAX(p.total_score) as max_score
                FROM sessions s
                LEFT JOIN participants p ON s.id = p.session_id
                WHERE s.quiz_id = ?";
        return $this->query($sql, [$quizId])->fetch();
    }
}
?>
