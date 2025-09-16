<?php
/**
 * Modèle User - Gestion des utilisateurs
 */

require_once ROOT . '/app/core/Model.php';

class User extends Model {
    protected $table = 'users';
    
    /**
     * Crée un nouvel utilisateur avec mot de passe hashé
     */
    public function createUser($data) {
        // Hachage du mot de passe
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        
        // Ajout des timestamps
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        return $this->create($data);
    }
    
    /**
     * Trouve un utilisateur par nom d'utilisateur
     */
    public function findByUsername($username) {
        return $this->findBy('username = ?', [$username]);
    }
    
    /**
     * Trouve un utilisateur par email
     */
    public function findByEmail($email) {
        return $this->findBy('email = ?', [$email]);
    }
    
    /**
     * Trouve un utilisateur par token de connexion automatique
     */
    public function findByRememberToken($token) {
        return $this->findBy('remember_token = ? AND is_active = 1', [$token]);
    }
    
    /**
     * Vérifie les identifiants de connexion
     */
    public function authenticate($login, $password) {
        // Recherche par username ou email
        $user = $this->findByUsername($login);
        if (!$user) {
            $user = $this->findByEmail($login);
        }
        
        if ($user && password_verify($password, $user['password']) && $user['is_active']) {
            return $user;
        }
        
        return false;
    }
    
    /**
     * Met à jour le token de connexion automatique
     */
    public function updateRememberToken($userId, $token) {
        return $this->update($userId, ['remember_token' => $token]);
    }
    
    /**
     * Supprime le token de connexion automatique
     */
    public function clearRememberToken($userId) {
        return $this->update($userId, ['remember_token' => null]);
    }
    
    /**
     * Vérifie si un username existe déjà
     */
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE username = ?";
        $params = [$username];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->query($sql, $params)->fetchColumn() > 0;
    }
    
    /**
     * Vérifie si un email existe déjà
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        $params = [$email];
        
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        
        return $this->query($sql, $params)->fetchColumn() > 0;
    }
    
    /**
     * Récupère tous les joueurs
     */
    public function getAllPlayers() {
        return $this->findAllBy("role = 'player' AND is_active = 1", [], 'first_name, last_name');
    }
    
    /**
     * Récupère tous les admins
     */
    public function getAllAdmins() {
        return $this->findAllBy("role = 'admin' AND is_active = 1", [], 'first_name, last_name');
    }
    
    /**
     * Met à jour la dernière activité de l'utilisateur
     */
    public function updateLastActivity($userId) {
        return $this->update($userId, ['updated_at' => date('Y-m-d H:i:s')]);
    }
    
    /**
     * Désactive un utilisateur
     */
    public function deactivateUser($userId) {
        return $this->update($userId, ['is_active' => 0]);
    }
    
    /**
     * Active un utilisateur
     */
    public function activateUser($userId) {
        return $this->update($userId, ['is_active' => 1]);
    }
}
?>
