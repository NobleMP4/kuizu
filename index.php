<?php
require_once __DIR__ . '/classes/User.php';

// Vérifier la connexion automatique via cookie
if (!User::isLoggedIn() && isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
    $user = new User();
    if ($user->checkRememberToken($_COOKIE['user_id'], $_COOKIE['remember_token'])) {
        // Rediriger selon le rôle
        if (User::isAdmin()) {
            header('Location: admin/dashboard.php');
        } else {
            header('Location: player/dashboard.php');
        }
        exit();
    }
}

// Si déjà connecté, rediriger vers le dashboard approprié
if (User::isLoggedIn()) {
    if (User::isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: player/dashboard.php');
    }
    exit();
}

// Vérifier s'il y a un quiz en paramètre pour redirection après login
$quiz_redirect = $_GET['quiz'] ?? null;
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuizu - Quiz pour Jeunes Sapeurs-Pompiers</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body class="landing-body">
    <div class="landing-container">
        <!-- Header -->
        <header class="landing-header">
            <div class="header-content">
                <div class="logo">
                    <img src="assets/images/logo.png" alt="Kuizu" width="60" height="60">
                    <h1>Kuizu</h1>
                </div>
                <div class="header-actions">
                    <a href="auth/login.php<?php echo $quiz_redirect ? '?quiz=' . urlencode($quiz_redirect) : ''; ?>" class="btn btn-primary">
                        Se connecter
                    </a>
                    <a href="auth/register.php<?php echo $quiz_redirect ? '?quiz=' . urlencode($quiz_redirect) : ''; ?>" class="btn btn-outline-primary">
                        S'inscrire
                    </a>
                </div>
            </div>
        </header>

        <!-- Section héro -->
        <section class="hero-section">
            <div class="hero-content">
                <div class="hero-text">
                    <h2>Testez vos connaissances de sapeur-pompier !</h2>
                    <p>
                        Kuizu est la plateforme interactive dédiée à la formation des jeunes sapeurs-pompiers. 
                        Participez à des quiz en temps réel, suivez votre progression et apprenez en vous amusant.
                    </p>
                    
                    <?php if ($quiz_redirect): ?>
                        <div class="quiz-redirect-notice">
                            <div class="notice-icon">🎯</div>
                            <div class="notice-content">
                                <strong>Quiz spécifique détecté !</strong>
                                <p>Vous avez été invité à participer à un quiz. Connectez-vous pour y accéder.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="hero-actions">
                        <a href="auth/register.php<?php echo $quiz_redirect ? '?quiz=' . urlencode($quiz_redirect) : ''; ?>" class="btn btn-primary btn-lg">
                            🚀 Commencer maintenant
                        </a>
                        <a href="auth/login.php<?php echo $quiz_redirect ? '?quiz=' . urlencode($quiz_redirect) : ''; ?>" class="btn btn-outline-primary btn-lg">
                            🔑 J'ai déjà un compte
                        </a>
                    </div>
                </div>
                
                <div class="hero-image">
                    <div class="hero-illustration">
                        <div class="illustration-card">
                            <div class="card-icon">🚒</div>
                            <h3>Formation interactive</h3>
                            <p>Quiz en temps réel avec vos collègues</p>
                        </div>
                        <div class="illustration-card">
                            <div class="card-icon">📊</div>
                            <h3>Suivi des progrès</h3>
                            <p>Statistiques et historique détaillés</p>
                        </div>
                        <div class="illustration-card">
                            <div class="card-icon">🏆</div>
                            <h3>Système de points</h3>
                            <p>Gagnez des points selon votre rapidité</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Fonctionnalités -->
        <section class="features-section">
            <div class="features-content">
                <h2>Pourquoi choisir Kuizu ?</h2>
                
                <div class="features-grid">
                    <div class="feature-card">
                        <div class="feature-icon">🎮</div>
                        <h3>Sessions en temps réel</h3>
                        <p>Participez à des sessions de quiz interactives dirigées par vos instructeurs. Répondez en temps réel et voyez les résultats instantanément.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📱</div>
                        <h3>Accès par QR Code</h3>
                        <p>Scannez simplement le QR code affiché par votre instructeur pour rejoindre immédiatement un quiz. Simple et rapide !</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">⏱️</div>
                        <h3>Timer dynamique</h3>
                        <p>Chaque question a un temps limite. Plus vous répondez vite et correctement, plus vous gagnez de points !</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📈</div>
                        <h3>Classement en direct</h3>
                        <p>Suivez votre position dans le classement en temps réel et comparez vos performances avec les autres participants.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">🎯</div>
                        <h3>Contenu spécialisé</h3>
                        <p>Questions adaptées à la formation de sapeur-pompier : sécurité incendie, premiers secours, matériel et procédures.</p>
                    </div>
                    
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <h3>Suivi personnalisé</h3>
                        <p>Consultez votre historique, vos statistiques de progression et identifiez vos points d'amélioration.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Comment ça marche -->
        <section class="how-it-works">
            <div class="how-content">
                <h2>Comment ça marche ?</h2>
                
                <div class="steps-grid">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Créez votre compte</h3>
                            <p>Inscrivez-vous gratuitement avec votre nom, email et informations de base.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Rejoignez une session</h3>
                            <p>Scannez le QR code ou entrez le code de session fourni par votre instructeur.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Répondez aux questions</h3>
                            <p>Participez au quiz en temps réel, répondez rapidement et correctement pour gagner plus de points.</p>
                        </div>
                    </div>
                    
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>Consultez vos résultats</h3>
                            <p>Découvrez votre classement, vos statistiques et suivez votre progression au fil du temps.</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- CTA Final -->
        <section class="cta-section">
            <div class="cta-content">
                <h2>Prêt à tester vos connaissances ?</h2>
                <p>Rejoignez la communauté des jeunes sapeurs-pompiers et participez à des quiz interactifs pour améliorer vos compétences.</p>
                
                <div class="cta-actions">
                    <a href="auth/register.php<?php echo $quiz_redirect ? '?quiz=' . urlencode($quiz_redirect) : ''; ?>" class="btn btn-primary btn-lg">
                        🚀 Commencer gratuitement
                    </a>
                </div>
                
                <div class="demo-info">
                    <p><strong>Compte de démonstration administrateur :</strong></p>
                    <p>Email : admin@sapeurs-pompiers.fr | Mot de passe : password</p>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="landing-footer">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>🚒 Kuizu</h3>
                    <p>Plateforme de formation interactive pour jeunes sapeurs-pompiers</p>
                </div>
                
                <div class="footer-section">
                    <h4>Fonctionnalités</h4>
                    <ul>
                        <li>Quiz en temps réel</li>
                        <li>Accès par QR Code</li>
                        <li>Suivi des progrès</li>
                        <li>Classements</li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="auth/login.php">Se connecter</a></li>
                        <li><a href="auth/register.php">S'inscrire</a></li>
                        <li>Contact : kuizu@sapeurs-pompiers.fr</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; 2025 Kuizu - Système de quiz pour sapeurs-pompiers. Développé avec ❤️ pour la formation.</p>
            </div>
        </footer>
    </div>

    <script src="assets/js/landing.js"></script>
</body>
</html>
