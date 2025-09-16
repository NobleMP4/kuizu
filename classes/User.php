<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;
    private $table_name = "users";
    
    public $id;
    public $username;
    public $email;
    public $password;
    public $role;
    public $first_name;
    public $last_name;
    public $remember_token;
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
     * Inscription d'un nouvel utilisateur
     */
    public function register($username, $email, $password, $first_name, $last_name, $role = 'player') {
        // Vérifier si l'utilisateur existe déjà
        if ($this->userExists($username, $email)) {
            return ['success' => false, 'message' => 'Nom d\'utilisateur ou email déjà utilisé'];
        }

        // Hacher le mot de passe
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $query = "INSERT INTO " . $this->table_name . " 
                 (username, email, password, first_name, last_name, role) 
                 VALUES (:username, :email, :password, :first_name, :last_name, :role)";

        $stmt = $this->conn->prepare($query);

        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $hashed_password);
        $stmt->bindParam(":first_name", $first_name);
        $stmt->bindParam(":last_name", $last_name);
        $stmt->bindParam(":role", $role);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Inscription réussie', 'user_id' => $this->conn->lastInsertId()];
        }

        return ['success' => false, 'message' => 'Erreur lors de l\'inscription'];
    }

    /**
     * Connexion utilisateur
     */
    public function login($username, $password, $remember = false) {
        $query = "SELECT id, username, email, password, role, first_name, last_name 
                 FROM " . $this->table_name . " 
                 WHERE username = :username OR email = :username";

        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (password_verify($password, $row['password'])) {
                // Définir les variables de session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['logged_in'] = true;

                // Si "Se souvenir de moi" est coché
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    $this->setRememberToken($row['id'], $token);
                    
                    // Cookie valide 7 jours
                    setcookie('remember_token', $token, time() + (86400 * 7), '/');
                    setcookie('user_id', $row['id'], time() + (86400 * 7), '/');
                }

                return ['success' => true, 'user' => $row];
            }
        }

        return ['success' => false, 'message' => 'Nom d\'utilisateur ou mot de passe incorrect'];
    }

    /**
     * Vérifier si l'utilisateur existe
     */
    private function userExists($username, $email) {
        $query = "SELECT id FROM " . $this->table_name . " WHERE username = :username OR email = :email";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":username", $username);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        
        return $stmt->rowCount() > 0;
    }

    /**
     * Définir le token de "se souvenir de moi"
     */
    private function setRememberToken($user_id, $token) {
        $hashed_token = password_hash($token, PASSWORD_DEFAULT);
        $query = "UPDATE " . $this->table_name . " SET remember_token = :token WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":token", $hashed_token);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
    }

    /**
     * Vérifier le token de "se souvenir de moi"
     */
    public function checkRememberToken($user_id, $token) {
        $query = "SELECT id, username, role, first_name, last_name, remember_token 
                 FROM " . $this->table_name . " WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row['remember_token'] && password_verify($token, $row['remember_token'])) {
                // Restaurer la session
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];
                $_SESSION['first_name'] = $row['first_name'];
                $_SESSION['last_name'] = $row['last_name'];
                $_SESSION['logged_in'] = true;
                
                return true;
            }
        }
        
        return false;
    }

    /**
     * Déconnexion
     */
    public function logout() {
        // Supprimer le token de "se souvenir de moi"
        if (isset($_SESSION['user_id'])) {
            $this->clearRememberToken($_SESSION['user_id']);
        }

        // Supprimer les cookies
        setcookie('remember_token', '', time() - 3600, '/');
        setcookie('user_id', '', time() - 3600, '/');

        // Détruire la session
        session_unset();
        session_destroy();
    }

    /**
     * Supprimer le token de "se souvenir de moi"
     */
    private function clearRememberToken($user_id) {
        $query = "UPDATE " . $this->table_name . " SET remember_token = NULL WHERE id = :user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->execute();
    }

    /**
     * Vérifier si l'utilisateur est connecté
     */
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }

    /**
     * Vérifier si l'utilisateur est admin
     */
    public static function isAdmin() {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    /**
     * Vérifier si l'utilisateur est encadrant
     */
    public static function isEncadrant() {
        return self::isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'encadrant';
    }

    /**
     * Vérifier si l'utilisateur est admin ou encadrant (peut gérer les quiz)
     */
    public static function canManageQuizzes() {
        return self::isLoggedIn() && isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'encadrant']);
    }

    /**
     * Vérifier si l'utilisateur peut gérer les utilisateurs (admin uniquement)
     */
    public static function canManageUsers() {
        return self::isAdmin();
    }

    /**
     * Obtenir les informations de l'utilisateur connecté
     */
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'role' => $_SESSION['role'],
                'first_name' => $_SESSION['first_name'],
                'last_name' => $_SESSION['last_name']
            ];
        }
        return null;
    }

    /**
     * Obtenir un utilisateur par ID
     */
    public function getUserById($id) {
        $query = "SELECT id, username, email, role, first_name, last_name, created_at 
                 FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
        return null;
    }

    /**
     * Obtenir tous les utilisateurs
     */
    public function getAllUsers() {
        $query = "SELECT id, username, email, role, first_name, last_name, created_at 
                 FROM " . $this->table_name . " ORDER BY created_at DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
