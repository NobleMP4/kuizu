<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/Quiz.php';

requireQuizManager();

$current_user = getCurrentUserOrRedirect();
$error_message = '';
$success_message = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if (empty($title)) {
        $error_message = 'Le titre est requis';
    } else {
        $quiz = new Quiz();
        $result = $quiz->create($title, $description, $current_user['id']);
        
        if ($result['success']) {
            header('Location: quiz_questions.php?id=' . $result['quiz_id'] . '&new=1');
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un quiz - Kuizu Sapeurs-Pompiers</title>
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
                <div class="header-actions">
                    <div>
                        <h1>Créer un nouveau quiz</h1>
                        <p>Créez un quiz pour tester les connaissances des jeunes sapeurs-pompiers</p>
                    </div>
                    <a href="quizzes.php" class="btn btn-outline-primary">
                        ← Retour aux quiz
                    </a>
                </div>
            </div>

            <div class="form-container">
                <div class="form-card">
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

                    <form method="POST" class="quiz-form">
                        <div class="form-group">
                            <label for="title">Titre du quiz *</label>
                            <input type="text" id="title" name="title" required 
                                   value="<?php echo htmlspecialchars($title ?? ''); ?>"
                                   placeholder="Ex: Formation Sécurité Incendie - Niveau 1">
                            <small class="form-help">Choisissez un titre clair et descriptif</small>
                        </div>

                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4"
                                      placeholder="Décrivez le contenu et les objectifs de ce quiz..."><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            <small class="form-help">Expliquez aux joueurs ce qu'ils vont apprendre avec ce quiz</small>
                        </div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                Créer le quiz
                            </button>
                            <a href="quizzes.php" class="btn btn-secondary">
                                Annuler
                            </a>
                        </div>
                    </form>
                </div>

                <!-- Conseils -->
                <div class="tips-card">
                    <h3>💡 Conseils pour créer un bon quiz</h3>
                    <ul class="tips-list">
                        <li>
                            <strong>Titre explicite :</strong> Utilisez un titre qui indique clairement le sujet et le niveau
                        </li>
                        <li>
                            <strong>Description détaillée :</strong> Expliquez les objectifs pédagogiques et le public cible
                        </li>
                        <li>
                            <strong>Progression logique :</strong> Organisez vos questions du plus simple au plus complexe
                        </li>
                        <li>
                            <strong>Questions variées :</strong> Mélangez questions théoriques et cas pratiques
                        </li>
                        <li>
                            <strong>Feedback constructif :</strong> Préparez des explications pour chaque réponse
                        </li>
                    </ul>
                    
                    <div class="tip-highlight">
                        <strong>🎯 Prochaine étape :</strong> Une fois votre quiz créé, vous pourrez ajouter des questions et configurer les paramètres de jeu.
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/admin.js"></script>
</body>
</html>

<style>
.header-actions {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 2rem;
}

.form-container {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    max-width: 1200px;
}

.form-card, .tips-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    padding: 2rem;
}

.quiz-form .form-group:last-of-type {
    margin-bottom: 2rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
}

.tips-card h3 {
    color: var(--gray-800);
    margin-bottom: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.tips-list {
    list-style: none;
    padding: 0;
}

.tips-list li {
    padding: 1rem;
    margin-bottom: 0.75rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    border-left: 3px solid var(--primary-color);
}

.tips-list strong {
    color: var(--primary-color);
}

.tip-highlight {
    background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    padding: 1.5rem;
    border-radius: var(--border-radius);
    margin-top: 1.5rem;
    border-left: 3px solid var(--warning-color);
}

@media (max-width: 768px) {
    .header-actions {
        flex-direction: column;
        gap: 1rem;
    }
    
    .form-container {
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }
    
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
        justify-content: center;
    }
}
</style>
