<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/Quiz.php';
require_once __DIR__ . '/../classes/Question.php';

requireQuizManager();

$current_user = getCurrentUserOrRedirect();
$quiz_id = $_GET['quiz_id'] ?? null;

if (!$quiz_id) {
    header('Location: quizzes.php');
    exit();
}

$quiz = new Quiz();
$quiz_data = $quiz->getById($quiz_id);

if (!$quiz_data || $quiz_data['created_by'] != $current_user['id']) {
    header('Location: quizzes.php');
    exit();
}

$error_message = '';
$success_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question_text = trim($_POST['question_text'] ?? '');
    $question_type = $_POST['question_type'] ?? 'multiple_choice';
    $time_limit = intval($_POST['time_limit'] ?? 30);
    $points = intval($_POST['points'] ?? 100);
    
    // Récupération des réponses
    $answers = [];
    $answer_texts = $_POST['answers'] ?? [];
    $correct_answers = $_POST['correct_answers'] ?? [];
    
    // S'assurer que $correct_answers est un tableau
    if (!is_array($correct_answers)) {
        $correct_answers = [$correct_answers];
    }
    
    if (empty($question_text)) {
        $error_message = 'Le texte de la question est requis';
    } elseif (empty($answer_texts) || count($answer_texts) < 2) {
        $error_message = 'Au moins 2 réponses sont requises';
    } elseif (empty($correct_answers)) {
        $error_message = 'Au moins une réponse correcte doit être sélectionnée';
    } else {
        // Préparer les réponses
        foreach ($answer_texts as $index => $text) {
            $text = trim($text);
            if (!empty($text)) {
                $answers[] = [
                    'text' => $text,
                    'is_correct' => in_array($index, $correct_answers)
                ];
            }
        }
        
        if (count($answers) < 2) {
            $error_message = 'Au moins 2 réponses valides sont requises';
        } else {
            $question = new Question();
            $result = $question->create($quiz_id, $question_text, $question_type, $time_limit, $points, $answers);
            
            if ($result['success']) {
                header('Location: quiz_questions.php?id=' . $quiz_id);
                exit();
            } else {
                $error_message = $result['message'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle question - <?php echo htmlspecialchars($quiz_data['title']); ?> - Kuizu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../assets/images/logo.png" alt="Kuizu" width="60" height="60">
                    <h1>Kuizu</h1>
                </div>
                <p>Administration</p>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="dashboard.php">
                        <span class="menu-icon">📊</span>
                        Tableau de bord
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="quizzes.php">
                        <span class="menu-icon">❓</span>
                        Mes Quiz
                    </a>
                </li>
                <li class="menu-item">
                    <a href="sessions.php">
                        <span class="menu-icon">🎮</span>
                        Sessions de jeu
                    </a>
                </li>
                <li class="menu-item">
                    <a href="players.php">
                        <span class="menu-icon">👥</span>
                        Joueurs
                    </a>
                </li>
                <?php if (User::canManageUsers()): ?>
                    <li class="menu-item">
                        <a href="users.php">
                            <span class="menu-icon">⚙️</span>
                            Utilisateurs
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></strong>
                    <small><?php echo $current_user['role'] === 'admin' ? 'Administrateur' : 'Encadrant'; ?></small>
                </div>
                <a href="../auth/logout.php" class="btn btn-outline-primary btn-sm">
                    Déconnexion
                </a>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="admin-content">
            <div class="content-header">
                <div class="header-actions">
                    <div>
                        <h1>Nouvelle question</h1>
                        <p>Quiz : <strong><?php echo htmlspecialchars($quiz_data['title']); ?></strong></p>
                    </div>
                    <a href="quiz_questions.php?id=<?php echo $quiz_id; ?>" class="btn btn-outline-primary">
                        ← Retour aux questions
                    </a>
                </div>
            </div>

            <div class="question-form-container">
                <div class="form-card">
                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="question-form" id="questionForm">
                        <!-- Type de question -->
                        <div class="form-group">
                            <label for="question_type">Type de question</label>
                            <select id="question_type" name="question_type" onchange="updateQuestionType()">
                                <option value="multiple_choice" <?php echo ($question_type ?? 'multiple_choice') === 'multiple_choice' ? 'selected' : ''; ?>>
                                    Choix multiple (QCM)
                                </option>
                                <option value="true_false" <?php echo ($question_type ?? '') === 'true_false' ? 'selected' : ''; ?>>
                                    Vrai/Faux
                                </option>
                            </select>
                        </div>

                        <!-- Texte de la question -->
                        <div class="form-group">
                            <label for="question_text">Texte de la question *</label>
                            <textarea id="question_text" name="question_text" rows="3" required
                                      placeholder="Posez votre question ici..."><?php echo htmlspecialchars($question_text ?? ''); ?></textarea>
                            <small class="form-help">Formulez une question claire et précise</small>
                        </div>

                        <!-- Paramètres -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="time_limit">Temps limite (secondes)</label>
                                <input type="number" id="time_limit" name="time_limit" min="10" max="300" 
                                       value="<?php echo $time_limit ?? 30; ?>">
                                <small class="form-help">Temps accordé pour répondre</small>
                            </div>
                            <div class="form-group">
                                <label for="points">Points</label>
                                <input type="number" id="points" name="points" min="10" max="1000" step="10"
                                       value="<?php echo $points ?? 100; ?>">
                                <small class="form-help">Points attribués pour une bonne réponse</small>
                            </div>
                        </div>

                        <!-- Réponses -->
                        <div class="answers-section">
                            <h3>Réponses possibles</h3>
                            <div id="answersContainer">
                                <!-- Les réponses seront ajoutées ici par JavaScript -->
                            </div>
                            <button type="button" onclick="addAnswer()" class="btn btn-outline-primary btn-sm" id="addAnswerBtn">
                                ➕ Ajouter une réponse
                            </button>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Créer la question
                            </button>
                            <a href="quiz_questions.php?id=<?php echo $quiz_id; ?>" class="btn btn-secondary">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Aperçu -->
                <div class="preview-card">
                    <h3>Aperçu de la question</h3>
                    <div id="questionPreview" class="question-preview">
                        <div class="preview-placeholder">
                            L'aperçu apparaîtra ici pendant que vous tapez...
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/question-form.js"></script>
</body>
</html>

<style>
.question-form-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    max-width: 1400px;
}

.form-card, .preview-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    padding: 2rem;
}

.preview-card {
    position: sticky;
    top: 2rem;
    height: fit-content;
}

.preview-card h3 {
    color: var(--gray-800);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.question-preview {
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    background: var(--gray-50);
}

.preview-placeholder {
    color: var(--gray-500);
    font-style: italic;
    text-align: center;
    padding: 2rem;
}

.answers-section {
    margin: 2rem 0;
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
}

.answers-section h3 {
    color: var(--gray-800);
    margin-bottom: 1rem;
}

.answer-input-group {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    background: var(--white);
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.answer-checkbox {
    flex-shrink: 0;
}

.answer-text {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 1rem;
    padding: 0.5rem;
}

.answer-text:focus {
    outline: none;
    background: var(--gray-50);
    border-radius: 4px;
}

.remove-answer {
    flex-shrink: 0;
    background: none;
    border: none;
    color: var(--error-color);
    cursor: pointer;
    padding: 0.25rem;
    border-radius: 4px;
    transition: var(--transition);
}

.remove-answer:hover {
    background: var(--error-color);
    color: var(--white);
}

.correct-answer {
    background: #d1fae5 !important;
    border-color: var(--success-color) !important;
}

@media (max-width: 1024px) {
    .question-form-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .preview-card {
        position: static;
        order: -1;
    }
}

@media (max-width: 768px) {
    .answer-input-group {
        flex-wrap: wrap;
        gap: 0.5rem;
    }
    
    .answer-text {
        width: 100%;
        min-width: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
