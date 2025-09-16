-- Base de données pour le système de quiz Kahoot pour jeunes sapeurs-pompiers
-- Créé le 16 septembre 2025

DROP DATABASE IF EXISTS kuizu_db;
CREATE DATABASE kuizu_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kuizu_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'encadrant', 'player') DEFAULT 'player',
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    remember_token VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des quiz
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT FALSE,
    is_locked BOOLEAN DEFAULT TRUE,
    qr_code VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des questions
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false') DEFAULT 'multiple_choice',
    time_limit INT DEFAULT 30, -- en secondes
    points INT DEFAULT 100,
    question_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
);

-- Table des réponses possibles
CREATE TABLE answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    question_id INT NOT NULL,
    answer_text VARCHAR(500) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    answer_order INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE
);

-- Table des sessions de jeu
CREATE TABLE game_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    admin_id INT NOT NULL,
    session_code VARCHAR(10) NOT NULL UNIQUE,
    current_question_id INT NULL,
    status ENUM('waiting', 'active', 'paused', 'finished') DEFAULT 'waiting',
    started_at TIMESTAMP NULL,
    finished_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (current_question_id) REFERENCES questions(id) ON DELETE SET NULL
);

-- Table des participants aux sessions
CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    total_score INT DEFAULT 0,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (session_id, user_id)
);

-- Table des réponses des joueurs
CREATE TABLE player_responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participant_id INT NOT NULL,
    question_id INT NOT NULL,
    answer_id INT NOT NULL,
    response_time INT NOT NULL, -- temps en millisecondes
    points_earned INT DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (participant_id) REFERENCES participants(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (answer_id) REFERENCES answers(id) ON DELETE CASCADE
);

-- Table pour l'historique des parties
CREATE TABLE game_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    quiz_id INT NOT NULL,
    session_id INT NOT NULL,
    final_score INT NOT NULL,
    total_questions INT NOT NULL,
    correct_answers INT NOT NULL,
    completion_time INT, -- en secondes
    played_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (session_id) REFERENCES game_sessions(id) ON DELETE CASCADE
);

-- Index pour améliorer les performances
CREATE INDEX idx_quiz_active ON quizzes(is_active, is_locked);
CREATE INDEX idx_questions_quiz ON questions(quiz_id, question_order);
CREATE INDEX idx_answers_question ON answers(question_id, answer_order);
CREATE INDEX idx_session_status ON game_sessions(status);
CREATE INDEX idx_participants_session ON participants(session_id);
CREATE INDEX idx_responses_participant ON player_responses(participant_id);

-- Insertion des utilisateurs par défaut
INSERT INTO users (username, email, password, role, first_name, last_name) VALUES
('admin', 'admin@sapeurs-pompiers.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'Principal'),
('encadrant', 'encadrant@sapeurs-pompiers.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'encadrant', 'Encadrant', 'Formation');
-- Mot de passe par défaut pour tous : "password"

-- Insertion d'un quiz d'exemple
INSERT INTO quizzes (title, description, created_by, is_active) 
VALUES ('Formation Sécurité Incendie', 'Quiz sur les bases de la sécurité incendie pour jeunes sapeurs-pompiers', 1, TRUE);

-- Questions d'exemple
INSERT INTO questions (quiz_id, question_text, question_type, time_limit, points, question_order) VALUES
(1, 'Quelle est la température d\'inflammation du bois ?', 'multiple_choice', 30, 100, 1),
(1, 'Le triangle du feu est composé de trois éléments. Lesquels ?', 'multiple_choice', 45, 150, 2),
(1, 'En cas d\'incendie électrique, peut-on utiliser de l\'eau ?', 'true_false', 20, 100, 3);

-- Réponses d'exemple
INSERT INTO answers (question_id, answer_text, is_correct, answer_order) VALUES
-- Question 1
(1, '200°C', FALSE, 1),
(1, '300°C', TRUE, 2),
(1, '400°C', FALSE, 3),
(1, '500°C', FALSE, 4),
-- Question 2
(2, 'Combustible, Comburant, Chaleur', TRUE, 1),
(2, 'Eau, Air, Feu', FALSE, 2),
(2, 'Oxygène, Hydrogène, Carbone', FALSE, 3),
(2, 'Fumée, Flamme, Cendre', FALSE, 4),
-- Question 3
(3, 'Vrai', FALSE, 1),
(3, 'Faux', TRUE, 2);
