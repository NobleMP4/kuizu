<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/GameSession.php';

requirePlayer();

$current_user = getCurrentUserOrRedirect();
$session_id = $_GET['session_id'] ?? null;

if (!$session_id) {
    header('Location: dashboard.php');
    exit();
}

$gameSession = new GameSession();
$session = $gameSession->getById($session_id);

if (!$session) {
    header('Location: dashboard.php?error=session_not_found');
    exit();
}

// Vérifier que l'utilisateur participe à cette session
$participants = $gameSession->getParticipants($session_id);
$is_participant = false;
$participant_data = null;

foreach ($participants as $participant) {
    if ($participant['user_id'] == $current_user['id']) {
        $is_participant = true;
        $participant_data = $participant;
        break;
    }
}

if (!$is_participant) {
    // Essayer de rejoindre automatiquement si la session est en attente
    if ($session['status'] === 'waiting') {
        $join_result = $gameSession->addParticipant($session_id, $current_user['id']);
        if ($join_result['success']) {
            // Recharger la page pour actualiser les données
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit();
        }
    }
    
    header('Location: dashboard.php?error=not_participant');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($session['quiz_title']); ?> - Kuizu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/player.css">
    <link rel="stylesheet" href="../assets/css/game.css">
</head>
<body class="game-body">
    <div class="game-layout">
        <!-- Header de jeu -->
        <header class="game-header">
            <div class="header-content">
                <div class="game-info">
                    <h1><?php echo htmlspecialchars($session['quiz_title']); ?></h1>
                    <div class="game-meta">
                        <span class="player-name">👤 <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                        <span class="session-code">Code: <?php echo $session['session_code']; ?></span>
                        <span class="player-score">🏆 <span id="playerScore"><?php echo $participant_data['total_score']; ?></span> points</span>
                    </div>
                </div>
                
                <div class="game-status">
                    <div class="status-indicator status-<?php echo $session['status']; ?>" id="gameStatus">
                        <?php 
                        $status_labels = [
                            'waiting' => '⏳ En attente',
                            'active' => '▶️ En cours',
                            'paused' => '⏸️ En pause',
                            'finished' => '✅ Terminé'
                        ];
                        echo $status_labels[$session['status']] ?? $session['status'];
                        ?>
                    </div>
                </div>
            </div>
        </header>

        <!-- Contenu principal -->
        <main class="game-content">
            <!-- État d'attente -->
            <div id="waitingState" class="game-state" style="<?php echo $session['status'] === 'waiting' ? 'display: block;' : 'display: none;'; ?>">
                <div class="waiting-content">
                    <div class="waiting-icon">⏳</div>
                    <h2>En attente du démarrage...</h2>
                    <p>L'animateur va bientôt lancer le quiz. Restez connecté !</p>
                    
                    <div class="participants-preview">
                        <h3>Participants connectés (<span id="participantCountWaiting"><?php echo count($participants); ?></span>)</h3>
                        <div class="participants-grid" id="participantsGridWaiting">
                            <!-- Les participants seront chargés ici -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- État de jeu actif -->
            <div id="activeState" class="game-state" style="<?php echo $session['status'] === 'active' ? 'display: block;' : 'display: none;'; ?>">
                <!-- Timer de question -->
                <div class="question-timer" id="questionTimer" style="display: none;">
                    <div class="timer-circle">
                        <div class="timer-value" id="timerValue">30</div>
                        <div class="timer-label">sec</div>
                    </div>
                    <div class="timer-bar">
                        <div class="timer-progress" id="timerProgress"></div>
                    </div>
                </div>

                <!-- Question actuelle -->
                <div class="question-container" id="questionContainer" style="display: none;">
                    <div class="question-header">
                        <div class="question-number" id="questionNumber">Question 1</div>
                        <div class="question-points" id="questionPoints">100 points</div>
                    </div>
                    
                    <div class="question-content">
                        <h2 id="questionText">Question en cours de chargement...</h2>
                    </div>
                    
                    <div class="answers-container" id="answersContainer">
                        <!-- Les réponses seront chargées ici -->
                    </div>
                </div>

                <!-- Résultat de la question -->
                <div class="question-result" id="questionResult" style="display: none;">
                    <div class="result-icon" id="resultIcon">✅</div>
                    <div class="result-message" id="resultMessage">Bonne réponse !</div>
                    <div class="result-points" id="resultPoints">+150 points</div>
                </div>

                <!-- Attente de la prochaine question -->
                <div class="waiting-next" id="waitingNext" style="display: none;">
                    <div class="waiting-next-icon">⏭️</div>
                    <h3>En attente de la prochaine question...</h3>
                    <p>L'animateur va bientôt afficher la question suivante</p>
                </div>
            </div>

            <!-- État en pause -->
            <div id="pausedState" class="game-state" style="<?php echo $session['status'] === 'paused' ? 'display: block;' : 'display: none;'; ?>">
                <div class="paused-content">
                    <div class="paused-icon">⏸️</div>
                    <h2>Session en pause</h2>
                    <p>L'animateur a mis le quiz en pause. Nous reprendrons bientôt !</p>
                    
                    <div class="current-score">
                        <h3>Votre score actuel</h3>
                        <div class="score-display">
                            <span id="currentScorePaused"><?php echo $participant_data['total_score']; ?></span> points
                        </div>
                    </div>
                </div>
            </div>

            <!-- État terminé -->
            <div id="finishedState" class="game-state" style="<?php echo $session['status'] === 'finished' ? 'display: block;' : 'display: none;'; ?>">
                <div class="finished-content">
                    <div class="finished-icon">🎉</div>
                    <h2>Quiz terminé !</h2>
                    <p>Bravo pour votre participation !</p>
                    
                    <div class="final-score">
                        <h3>Votre score final</h3>
                        <div class="score-display">
                            <span id="finalScore"><?php echo $participant_data['total_score']; ?></span> points
                        </div>
                    </div>
                    
                    <div class="leaderboard-final" id="leaderboardFinal">
                        <!-- Le classement final sera affiché ici -->
                    </div>
                    
                    <div class="game-actions">
                        <a href="dashboard.php" class="btn btn-primary">
                            🏠 Retour au tableau de bord
                        </a>
                        <a href="history.php" class="btn btn-outline-primary">
                            📊 Voir mon historique
                        </a>
                    </div>
                </div>
            </div>
        </main>

        <!-- Classement en temps réel -->
        <aside class="leaderboard-sidebar" id="leaderboardSidebar">
            <div class="sidebar-header">
                <h3>🏆 Classement</h3>
                <button onclick="toggleLeaderboard()" class="toggle-btn" id="toggleBtn">
                    ←
                </button>
            </div>
            <div class="leaderboard-content" id="leaderboardContent">
                <!-- Le classement sera chargé ici -->
            </div>
        </aside>
    </div>

    <script>
        // Variables globales
        const SESSION_ID = <?php echo $session_id; ?>;
        const USER_ID = <?php echo $current_user['id']; ?>;
        const PARTICIPANT_ID = <?php echo $participant_data['id']; ?>;
        
        let currentQuestion = null;
        let questionTimer = null;
        let timeLeft = 0;
        let hasAnswered = false;
        let gameInterval = null;
        
        // Initialiser le jeu
        document.addEventListener('DOMContentLoaded', function() {
            initializeGame();
            startGameLoop();
        });
        
        function initializeGame() {
            loadParticipants();
            loadLeaderboard();
            checkCurrentQuestion();
        }
        
        function startGameLoop() {
            // Vérifier l'état du jeu toutes les 2 secondes
            gameInterval = setInterval(function() {
                checkGameStatus();
                loadLeaderboard();
                
                if (document.getElementById('waitingState').style.display !== 'none') {
                    loadParticipants();
                }
            }, 2000);
        }
        
        async function checkGameStatus() {
            try {
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_session',
                        session_id: SESSION_ID
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    updateGameState(result.session.status);
                    
                    // Si le jeu est actif, vérifier la question courante
                    if (result.session.status === 'active') {
                        checkCurrentQuestion();
                    }
                }
            } catch (error) {
                console.error('Erreur lors de la vérification du statut:', error);
            }
        }
        
        function updateGameState(status) {
            // Masquer tous les états
            document.querySelectorAll('.game-state').forEach(state => {
                state.style.display = 'none';
            });
            
            // Afficher l'état correspondant
            const stateElement = document.getElementById(status + 'State');
            if (stateElement) {
                stateElement.style.display = 'block';
            }
            
            // Mettre à jour l'indicateur de statut
            const statusElement = document.getElementById('gameStatus');
            const statusLabels = {
                'waiting': '⏳ En attente',
                'active': '▶️ En cours',
                'paused': '⏸️ En pause',
                'finished': '✅ Terminé'
            };
            
            statusElement.textContent = statusLabels[status] || status;
            statusElement.className = `status-indicator status-${status}`;
        }
        
        async function checkCurrentQuestion() {
            try {
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_current_question',
                        session_id: SESSION_ID
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    if (result.question && result.session_status === 'active') {
                        displayQuestion(result.question);
                    } else {
                        showWaitingNext();
                    }
                }
            } catch (error) {
                console.error('Erreur lors de la récupération de la question:', error);
            }
        }
        
        function displayQuestion(question) {
            currentQuestion = question;
            hasAnswered = false;
            
            // Masquer les autres éléments
            document.getElementById('questionResult').style.display = 'none';
            document.getElementById('waitingNext').style.display = 'none';
            
            // Afficher la question
            document.getElementById('questionContainer').style.display = 'block';
            document.getElementById('questionTimer').style.display = 'flex';
            
            // Remplir les données
            document.getElementById('questionNumber').textContent = `Question`;
            document.getElementById('questionPoints').textContent = `${question.points} points`;
            document.getElementById('questionText').textContent = question.question_text;
            
            // Afficher les réponses
            const answersContainer = document.getElementById('answersContainer');
            answersContainer.innerHTML = question.answers.map((answer, index) => `
                <button class="answer-btn" onclick="selectAnswer(${answer.id}, this)" data-answer-id="${answer.id}">
                    <span class="answer-letter">${String.fromCharCode(65 + index)}</span>
                    <span class="answer-text">${answer.text}</span>
                </button>
            `).join('');
            
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
                
                // Changer la couleur selon le temps restant
                if (timeLeft <= 5) {
                    timerProgress.style.backgroundColor = '#ef4444';
                } else if (timeLeft <= 10) {
                    timerProgress.style.backgroundColor = '#f59e0b';
                } else {
                    timerProgress.style.backgroundColor = '#10b981';
                }
                
                if (timeLeft <= 0) {
                    clearInterval(questionTimer);
                    if (!hasAnswered) {
                        showTimeUp();
                    }
                }
            }, 1000);
        }
        
        async function selectAnswer(answerId, buttonElement) {
            if (hasAnswered) return;
            
            hasAnswered = true;
            const responseTime = (currentQuestion.time_limit - timeLeft) * 1000; // en millisecondes
            
            // Désactiver tous les boutons
            document.querySelectorAll('.answer-btn').forEach(btn => {
                btn.disabled = true;
            });
            
            // Marquer la réponse sélectionnée
            buttonElement.classList.add('selected');
            
            try {
                const response = await fetch('../api/player_response.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'submit_answer',
                        session_id: SESSION_ID,
                        question_id: currentQuestion.id,
                        answer_id: answerId,
                        response_time: responseTime
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    showResult(result.is_correct, result.points_earned);
                    updatePlayerScore(result.points_earned);
                } else {
                    console.error('Erreur lors de l\'envoi de la réponse:', result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
            }
        }
        
        function showResult(isCorrect, pointsEarned) {
            if (questionTimer) {
                clearInterval(questionTimer);
            }
            
            // Masquer la question et le timer
            document.getElementById('questionContainer').style.display = 'none';
            document.getElementById('questionTimer').style.display = 'none';
            
            // Afficher le résultat
            const resultElement = document.getElementById('questionResult');
            const iconElement = document.getElementById('resultIcon');
            const messageElement = document.getElementById('resultMessage');
            const pointsElement = document.getElementById('resultPoints');
            
            if (isCorrect) {
                iconElement.textContent = '✅';
                iconElement.className = 'result-icon correct';
                messageElement.textContent = 'Bonne réponse !';
                pointsElement.textContent = `+${pointsEarned} points`;
            } else {
                iconElement.textContent = '❌';
                iconElement.className = 'result-icon incorrect';
                messageElement.textContent = 'Mauvaise réponse';
                pointsElement.textContent = '+0 point';
            }
            
            resultElement.style.display = 'block';
            
            // Passer à l'attente après 3 secondes
            setTimeout(() => {
                showWaitingNext();
            }, 3000);
        }
        
        function showTimeUp() {
            // Désactiver tous les boutons
            document.querySelectorAll('.answer-btn').forEach(btn => {
                btn.disabled = true;
            });
            
            showResult(false, 0);
        }
        
        function showWaitingNext() {
            document.getElementById('questionResult').style.display = 'none';
            document.getElementById('questionContainer').style.display = 'none';
            document.getElementById('questionTimer').style.display = 'none';
            document.getElementById('waitingNext').style.display = 'block';
        }
        
        function updatePlayerScore(pointsEarned) {
            const scoreElement = document.getElementById('playerScore');
            const currentScore = parseInt(scoreElement.textContent);
            const newScore = currentScore + pointsEarned;
            scoreElement.textContent = newScore;
        }
        
        async function loadParticipants() {
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
                }
            } catch (error) {
                console.error('Erreur lors du chargement des participants:', error);
            }
        }
        
        function displayParticipants(participants) {
            const gridElement = document.getElementById('participantsGridWaiting');
            const countElement = document.getElementById('participantCountWaiting');
            
            if (countElement) {
                countElement.textContent = participants.length;
            }
            
            if (gridElement) {
                gridElement.innerHTML = participants.map(participant => `
                    <div class="participant-card ${participant.user_id == USER_ID ? 'current-user' : ''}">
                        <div class="participant-name">${participant.first_name} ${participant.last_name}</div>
                        <div class="participant-username">@${participant.username}</div>
                    </div>
                `).join('');
            }
        }
        
        async function loadLeaderboard() {
            try {
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_leaderboard',
                        session_id: SESSION_ID
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    displayLeaderboard(result.leaderboard);
                }
            } catch (error) {
                console.error('Erreur lors du chargement du classement:', error);
            }
        }
        
        function displayLeaderboard(leaderboard) {
            const contentElement = document.getElementById('leaderboardContent');
            
            contentElement.innerHTML = leaderboard.map((participant, index) => `
                <div class="leaderboard-item ${participant.user_id == USER_ID ? 'current-user' : ''} ${index < 3 ? 'top-3' : ''}">
                    <div class="leaderboard-position">${index + 1}</div>
                    <div class="leaderboard-info">
                        <div class="leaderboard-name">${participant.first_name} ${participant.last_name}</div>
                        <div class="leaderboard-score">${participant.total_score} pts</div>
                    </div>
                </div>
            `).join('');
        }
        
        function toggleLeaderboard() {
            const sidebar = document.getElementById('leaderboardSidebar');
            const toggleBtn = document.getElementById('toggleBtn');
            
            sidebar.classList.toggle('collapsed');
            toggleBtn.textContent = sidebar.classList.contains('collapsed') ? '→' : '←';
        }
        
        // Nettoyer les intervals avant de quitter
        window.addEventListener('beforeunload', function() {
            if (gameInterval) {
                clearInterval(gameInterval);
            }
            if (questionTimer) {
                clearInterval(questionTimer);
            }
        });
    </script>
</body>
</html>
