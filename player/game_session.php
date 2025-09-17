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
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/player.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/game.css?v=<?php echo time(); ?>">
</head>
<body class="game-body">
    <div class="game-layout">
        <!-- Header de jeu minimaliste -->
        <header class="game-header-simple" id="gameHeader">
            <div class="simple-header-content">
                <div class="game-logo">
                    <img src="../assets/images/logo.png" alt="Kuizu" width="30" height="30">
                    <span class="game-title">Kuizu</span>
                </div>
                
                <div class="game-score-simple">
                    <span id="playerScore"><?php echo $participant_data['total_score']; ?></span> pts
                </div>
                
                <div class="game-actions">
                    <button onclick="showGameInfo()" class="info-btn" title="Informations">
                        ℹ️
                    </button>
                    <a href="../player/dashboard.php" class="exit-btn" title="Quitter la session">
                        ✕
                    </a>
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

                <!-- Attente des autres participants -->
                <div class="waiting-participants" id="waitingParticipants" style="display: none;">
                    <div class="waiting-participants-icon">⏳</div>
                    <h3>En attente des autres participants...</h3>
                    <p>Vous avez répondu ! Attendez que tous les participants terminent.</p>
                    <div class="participants-progress" id="participantsProgress">
                        <!-- Progress des participants sera affiché ici -->
                    </div>
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
        let currentQuestionId = null;
        let questionTimer = null;
        let timeLeft = 0;
        let hasAnswered = false;
        let gameInterval = null;
        let isTimerRunning = false;
        
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
                
                // Mettre à jour le progrès si on attend les participants
                if (document.getElementById('waitingParticipants').style.display !== 'none') {
                    updateParticipantsProgress();
                }
                
                // Ne vérifier la question courante que si on n'est pas en train de répondre
                if (document.getElementById('activeState').style.display !== 'none' && 
                    !isTimerRunning) {
                    checkCurrentQuestion();
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
                console.log('Question actuelle depuis API:', result);
                
                if (result.success) {
                    if (result.question && result.session_status === 'active') {
                        // Vérifier si c'est une nouvelle question
                        if (!currentQuestionId || currentQuestionId !== result.question.id) {
                            console.log('Nouvelle question détectée:', result.question.id);
                            // Vérifier si le joueur a déjà répondu à cette question
                            checkIfAlreadyAnswered(result.question);
                        } else {
                            console.log('Même question, pas de redisplay');
                        }
                    } else {
                        console.log('Pas de question active ou session pas active');
                        if (isTimerRunning) {
                            // Arrêter le timer et réinitialiser
                            if (questionTimer) {
                                clearInterval(questionTimer);
                                questionTimer = null;
                                isTimerRunning = false;
                            }
                        }
                        showWaitingNext();
                    }
                }
            } catch (error) {
                console.error('Erreur lors de la récupération de la question:', error);
            }
        }
        
        async function checkIfAlreadyAnswered(question) {
            try {
                // Vérifier si le joueur a déjà répondu à cette question
                const response = await fetch('../api/player_response.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'check_answer_exists',
                        session_id: SESSION_ID,
                        question_id: question.id,
                        user_id: USER_ID
                    })
                });
                
                const result = await response.json();
                console.log('Vérification réponse existante:', result);
                
                if (result.success && result.has_answered) {
                    console.log('Joueur a déjà répondu, affichage attente participants');
                    currentQuestion = question;
                    currentQuestionId = question.id;
                    hasAnswered = true;
                    
                    // Récupérer la réponse du joueur pour afficher le résultat
                    showPreviousAnswer(question);
                } else {
                    console.log('Joueur n\'a pas encore répondu, affichage question');
                    displayQuestion(question);
                }
            } catch (error) {
                console.error('Erreur lors de la vérification:', error);
                // En cas d'erreur, afficher la question par défaut
                displayQuestion(question);
            }
        }
        
        async function showPreviousAnswer(question) {
            try {
                // Récupérer la réponse du joueur pour cette question
                const response = await fetch('../api/player_response.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_my_response',
                        session_id: SESSION_ID,
                        question_id: question.id
                    })
                });
                
                const result = await response.json();
                console.log('Réponse précédente du joueur:', result);
                
                if (result.success && result.response) {
                    const response_data = result.response;
                    
                    // Afficher le résultat de la réponse précédente
                    const isCorrect = response_data.is_correct == 1;
                    const pointsEarned = response_data.points_earned || 0;
                    
                    // Afficher brièvement le résultat puis passer à l'attente
                    showResult(isCorrect, pointsEarned);
                    
                    // Passer directement à l'attente des participants après 1 seconde (plus court)
                    setTimeout(() => {
                        showWaitingParticipants();
                    }, 1000);
                } else {
                    // Si on ne peut pas récupérer la réponse, aller directement à l'attente
                    showWaitingParticipants();
                }
            } catch (error) {
                console.error('Erreur lors de la récupération de la réponse:', error);
                showWaitingParticipants();
            }
        }
        
        function displayQuestion(question) {
            console.log('DisplayQuestion appelée avec:', question);
            
            // Vérifier si c'est la même question pour éviter de redémarrer le timer
            if (currentQuestionId === question.id) {
                console.log('Même question, pas de redisplay');
                return;
            }
            
            console.log('Affichage nouvelle question:', question.id);
            
            currentQuestion = question;
            currentQuestionId = question.id;
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
            
            console.log('Réponses de la question:', question.answers);
            
            // Afficher les réponses
            const answersContainer = document.getElementById('answersContainer');
            
            if (!question.answers || question.answers.length === 0) {
                console.error('Aucune réponse trouvée pour cette question');
                answersContainer.innerHTML = '<p style="text-align: center; color: red;">Aucune réponse disponible pour cette question</p>';
                return;
            }
            
            answersContainer.innerHTML = question.answers.map((answer, index) => {
                console.log(`Réponse ${index}:`, answer);
                
                // Gérer les cas où answer pourrait être null ou incomplet
                const answerId = answer?.id || 0;
                const answerText = answer?.answer_text || answer?.text || 'Réponse non disponible';
                
                if (!answerId) {
                    console.error('ID de réponse manquant:', answer);
                    return '';
                }
                
                return `
                    <button class="answer-btn" onclick="selectAnswer(${answerId}, this)" data-answer-id="${answerId}">
                        <span class="answer-letter">${String.fromCharCode(65 + index)}</span>
                        <span class="answer-text">${answerText}</span>
                    </button>
                `;
            }).filter(html => html !== '').join('');
            
            console.log('HTML des réponses généré:', answersContainer.innerHTML);
            console.log('Nombre de boutons créés:', answersContainer.children.length);
            
            // Vérifier que les boutons sont bien créés
            if (answersContainer.children.length === 0) {
                console.error('Aucun bouton de réponse créé !');
                answersContainer.innerHTML = '<p style="text-align: center; color: red;">Erreur lors de l\'affichage des réponses</p>';
                return;
            }
            
            console.log('Démarrage du timer...');
            
            // Démarrer le timer
            startQuestionTimer(question.time_limit);
        }
        
        function startQuestionTimer(timeLimit) {
            // Arrêter le timer précédent s'il existe
            if (questionTimer) {
                clearInterval(questionTimer);
                questionTimer = null;
            }
            
            timeLeft = timeLimit;
            isTimerRunning = true;
            const timerValue = document.getElementById('timerValue');
            const timerProgress = document.getElementById('timerProgress');
            
            // Mettre à jour l'affichage initial
            timerValue.textContent = timeLeft;
            timerProgress.style.width = '0%';
            timerProgress.style.backgroundColor = '#10b981';
            
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
                    questionTimer = null;
                    isTimerRunning = false;
                    if (!hasAnswered) {
                        showTimeUp();
                    }
                }
            }, 1000);
        }
        
        async function selectAnswer(answerId, buttonElement) {
            if (hasAnswered) {
                console.log('Déjà répondu, ignorer');
                return;
            }
            
            console.log('Sélection de la réponse:', answerId);
            hasAnswered = true;
            const responseTime = (currentQuestion.time_limit - timeLeft) * 1000; // en millisecondes
            
            // Désactiver tous les boutons
            document.querySelectorAll('.answer-btn').forEach(btn => {
                btn.disabled = true;
            });
            
            // Marquer la réponse sélectionnée
            buttonElement.classList.add('selected');
            
            try {
                console.log('Envoi de la réponse à l\'API...');
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
                console.log('Résultat de l\'API:', result);
                
                if (result.success) {
                    showResult(result.is_correct, result.points_earned);
                    updatePlayerScore(result.points_earned);
                } else {
                    console.error('Erreur lors de l\'envoi de la réponse:', result.message);
                    alert('Erreur: ' + result.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion: ' + error.message);
            }
        }
        
        function showResult(isCorrect, pointsEarned, isTimeUp = false) {
            if (questionTimer) {
                clearInterval(questionTimer);
                questionTimer = null;
                isTimerRunning = false;
            }
            
            // Masquer la question et le timer
            document.getElementById('questionContainer').style.display = 'none';
            document.getElementById('questionTimer').style.display = 'none';
            
            // Afficher le résultat
            const resultElement = document.getElementById('questionResult');
            const iconElement = document.getElementById('resultIcon');
            const messageElement = document.getElementById('resultMessage');
            const pointsElement = document.getElementById('resultPoints');
            
            if (isTimeUp) {
                iconElement.textContent = '⏰';
                iconElement.className = 'result-icon timeout';
                messageElement.textContent = 'Temps écoulé !';
                pointsElement.textContent = '+0 point';
            } else if (isCorrect) {
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
            
            // Passer à l'attente des participants après 3 secondes
            setTimeout(() => {
                showWaitingParticipants();
            }, 3000);
        }
        
        function showTimeUp() {
            // Désactiver tous les boutons
            document.querySelectorAll('.answer-btn').forEach(btn => {
                btn.disabled = true;
            });
            
            // Marquer comme ayant "répondu" (même si pas de réponse)
            hasAnswered = true;
            
            // Afficher le résultat "temps écoulé" puis passer à l'attente des participants
            showResult(false, 0, true);
        }
        
        function showWaitingParticipants() {
            document.getElementById('questionResult').style.display = 'none';
            document.getElementById('questionContainer').style.display = 'none';
            document.getElementById('questionTimer').style.display = 'none';
            document.getElementById('waitingNext').style.display = 'none';
            document.getElementById('waitingParticipants').style.display = 'block';
            
            // Démarrer le suivi des participants
            updateParticipantsProgress();
        }
        
        function showWaitingNext() {
            document.getElementById('questionResult').style.display = 'none';
            document.getElementById('questionContainer').style.display = 'none';
            document.getElementById('questionTimer').style.display = 'none';
            document.getElementById('waitingParticipants').style.display = 'none';
            document.getElementById('waitingNext').style.display = 'block';
            
            // Réinitialiser pour permettre la prochaine question
            currentQuestionId = null;
        }
        
        async function updateParticipantsProgress() {
            if (!currentQuestion) return;
            
            try {
                // Obtenir les statistiques de réponses pour la question courante
                const response = await fetch('../api/player_response.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_question_stats',
                        session_id: SESSION_ID,
                        question_id: currentQuestion.id
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    displayParticipantsProgress(result.total_responses, result.stats);
                }
            } catch (error) {
                console.error('Erreur lors de la récupération du progrès:', error);
            }
        }
        
        function displayParticipantsProgress(totalResponses, stats) {
            const progressContainer = document.getElementById('participantsProgress');
            
            // Obtenir le nombre total de participants
            loadParticipants().then(() => {
                const participantCount = document.getElementById('participantCountWaiting')?.textContent || '?';
                
                progressContainer.innerHTML = `
                    <div class="progress-info">
                        <div class="progress-text">
                            <strong>${totalResponses}</strong> sur <strong>${participantCount}</strong> participants ont répondu
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: ${participantCount > 0 ? (totalResponses / participantCount) * 100 : 0}%"></div>
                        </div>
                    </div>
                    <div class="progress-message">
                        ${totalResponses === parseInt(participantCount) ? 
                            '🎉 Tous les participants ont répondu !' : 
                            '⏳ En attente des autres participants...'}
                    </div>
                `;
                
                // Si tous ont répondu, passer à l'attente de la prochaine question après 2 secondes
                if (totalResponses === parseInt(participantCount)) {
                    setTimeout(() => {
                        showWaitingNext();
                    }, 2000);
                }
            });
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
        
        function showGameInfo() {
            const session = <?php echo json_encode($session); ?>;
            const participant = <?php echo json_encode($participant_data); ?>;
            
            const infoHTML = `
                <div style="background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); max-width: 400px; margin: 20px auto;">
                    <h3 style="margin: 0 0 1rem 0; color: #374151; text-align: center;">${session.quiz_title}</h3>
                    <div style="display: flex; flex-direction: column; gap: 0.75rem; font-size: 0.9rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6b7280;">👤 Joueur:</span>
                            <span style="font-weight: 600;"><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6b7280;">🔢 Code:</span>
                            <span style="font-family: monospace; font-weight: 600;">${session.session_code}</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6b7280;">🏆 Score:</span>
                            <span style="font-weight: 600; color: #224d71;">${participant.total_score} points</span>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span style="color: #6b7280;">📊 Statut:</span>
                            <span style="font-weight: 600;">${getStatusLabel(session.status)}</span>
                        </div>
                    </div>
                    <button onclick="hideGameInfo()" style="width: 100%; margin-top: 1rem; padding: 0.5rem; background: #224d71; color: white; border: none; border-radius: 6px; cursor: pointer;">
                        Fermer
                    </button>
                </div>
            `;
            
            // Créer et afficher l'overlay d'informations
            const overlay = document.createElement('div');
            overlay.id = 'gameInfoOverlay';
            overlay.style.cssText = 'position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center;';
            overlay.innerHTML = infoHTML;
            overlay.onclick = function(e) {
                if (e.target === overlay) hideGameInfo();
            };
            
            document.body.appendChild(overlay);
        }
        
        function hideGameInfo() {
            const overlay = document.getElementById('gameInfoOverlay');
            if (overlay) {
                overlay.remove();
            }
        }
        
        function getStatusLabel(status) {
            const labels = {
                'waiting': '⏳ En attente',
                'active': '▶️ En cours',
                'paused': '⏸️ En pause',
                'finished': '✅ Terminé'
            };
            return labels[status] || status;
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
