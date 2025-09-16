<?php
require_once __DIR__ . '/../auth/check_auth.php';

requirePlayer();

$current_user = getCurrentUserOrRedirect();

// Connexion à la base de données
$database = new Database();
$conn = $database->getConnection();

// Récupérer l'historique des parties
$historyQuery = "SELECT gh.*, q.title as quiz_title, q.description as quiz_description,
                u.username as creator_name
                FROM game_history gh
                JOIN quizzes q ON gh.quiz_id = q.id
                JOIN users u ON q.created_by = u.id
                WHERE gh.user_id = :user_id
                ORDER BY gh.played_at DESC";
$historyStmt = $conn->prepare($historyQuery);
$historyStmt->bindParam(":user_id", $current_user['id']);
$historyStmt->execute();
$game_history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);

// Calculer les statistiques globales
$statsQuery = "SELECT 
              COUNT(*) as total_games,
              AVG(final_score) as avg_score,
              AVG(correct_answers * 100.0 / total_questions) as avg_success_rate,
              MAX(final_score) as best_score,
              SUM(correct_answers) as total_correct,
              SUM(total_questions) as total_questions_answered
              FROM game_history 
              WHERE user_id = :user_id";
$statsStmt = $conn->prepare($statsQuery);
$statsStmt->bindParam(":user_id", $current_user['id']);
$statsStmt->execute();
$stats = $statsStmt->fetch(PDO::FETCH_ASSOC);

// Récupérer les quiz les plus joués
$popularQuery = "SELECT q.title, COUNT(*) as play_count,
                AVG(gh.final_score) as avg_score,
                AVG(gh.correct_answers * 100.0 / gh.total_questions) as avg_success_rate
                FROM game_history gh
                JOIN quizzes q ON gh.quiz_id = q.id
                WHERE gh.user_id = :user_id
                GROUP BY gh.quiz_id, q.title
                ORDER BY play_count DESC, avg_score DESC
                LIMIT 5";
$popularStmt = $conn->prepare($popularQuery);
$popularStmt->bindParam(":user_id", $current_user['id']);
$popularStmt->execute();
$popular_quizzes = $popularStmt->fetchAll(PDO::FETCH_ASSOC);

// Récupérer l'évolution des scores (derniers 10 jeux)
$evolutionQuery = "SELECT final_score, played_at, q.title
                  FROM game_history gh
                  JOIN quizzes q ON gh.quiz_id = q.id
                  WHERE gh.user_id = :user_id
                  ORDER BY gh.played_at DESC
                  LIMIT 10";
$evolutionStmt = $conn->prepare($evolutionQuery);
$evolutionStmt->bindParam(":user_id", $current_user['id']);
$evolutionStmt->execute();
$score_evolution = array_reverse($evolutionStmt->fetchAll(PDO::FETCH_ASSOC));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon historique - Kuizu Sapeurs-Pompiers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/player.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="player-layout">
        <!-- Header -->
        <header class="player-header">
            <div class="header-content">
                <div class="logo">
                    <h1>🚒 Kuizu</h1>
                    <span>Jeunes Sapeurs-Pompiers</span>
                </div>
                
                <nav class="header-nav">
                    <a href="dashboard.php" class="nav-link">Tableau de bord</a>
                    <a href="join_session.php" class="nav-link">Rejoindre une session</a>
                    <a href="history.php" class="nav-link active">Mon historique</a>
                </nav>
                
                <div class="user-menu">
                    <div class="user-info">
                        <span>👤 <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                    </div>
                    <a href="../auth/logout.php" class="btn btn-outline-primary btn-sm">
                        Déconnexion
                    </a>
                </div>
            </div>
        </header>

        <!-- Contenu principal -->
        <main class="player-content">
            <div class="page-header">
                <h1>Mon historique</h1>
                <p>Consultez vos performances et suivez votre progression</p>
            </div>

            <?php if (empty($game_history)): ?>
                <div class="empty-state">
                    <div class="empty-icon">📊</div>
                    <h3>Aucune partie jouée</h3>
                    <p>Vous n'avez pas encore participé à des quiz. Commencez dès maintenant !</p>
                    <a href="dashboard.php" class="btn btn-primary">Participer à un quiz</a>
                </div>
            <?php else: ?>
                <!-- Statistiques globales -->
                <div class="stats-overview">
                    <h2>Vos statistiques</h2>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">🎮</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['total_games']; ?></div>
                                <div class="stat-label">Parties jouées</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">🏆</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo round($stats['avg_score'], 0); ?></div>
                                <div class="stat-label">Score moyen</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">✅</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo round($stats['avg_success_rate'], 1); ?>%</div>
                                <div class="stat-label">Taux de réussite</div>
                            </div>
                        </div>
                        
                        <div class="stat-card highlight">
                            <div class="stat-icon">🌟</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['best_score']; ?></div>
                                <div class="stat-label">Meilleur score</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">📝</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['total_correct']; ?></div>
                                <div class="stat-label">Bonnes réponses</div>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">❓</div>
                            <div class="stat-content">
                                <div class="stat-value"><?php echo $stats['total_questions_answered']; ?></div>
                                <div class="stat-label">Questions répondues</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Graphiques -->
                <div class="charts-section">
                    <div class="charts-grid">
                        <!-- Évolution des scores -->
                        <div class="chart-card">
                            <h3>Évolution de vos scores</h3>
                            <div class="chart-container">
                                <canvas id="scoreChart"></canvas>
                            </div>
                        </div>
                        
                        <!-- Quiz les plus joués -->
                        <div class="chart-card">
                            <h3>Vos quiz favoris</h3>
                            <div class="popular-quizzes">
                                <?php foreach ($popular_quizzes as $index => $quiz): ?>
                                    <div class="popular-quiz-item">
                                        <div class="quiz-rank"><?php echo $index + 1; ?></div>
                                        <div class="quiz-info">
                                            <div class="quiz-name"><?php echo htmlspecialchars($quiz['title']); ?></div>
                                            <div class="quiz-stats">
                                                <span><?php echo $quiz['play_count']; ?> partie(s)</span>
                                                <span>•</span>
                                                <span><?php echo round($quiz['avg_score'], 0); ?> pts moy.</span>
                                                <span>•</span>
                                                <span><?php echo round($quiz['avg_success_rate'], 1); ?>% réussite</span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Historique détaillé -->
                <div class="history-section">
                    <div class="section-header">
                        <h2>Historique détaillé</h2>
                        <div class="filter-controls">
                            <select id="sortFilter" onchange="sortHistory()">
                                <option value="date_desc">Plus récent d'abord</option>
                                <option value="date_asc">Plus ancien d'abord</option>
                                <option value="score_desc">Meilleur score d'abord</option>
                                <option value="score_asc">Score le plus bas d'abord</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="history-list" id="historyList">
                        <?php foreach ($game_history as $game): ?>
                            <div class="history-card" data-date="<?php echo strtotime($game['played_at']); ?>" data-score="<?php echo $game['final_score']; ?>">
                                <div class="history-header">
                                    <div class="history-title">
                                        <h4><?php echo htmlspecialchars($game['quiz_title']); ?></h4>
                                        <div class="history-meta">
                                            <span>📅 <?php echo date('d/m/Y à H:i', strtotime($game['played_at'])); ?></span>
                                            <span>👨‍🏫 <?php echo htmlspecialchars($game['creator_name']); ?></span>
                                        </div>
                                    </div>
                                    <div class="history-score">
                                        <div class="score-value"><?php echo $game['final_score']; ?></div>
                                        <div class="score-label">points</div>
                                    </div>
                                </div>
                                
                                <div class="history-details">
                                    <div class="detail-grid">
                                        <div class="detail-item">
                                            <span class="detail-icon">✅</span>
                                            <span class="detail-text"><?php echo $game['correct_answers']; ?>/<?php echo $game['total_questions']; ?> correctes</span>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-icon">📊</span>
                                            <span class="detail-text"><?php echo round(($game['correct_answers'] / $game['total_questions']) * 100, 1); ?>% de réussite</span>
                                        </div>
                                        <?php if ($game['completion_time']): ?>
                                            <div class="detail-item">
                                                <span class="detail-icon">⏱️</span>
                                                <span class="detail-text"><?php echo gmdate("i:s", $game['completion_time'] / 1000); ?> temps total</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="detail-item">
                                            <span class="detail-icon">🎯</span>
                                            <span class="detail-text"><?php echo round($game['final_score'] / $game['total_questions'], 0); ?> pts/question</span>
                                        </div>
                                    </div>
                                    
                                    <?php if ($game['quiz_description']): ?>
                                        <div class="quiz-description">
                                            <p><?php echo htmlspecialchars($game['quiz_description']); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="performance-indicator">
                                    <?php 
                                    $success_rate = ($game['correct_answers'] / $game['total_questions']) * 100;
                                    if ($success_rate >= 80): ?>
                                        <span class="performance excellent">🌟 Excellent</span>
                                    <?php elseif ($success_rate >= 60): ?>
                                        <span class="performance good">👍 Bien</span>
                                    <?php elseif ($success_rate >= 40): ?>
                                        <span class="performance average">📚 À améliorer</span>
                                    <?php else: ?>
                                        <span class="performance poor">💪 Continuez vos efforts</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Données pour les graphiques
        const scoreData = <?php echo json_encode($score_evolution); ?>;
        
        // Graphique d'évolution des scores
        if (scoreData.length > 0) {
            const ctx = document.getElementById('scoreChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: scoreData.map(game => {
                        const date = new Date(game.played_at);
                        return date.toLocaleDateString('fr-FR', { month: 'short', day: 'numeric' });
                    }),
                    datasets: [{
                        label: 'Score',
                        data: scoreData.map(game => game.final_score),
                        borderColor: '#dc2626',
                        backgroundColor: 'rgba(220, 38, 38, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#dc2626',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.1)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    elements: {
                        point: {
                            hoverRadius: 8
                        }
                    }
                }
            });
        }
        
        // Tri de l'historique
        function sortHistory() {
            const sortBy = document.getElementById('sortFilter').value;
            const historyList = document.getElementById('historyList');
            const cards = Array.from(historyList.children);
            
            cards.sort((a, b) => {
                switch (sortBy) {
                    case 'date_desc':
                        return parseInt(b.dataset.date) - parseInt(a.dataset.date);
                    case 'date_asc':
                        return parseInt(a.dataset.date) - parseInt(b.dataset.date);
                    case 'score_desc':
                        return parseInt(b.dataset.score) - parseInt(a.dataset.score);
                    case 'score_asc':
                        return parseInt(a.dataset.score) - parseInt(b.dataset.score);
                    default:
                        return 0;
                }
            });
            
            // Réorganiser les éléments
            cards.forEach(card => historyList.appendChild(card));
        }
    </script>
</body>
</html>

<style>
.page-header {
    text-align: center;
    margin-bottom: 3rem;
}

.page-header h1 {
    color: var(--gray-800);
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
}

.page-header p {
    color: var(--gray-600);
    font-size: 1.1rem;
    margin: 0;
}

.stats-overview {
    margin-bottom: 3rem;
}

.stats-overview h2 {
    color: var(--gray-800);
    margin-bottom: 1.5rem;
    text-align: center;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1.5rem;
}

.stat-card {
    background: var(--white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    display: flex;
    align-items: center;
    gap: 1.5rem;
    transition: var(--transition);
}

.stat-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.stat-card.highlight {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    border: 2px solid var(--warning-color);
}

.stat-icon {
    font-size: 2.5rem;
    flex-shrink: 0;
}

.stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 0.25rem;
}

.stat-label {
    color: var(--gray-600);
    font-size: 0.9rem;
    font-weight: 500;
}

.charts-section {
    margin-bottom: 3rem;
}

.charts-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
}

.chart-card {
    background: var(--white);
    padding: 2rem;
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
}

.chart-card h3 {
    color: var(--gray-800);
    margin-bottom: 1.5rem;
    text-align: center;
}

.chart-container {
    height: 300px;
    position: relative;
}

.popular-quizzes {
    display: grid;
    gap: 1rem;
}

.popular-quiz-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
}

.quiz-rank {
    width: 32px;
    height: 32px;
    background: var(--primary-color);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.quiz-info {
    flex: 1;
}

.quiz-name {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.quiz-stats {
    font-size: 0.85rem;
    color: var(--gray-500);
}

.history-section h2 {
    color: var(--gray-800);
    margin-bottom: 1.5rem;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
}

.filter-controls select {
    padding: 0.5rem 1rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    background: var(--white);
}

.history-list {
    display: grid;
    gap: 1.5rem;
}

.history-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
    transition: var(--transition);
}

.history-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.history-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    background: var(--gray-50);
}

.history-title h4 {
    color: var(--gray-800);
    margin-bottom: 0.5rem;
}

.history-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: var(--gray-500);
    flex-wrap: wrap;
}

.history-score {
    text-align: center;
    flex-shrink: 0;
}

.score-value {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
}

.score-label {
    color: var(--gray-600);
    font-size: 0.85rem;
}

.history-details {
    padding: 1.5rem;
}

.detail-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.detail-icon {
    font-size: 1.1rem;
}

.detail-text {
    color: var(--gray-700);
    font-size: 0.9rem;
}

.quiz-description {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--gray-200);
}

.quiz-description p {
    color: var(--gray-600);
    font-size: 0.9rem;
    margin: 0;
}

.performance-indicator {
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
    text-align: center;
}

.performance {
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 0.9rem;
}

.performance.excellent {
    background: #d1fae5;
    color: #065f46;
}

.performance.good {
    background: #dbeafe;
    color: #1e40af;
}

.performance.average {
    background: #fef3c7;
    color: #92400e;
}

.performance.poor {
    background: #fee2e2;
    color: #991b1b;
}

@media (max-width: 1024px) {
    .charts-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 768px) {
    .page-header h1 {
        font-size: 2rem;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .stat-card {
        padding: 1.5rem;
        flex-direction: column;
        text-align: center;
    }
    
    .stat-value {
        font-size: 2rem;
    }
    
    .history-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .detail-grid {
        grid-template-columns: 1fr;
    }
    
    .section-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .history-meta {
        flex-direction: column;
        gap: 0.5rem;
    }
}
</style>
