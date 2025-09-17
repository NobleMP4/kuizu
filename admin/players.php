<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/User.php';

requireQuizManager(); // Accessible aux encadrants et admins

$current_user = getCurrentUserOrRedirect();

// Connexion à la base de données
$database = new Database();
$conn = $database->getConnection();

// Récupérer tous les joueurs avec leurs statistiques
$playersQuery = "SELECT 
    u.id, 
    u.username, 
    u.email, 
    u.first_name, 
    u.last_name, 
    u.created_at,
    COUNT(DISTINCT gh.id) as total_games,
    AVG(gh.final_score) as avg_score,
    MAX(gh.final_score) as best_score,
    AVG(gh.correct_answers * 100.0 / gh.total_questions) as avg_success_rate,
    SUM(gh.correct_answers) as total_correct_answers,
    SUM(gh.total_questions) as total_questions_answered,
    MAX(gh.played_at) as last_game_date
FROM users u
LEFT JOIN game_history gh ON u.id = gh.user_id
WHERE u.role = 'player'
GROUP BY u.id, u.username, u.email, u.first_name, u.last_name, u.created_at
ORDER BY total_games DESC, avg_score DESC";

$playersStmt = $conn->prepare($playersQuery);
$playersStmt->execute();
$players = $playersStmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques globales
$globalStatsQuery = "SELECT 
    COUNT(DISTINCT u.id) as total_players,
    COUNT(DISTINCT gh.id) as total_games_played,
    AVG(gh.final_score) as global_avg_score,
    COUNT(DISTINCT CASE WHEN gh.played_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN u.id END) as active_players_week
FROM users u
LEFT JOIN game_history gh ON u.id = gh.user_id
WHERE u.role = 'player'";

$globalStatsStmt = $conn->prepare($globalStatsQuery);
$globalStatsStmt->execute();
$globalStats = $globalStatsStmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Joueurs - Administration Kuizu</title>
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
                    <li class="menu-item">
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
                    <li class="menu-item active">
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
                <a href="../auth/logout.php" class="btn btn-outline-light btn-sm">
                    Déconnexion
                </a>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="admin-content">
            <div class="content-header">
                <h1>Gestion des Joueurs</h1>
                <p>Suivi des performances et statistiques des joueurs</p>
            </div>

            <!-- Statistiques globales -->
            <div class="stats-overview">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $globalStats['total_players']; ?></div>
                            <div class="stat-label">Joueurs inscrits</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🎮</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $globalStats['total_games_played']; ?></div>
                            <div class="stat-label">Parties jouées</div>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">🏆</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo round($globalStats['global_avg_score'] ?? 0, 0); ?></div>
                            <div class="stat-label">Score moyen</div>
                        </div>
                    </div>
                    
                    <div class="stat-card highlight">
                        <div class="stat-icon">⚡</div>
                        <div class="stat-content">
                            <div class="stat-value"><?php echo $globalStats['active_players_week']; ?></div>
                            <div class="stat-label">Actifs (7 jours)</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des joueurs -->
            <div class="players-container">
                <div class="players-header">
                    <h2>Liste des Joueurs (<?php echo count($players); ?>)</h2>
                    <div class="filter-controls">
                        <input type="text" id="searchPlayer" placeholder="Rechercher un joueur..." class="search-input">
                        <select id="sortPlayers" class="form-select">
                            <option value="games">Trier par parties</option>
                            <option value="score">Trier par score</option>
                            <option value="success">Trier par réussite</option>
                            <option value="recent">Plus récents</option>
                            <option value="name">Nom alphabétique</option>
                        </select>
                    </div>
                </div>

                <?php if (empty($players)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">👥</div>
                        <h3>Aucun joueur inscrit</h3>
                        <p>Il n'y a actuellement aucun joueur inscrit sur la plateforme.</p>
                    </div>
                <?php else: ?>
                    <div class="players-table-container">
                        <table class="players-table">
                            <thead>
                                <tr>
                                    <th>Joueur</th>
                                    <th>Inscription</th>
                                    <th>Statistiques</th>
                                    <th>Dernière activité</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="playersTableBody">
                                <?php foreach ($players as $player): ?>
                                    <tr class="player-row" data-player-id="<?php echo $player['id']; ?>" 
                                        onclick="showPlayerDetails(<?php echo $player['id']; ?>)" style="cursor: pointer;">
                                        <td class="player-info-cell">
                                            <div class="player-avatar-table">
                                                <?php echo strtoupper(substr($player['first_name'], 0, 1) . substr($player['last_name'], 0, 1)); ?>
                                            </div>
                                            <div class="player-details-table">
                                                <h4><?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?></h4>
                                                <p>@<?php echo htmlspecialchars($player['username']); ?> • <?php echo htmlspecialchars($player['email']); ?></p>
                                            </div>
                                        </td>
                                        <td class="date-cell">
                                            <div class="date-info">
                                                <div class="date-primary"><?php echo date('d/m/Y', strtotime($player['created_at'])); ?></div>
                                                <div class="date-secondary"><?php echo date('H:i', strtotime($player['created_at'])); ?></div>
                                            </div>
                                        </td>
                                        <td class="stats-cell">
                                            <?php if ($player['total_games'] > 0): ?>
                                                <div class="stat-item">
                                                    <span class="stat-label">Parties:</span>
                                                    <span class="stat-value"><?php echo $player['total_games']; ?></span>
                                                </div>
                                                <div class="stat-item">
                                                    <span class="stat-label">Meilleur:</span>
                                                    <span class="stat-value"><?php echo round($player['best_score'], 0); ?></span>
                                                </div>
                                                <div class="stat-item">
                                                    <span class="stat-label">Réussite:</span>
                                                    <span class="stat-value"><?php echo round($player['avg_success_rate'], 1); ?>%</span>
                                                </div>
                                            <?php else: ?>
                                                <span class="activity-never">Aucune activité</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="activity-cell">
                                            <?php if ($player['last_game_date']): ?>
                                                <?php echo date('d/m/Y', strtotime($player['last_game_date'])); ?>
                                                <br>
                                                <small><?php echo date('H:i', strtotime($player['last_game_date'])); ?></small>
                                            <?php else: ?>
                                                <span class="activity-never">Jamais joué</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-cell">
                                            <?php if ($player['total_games'] > 0): ?>
                                                <span class="status-badge active">Actif</span>
                                            <?php else: ?>
                                                <span class="status-badge inactive">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal détails joueur -->
    <div id="playerModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Détails du joueur</h3>
                <button onclick="closePlayerModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="playerDetailsContainer">
                    <!-- Les détails seront chargés ici -->
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        // Variables globales
        let allPlayers = <?php echo json_encode($players); ?>;
        
        // Recherche de joueurs
        document.getElementById('searchPlayer').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterPlayers(searchTerm);
        });
        
        // Tri des joueurs
        document.getElementById('sortPlayers').addEventListener('change', function() {
            const sortBy = this.value;
            sortPlayers(sortBy);
        });
        
        function filterPlayers(searchTerm) {
            const playerRows = document.querySelectorAll('.player-row');
            playerRows.forEach(row => {
                const playerName = row.querySelector('h4').textContent.toLowerCase();
                const playerEmail = row.querySelector('p').textContent.toLowerCase();
                
                if (playerName.includes(searchTerm) || playerEmail.includes(searchTerm)) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        function sortPlayers(sortBy) {
            const tbody = document.getElementById('playersTableBody');
            const playerRows = Array.from(tbody.children);
            
            playerRows.sort((a, b) => {
                const playerA = allPlayers.find(p => p.id == a.dataset.playerId);
                const playerB = allPlayers.find(p => p.id == b.dataset.playerId);
                
                switch(sortBy) {
                    case 'games':
                        return (playerB.total_games || 0) - (playerA.total_games || 0);
                    case 'score':
                        return (playerB.best_score || 0) - (playerA.best_score || 0);
                    case 'success':
                        return (playerB.avg_success_rate || 0) - (playerA.avg_success_rate || 0);
                    case 'recent':
                        return new Date(playerB.created_at) - new Date(playerA.created_at);
                    case 'name':
                        return (playerA.first_name + ' ' + playerA.last_name).localeCompare(playerB.first_name + ' ' + playerB.last_name);
                    default:
                        return 0;
                }
            });
            
            // Réorganiser les éléments
            playerRows.forEach(row => tbody.appendChild(row));
        }
        
        async function showPlayerDetails(playerId) {
            const modal = document.getElementById('playerModal');
            const container = document.getElementById('playerDetailsContainer');
            
            // Afficher un loader
            container.innerHTML = '<div class="loading">Chargement des détails...</div>';
            modal.style.display = 'block';
            
            try {
                const response = await fetch(`../api/player_details.php?id=${playerId}`);
                const data = await response.json();
                
                if (data.success) {
                    container.innerHTML = generatePlayerDetailsHTML(data.player, data.history, data.stats);
                } else {
                    container.innerHTML = '<div class="error">Erreur lors du chargement des détails.</div>';
                }
            } catch (error) {
                console.error('Erreur:', error);
                container.innerHTML = '<div class="error">Erreur de connexion.</div>';
            }
        }
        
        function generatePlayerDetailsHTML(player, history, stats) {
            return `
                <div class="player-details">
                    <div class="player-profile">
                        <div class="profile-header">
                            <div class="profile-avatar">
                                ${player.first_name.charAt(0).toUpperCase()}${player.last_name.charAt(0).toUpperCase()}
                            </div>
                            <div class="profile-info">
                                <h3>${player.first_name} ${player.last_name}</h3>
                                <p>@${player.username}</p>
                                <p>${player.email}</p>
                                <p>Inscrit le ${new Date(player.created_at).toLocaleDateString('fr-FR')}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detailed-stats">
                        <h4>Statistiques détaillées</h4>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value">${stats.total_games}</div>
                                <div class="stat-label">Parties jouées</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${Math.round(stats.avg_score || 0)}</div>
                                <div class="stat-label">Score moyen</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${stats.best_score || 0}</div>
                                <div class="stat-label">Meilleur score</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${Math.round(stats.avg_success_rate || 0)}%</div>
                                <div class="stat-label">Taux de réussite</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${stats.total_correct_answers || 0}</div>
                                <div class="stat-label">Bonnes réponses</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value">${stats.total_questions_answered || 0}</div>
                                <div class="stat-label">Questions répondues</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="recent-games">
                        <h4>Parties récentes</h4>
                        <div class="games-list">
                            ${history.map(game => `
                                <div class="game-item">
                                    <div class="game-info">
                                        <h5>${game.quiz_title}</h5>
                                        <p>${new Date(game.played_at).toLocaleDateString('fr-FR')}</p>
                                    </div>
                                    <div class="game-score">
                                        <span class="score">${game.final_score} pts</span>
                                        <span class="success-rate">${Math.round((game.correct_answers / game.total_questions) * 100)}%</span>
                                    </div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                </div>
            `;
        }
        
        function closePlayerModal() {
            document.getElementById('playerModal').style.display = 'none';
        }
    </script>
</body>
</html>
