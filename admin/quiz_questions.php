<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/Quiz.php';
require_once __DIR__ . '/../classes/Question.php';

requireQuizManager();

$current_user = getCurrentUserOrRedirect();
$quiz_id = $_GET['id'] ?? null;
$is_new = $_GET['new'] ?? false;

if (!$quiz_id) {
    header('Location: quizzes.php');
    exit();
}

$quiz = new Quiz();
$question = new Question();

$quiz_data = $quiz->getById($quiz_id);
if (!$quiz_data || $quiz_data['created_by'] != $current_user['id']) {
    header('Location: quizzes.php');
    exit();
}

$questions = $question->getByQuizId($quiz_id);
$error_message = '';
$success_message = '';

if ($is_new) {
    $success_message = 'Quiz créé avec succès ! Ajoutez maintenant vos questions.';
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'delete_question') {
        $question_id = $_POST['question_id'] ?? '';
        $result = $question->delete($question_id);
        
        if ($result['success']) {
            $success_message = 'Question supprimée avec succès';
            $questions = $question->getByQuizId($quiz_id); // Recharger
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Questions - <?php echo htmlspecialchars($quiz_data['title']); ?> - Kuizu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <h1>🚒 Kuizu</h1>
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
                    <a href="users.php">
                        <span class="menu-icon">👥</span>
                        Utilisateurs
                    </a>
                </li>
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
                        <h1><?php echo htmlspecialchars($quiz_data['title']); ?></h1>
                        <p>Gérer les questions de ce quiz</p>
                        <div class="quiz-info">
                            <span class="info-badge">📝 <?php echo count($questions); ?> question(s)</span>
                            <?php if ($quiz_data['is_active']): ?>
                                <span class="badge badge-success">Actif</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inactif</span>
                            <?php endif; ?>
                            <?php if ($quiz_data['is_locked']): ?>
                                <span class="badge badge-warning">Verrouillé</span>
                            <?php else: ?>
                                <span class="badge badge-info">Ouvert</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="header-buttons">
                        <a href="question_create.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                            ➕ Nouvelle question
                        </a>
                        <a href="quizzes.php" class="btn btn-outline-primary">
                            ← Retour aux quiz
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Actions du quiz -->
            <div class="quiz-actions-bar">
                <div class="action-group">
                    <button onclick="toggleQuizStatus(<?php echo $quiz_id; ?>, 'active')" 
                            class="btn <?php echo $quiz_data['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                        <?php echo $quiz_data['is_active'] ? '⏸️ Désactiver' : '▶️ Activer'; ?>
                    </button>
                    <button onclick="toggleQuizStatus(<?php echo $quiz_id; ?>, 'lock')" 
                            class="btn <?php echo $quiz_data['is_locked'] ? 'btn-info' : 'btn-warning'; ?>">
                        <?php echo $quiz_data['is_locked'] ? '🔓 Déverrouiller' : '🔒 Verrouiller'; ?>
                    </button>
                </div>
                
                <div class="action-group">
                    <button onclick="generateQRCode(<?php echo $quiz_id; ?>)" class="btn btn-secondary">
                        📱 QR Code
                    </button>
                    <?php if (count($questions) > 0): ?>
                        <a href="session_create.php?quiz=<?php echo $quiz_id; ?>" class="btn btn-success">
                            🎮 Lancer une session
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Liste des questions -->
            <?php if (empty($questions)): ?>
                <div class="empty-state">
                    <div class="empty-icon">❓</div>
                    <h3>Aucune question</h3>
                    <p>Ce quiz n'a pas encore de questions. Commencez par créer votre première question.</p>
                    <a href="question_create.php?quiz_id=<?php echo $quiz_id; ?>" class="btn btn-primary">
                        Créer ma première question
                    </a>
                </div>
            <?php else: ?>
                <div class="questions-container">
                    <div class="questions-header">
                        <h2>Questions (<?php echo count($questions); ?>)</h2>
                        <div class="questions-actions">
                            <button onclick="reorderQuestions()" class="btn btn-outline-primary btn-sm">
                                ↕️ Réorganiser
                            </button>
                        </div>
                    </div>
                    
                    <div class="questions-list" id="questions-list">
                        <?php foreach ($questions as $index => $q): ?>
                            <div class="question-card" data-question-id="<?php echo $q['id']; ?>">
                                <div class="question-header">
                                    <div class="question-number">
                                        Question <?php echo $index + 1; ?>
                                    </div>
                                    <div class="question-meta">
                                        <span class="time-badge">⏱️ <?php echo $q['time_limit']; ?>s</span>
                                        <span class="points-badge">🏆 <?php echo $q['points']; ?> pts</span>
                                        <span class="type-badge">
                                            <?php echo $q['question_type'] === 'multiple_choice' ? '🔘 QCM' : '✅ Vrai/Faux'; ?>
                                        </span>
                                    </div>
                                    <div class="question-actions">
                                        <a href="question_edit.php?id=<?php echo $q['id']; ?>" 
                                           class="btn btn-sm btn-primary">Modifier</a>
                                        <button onclick="deleteQuestion(<?php echo $q['id']; ?>)" 
                                                class="btn btn-sm btn-danger">Supprimer</button>
                                    </div>
                                </div>
                                
                                <div class="question-content">
                                    <h4><?php echo htmlspecialchars($q['question_text']); ?></h4>
                                    
                                    <div class="answers-preview">
                                        <?php foreach ($q['answers'] as $answer): ?>
                                            <div class="answer-preview <?php echo $answer['is_correct'] ? 'correct' : ''; ?>">
                                                <span class="answer-indicator">
                                                    <?php echo $answer['is_correct'] ? '✅' : '❌'; ?>
                                                </span>
                                                <?php echo htmlspecialchars($answer['text']); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal QR Code -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>QR Code du Quiz</h3>
                <button onclick="closeQRModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="qrCodeContainer" class="qr-container">
                    <!-- QR Code sera inséré ici -->
                </div>
                <div class="qr-instructions">
                    <p><strong>Instructions :</strong></p>
                    <ul>
                        <li>Les joueurs peuvent scanner ce QR code avec leur téléphone</li>
                        <li>Ils seront redirigés vers la page de connexion</li>
                        <li>Après connexion, ils accèdent automatiquement à ce quiz</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script src="../assets/js/quiz-questions.js"></script>
</body>
</html>

<style>
.quiz-info {
    margin-top: 0.5rem;
    display: flex;
    gap: 0.5rem;
    align-items: center;
    flex-wrap: wrap;
}

.info-badge {
    background: var(--gray-100);
    color: var(--gray-700);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.header-buttons {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
}

.quiz-actions-bar {
    background: var(--white);
    padding: 1.5rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    margin-bottom: 2rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.action-group {
    display: flex;
    gap: 0.75rem;
}

.questions-container {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.questions-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.questions-header h2 {
    margin: 0;
    color: var(--gray-800);
}

.questions-list {
    padding: 1rem;
}

.question-card {
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    overflow: hidden;
    transition: var(--transition);
}

.question-card:hover {
    box-shadow: var(--shadow);
}

.question-header {
    background: var(--gray-50);
    padding: 1rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
}

.question-number {
    font-weight: 600;
    color: var(--primary-color);
    font-size: 1.1rem;
}

.question-meta {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.time-badge, .points-badge, .type-badge {
    background: var(--white);
    color: var(--gray-700);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    font-size: 0.8rem;
    font-weight: 500;
}

.question-actions {
    display: flex;
    gap: 0.5rem;
}

.question-content {
    padding: 1rem;
}

.question-content h4 {
    margin-bottom: 1rem;
    color: var(--gray-800);
    line-height: 1.4;
}

.answers-preview {
    display: grid;
    gap: 0.5rem;
}

.answer-preview {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem;
    background: var(--gray-50);
    border-radius: 4px;
    font-size: 0.9rem;
}

.answer-preview.correct {
    background: #d1fae5;
    color: #065f46;
}

.answer-indicator {
    font-size: 0.8rem;
}

/* Modal */
.modal {
    display: none;
    position: fixed;
    z-index: 2000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: var(--white);
    margin: 5% auto;
    border-radius: var(--border-radius-lg);
    width: 90%;
    max-width: 500px;
    box-shadow: var(--shadow-lg);
}

.modal-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-close {
    background: none;
    border: none;
    font-size: 2rem;
    cursor: pointer;
    color: var(--gray-500);
}

.modal-body {
    padding: 1.5rem;
}

.qr-container {
    text-align: center;
    margin-bottom: 1.5rem;
}

.qr-instructions {
    background: var(--gray-50);
    padding: 1rem;
    border-radius: var(--border-radius);
}

.qr-instructions ul {
    margin: 0.5rem 0 0 1rem;
}

@media (max-width: 768px) {
    .header-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .header-buttons {
        width: 100%;
        justify-content: stretch;
    }
    
    .header-buttons .btn {
        flex: 1;
        text-align: center;
    }
    
    .quiz-actions-bar {
        flex-direction: column;
        align-items: stretch;
    }
    
    .action-group {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .question-header {
        flex-direction: column;
        align-items: stretch;
        gap: 0.5rem;
    }
    
    .question-actions {
        justify-content: center;
    }
}
</style>
