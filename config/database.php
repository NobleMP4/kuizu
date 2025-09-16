<?php
/**
 * Configuration de la base de données
 * Système de quiz Kahoot pour jeunes sapeurs-pompiers
 */

class Database {
    private $host = 'localhost';
    private $db_name = 'kuizu_db';
    private $username = 'root';
    private $password = 'root'; // Modifier selon votre configuration MAMP
    private $port = '8889'; // Port par défaut MAMP
    public $conn;

    public function getConnection() {
        $this->conn = null;
        
        try {
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $exception) {
            echo "Erreur de connexion : " . $exception->getMessage();
            die();
        }

        return $this->conn;
    }
    
    /**
     * Fermer la connexion
     */
    public function closeConnection() {
        $this->conn = null;
    }
    
    /**
     * Vérifier si la base de données existe et est accessible
     */
    public function testConnection() {
        try {
            $conn = $this->getConnection();
            if ($conn) {
                return true;
            }
        } catch (Exception $e) {
            return false;
        }
        return false;
    }
}

// Configuration globale
define('DB_HOST', 'localhost');
define('DB_NAME', 'kuizu_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_PORT', '8889');
define('DB_CHARSET', 'utf8mb4');

// Configuration de session
ini_set('session.cookie_lifetime', 86400 * 7); // 7 jours
ini_set('session.gc_maxlifetime', 86400 * 7);
session_start();

// Fuseau horaire
date_default_timezone_set('Europe/Paris');
?>
