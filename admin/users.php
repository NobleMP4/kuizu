<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/User.php';

requireUserManager();

$current_user = getCurrentUserOrRedirect();
$user = new User();

// Obtenir tous les utilisateurs
$users = $user->getAllUsers();

// Calculer les statistiques
$total_users = count($users);
$admin_count = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
$encadrant_count = count(array_filter($users, fn($u) => $u['role'] === 'encadrant'));
$player_count = count(array_filter($users, fn($u) => $u['role'] === 'player'));

// Obtenir les statistiques de jeu pour chaque utilisateur
$database = new Database();
$conn = $database->getConnection();

$user_stats = [];
foreach ($users as $user_item) {
    $statsQuery = "SELECT 
                  COUNT(*) as total_games,
                  AVG(final_score) as avg_score,
                  MAX(final_score) as best_score,
                  AVG(correct_answers * 100.0 / total_questions) as avg_success_rate,
                  MAX(played_at) as last_played
                  FROM game_history 
                  WHERE user_id = :user_id";
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bindParam(":user_id", $user_item['id']);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    $user_stats[$user_item['id']] = $stats;
}

$error_message = '';
$success_message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $user_id = $_POST['user_id'] ?? '';
    
    if ($action === 'change_role' && $user_id && $user_id != $current_user['id']) {
        $new_role = $_POST['new_role'] ?? '';
        
        if (in_array($new_role, ['admin', 'encadrant', 'player'])) {
            $updateQuery = "UPDATE users SET role = :new_role WHERE id = :user_id";
            $updateStmt = $conn->prepare($updateQuery);
            $updateStmt->bindParam(":new_role", $new_role);
            $updateStmt->bindParam(":user_id", $user_id);
            
            if ($updateStmt->execute()) {
                $success_message = 'Rôle utilisateur modifié avec succès';
                $users = $user->getAllUsers(); // Recharger
                
                // Recalculer les statistiques
                $admin_count = count(array_filter($users, fn($u) => $u['role'] === 'admin'));
                $encadrant_count = count(array_filter($users, fn($u) => $u['role'] === 'encadrant'));
                $player_count = count(array_filter($users, fn($u) => $u['role'] === 'player'));
            } else {
                $error_message = 'Erreur lors de la modification du rôle';
            }
        } else {
            $error_message = 'Rôle invalide';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - Kuizu Sapeurs-Pompiers</title>
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
                <li class="menu-item">
                    <a href="players.php">
                        <span class="menu-icon">👥</span>
                        Joueurs
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="users.php">
                        <span class="menu-icon">⚙️</span>
                        Utilisateurs
                    </a>
                </li>
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
                <div class="header-actions">
                    <div>
                        <h1>Gestion des utilisateurs</h1>
                        <p>Consultez et gérez les comptes utilisateurs de la plateforme</p>
                    </div>
                    <div class="header-buttons">
                        <button onclick="exportUsers()" class="btn btn-outline-primary">
                            📥 Exporter
                        </button>
                    </div>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistiques des utilisateurs -->
            <div class="users-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3><?php echo $total_users; ?></h3>
                            <p>Utilisateurs totaux</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👨‍💼</div>
                        <div class="stat-content">
                            <h3><?php echo $admin_count; ?></h3>
                            <p>Administrateurs</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👨‍🏫</div>
                        <div class="stat-content">
                            <h3><?php echo $encadrant_count; ?></h3>
                            <p>Encadrants</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👨‍🎓</div>
                        <div class="stat-content">
                            <h3><?php echo $player_count; ?></h3>
                            <p>Joueurs</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des utilisateurs -->
            <div class="users-container">
                <div class="users-header">
                    <h2>Tous les utilisateurs (<?php echo count($users); ?>)</h2>
                    <div class="filter-controls">
                        <select id="roleFilter" onchange="filterUsers()">
                            <option value="all">Tous les rôles</option>
                            <option value="admin">Administrateurs</option>
                            <option value="encadrant">Encadrants</option>
                            <option value="player">Joueurs</option>
                        </select>
                        <select id="activityFilter" onchange="filterUsers()">
                            <option value="all">Toute activité</option>
                            <option value="active">Joueurs actifs</option>
                            <option value="inactive">Jamais joué</option>
                        </select>
                    </div>
                </div>
                
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Rôle</th>
                                <th>Inscription</th>
                                <th>Statistiques</th>
                                <th>Dernière activité</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user_item): ?>
                                <?php $stats = $user_stats[$user_item['id']]; ?>
                                <tr class="user-row" data-role="<?php echo $user_item['role']; ?>" 
                                    data-activity="<?php echo $stats['total_games'] > 0 ? 'active' : 'inactive'; ?>">
                                    <td class="user-info-cell">
                                        <div class="user-avatar-table">
                                            <?php echo strtoupper(substr($user_item['first_name'], 0, 1) . substr($user_item['last_name'], 0, 1)); ?>
                                        </div>
                                        <div class="user-details-table">
                                            <h4>
                                                <?php echo htmlspecialchars($user_item['first_name'] . ' ' . $user_item['last_name']); ?>
                                                <?php if ($user_item['id'] == $current_user['id']): ?>
                                                    <span class="current-user-badge">Vous</span>
                                                <?php endif; ?>
                                            </h4>
                                            <p>@<?php echo htmlspecialchars($user_item['username']); ?> • <?php echo htmlspecialchars($user_item['email']); ?></p>
                                        </div>
                                    </td>
                                    <td class="user-role">
                                        <span class="role-badge role-<?php echo $user_item['role']; ?>">
                                            <?php 
                                            $role_labels = [
                                                'admin' => '👨‍💼 Admin',
                                                'encadrant' => '👨‍🏫 Encadrant',
                                                'player' => '👨‍🎓 Joueur'
                                            ];
                                            echo $role_labels[$user_item['role']] ?? '👤 Inconnu';
                                            ?>
                                        </span>
                                    </td>
                                    <td class="user-created">
                                        <div class="date-info">
                                            <div class="date-primary"><?php echo date('d/m/Y', strtotime($user_item['created_at'])); ?></div>
                                            <div class="date-secondary"><?php echo date('H:i', strtotime($user_item['created_at'])); ?></div>
                                        </div>
                                    </td>
                                    <td class="stats-cell">
                                        <?php if ($stats['total_games'] > 0): ?>
                                            <div class="stat-item">
                                                <span class="stat-label">Parties:</span>
                                                <span class="stat-value"><?php echo $stats['total_games']; ?></span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-label">Score moy:</span>
                                                <span class="stat-value"><?php echo round($stats['avg_score'], 0); ?></span>
                                            </div>
                                            <div class="stat-item">
                                                <span class="stat-label">Réussite:</span>
                                                <span class="stat-value"><?php echo round($stats['avg_success_rate'], 1); ?>%</span>
                                            </div>
                                        <?php else: ?>
                                            <span class="activity-never">Aucune activité</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="activity-cell">
                                        <?php if ($stats['last_played']): ?>
                                            <?php echo date('d/m/Y', strtotime($stats['last_played'])); ?>
                                            <br>
                                            <small><?php echo date('H:i', strtotime($stats['last_played'])); ?></small>
                                        <?php else: ?>
                                            <span class="activity-never">Jamais joué</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="actions-cell">
                                        <?php if ($user_item['id'] != $current_user['id']): ?>
                                            <select onchange="changeUserRole(<?php echo $user_item['id']; ?>, this.value)" 
                                                    class="role-select"
                                                    data-current-role="<?php echo $user_item['role']; ?>">
                                                <option value="admin" <?php echo $user_item['role'] === 'admin' ? 'selected' : ''; ?>>
                                                    👨‍💼 Admin
                                                </option>
                                                <option value="encadrant" <?php echo $user_item['role'] === 'encadrant' ? 'selected' : ''; ?>>
                                                    👨‍🏫 Encadrant
                                                </option>
                                                <option value="player" <?php echo $user_item['role'] === 'player' ? 'selected' : ''; ?>>
                                                    👨‍🎓 Joueur
                                                </option>
                                            </select>
                                        <?php else: ?>
                                            <span class="current-user-note">Compte actuel</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function filterUsers() {
            const roleFilter = document.getElementById('roleFilter').value;
            const activityFilter = document.getElementById('activityFilter').value;
            const rows = document.querySelectorAll('.user-row');
            
            rows.forEach(row => {
                const role = row.dataset.role;
                const activity = row.dataset.activity;
                
                const roleMatch = roleFilter === 'all' || role === roleFilter;
                const activityMatch = activityFilter === 'all' || activity === activityFilter;
                
                if (roleMatch && activityMatch) {
                    row.style.display = 'table-row';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        async function changeUserRole(userId, newRole) {
            if (!newRole) return;
            
            const selectElement = event.target;
            const currentRole = selectElement.getAttribute('data-current-role') || '';
            
            // Si le rôle n'a pas changé, ne rien faire
            if (newRole === currentRole) {
                return;
            }
            
            const roleLabels = {
                'admin': 'Administrateur',
                'encadrant': 'Encadrant', 
                'player': 'Joueur'
            };
            
            if (!confirm(`Êtes-vous sûr de vouloir changer ce compte en ${roleLabels[newRole]} ?`)) {
                // Remettre la sélection précédente
                selectElement.value = currentRole;
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'change_role');
                formData.append('user_id', userId);
                formData.append('new_role', newRole);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Erreur lors de la modification du rôle');
                    selectElement.value = currentRole;
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
                selectElement.value = currentRole;
            }
        }

        function exportUsers() {
            // Créer un CSV simple des utilisateurs
            let csv = 'Nom,Prénom,Email,Nom d\'utilisateur,Rôle,Date d\'inscription,Parties jouées,Score moyen\n';
            
            const rows = document.querySelectorAll('.user-row');
            rows.forEach(row => {
                if (row.style.display !== 'none') {
                    const cells = row.querySelectorAll('td');
                    const userName = cells[0].querySelector('.user-name').textContent.trim().replace(' Vous', '');
                    const [firstName, ...lastNameParts] = userName.split(' ');
                    const lastName = lastNameParts.join(' ');
                    const email = cells[0].querySelector('.user-meta').textContent.split('•')[1].trim();
                    const username = cells[0].querySelector('.user-meta').textContent.split('•')[0].trim().replace('@', '');
                    const role = cells[1].textContent.includes('Admin') ? 'Admin' : 'Joueur';
                    const createdDate = cells[2].querySelector('.date-primary').textContent;
                    const statsText = cells[3].textContent.trim();
                    const games = statsText.includes('parties') ? statsText.split(' ')[0] : '0';
                    const avgScore = statsText.includes('moy.') ? statsText.split(' ')[2] : '0';
                    
                    csv += `"${lastName}","${firstName}","${email}","${username}","${role}","${createdDate}","${games}","${avgScore}"\n`;
                }
            });
            
            // Télécharger le fichier
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `utilisateurs_kuizu_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
    </script>
</body>
</html>

<style>
.users-stats {
    margin-bottom: 3rem;
}

.users-container {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.users-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.users-header h2 {
    margin: 0;
    color: var(--gray-800);
}

.filter-controls {
    display: flex;
    gap: 1rem;
}

.filter-controls select {
    padding: 0.5rem 1rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    background: var(--white);
    font-size: 0.9rem;
}

.users-table-container {
    overflow-x: auto;
}

.users-table {
    width: 100%;
    border-collapse: collapse;
}

.users-table th {
    background: var(--gray-100);
    color: var(--gray-700);
    font-weight: 600;
    padding: 1rem;
    text-align: left;
    border-bottom: 2px solid var(--gray-200);
    font-size: 0.9rem;
    white-space: nowrap;
}

.users-table td {
    padding: 1rem;
    border-bottom: 1px solid var(--gray-200);
    vertical-align: top;
}

.user-row:hover {
    background: var(--gray-50);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    font-size: 2rem;
    flex-shrink: 0;
}

.user-details {
    flex: 1;
    min-width: 0;
}

.user-name {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.current-user-badge {
    background: var(--secondary-color);
    color: var(--white);
    padding: 0.125rem 0.5rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 500;
}

.user-meta {
    font-size: 0.85rem;
    color: var(--gray-500);
}

.role-badge {
    padding: 0.375rem 0.75rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 0.8rem;
}

.role-admin {
    background: #dbeafe;
    color: #1e40af;
}

.role-encadrant {
    background: #fff4f0;
    color: #ea580c;
}

.role-player {
    background: #d1fae5;
    color: #065f46;
}

.date-info {
    text-align: center;
}

.date-primary {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.date-secondary {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.stats-summary {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

.stat-item {
    text-align: center;
}

.stat-value {
    display: block;
    font-weight: 600;
    color: var(--primary-color);
    font-size: 0.9rem;
}

.stat-label {
    font-size: 0.75rem;
    color: var(--gray-500);
}

.no-activity {
    color: var(--gray-400);
    font-style: italic;
    font-size: 0.9rem;
}

.activity-info {
    text-align: center;
}

.activity-date {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.activity-time {
    font-size: 0.8rem;
    color: var(--gray-500);
}

.user-actions {
    text-align: center;
}

.current-user-note {
    color: var(--gray-500);
    font-style: italic;
    font-size: 0.85rem;
}

.role-select {
    padding: 0.375rem 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    background: var(--white);
    font-size: 0.85rem;
    cursor: pointer;
    transition: var(--transition);
}

.role-select:hover {
    border-color: var(--primary-color);
}

.role-select:focus {
    outline: none;
    border-color: var(--primary-color);
    box-shadow: 0 0 0 2px rgba(34, 77, 113, 0.1);
}

@media (max-width: 1200px) {
    .users-table th,
    .users-table td {
        padding: 0.75rem 0.5rem;
        font-size: 0.85rem;
    }
    
    .stats-summary {
        flex-direction: column;
        gap: 0.5rem;
    }
}

@media (max-width: 768px) {
    .users-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .filter-controls {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .users-table-container {
        font-size: 0.8rem;
    }
    
    .users-table th,
    .users-table td {
        padding: 0.5rem 0.25rem;
    }
    
    .user-info {
        flex-direction: column;
        gap: 0.5rem;
        text-align: center;
    }
    
    .user-name {
        justify-content: center;
    }
    
    .stats-summary {
        gap: 0.25rem;
    }
    
    .stat-item {
        font-size: 0.8rem;
    }
}

@media (max-width: 480px) {
    /* Masquer certaines colonnes sur très petits écrans */
    .users-table th:nth-child(3),
    .users-table td:nth-child(3),
    .users-table th:nth-child(5),
    .users-table td:nth-child(5) {
        display: none;
    }
    
    .users-table th,
    .users-table td {
        padding: 0.75rem 0.5rem;
    }
}
</style>
