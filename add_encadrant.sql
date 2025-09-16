-- Script pour ajouter le rôle encadrant à une base existante
USE kuizu_db;

-- Modifier la colonne role pour inclure 'encadrant'
ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'encadrant', 'player') DEFAULT 'player';

-- Ajouter un utilisateur encadrant par défaut
INSERT IGNORE INTO users (username, email, password, role, first_name, last_name) 
VALUES ('encadrant', 'encadrant@sapeurs-pompiers.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'encadrant', 'Encadrant', 'Formation');

-- Vérifier les utilisateurs
SELECT 'Utilisateurs après mise à jour:' as message;
SELECT id, username, role, first_name, last_name FROM users;
