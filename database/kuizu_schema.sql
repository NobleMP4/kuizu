-- Script SQL pour créer la base de données Kuizu
-- Base de données pour système de quiz type Kahoot pour jeunes sapeurs-pompiers

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS kuizu_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE kuizu_db;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'player') DEFAULT 'player',
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    remember_token VARCHAR(100) NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Table des quiz
CREATE TABLE quizzes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    created_by INT NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    is_locked BOOLEAN DEFAULT TRUE,
    time_per_question INT DEFAULT 30, -- temps en secondes
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Table des questions
CREATE TABLE questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    question_text TEXT NOT NULL,
    question_order INT NOT NULL,
    time_limit INT DEFAULT 30, -- temps en secondes pour cette question
    points INT DEFAULT 100,
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
CREATE TABLE sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quiz_id INT NOT NULL,
    session_code VARCHAR(10) NOT NULL UNIQUE,
    created_by INT NOT NULL,
    status ENUM('waiting', 'active', 'finished') DEFAULT 'waiting',
    current_question_id INT NULL,
    current_question_started_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ended_at TIMESTAMP NULL,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (current_question_id) REFERENCES questions(id) ON DELETE SET NULL
);

-- Table des participants à une session
CREATE TABLE participants (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    user_id INT NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_score INT DEFAULT 0,
    is_connected BOOLEAN DEFAULT TRUE,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_participant (session_id, user_id)
);

-- Table des réponses des joueurs
CREATE TABLE responses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    question_id INT NOT NULL,
    user_id INT NOT NULL,
    answer_id INT NULL,
    response_time INT NOT NULL, -- temps de réponse en millisecondes
    points_earned INT DEFAULT 0,
    answered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (answer_id) REFERENCES answers(id) ON DELETE SET NULL,
    UNIQUE KEY unique_response (session_id, question_id, user_id)
);

-- Index pour optimiser les performances
CREATE INDEX idx_users_username ON users(username);
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_remember_token ON users(remember_token);
CREATE INDEX idx_quizzes_created_by ON quizzes(created_by);
CREATE INDEX idx_questions_quiz_id ON questions(quiz_id);
CREATE INDEX idx_answers_question_id ON answers(question_id);
CREATE INDEX idx_sessions_code ON sessions(session_code);
CREATE INDEX idx_sessions_status ON sessions(status);
CREATE INDEX idx_participants_session_user ON participants(session_id, user_id);
CREATE INDEX idx_responses_session_question ON responses(session_id, question_id);

-- Insertion des données de test
-- Utilisateur admin par défaut
INSERT INTO users (username, email, password, role, first_name, last_name) VALUES 
('admin', 'admin@kuizu.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Admin', 'Kuizu'),
('testeur', 'testeur@kuizu.fr', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'player', 'Jean', 'Testeur');

-- Quiz de test sur les sapeurs-pompiers
INSERT INTO quizzes (title, description, created_by, is_active, is_locked, time_per_question) VALUES 
('Connaissances Générales JSP', 'Quiz sur les connaissances de base des jeunes sapeurs-pompiers', 1, TRUE, FALSE, 20);

-- Questions de test
INSERT INTO questions (quiz_id, question_text, question_order, time_limit, points) VALUES 
(1, 'Quel est le numéro d\'urgence pour les pompiers en France ?', 1, 15, 100),
(1, 'Que signifie l\'acronyme JSP ?', 2, 20, 100),
(1, 'Quelle est la couleur principale des véhicules de pompiers en France ?', 3, 15, 100);

-- Réponses pour la première question
INSERT INTO answers (question_id, answer_text, is_correct, answer_order) VALUES 
(1, '15', FALSE, 1),
(1, '17', FALSE, 2),
(1, '18', TRUE, 3),
(1, '112', FALSE, 4);

-- Réponses pour la deuxième question
INSERT INTO answers (question_id, answer_text, is_correct, answer_order) VALUES 
(2, 'Jeune Sapeur-Pompier', TRUE, 1),
(2, 'Junior Service Public', FALSE, 2),
(2, 'Jeune Secours Populaire', FALSE, 3),
(2, 'Jeu de Société Pompier', FALSE, 4);

-- Réponses pour la troisième question
INSERT INTO answers (question_id, answer_text, is_correct, answer_order) VALUES 
(3, 'Bleu', FALSE, 1),
(3, 'Rouge', TRUE, 2),
(3, 'Jaune', FALSE, 3),
(3, 'Vert', FALSE, 4);
