<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/Quiz.php';
require_once __DIR__ . '/../classes/Question.php';

requireQuizManager();

$current_user = getCurrentUserOrRedirect();
$question_id = $_GET['id'] ?? null;

if (!$question_id) {
    header('Location: quizzes.php');
    exit();
}

$question = new Question();
$quiz = new Quiz();

$question_data = $question->getById($question_id);
if (!$question_data) {
    header('Location: quizzes.php');
    exit();
}

$quiz_data = $quiz->getById($question_data['quiz_id']);
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
            $result = $question->update($question_id, $question_text, $question_type, $time_limit, $points, $answers);
            
            if ($result['success']) {
                header('Location: quiz_questions.php?id=' . $quiz_data['id']);
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
    <title>Modifier la question - <?php echo htmlspecialchars($quiz_data['title']); ?> - Kuizu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../assets/images/logo.png" alt="Kuizu" width="40" height="40">
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
                        <h1>Modifier la question</h1>
                        <p>Quiz : <strong><?php echo htmlspecialchars($quiz_data['title']); ?></strong></p>
                    </div>
                    <a href="quiz_questions.php?id=<?php echo $quiz_data['id']; ?>" class="btn btn-outline-primary">
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
                                <option value="multiple_choice" <?php echo $question_data['question_type'] === 'multiple_choice' ? 'selected' : ''; ?>>
                                    Choix multiple (QCM)
                                </option>
                                <option value="true_false" <?php echo $question_data['question_type'] === 'true_false' ? 'selected' : ''; ?>>
                                    Vrai/Faux
                                </option>
                            </select>
                        </div>

                        <!-- Texte de la question -->
                        <div class="form-group">
                            <label for="question_text">Texte de la question *</label>
                            <textarea id="question_text" name="question_text" rows="3" required
                                      placeholder="Posez votre question ici..."><?php echo htmlspecialchars($question_data['question_text']); ?></textarea>
                            <small class="form-help">Formulez une question claire et précise</small>
                        </div>

                        <!-- Paramètres -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="time_limit">Temps limite (secondes)</label>
                                <input type="number" id="time_limit" name="time_limit" min="10" max="300" 
                                       value="<?php echo $question_data['time_limit']; ?>">
                                <small class="form-help">Temps accordé pour répondre</small>
                            </div>
                            <div class="form-group">
                                <label for="points">Points</label>
                                <input type="number" id="points" name="points" min="10" max="1000" step="10"
                                       value="<?php echo $question_data['points']; ?>">
                                <small class="form-help">Points attribués pour une bonne réponse</small>
                            </div>
                        </div>

                        <!-- Réponses -->
                        <div class="answers-section">
                            <h3>Réponses possibles</h3>
                            <div id="answersContainer">
                                <!-- Les réponses seront chargées ici par JavaScript -->
                            </div>
                            <button type="button" onclick="addAnswer()" class="btn btn-outline-primary btn-sm" id="addAnswerBtn">
                                ➕ Ajouter une réponse
                            </button>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Modifier la question
                            </button>
                            <a href="quiz_questions.php?id=<?php echo $quiz_data['id']; ?>" class="btn btn-secondary">
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
    <script>
        // Données de la question existante
        const EXISTING_QUESTION = <?php echo json_encode($question_data); ?>;
        
        // Variables globales
        let answerCount = 0;
        let currentQuestionType = '<?php echo $question_data['question_type']; ?>';
        
        document.addEventListener('DOMContentLoaded', function() {
            initializeEditForm();
            setupPreview();
            initFormValidation();
        });
        
        function initializeEditForm() {
            // Charger les réponses existantes
            const container = document.getElementById('answersContainer');
            container.innerHTML = '';
            answerCount = 0;
            
            if (EXISTING_QUESTION.answers && EXISTING_QUESTION.answers.length > 0) {
                EXISTING_QUESTION.answers.forEach(answer => {
                    addAnswer(answer.answer_text, answer.is_correct == 1);
                });
            } else {
                // Si pas de réponses, initialiser selon le type
                initializeAnswers();
            }
            
            updatePreview();
        }
        
        // Réutiliser les fonctions du question-form.js
        function initializeAnswers() {
            const container = document.getElementById('answersContainer');
            container.innerHTML = '';
            answerCount = 0;
            
            if (currentQuestionType === 'true_false') {
                addAnswer('Vrai', false);
                addAnswer('Faux', true);
                document.getElementById('addAnswerBtn').style.display = 'none';
            } else {
                // Ajouter 4 réponses par défaut pour les QCM
                addAnswer('', false);
                addAnswer('', false);
                addAnswer('', false);
                addAnswer('', false);
                document.getElementById('addAnswerBtn').style.display = 'inline-flex';
            }
            
            updatePreview();
        }
        
        function updateQuestionType() {
            const newType = document.getElementById('question_type').value;
            
            if (newType !== currentQuestionType) {
                if (confirm('Changer le type de question effacera toutes les réponses actuelles. Continuer ?')) {
                    currentQuestionType = newType;
                    initializeAnswers();
                } else {
                    // Restaurer la sélection précédente
                    document.getElementById('question_type').value = currentQuestionType;
                }
            }
        }
        
        function addAnswer(text = '', isCorrect = false) {
            const container = document.getElementById('answersContainer');
            const index = answerCount++;
            
            const answerDiv = document.createElement('div');
            answerDiv.className = 'answer-input-group';
            answerDiv.dataset.index = index;
            
            const inputType = currentQuestionType === 'true_false' ? 'radio' : 'checkbox';
            const inputName = currentQuestionType === 'true_false' ? 'correct_answers' : 'correct_answers[]';
            
            answerDiv.innerHTML = `
                <input type="${inputType}" 
                       name="${inputName}" 
                       value="${index}" 
                       class="answer-checkbox"
                       ${isCorrect ? 'checked' : ''}
                       onchange="updateAnswerStatus(this)">
                <input type="text" 
                       name="answers[]" 
                       value="${text}"
                       placeholder="Tapez votre réponse ici..."
                       class="answer-text"
                       oninput="updatePreview()"
                       ${currentQuestionType === 'true_false' ? 'readonly' : ''}>
                ${currentQuestionType !== 'true_false' ? 
                    `<button type="button" onclick="removeAnswer(this)" class="remove-answer" title="Supprimer cette réponse">✖</button>` 
                    : ''}
            `;
            
            if (isCorrect) {
                answerDiv.classList.add('correct-answer');
            }
            
            container.appendChild(answerDiv);
            
            // Limiter à 6 réponses maximum pour les QCM
            if (currentQuestionType === 'multiple_choice' && answerCount >= 6) {
                document.getElementById('addAnswerBtn').style.display = 'none';
            }
        }
        
        function removeAnswer(button) {
            const answerDiv = button.closest('.answer-input-group');
            answerDiv.remove();
            
            // Réafficher le bouton d'ajout si nécessaire
            if (currentQuestionType === 'multiple_choice') {
                const remainingAnswers = document.querySelectorAll('.answer-input-group').length;
                if (remainingAnswers < 6) {
                    document.getElementById('addAnswerBtn').style.display = 'inline-flex';
                }
            }
            
            updatePreview();
        }
        
        function updateAnswerStatus(checkbox) {
            const answerDiv = checkbox.closest('.answer-input-group');
            
            if (checkbox.checked) {
                answerDiv.classList.add('correct-answer');
            } else {
                answerDiv.classList.remove('correct-answer');
            }
            
            // Pour les questions vrai/faux, décocher l'autre option
            if (currentQuestionType === 'true_false') {
                const allCheckboxes = document.querySelectorAll('.answer-checkbox');
                allCheckboxes.forEach(cb => {
                    if (cb !== checkbox) {
                        cb.checked = false;
                        cb.closest('.answer-input-group').classList.remove('correct-answer');
                    }
                });
            }
            
            updatePreview();
        }
        
        function setupPreview() {
            const questionText = document.getElementById('question_text');
            const timeLimit = document.getElementById('time_limit');
            const points = document.getElementById('points');
            
            questionText.addEventListener('input', updatePreview);
            timeLimit.addEventListener('input', updatePreview);
            points.addEventListener('input', updatePreview);
            
            updatePreview();
        }
        
        function updatePreview() {
            const preview = document.getElementById('questionPreview');
            const questionText = document.getElementById('question_text').value.trim();
            const timeLimit = document.getElementById('time_limit').value;
            const points = document.getElementById('points').value;
            
            if (!questionText) {
                preview.innerHTML = '<div class="preview-placeholder">L\'aperçu apparaîtra ici pendant que vous tapez...</div>';
                return;
            }
            
            const answers = Array.from(document.querySelectorAll('.answer-text'))
                .map((input, index) => ({
                    text: input.value.trim(),
                    isCorrect: input.closest('.answer-input-group').querySelector('.answer-checkbox').checked,
                    index: index
                }))
                .filter(answer => answer.text);
            
            const typeIcon = currentQuestionType === 'multiple_choice' ? '🔘' : '✅';
            const typeLabel = currentQuestionType === 'multiple_choice' ? 'QCM' : 'Vrai/Faux';
            
            preview.innerHTML = `
                <div class="preview-header">
                    <div class="preview-meta">
                        <span class="preview-type">${typeIcon} ${typeLabel}</span>
                        <span class="preview-time">⏱️ ${timeLimit}s</span>
                        <span class="preview-points">🏆 ${points} pts</span>
                    </div>
                </div>
                <div class="preview-question">
                    <h4>${escapeHtml(questionText)}</h4>
                </div>
                <div class="preview-answers">
                    ${answers.map((answer, index) => `
                        <div class="preview-answer ${answer.isCorrect ? 'preview-correct' : ''}">
                            <span class="preview-answer-letter">${String.fromCharCode(65 + index)}</span>
                            <span class="preview-answer-text">${escapeHtml(answer.text)}</span>
                            ${answer.isCorrect ? '<span class="preview-correct-indicator">✓</span>' : ''}
                        </div>
                    `).join('')}
                </div>
                ${answers.length === 0 ? '<div class="preview-no-answers">Aucune réponse saisie</div>' : ''}
            `;
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        function initFormValidation() {
            const form = document.getElementById('questionForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                    }
                });
            }
        }
        
        function validateForm() {
            const questionText = document.getElementById('question_text').value.trim();
            const answers = Array.from(document.querySelectorAll('.answer-text'))
                .map(input => input.value.trim())
                .filter(text => text.length > 0);
            
            const correctAnswers = Array.from(document.querySelectorAll('.answer-checkbox:checked'));
            
            // Vérifications
            if (!questionText) {
                showFormError('Le texte de la question est requis');
                return false;
            }
            
            if (answers.length < 2) {
                showFormError('Au moins 2 réponses sont requises');
                return false;
            }
            
            if (correctAnswers.length === 0) {
                showFormError('Au moins une réponse correcte doit être sélectionnée');
                return false;
            }
            
            if (currentQuestionType === 'true_false' && answers.length !== 2) {
                showFormError('Les questions Vrai/Faux doivent avoir exactement 2 réponses');
                return false;
            }
            
            return true;
        }
        
        function showFormError(message) {
            // Supprimer les anciennes erreurs
            const existingError = document.querySelector('.form-error');
            if (existingError) {
                existingError.remove();
            }
            
            // Créer la nouvelle erreur
            const errorDiv = document.createElement('div');
            errorDiv.className = 'alert alert-error form-error';
            errorDiv.textContent = message;
            
            // Insérer avant le formulaire
            const form = document.getElementById('questionForm');
            form.parentNode.insertBefore(errorDiv, form);
            
            // Faire défiler vers l'erreur
            errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
            
            // Auto-suppression après 5 secondes
            setTimeout(() => {
                if (errorDiv.parentNode) {
                    errorDiv.remove();
                }
            }, 5000);
        }
    </script>
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

/* Styles pour l'aperçu */
.preview-header {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--gray-200);
}

.preview-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--gray-600);
    flex-wrap: wrap;
}

.preview-type, .preview-time, .preview-points {
    background: var(--white);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid var(--gray-200);
}

.preview-question h4 {
    color: var(--gray-800);
    margin-bottom: 1rem;
    line-height: 1.4;
}

.preview-answers {
    display: grid;
    gap: 0.5rem;
}

.preview-answer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.preview-answer.preview-correct {
    background: #d1fae5;
    border-color: var(--success-color);
    color: #065f46;
}

.preview-answer-letter {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: var(--gray-200);
    color: var(--gray-700);
    border-radius: 50%;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.preview-correct .preview-answer-letter {
    background: var(--success-color);
    color: var(--white);
}

.preview-answer-text {
    flex: 1;
}

.preview-correct-indicator {
    color: var(--success-color);
    font-weight: bold;
    flex-shrink: 0;
}

.preview-no-answers {
    text-align: center;
    color: var(--gray-500);
    font-style: italic;
    padding: 1rem;
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
