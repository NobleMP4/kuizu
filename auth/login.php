<?php
require_once __DIR__ . '/../classes/User.php';

// Vérifier la connexion automatique via cookie
if (!User::isLoggedIn() && isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
    $user = new User();
    if ($user->checkRememberToken($_COOKIE['user_id'], $_COOKIE['remember_token'])) {
        // Rediriger selon le rôle
        if (User::isAdmin()) {
            header('Location: ../admin/dashboard.php');
        } else {
            header('Location: ../player/dashboard.php');
        }
        exit();
    }
}

// Si déjà connecté, rediriger
if (User::isLoggedIn()) {
    if (User::canManageQuizzes()) {
        header('Location: ../admin/dashboard.php');
    } else {
        header('Location: ../player/dashboard.php');
    }
    exit();
}

$error_message = '';
$success_message = '';

// Traitement du formulaire de connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($username) || empty($password)) {
        $error_message = 'Veuillez remplir tous les champs';
    } else {
        $user = new User();
        $result = $user->login($username, $password, $remember);
        
        if ($result['success']) {
            // Vérifier s'il y a un quiz en paramètre pour redirection
            $redirect_quiz = $_GET['quiz'] ?? null;
            
            if ($redirect_quiz && !User::canManageQuizzes()) {
                header('Location: ../player/join_quiz.php?id=' . urlencode($redirect_quiz));
            } elseif (User::canManageQuizzes()) {
                header('Location: ../admin/dashboard.php');
            } else {
                header('Location: ../player/dashboard.php');
            }
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
    <title>Connexion - Kuizu Sapeurs-Pompiers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/auth.css">
</head>
<body class="auth-body">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <div class="logo">
                    <img src="../assets/images/logo.png" alt="Kuizu" width="50" height="50">
                    <div class="logo-text">
                        <h1>Kuizu</h1>
                        <p>Plateforme de quiz pour jeunes sapeurs-pompiers</p>
                    </div>
                </div>
                <h2>Connexion</h2>
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

            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="username">Nom d'utilisateur ou Email</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           placeholder="Entrez votre nom d'utilisateur ou email">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Entrez votre mot de passe">
                </div>

                <div class="form-group checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Se souvenir de moi (7 jours)
                    </label>
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    Se connecter
                </button>
            </form>

            <div class="auth-links">
                <p>Pas encore de compte ? <a href="register.php<?php echo isset($_GET['quiz']) ? '?quiz=' . urlencode($_GET['quiz']) : ''; ?>">S'inscrire</a></p>
            </div>

            <!-- Comptes de démonstration -->
            <div class="demo-accounts">
                <h3>Comptes de démonstration</h3>
                <div class="demo-account">
                    <strong>Admin :</strong> admin / password
                    <small>Gestion complète (quiz + utilisateurs)</small>
                </div>
                <div class="demo-account">
                    <strong>Encadrant :</strong> encadrant / password
                    <small>Gestion des quiz et sessions uniquement</small>
                </div>
                <div class="demo-account">
                    <strong>Joueur :</strong> Créez un compte ou utilisez un compte existant
                    <small>Participation aux quiz</small>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/auth.js"></script>
</body>
</html>
