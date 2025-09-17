<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/Quiz.php';
require_once __DIR__ . '/../classes/GameSession.php';

requirePlayer();

$current_user = getCurrentUserOrRedirect();
$quiz = new Quiz();
$gameSession = new GameSession();

// Obtenir les quiz disponibles
$available_quizzes = $quiz->getAccessibleQuizzes();

// Obtenir l'historique des parties du joueur
$database = new Database();
$conn = $database->getConnection();

$historyQuery = "SELECT gh.*, q.title as quiz_title 
                FROM game_history gh
                JOIN quizzes q ON gh.quiz_id = q.id
                WHERE gh.user_id = :user_id
                ORDER BY gh.played_at DESC
                LIMIT 10";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bindParam(":user_id", $current_user['id']);
$historyStmt->execute();
$game_history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques du joueur
$statsQuery = "SELECT 
              COUNT(*) as total_games,
              AVG(final_score) as avg_score,
              AVG(correct_answers * 100.0 / total_questions) as avg_success_rate,
              MAX(final_score) as best_score
              FROM game_history 
              WHERE user_id = :user_id";
$statsStmt = $conn->prepare($statsQuery);
$statsStmt->bindParam(":user_id", $current_user['id']);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Kuizu Sapeurs-Pompiers</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/player.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="player-layout">
        <!-- Header -->
        <header class="player-header">
            <div class="header-content">
                <div class="logo">
                    <img src="../assets/images/logo.png" alt="Kuizu" width="50" height="50">
                    <div class="logo-text">
                        <h1>Kuizu</h1>
                        <span>Jeunes Sapeurs-Pompiers</span>
                    </div>
                </div>
                
                <nav class="header-nav">
                    <a href="dashboard.php" class="nav-link active">Tableau de bord</a>
                    <a href="join_session.php" class="nav-link">Rejoindre une session</a>
                    <a href="history.php" class="nav-link">Mon historique</a>
                </nav>
                
                <div class="user-menu">
                    <div class="user-info">
                        <span>👤 <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                    </div>
                    <button onclick="toggleBurgerMenu()" class="burger-menu" id="burgerBtn">
                        ☰
                    </button>
                    <div class="burger-dropdown" id="burgerDropdown">
                        <a href="dashboard.php" class="active">🏠 Tableau de bord</a>
                        <a href="join_session.php">🎮 Rejoindre une session</a>
                        <a href="history.php">📊 Mon historique</a>
                    </div>
                    <a href="../auth/logout.php" class="btn btn-outline-primary btn-sm">
                        Déconnexion
                    </a>
                </div>
            </div>
        </header>

        <!-- Contenu principal -->
        <main class="player-content">
            <div class="welcome-section">
                <h2>Bienvenue, <?php echo htmlspecialchars($current_user['first_name']); ?> ! 👋</h2>
                <p>Prêt à tester tes connaissances en tant que jeune sapeur-pompier ?</p>
            </div>

            <!-- Actions rapides -->
            <div class="quick-actions">
                <div class="action-card primary">
                    <div class="action-icon">🎮</div>
                    <h3>Rejoindre une session</h3>
                    <p>Participe à une session de quiz en temps réel</p>
                    <a href="join_session.php" class="btn btn-primary">Rejoindre</a>
                </div>
                
            </div>

            <!-- Statistiques personnelles -->
            <?php if ($stats['total_games'] > 0): ?>
                <div class="stats-section">
                    <h3>Tes statistiques</h3>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['total_games']; ?></div>
                            <div class="stat-label">Parties jouées</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo round($stats['avg_score'], 0); ?></div>
                            <div class="stat-label">Score moyen</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo round($stats['avg_success_rate'], 1); ?>%</div>
                            <div class="stat-label">Taux de réussite</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $stats['best_score']; ?></div>
                            <div class="stat-label">Meilleur score</div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Quiz disponibles -->
            <div class="available-quizzes">
                <h3>Quiz disponibles</h3>
                <?php if (empty($available_quizzes)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">❓</div>
                        <h4>Aucun quiz disponible</h4>
                        <p>Il n'y a actuellement aucun quiz ouvert. Reviens plus tard ou demande à ton instructeur de débloquer des quiz.</p>
                    </div>
                <?php else: ?>
                    <div class="quiz-grid">
                        <?php foreach ($available_quizzes as $quiz_item): ?>
                            <div class="quiz-card">
                                <div class="quiz-header">
                                    <h4><?php echo htmlspecialchars($quiz_item['title']); ?></h4>
                                    <div class="quiz-meta">
                                        <span>📝 <?php echo $quiz_item['question_count']; ?> questions</span>
                                        <span>👨‍🏫 <?php echo htmlspecialchars($quiz_item['creator_name']); ?></span>
                                    </div>
                                </div>
                                <div class="quiz-description">
                                    <p><?php echo htmlspecialchars(substr($quiz_item['description'], 0, 120) . (strlen($quiz_item['description']) > 120 ? '...' : '')); ?></p>
                                </div>
                                <div class="quiz-actions">
                                    <a href="quiz_preview.php?id=<?php echo $quiz_item['id']; ?>" class="btn btn-outline-primary btn-sm">
                                        👁️ Aperçu
                                    </a>
                                    <button onclick="joinQuizSession(<?php echo $quiz_item['id']; ?>)" class="btn btn-primary btn-sm">
                                        🎮 Jouer
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Historique récent -->
            <?php if (!empty($game_history)): ?>
                <div class="recent-history">
                    <div class="section-header">
                        <h3>Parties récentes</h3>
                        <a href="history.php" class="btn btn-outline-primary btn-sm">Voir tout</a>
                    </div>
                    
                    <div class="history-list">
                        <?php foreach (array_slice($game_history, 0, 5) as $game): ?>
                            <div class="history-item">
                                <div class="history-info">
                                    <h5><?php echo htmlspecialchars($game['quiz_title']); ?></h5>
                                    <div class="history-meta">
                                        <span>📅 <?php echo date('d/m/Y à H:i', strtotime($game['played_at'])); ?></span>
                                        <span>🏆 <?php echo $game['final_score']; ?> points</span>
                                        <span>✅ <?php echo $game['correct_answers']; ?>/<?php echo $game['total_questions']; ?> correctes</span>
                                    </div>
                                </div>
                                <div class="history-score">
                                    <div class="score-circle <?php echo ($game['correct_answers'] / $game['total_questions']) >= 0.7 ? 'good' : (($game['correct_answers'] / $game['total_questions']) >= 0.5 ? 'ok' : 'poor'); ?>">
                                        <?php echo round(($game['correct_answers'] / $game['total_questions']) * 100); ?>%
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal pour rejoindre une session -->
    <div id="joinSessionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rejoindre une session</h3>
                <button onclick="closeJoinSessionModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <p>Demande le code de session à ton instructeur :</p>
                <div class="form-group">
                    <input type="text" id="sessionCodeInput" placeholder="Code de session (6 chiffres)" 
                           maxlength="6" class="session-code-input">
                </div>
                <div class="modal-actions">
                    <button onclick="joinSessionByCode()" class="btn btn-primary">Rejoindre</button>
                    <button onclick="closeJoinSessionModal()" class="btn btn-secondary">Annuler</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/player.js"></script>
</body>
</html>
