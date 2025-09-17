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
                
                <div class="user-menu" style="position: relative;">
                    <div class="user-info">
                        <span>👤 <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                    </div>
                    <button onclick="toggleBurgerMenu()" class="burger-menu" id="burgerBtn" 
                            style="display: inline-block; background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem; border-radius: 6px; cursor: pointer; font-size: 1rem; margin-left: 0.5rem; border: 2px solid yellow;">
                        ☰
                    </button>
                    <div class="burger-dropdown" id="burgerDropdown" 
                         style="position: absolute; top: 100%; right: 0; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 200px; z-index: 1000; display: none; margin-top: 0.5rem;">
                        <a href="dashboard.php" class="active" style="display: block; padding: 0.75rem 1rem; color: white; background: #224d71; text-decoration: none; border-bottom: 1px solid #e5e7eb;">🏠 Tableau de bord</a>
                        <a href="join_session.php" style="display: block; padding: 0.75rem 1rem; color: #374151; text-decoration: none; border-bottom: 1px solid #e5e7eb; transition: background 0.2s; cursor: pointer;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">🎮 Rejoindre une session</a>
                        <a href="history.php" style="display: block; padding: 0.75rem 1rem; color: #374151; text-decoration: none; cursor: pointer;" onmouseover="this.style.background='#f3f4f6'" onmouseout="this.style.background='white'">📊 Mon historique</a>
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

            <!-- Actions principales -->
            <div class="main-actions">
                <div class="primary-action">
                    <div class="action-content">
                        <div class="action-icon">🎮</div>
                        <div class="action-text">
                            <h3>Rejoindre une session</h3>
                            <p>Participe à un quiz en temps réel</p>
                        </div>
                    </div>
                    <a href="join_session.php" class="btn btn-primary">Rejoindre</a>
                </div>
            </div>

            <!-- Statistiques compactes -->
            <?php if ($stats['total_games'] > 0): ?>
                <div class="stats-compact">
                    <h3>Tes performances</h3>
                    <div class="stats-row">
                        <div class="stat-mini">
                            <span class="stat-icon">🎮</span>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo $stats['total_games']; ?></div>
                                <div class="stat-label">Parties</div>
                            </div>
                        </div>
                        <div class="stat-mini highlight">
                            <span class="stat-icon">🏆</span>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo $stats['best_score']; ?></div>
                                <div class="stat-label">Meilleur</div>
                            </div>
                        </div>
                        <div class="stat-mini">
                            <span class="stat-icon">📊</span>
                            <div class="stat-info">
                                <div class="stat-value"><?php echo round($stats['avg_success_rate'], 0); ?>%</div>
                                <div class="stat-label">Réussite</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

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
    <script>
        // Fonction pour toggle le menu burger
        function toggleBurgerMenu() {
            console.log('toggleBurgerMenu appelée');
            const dropdown = document.getElementById('burgerDropdown');
            const burgerBtn = document.getElementById('burgerBtn');
            
            console.log('dropdown:', dropdown);
            console.log('burgerBtn:', burgerBtn);
            
            if (dropdown && burgerBtn) {
                const isShown = dropdown.style.display === 'block';
                dropdown.style.display = isShown ? 'none' : 'block';
                burgerBtn.textContent = isShown ? '☰' : '✕';
                console.log('Menu toggled, display:', dropdown.style.display);
            } else {
                console.error('Éléments non trouvés');
            }
        }
        
        // Test au chargement
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page chargée, vérification du menu burger...');
            const burgerBtn = document.getElementById('burgerBtn');
            const dropdown = document.getElementById('burgerDropdown');
            
            if (burgerBtn) {
                console.log('Bouton burger trouvé');
                burgerBtn.style.border = '2px solid red'; // Test visuel temporaire
            } else {
                console.error('Bouton burger non trouvé');
            }
            
            if (dropdown) {
                console.log('Dropdown trouvé');
            } else {
                console.error('Dropdown non trouvé');
            }
        });
        
        // Fermer le menu si on clique ailleurs
        document.addEventListener('click', function(event) {
            const burgerBtn = document.getElementById('burgerBtn');
            const dropdown = document.getElementById('burgerDropdown');
            
            if (burgerBtn && dropdown && 
                !burgerBtn.contains(event.target) && 
                !dropdown.contains(event.target)) {
                dropdown.style.display = 'none';
                burgerBtn.textContent = '☰';
                console.log('Menu fermé par clic extérieur');
            }
        });
    </script>
</body>
</html>
