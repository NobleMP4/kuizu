<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/GameSession.php';
require_once __DIR__ . '/../classes/Question.php';

requireQuizManager();

$current_user = getCurrentUserOrRedirect();
$session_id = $_GET['id'] ?? null;

if (!$session_id) {
    header('Location: sessions.php');
    exit();
}

$gameSession = new GameSession();
$question = new Question();

$session = $gameSession->getById($session_id);
if (!$session || $session['admin_id'] != $current_user['id']) {
    header('Location: sessions.php');
    exit();
}

// Obtenir les questions du quiz
$questions = $question->getByQuizId($session['quiz_id']);
$participants = $gameSession->getParticipants($session_id);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Session: <?php echo htmlspecialchars($session['quiz_title']); ?> - Kuizu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/session.css">
</head>
<body>
    <div class="session-layout">
        <!-- Header de session -->
        <header class="session-header">
            <div class="header-content">
                <div class="session-info">
                    <h1><?php echo htmlspecialchars($session['quiz_title']); ?></h1>
                    <div class="session-meta">
                        <span class="session-code">Code: <strong><?php echo $session['session_code']; ?></strong></span>
                        <span class="session-status status-<?php echo $session['status']; ?>">
                            <?php 
                            $status_labels = [
                                'waiting' => '⏳ En attente',
                                'active' => '▶️ Actif',
                                'paused' => '⏸️ En pause',
                                'finished' => '✅ Terminé'
                            ];
                            echo $status_labels[$session['status']] ?? $session['status'];
                            ?>
                        </span>
                        <span class="participant-count">👥 <span id="participantCount"><?php echo count($participants); ?></span> participants</span>
                    </div>
                </div>
                
                <div class="session-actions">
                    <?php if ($session['status'] === 'waiting'): ?>
                        <button onclick="startSession()" class="btn btn-success" id="startBtn">
                            ▶️ Démarrer
                        </button>
                    <?php elseif ($session['status'] === 'active'): ?>
                        <button onclick="pauseSession()" class="btn btn-warning" id="pauseBtn">
                            ⏸️ Pause
                        </button>
                    <?php elseif ($session['status'] === 'paused'): ?>
                        <button onclick="resumeSession()" class="btn btn-success" id="resumeBtn">
                            ▶️ Reprendre
                        </button>
                    <?php endif; ?>
                    
                    <?php if (in_array($session['status'], ['waiting', 'active', 'paused'])): ?>
                        <button onclick="finishSession()" class="btn btn-danger" id="finishBtn">
                            🏁 Terminer
                        </button>
                    <?php endif; ?>
                    
                    <button onclick="showQRCode()" class="btn btn-secondary">
                        📱 QR Code
                    </button>
                    
                    <a href="sessions.php" class="btn btn-outline-primary">
                        ← Retour
                    </a>
                </div>
            </div>
        </header>

        <div class="session-content">
            <!-- Colonne de gauche: Contrôle des questions -->
            <div class="questions-panel">
                <div class="panel-header">
                    <h3>Questions (<?php echo count($questions); ?>)</h3>
                    <div class="question-progress">
                        <span id="currentQuestionIndex">0</span> / <?php echo count($questions); ?>
                    </div>
                </div>
                
                <div class="questions-list">
                    <?php foreach ($questions as $index => $q): ?>
                        <div class="question-item" data-question-id="<?php echo $q['id']; ?>" data-index="<?php echo $index; ?>">
                            <div class="question-header">
                                <div class="question-number">Q<?php echo $index + 1; ?></div>
                                <div class="question-info">
                                    <div class="question-text"><?php echo htmlspecialchars(substr($q['question_text'], 0, 80) . (strlen($q['question_text']) > 80 ? '...' : '')); ?></div>
                                    <div class="question-meta">
                                        <span>⏱️ <?php echo $q['time_limit']; ?>s</span>
                                        <span>🏆 <?php echo $q['points']; ?> pts</span>
                                    </div>
                                </div>
                                <button onclick="setCurrentQuestion(<?php echo $q['id']; ?>, <?php echo $index; ?>)" 
                                        class="btn btn-primary btn-sm set-question-btn">
                                    Afficher
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Colonne du milieu: Question actuelle -->
            <div class="current-question-panel">
                <div class="panel-header">
                    <h3>Question actuelle</h3>
                    <div class="question-timer" id="questionTimer" style="display: none;">
                        <div class="timer-display">
                            <span class="timer-value" id="timerValue">30</span>
                            <span class="timer-label">secondes</span>
                        </div>
                        <div class="timer-bar">
                            <div class="timer-progress" id="timerProgress"></div>
                        </div>
                    </div>
                </div>
                
                <div class="question-display" id="questionDisplay">
                    <div class="no-question">
                        <div class="no-question-icon">❓</div>
                        <h4>Aucune question affichée</h4>
                        <p>Sélectionnez une question pour commencer</p>
                    </div>
                </div>
                
                <div class="question-controls" id="questionControls" style="display: none;">
                    <button onclick="showAnswers()" class="btn btn-warning" id="showAnswersBtn">
                        👁️ Révéler les réponses
                    </button>
                    <button onclick="nextQuestion()" class="btn btn-success" id="nextQuestionBtn">
                        ➡️ Question suivante
                    </button>
                </div>
            </div>

            <!-- Colonne de droite: Participants -->
            <div class="participants-panel">
                <div class="panel-header">
                    <h3>Participants</h3>
                    <button onclick="refreshParticipants()" class="btn btn-outline-primary btn-sm">
                        🔄 Actualiser
                    </button>
                </div>
                
                <div class="participants-list" id="participantsList">
                    <!-- Les participants seront chargés ici -->
                </div>
                
                <div class="leaderboard" id="leaderboard" style="display: none;">
                    <h4>Classement</h4>
                    <div class="leaderboard-list" id="leaderboardList">
                        <!-- Le classement sera affiché ici -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal QR Code -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>QR Code de la session</h3>
                <button onclick="closeQRModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="qr-display">
                <div class="qr-code">
                    <?php 
                    require_once __DIR__ . '/../config/app.php';
                    $session_url = getSessionJoinUrl($session['session_code']);
                    $qr_url = QR_CODE_API . '?size=' . QR_CODE_SIZE . '&data=' . urlencode($session_url);
                    ?>
                    <img src="<?php echo $qr_url; ?>" alt="QR Code de la session">
                </div>
                    <div class="session-details">
                        <div class="detail-item">
                            <strong>Code de session:</strong>
                            <span class="session-code-large"><?php echo $session['session_code']; ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        // Variables globales
        const SESSION_ID = <?php echo $session_id; ?>;
        const SESSION_STATUS = '<?php echo $session['status']; ?>';
        const QUESTIONS = <?php echo json_encode($questions); ?>;
        
        let currentQuestionId = null;
        let currentQuestionIndex = -1;
        let questionTimer = null;
        let timeLeft = 0;
        let refreshInterval = null;
        
        // Initialiser la session
        document.addEventListener('DOMContentLoaded', function() {
            initializeSession();
            startRefreshInterval();
        });
        
        function initializeSession() {
            refreshParticipants();
            
            // Si la session est active, démarrer les mises à jour
            if (SESSION_STATUS === 'active') {
                document.getElementById('leaderboard').style.display = 'block';
            }
        }
        
        function startRefreshInterval() {
            // Actualiser les participants toutes les 2 secondes
            refreshInterval = setInterval(function() {
                refreshParticipants();
                if (currentQuestionId && SESSION_STATUS === 'active') {
                    updateQuestionStats();
                }
            }, 2000);
        }
        
        async function startSession() {
            try {
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'start',
                        session_id: SESSION_ID
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            }
        }
        
        async function pauseSession() {
            try {
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'pause',
                        session_id: SESSION_ID
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            }
        }
        
        async function resumeSession() {
            try {
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'resume',
                        session_id: SESSION_ID
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            }
        }
        
        async function finishSession() {
            if (!confirm('Êtes-vous sûr de vouloir terminer cette session ? Cette action est irréversible.')) {
                return;
            }
            
            try {
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'finish',
                        session_id: SESSION_ID
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    location.reload();
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            }
        }
        
        async function setCurrentQuestion(questionId, index) {
            try {
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'set_question',
                        session_id: SESSION_ID,
                        question_id: questionId
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    currentQuestionId = questionId;
                    currentQuestionIndex = index;
                    displayQuestion(QUESTIONS[index]);
                    updateQuestionProgress();
                    
                    // Marquer la question comme active
                    document.querySelectorAll('.question-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    document.querySelector(`[data-question-id="${questionId}"]`).classList.add('active');
                } else {
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            }
        }
        
        function displayQuestion(question) {
            const display = document.getElementById('questionDisplay');
            const controls = document.getElementById('questionControls');
            const timer = document.getElementById('questionTimer');
            
            display.innerHTML = `
                <div class="question-content">
                    <h4>${question.question_text}</h4>
                    <div class="answers-list">
                        ${question.answers.map((answer, index) => `
                            <div class="answer-option" data-answer-id="${answer.id}">
                                <span class="answer-letter">${String.fromCharCode(65 + index)}</span>
                                <span class="answer-text">${answer.text}</span>
                                <span class="answer-indicator" style="display: none;">
                                    ${answer.is_correct ? '✅' : '❌'}
                                </span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
            
            controls.style.display = 'flex';
            timer.style.display = 'block';
            
            // Démarrer le timer
            startQuestionTimer(question.time_limit);
        }
        
        function startQuestionTimer(timeLimit) {
            timeLeft = timeLimit;
            const timerValue = document.getElementById('timerValue');
            const timerProgress = document.getElementById('timerProgress');
            
            if (questionTimer) {
                clearInterval(questionTimer);
            }
            
            questionTimer = setInterval(function() {
                timeLeft--;
                timerValue.textContent = timeLeft;
                
                const progress = ((timeLimit - timeLeft) / timeLimit) * 100;
                timerProgress.style.width = progress + '%';
                
                if (timeLeft <= 0) {
                    clearInterval(questionTimer);
                    showAnswers();
                }
            }, 1000);
        }
        
        function showAnswers() {
            if (questionTimer) {
                clearInterval(questionTimer);
            }
            
            // Afficher les bonnes réponses
            document.querySelectorAll('.answer-indicator').forEach(indicator => {
                indicator.style.display = 'inline';
            });
            
            document.querySelectorAll('.answer-option').forEach(option => {
                const indicator = option.querySelector('.answer-indicator');
                if (indicator.textContent.includes('✅')) {
                    option.classList.add('correct');
                }
            });
            
            document.getElementById('showAnswersBtn').style.display = 'none';
        }
        
        function nextQuestion() {
            const nextIndex = currentQuestionIndex + 1;
            if (nextIndex < QUESTIONS.length) {
                setCurrentQuestion(QUESTIONS[nextIndex].id, nextIndex);
            } else {
                alert('C\'était la dernière question !');
            }
        }
        
        function updateQuestionProgress() {
            document.getElementById('currentQuestionIndex').textContent = currentQuestionIndex + 1;
        }
        
        async function refreshParticipants() {
            try {
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_participants',
                        session_id: SESSION_ID
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    displayParticipants(result.participants);
                    document.getElementById('participantCount').textContent = result.participants.length;
                }
            } catch (error) {
                console.error('Erreur lors de l\'actualisation des participants:', error);
            }
        }
        
        function displayParticipants(participants) {
            const list = document.getElementById('participantsList');
            
            if (participants.length === 0) {
                list.innerHTML = `
                    <div class="no-participants">
                        <div class="no-participants-icon">👥</div>
                        <p>Aucun participant</p>
                        <small>Partagez le code de session</small>
                    </div>
                `;
                return;
            }
            
            list.innerHTML = participants.map(participant => `
                <div class="participant-item">
                    <div class="participant-info">
                        <strong>${participant.first_name} ${participant.last_name}</strong>
                        <small>@${participant.username}</small>
                    </div>
                    <div class="participant-score">
                        ${participant.total_score} pts
                    </div>
                </div>
            `).join('');
        }
        
        function showQRCode() {
            document.getElementById('qrModal').style.display = 'block';
        }
        
        function closeQRModal() {
            document.getElementById('qrModal').style.display = 'none';
        }
        
        // Nettoyer les intervals avant de quitter la page
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
            if (questionTimer) {
                clearInterval(questionTimer);
            }
        });
    </script>
</body>
</html>
