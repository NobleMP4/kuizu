<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/Quiz.php';

requireQuizManager();

$current_user = getCurrentUserOrRedirect();
$quiz = new Quiz();
$quizzes = $quiz->getAll($current_user['id']);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Quiz - Kuizu Sapeurs-Pompiers</title>
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
                <li class="menu-item active">
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
                <?php if (User::canManageUsers()): ?>
                    <li class="menu-item">
                        <a href="users.php">
                            <span class="menu-icon">👥</span>
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
                        <h1>Mes Quiz</h1>
                        <p>Gérez vos quiz et créez de nouveaux contenus pédagogiques</p>
                    </div>
                    <a href="quiz_create.php" class="btn btn-primary">
                        ➕ Nouveau quiz
                    </a>
                </div>
            </div>

            <?php if (empty($quizzes)): ?>
                <div class="empty-state">
                    <div class="empty-icon">❓</div>
                    <h3>Aucun quiz créé</h3>
                    <p>Commencez par créer votre premier quiz pour les jeunes sapeurs-pompiers</p>
                    <a href="quiz_create.php" class="btn btn-primary">Créer mon premier quiz</a>
                </div>
            <?php else: ?>
                <div class="quizzes-container">
                    <div class="quizzes-grid">
                        <?php foreach ($quizzes as $quiz_item): ?>
                            <div class="quiz-card">
                                <div class="quiz-header">
                                    <h3><?php echo htmlspecialchars($quiz_item['title']); ?></h3>
                                    <div class="quiz-badges">
                                        <?php if ($quiz_item['is_active']): ?>
                                            <span class="badge badge-success">Actif</span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Inactif</span>
                                        <?php endif; ?>
                                        
                                        <?php if ($quiz_item['is_locked']): ?>
                                            <span class="badge badge-warning">Verrouillé</span>
                                        <?php else: ?>
                                            <span class="badge badge-info">Ouvert</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="quiz-content">
                                    <?php if ($quiz_item['description']): ?>
                                        <p><?php echo htmlspecialchars(substr($quiz_item['description'], 0, 120) . (strlen($quiz_item['description']) > 120 ? '...' : '')); ?></p>
                                    <?php else: ?>
                                        <p class="no-description">Aucune description</p>
                                    <?php endif; ?>
                                    
                                    <div class="quiz-meta">
                                        <span class="meta-item">
                                            <span class="meta-icon">📝</span>
                                            <?php echo $quiz_item['question_count']; ?> question(s)
                                        </span>
                                        <span class="meta-item">
                                            <span class="meta-icon">📅</span>
                                            <?php echo date('d/m/Y', strtotime($quiz_item['created_at'])); ?>
                                        </span>
                                        <?php if ($quiz_item['updated_at'] != $quiz_item['created_at']): ?>
                                            <span class="meta-item">
                                                <span class="meta-icon">🔄</span>
                                                Modifié le <?php echo date('d/m/Y', strtotime($quiz_item['updated_at'])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="quiz-actions">
                                    <a href="quiz_questions.php?id=<?php echo $quiz_item['id']; ?>" 
                                       class="btn btn-sm btn-primary">
                                        📝 Questions
                                    </a>
                                    
                                    <?php if ($quiz_item['question_count'] > 0): ?>
                                        <a href="session_create.php?quiz=<?php echo $quiz_item['id']; ?>" 
                                           class="btn btn-sm btn-success">
                                            🎮 Lancer
                                        </a>
                                    <?php endif; ?>
                                    
                                    <button onclick="toggleQuizStatus(<?php echo $quiz_item['id']; ?>, 'active')" 
                                            class="btn btn-sm <?php echo $quiz_item['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                        <?php echo $quiz_item['is_active'] ? '⏸️' : '▶️'; ?>
                                    </button>
                                    
                                    <button onclick="toggleQuizStatus(<?php echo $quiz_item['id']; ?>, 'lock')" 
                                            class="btn btn-sm <?php echo $quiz_item['is_locked'] ? 'btn-info' : 'btn-warning'; ?>">
                                        <?php echo $quiz_item['is_locked'] ? '🔓' : '🔒'; ?>
                                    </button>
                                    
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-primary dropdown-toggle" onclick="toggleDropdown(this)">
                                            ⋮
                                        </button>
                                        <div class="dropdown-menu">
                                            <a href="quiz_edit.php?id=<?php echo $quiz_item['id']; ?>" class="dropdown-item">
                                                ✏️ Modifier
                                            </a>
                                            <button onclick="generateQRCode(<?php echo $quiz_item['id']; ?>)" class="dropdown-item">
                                                📱 QR Code
                                            </button>
                                            <button onclick="showStats(<?php echo $quiz_item['id']; ?>)" class="dropdown-item">
                                                📊 Statistiques
                                            </button>
                                            <div class="dropdown-divider"></div>
                                            <button onclick="deleteQuiz(<?php echo $quiz_item['id']; ?>)" class="dropdown-item danger">
                                                🗑️ Supprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function toggleDropdown(button) {
            const dropdown = button.parentElement;
            const menu = dropdown.querySelector('.dropdown-menu');
            
            // Fermer tous les autres dropdowns
            document.querySelectorAll('.dropdown-menu.show').forEach(otherMenu => {
                if (otherMenu !== menu) {
                    otherMenu.classList.remove('show');
                }
            });
            
            menu.classList.toggle('show');
        }

        // Fermer les dropdowns en cliquant ailleurs
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                    menu.classList.remove('show');
                });
            }
        });

        async function deleteQuiz(quizId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer ce quiz ? Cette action est irréversible et supprimera toutes les questions associées.')) {
                return;
            }

            try {
                const response = await fetch('../api/quiz_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'delete',
                        quiz_id: quizId
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

        async function showStats(quizId) {
            try {
                const response = await fetch('../api/quiz_actions.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_stats',
                        quiz_id: quizId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    const stats = result.stats;
                    alert(`Statistiques du quiz:\n\n` +
                          `• Parties jouées: ${stats.total_games}\n` +
                          `• Joueurs uniques: ${stats.unique_players}\n` +
                          `• Score moyen: ${stats.average_score}\n` +
                          `• Taux de réussite: ${stats.success_rate}%`);
                } else {
                    alert('Erreur lors de la récupération des statistiques');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            }
        }
    </script>
</body>
</html>

<style>
.quizzes-container {
    max-width: 1400px;
}

.quizzes-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
    gap: 2rem;
}

.quiz-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: visible;
    transition: var(--transition);
    display: flex;
    flex-direction: column;
    position: relative;
}

.quiz-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-lg);
}

.quiz-header {
    padding: 1.5rem;
    background: var(--gray-50);
    border-bottom: 1px solid var(--gray-200);
}

.quiz-header h3 {
    color: var(--gray-800);
    margin-bottom: 1rem;
    font-size: 1.3rem;
    line-height: 1.3;
}

.quiz-badges {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.quiz-content {
    padding: 1.5rem;
    flex: 1;
}

.quiz-content p {
    color: var(--gray-600);
    line-height: 1.5;
    margin-bottom: 1rem;
}

.no-description {
    font-style: italic;
    color: var(--gray-400);
}

.quiz-meta {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.85rem;
    color: var(--gray-500);
}

.meta-icon {
    font-size: 1rem;
}

.quiz-actions {
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
    align-items: center;
    position: relative;
}

.dropdown {
    position: relative;
    margin-left: auto;
    z-index: 10;
}

.dropdown-toggle {
    background: var(--white);
    border: 1px solid var(--gray-300);
    padding: 0.5rem 0.75rem;
    border-radius: var(--border-radius);
    cursor: pointer;
    font-weight: bold;
    color: var(--gray-600);
    transition: var(--transition);
    min-width: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.dropdown-toggle:hover {
    background: var(--gray-50);
    border-color: var(--primary-color);
    color: var(--primary-color);
}

.dropdown-menu {
    position: absolute;
    top: 100%;
    right: 0;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
    min-width: 180px;
    z-index: 99999;
    opacity: 0;
    visibility: hidden;
    transform: translateY(-10px);
    transition: var(--transition);
}

.dropdown-menu.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-item {
    display: block;
    padding: 0.75rem 1rem;
    color: var(--gray-700);
    text-decoration: none;
    border: none;
    background: none;
    width: 100%;
    text-align: left;
    cursor: pointer;
    transition: var(--transition);
    font-size: 0.9rem;
}

.dropdown-item:hover {
    background: var(--gray-50);
    color: var(--gray-800);
}

.dropdown-item.danger {
    color: var(--error-color);
}

.dropdown-item.danger:hover {
    background: #fee2e2;
    color: var(--error-color);
}

.dropdown-divider {
    height: 1px;
    background: var(--gray-200);
    margin: 0.5rem 0;
}

@media (max-width: 768px) {
    .quizzes-grid {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .quiz-actions {
        flex-direction: column;
        align-items: stretch;
    }
    
    .quiz-actions .btn {
        flex: 1;
        justify-content: center;
    }
    
    .dropdown {
        margin-left: 0;
        margin-top: 0.5rem;
    }
}
</style>
