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
    <title>Dashboard Admin - Kuizu Sapeurs-Pompiers</title>
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
                <li class="menu-item active">
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
                <a href="../auth/logout.php" class="btn btn-outline-primary btn-sm">
                    Déconnexion
                </a>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="admin-content">
            <div class="content-header">
                <h1>Tableau de bord</h1>
                <p>Vue d'ensemble de votre plateforme de quiz</p>
            </div>

            <!-- Statistiques rapides -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">❓</div>
                    <div class="stat-content">
                        <h3><?php echo count($quizzes); ?></h3>
                        <p>Quiz créés</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">✅</div>
                    <div class="stat-content">
                        <h3><?php echo count(array_filter($quizzes, fn($q) => $q['is_active'])); ?></h3>
                        <p>Quiz actifs</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">🔓</div>
                    <div class="stat-content">
                        <h3><?php echo count(array_filter($quizzes, fn($q) => !$q['is_locked'])); ?></h3>
                        <p>Quiz déverrouillés</p>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">📝</div>
                    <div class="stat-content">
                        <h3><?php echo array_sum(array_column($quizzes, 'question_count')); ?></h3>
                        <p>Questions totales</p>
                    </div>
                </div>
            </div>

            <!-- Actions rapides -->
            <div class="quick-actions">
                <h2>Actions rapides</h2>
                <div class="action-cards">
                    <div class="action-card">
                        <div class="action-icon">➕</div>
                        <h3>Nouveau quiz</h3>
                        <p>Créer un nouveau quiz avec questions</p>
                        <a href="quiz_create.php" class="btn btn-primary">Créer</a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">🎮</div>
                        <h3>Lancer une session</h3>
                        <p>Démarrer une session de jeu en temps réel</p>
                        <a href="session_create.php" class="btn btn-success">Lancer</a>
                    </div>
                    
                    <div class="action-card">
                        <div class="action-icon">📊</div>
                        <h3>Voir les résultats</h3>
                        <p>Consulter les statistiques des parties</p>
                        <a href="results.php" class="btn btn-info">Consulter</a>
                    </div>
                </div>
            </div>

            <!-- Quiz récents -->
            <div class="recent-quizzes">
                <div class="section-header">
                    <h2>Quiz récents</h2>
                    <a href="quizzes.php" class="btn btn-outline-primary">Voir tous</a>
                </div>
                
                <?php if (empty($quizzes)): ?>
                    <div class="empty-state">
                        <div class="empty-icon">❓</div>
                        <h3>Aucun quiz créé</h3>
                        <p>Commencez par créer votre premier quiz pour les jeunes sapeurs-pompiers</p>
                        <a href="quiz_create.php" class="btn btn-primary">Créer mon premier quiz</a>
                    </div>
                <?php else: ?>
                    <div class="quiz-grid">
                        <?php foreach (array_slice($quizzes, 0, 6) as $quiz_item): ?>
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
                                    <p><?php echo htmlspecialchars(substr($quiz_item['description'], 0, 100) . (strlen($quiz_item['description']) > 100 ? '...' : '')); ?></p>
                                    <div class="quiz-meta">
                                        <span>📝 <?php echo $quiz_item['question_count']; ?> question(s)</span>
                                        <span>📅 <?php echo date('d/m/Y', strtotime($quiz_item['created_at'])); ?></span>
                                    </div>
                                </div>
                                
                                <div class="quiz-actions">
                                    <a href="quiz_edit.php?id=<?php echo $quiz_item['id']; ?>" class="btn btn-sm btn-primary">Modifier</a>
                                    <a href="quiz_questions.php?id=<?php echo $quiz_item['id']; ?>" class="btn btn-sm btn-secondary">Questions</a>
                                    <?php if ($quiz_item['question_count'] > 0): ?>
                                        <a href="session_create.php?quiz=<?php echo $quiz_item['id']; ?>" class="btn btn-sm btn-success">Jouer</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>
