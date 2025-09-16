<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/Quiz.php';
require_once __DIR__ . '/../classes/GameSession.php';

requireQuizManager();

$current_user = getCurrentUserOrRedirect();
$quiz_id = $_GET['quiz'] ?? null;

$quiz = new Quiz();
$gameSession = new GameSession();

// Si un quiz est spécifié, le récupérer
$selected_quiz = null;
if ($quiz_id) {
    $selected_quiz = $quiz->getById($quiz_id);
    if (!$selected_quiz || $selected_quiz['created_by'] != $current_user['id']) {
        header('Location: quizzes.php');
        exit();
    }
}

// Récupérer tous les quiz de l'admin
$quizzes = $quiz->getAll($current_user['id']);

$error_message = '';
$success_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_quiz_id = $_POST['quiz_id'] ?? null;
    
    if (!$selected_quiz_id) {
        $error_message = 'Veuillez sélectionner un quiz';
    } else {
        // Vérifier que le quiz appartient à l'admin
        $quiz_to_use = $quiz->getById($selected_quiz_id);
        if (!$quiz_to_use || $quiz_to_use['created_by'] != $current_user['id']) {
            $error_message = 'Quiz non autorisé';
        } elseif ($quiz_to_use['question_count'] == 0) {
            $error_message = 'Ce quiz n\'a pas de questions';
        } else {
            // Créer la session
            $result = $gameSession->create($selected_quiz_id, $current_user['id']);
            
            if ($result['success']) {
                // Rediriger vers la session
                header('Location: session_manage.php?id=' . $result['session_id']);
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
    <title>Créer une session - Kuizu Sapeurs-Pompiers</title>
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
                <li class="menu-item">
                    <a href="quizzes.php">
                        <span class="menu-icon">❓</span>
                        Mes Quiz
                    </a>
                </li>
                <li class="menu-item active">
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
                        <h1>Créer une session de jeu</h1>
                        <p>Lancez une session interactive pour vos participants</p>
                    </div>
                    <a href="sessions.php" class="btn btn-outline-primary">
                        ← Retour aux sessions
                    </a>
                </div>
            </div>

            <div class="session-create-container">
                <div class="form-card">
                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="session-form">
                        <div class="form-group">
                            <label for="quiz_id">Sélectionner un quiz *</label>
                            <select id="quiz_id" name="quiz_id" required onchange="updateQuizPreview()">
                                <option value="">-- Choisir un quiz --</option>
                                <?php foreach ($quizzes as $quiz_item): ?>
                                    <?php if ($quiz_item['question_count'] > 0): ?>
                                        <option value="<?php echo $quiz_item['id']; ?>" 
                                                <?php echo ($selected_quiz && $selected_quiz['id'] == $quiz_item['id']) ? 'selected' : ''; ?>
                                                data-questions="<?php echo $quiz_item['question_count']; ?>"
                                                data-description="<?php echo htmlspecialchars($quiz_item['description']); ?>">
                                            <?php echo htmlspecialchars($quiz_item['title']); ?> (<?php echo $quiz_item['question_count']; ?> questions)
                                        </option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-help">Seuls les quiz avec des questions sont disponibles</small>
                        </div>

                        <div id="quizPreview" class="quiz-preview" style="display: none;">
                            <!-- Aperçu du quiz sélectionné -->
                        </div>

                        <div class="session-info">
                            <h3>Informations sur la session</h3>
                            <div class="info-grid">
                                <div class="info-item">
                                    <div class="info-icon">👨‍🏫</div>
                                    <div class="info-content">
                                        <strong>Animateur</strong>
                                        <p><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></p>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">📅</div>
                                    <div class="info-content">
                                        <strong>Date et heure</strong>
                                        <p><?php echo date('d/m/Y à H:i'); ?></p>
                                    </div>
                                </div>
                                <div class="info-item">
                                    <div class="info-icon">🔢</div>
                                    <div class="info-content">
                                        <strong>Code de session</strong>
                                        <p>Généré automatiquement</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary btn-lg">
                                🎮 Créer la session
                            </button>
                            <a href="sessions.php" class="btn btn-secondary">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="instructions-card">
                    <h3>📋 Comment ça marche ?</h3>
                    
                    <div class="instruction-steps">
                        <div class="instruction-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Créer la session</h4>
                                <p>Sélectionnez votre quiz et cliquez sur "Créer la session"</p>
                            </div>
                        </div>
                        
                        <div class="instruction-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Partager le code</h4>
                                <p>Communiquez le code de session ou le QR code aux participants</p>
                            </div>
                        </div>
                        
                        <div class="instruction-step">
                            <div class="step-number">3</div>
                            <div class="step-content">
                                <h4>Attendre les joueurs</h4>
                                <p>Les participants rejoignent la session avec le code</p>
                            </div>
                        </div>
                        
                        <div class="instruction-step">
                            <div class="step-number">4</div>
                            <div class="step-content">
                                <h4>Démarrer le quiz</h4>
                                <p>Lancez le quiz quand tous les participants sont prêts</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="tips-section">
                        <h4>💡 Conseils</h4>
                        <ul>
                            <li>Assurez-vous que tous les participants ont accès à un appareil connecté</li>
                            <li>Testez votre quiz avant de le lancer avec les participants</li>
                            <li>Préparez des explications pour les réponses complexes</li>
                            <li>Gardez un rythme adapté à votre audience</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function updateQuizPreview() {
            const select = document.getElementById('quiz_id');
            const preview = document.getElementById('quizPreview');
            const selectedOption = select.options[select.selectedIndex];
            
            if (selectedOption.value) {
                const questions = selectedOption.getAttribute('data-questions');
                const description = selectedOption.getAttribute('data-description');
                const title = selectedOption.text.split(' (')[0];
                
                preview.innerHTML = `
                    <div class="preview-header">
                        <h4>${title}</h4>
                        <span class="question-count">${questions} question(s)</span>
                    </div>
                    <div class="preview-description">
                        <p>${description || 'Aucune description disponible'}</p>
                    </div>
                `;
                preview.style.display = 'block';
            } else {
                preview.style.display = 'none';
            }
        }
        
        // Initialiser l'aperçu si un quiz est présélectionné
        document.addEventListener('DOMContentLoaded', function() {
            updateQuizPreview();
        });
    </script>
</body>
</html>

<style>
.session-create-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    max-width: 1400px;
}

.form-card, .instructions-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    padding: 2rem;
}

.session-form .form-group:last-of-type {
    margin-bottom: 2rem;
}

.quiz-preview {
    background: var(--gray-50);
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
}

.preview-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.preview-header h4 {
    margin: 0;
    color: var(--gray-800);
}

.question-count {
    background: var(--primary-color);
    color: var(--white);
    padding: 0.25rem 0.75rem;
    border-radius: 9999px;
    font-size: 0.875rem;
    font-weight: 500;
}

.preview-description p {
    margin: 0;
    color: var(--gray-600);
    line-height: 1.5;
}

.session-info {
    background: var(--gray-50);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 2rem;
}

.session-info h3 {
    margin-bottom: 1rem;
    color: var(--gray-800);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--white);
    border-radius: var(--border-radius);
}

.info-icon {
    font-size: 1.5rem;
    flex-shrink: 0;
}

.info-content strong {
    display: block;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
    font-size: 0.9rem;
}

.info-content p {
    margin: 0;
    color: var(--gray-600);
    font-size: 0.85rem;
}

.instructions-card h3 {
    color: var(--gray-800);
    margin-bottom: 1.5rem;
}

.instruction-steps {
    margin-bottom: 2rem;
}

.instruction-step {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.5rem;
}

.step-number {
    width: 30px;
    height: 30px;
    background: var(--primary-color);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.9rem;
    flex-shrink: 0;
}

.step-content h4 {
    margin-bottom: 0.5rem;
    color: var(--gray-800);
    font-size: 1rem;
}

.step-content p {
    margin: 0;
    color: var(--gray-600);
    font-size: 0.9rem;
    line-height: 1.4;
}

.tips-section {
    background: var(--gray-50);
    padding: 1rem;
    border-radius: var(--border-radius);
}

.tips-section h4 {
    margin-bottom: 0.75rem;
    color: var(--gray-800);
    font-size: 0.95rem;
}

.tips-section ul {
    margin: 0;
    padding-left: 1rem;
}

.tips-section li {
    color: var(--gray-600);
    font-size: 0.85rem;
    line-height: 1.4;
    margin-bottom: 0.5rem;
}

@media (max-width: 1024px) {
    .session-create-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .instructions-card {
        order: -1;
    }
}

@media (max-width: 768px) {
    .info-grid {
        grid-template-columns: 1fr;
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
