<?php
/**
 * Classe de base pour tous les modèles
 * Fournit les fonctionnalités communes de base de données
 */

require_once ROOT . '/config/database.php';

class Model {
    protected $db;
    protected $table;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Exécute une requête SELECT
     */
    protected function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            throw new Exception("Erreur de requête : " . $e->getMessage());
        }
    }
    
    /**
     * Récupère tous les enregistrements
     */
    public function findAll($orderBy = 'id', $order = 'ASC') {
        $sql = "SELECT * FROM {$this->table} ORDER BY {$orderBy} {$order}";
        return $this->query($sql)->fetchAll();
    }
    
    /**
     * Récupère un enregistrement par ID
     */
    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = ?";
        return $this->query($sql, [$id])->fetch();
    }
    
    /**
     * Récupère un enregistrement selon des critères
     */
    public function findBy($criteria, $params = []) {
        $sql = "SELECT * FROM {$this->table} WHERE {$criteria}";
        return $this->query($sql, $params)->fetch();
    }
    
    /**
     * Récupère plusieurs enregistrements selon des critères
     */
    public function findAllBy($criteria, $params = [], $orderBy = 'id', $order = 'ASC') {
        $sql = "SELECT * FROM {$this->table} WHERE {$criteria} ORDER BY {$orderBy} {$order}";
        return $this->query($sql, $params)->fetchAll();
    }
    
    /**
     * Insère un nouvel enregistrement
     */
    public function create($data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $fieldsList = implode(', ', $fields);
        
        $sql = "INSERT INTO {$this->table} ({$fieldsList}) VALUES ({$placeholders})";
        
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($data);
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la création : " . $e->getMessage());
        }
    }
    
    /**
     * Met à jour un enregistrement
     */
    public function update($id, $data) {
        $fields = array_keys($data);
        $setClause = implode(' = ?, ', $fields) . ' = ?';
        
        $sql = "UPDATE {$this->table} SET {$setClause} WHERE id = ?";
        $params = array_values($data);
        $params[] = $id;
        
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la mise à jour : " . $e->getMessage());
        }
    }
    
    /**
     * Supprime un enregistrement
     */
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = ?";
        try {
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            throw new Exception("Erreur lors de la suppression : " . $e->getMessage());
        }
    }
    
    /**
     * Compte le nombre d'enregistrements
     */
    public function count($criteria = '1=1', $params = []) {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE {$criteria}";
        return $this->query($sql, $params)->fetchColumn();
    }
}
?>
