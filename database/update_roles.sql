-- Script de mise à jour pour ajouter le rôle "encadrant"
-- À exécuter sur une base de données Kuizu existante

USE kuizu_db;

-- Modifier la colonne role pour inclure 'encadrant'
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'encadrant', 'player') DEFAULT 'player';

-- Ajouter un utilisateur encadrant par défaut (si pas déjà existant)
INSERT IGNORE INTO users (username, email, password, role, first_name, last_name) 
VALUES ('encadrant', 'encadrant@sapeurs-pompiers.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'encadrant', 'Encadrant', 'Formation');

-- Vérification des rôles
SELECT 'Vérification des rôles utilisateurs:' as message;
SELECT role, COUNT(*) as count FROM users GROUP BY role;
