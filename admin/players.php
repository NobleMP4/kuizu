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
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/admin.css?v=<?php echo time(); ?>">
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
            <div class="players-container" style="background: white; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); padding: 2rem; margin-bottom: 2rem;">
                <div class="players-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;">
                    <h2 style="margin: 0; color: #374151; font-size: 1.5rem;">Liste des Joueurs (<?php echo count($players); ?>)</h2>
                    <div class="filter-controls" style="display: flex; gap: 1rem; align-items: center;">
                        <input type="text" id="searchPlayer" placeholder="Rechercher un joueur..." 
                               style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; min-width: 250px;">
                        <select id="sortPlayers" 
                                style="padding: 0.5rem 1rem; border: 1px solid #d1d5db; border-radius: 6px; font-size: 0.875rem; background: white; cursor: pointer;">
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
                    <div class="players-table-container" style="border: 1px solid #ddd; border-radius: 8px; overflow: hidden;">
                        <table class="players-table" style="width: 100%; border-collapse: collapse; background: white;">
                            <thead>
                                <tr>
                                    <th style="background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600;">Joueur</th>
                                    <th style="background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600;">Inscription</th>
                                    <th style="background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600;">Statistiques</th>
                                    <th style="background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600;">Dernière activité</th>
                                    <th style="background: #f8f9fa; padding: 1rem; border-bottom: 1px solid #dee2e6; font-weight: 600;">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="playersTableBody">
                                <?php foreach ($players as $player): ?>
                                    <tr class="player-row" data-player-id="<?php echo $player['id']; ?>" 
                                        onclick="showPlayerDetails(<?php echo $player['id']; ?>)" style="cursor: pointer;">
                                        <td style="padding: 1rem; border-bottom: 1px solid #f1f5f9;">
                                            <div style="display: flex; align-items: center; gap: 1rem;">
                                                <div style="width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, #224d71, #f46e46); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700;">
                                                    <?php echo strtoupper(substr($player['first_name'], 0, 1) . substr($player['last_name'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <h4 style="margin: 0 0 0.25rem 0; color: #374151; font-size: 1rem; font-weight: 600;">
                                                        <?php echo htmlspecialchars($player['first_name'] . ' ' . $player['last_name']); ?>
                                                    </h4>
                                                    <p style="margin: 0; color: #6b7280; font-size: 0.875rem;">
                                                        @<?php echo htmlspecialchars($player['username']); ?> • <?php echo htmlspecialchars($player['email']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem; border-bottom: 1px solid #f1f5f9;">
                                            <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                                                <?php echo date('d/m/Y', strtotime($player['created_at'])); ?>
                                            </div>
                                            <div style="color: #6b7280; font-size: 0.75rem;">
                                                <?php echo date('H:i', strtotime($player['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td style="padding: 1rem; border-bottom: 1px solid #f1f5f9;">
                                            <?php if ($player['total_games'] > 0): ?>
                                                <div style="display: flex; flex-direction: column; gap: 0.25rem; font-size: 0.875rem;">
                                                    <div style="display: flex; justify-content: space-between;">
                                                        <span style="color: #6b7280;">Parties:</span>
                                                        <span style="font-weight: 600; color: #374151;"><?php echo $player['total_games']; ?></span>
                                                    </div>
                                                    <div style="display: flex; justify-content: space-between;">
                                                        <span style="color: #6b7280;">Meilleur:</span>
                                                        <span style="font-weight: 600; color: #374151;"><?php echo round($player['best_score'], 0); ?></span>
                                                    </div>
                                                    <div style="display: flex; justify-content: space-between;">
                                                        <span style="color: #6b7280;">Réussite:</span>
                                                        <span style="font-weight: 600; color: #374151;"><?php echo round($player['avg_success_rate'], 1); ?>%</span>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #9ca3af; font-style: italic;">Aucune activité</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem; border-bottom: 1px solid #f1f5f9;">
                                            <?php if ($player['last_game_date']): ?>
                                                <div style="font-weight: 600; color: #374151; margin-bottom: 0.25rem;">
                                                    <?php echo date('d/m/Y', strtotime($player['last_game_date'])); ?>
                                                </div>
                                                <div style="color: #6b7280; font-size: 0.75rem;">
                                                    <?php echo date('H:i', strtotime($player['last_game_date'])); ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color: #9ca3af; font-style: italic;">Jamais joué</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="padding: 1rem; border-bottom: 1px solid #f1f5f9;">
                                            <?php if ($player['total_games'] > 0): ?>
                                                <span style="background: #10b981; color: white; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Actif</span>
                                            <?php else: ?>
                                                <span style="background: #e5e7eb; color: #6b7280; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">Inactif</span>
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
        <div class="modal-content modal-xl">
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
    
    <!-- Modal changement de mot de passe -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Changer le mot de passe</h3>
                <button onclick="closePasswordModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <form id="passwordForm">
                    <input type="hidden" id="passwordUserId" name="user_id">
                    <div class="form-group">
                        <label for="newPassword">Nouveau mot de passe</label>
                        <input type="password" id="newPassword" name="new_password" class="form-control" 
                               placeholder="Entrez le nouveau mot de passe" required minlength="6">
                    </div>
                    <div class="form-group">
                        <label for="confirmPassword">Confirmer le mot de passe</label>
                        <input type="password" id="confirmPassword" name="confirm_password" class="form-control" 
                               placeholder="Confirmez le nouveau mot de passe" required minlength="6">
                    </div>
                    <div class="form-actions">
                        <button type="button" onclick="closePasswordModal()" class="btn btn-secondary">Annuler</button>
                        <button type="submit" class="btn btn-primary">Changer le mot de passe</button>
                    </div>
                </form>
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
                    
                    <div class="player-actions">
                        <h4>Actions disponibles</h4>
                        <div class="action-buttons">
                            <button onclick="changePlayerPassword(${player.id}, '${player.first_name} ${player.last_name}')" class="btn-action btn-warning">
                                <span>🔑</span> Changer le mot de passe
                            </button>
                            <button onclick="resetPlayerStats(${player.id})" class="btn-action btn-danger">
                                <span>🗑️</span> Réinitialiser les stats
                            </button>
                            <button onclick="sendPlayerNotification(${player.id})" class="btn-action btn-primary">
                                <span>📧</span> Envoyer notification
                            </button>
                        </div>
                    </div>
                </div>
            `;
        }
        
        function closePlayerModal() {
            document.getElementById('playerModal').style.display = 'none';
        }
        
        // Fonctions pour les actions
        function changePlayerPassword(playerId, playerName) {
            document.getElementById('passwordUserId').value = playerId;
            document.querySelector('#passwordModal .modal-header h3').textContent = `Changer le mot de passe - ${playerName}`;
            document.getElementById('passwordModal').style.display = 'block';
        }
        
        function closePasswordModal() {
            document.getElementById('passwordModal').style.display = 'none';
            document.getElementById('passwordForm').reset();
        }
        
        // Gestion du formulaire de changement de mot de passe
        document.getElementById('passwordForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            if (newPassword !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
            
            if (newPassword.length < 6) {
                alert('Le mot de passe doit contenir au moins 6 caractères.');
                return;
            }
            
            try {
                const response = await fetch('../api/change_password.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Mot de passe changé avec succès.');
                    closePasswordModal();
                } else {
                    alert('Erreur: ' + data.message);
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion.');
            }
        });
        
        function resetPlayerStats(playerId) {
            if (confirm('Êtes-vous sûr de vouloir réinitialiser les statistiques de ce joueur ? Cette action est irréversible.')) {
                // TODO: Implémenter la réinitialisation des stats
                alert('Fonctionnalité à implémenter');
            }
        }
        
        function sendPlayerNotification(playerId) {
            // TODO: Implémenter l'envoi de notification
            alert('Fonctionnalité à implémenter');
        }
    </script>
</body>
</html>
