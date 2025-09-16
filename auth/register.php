<?php
require_once __DIR__ . '/../classes/User.php';

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

// Traitement du formulaire d'inscription
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $first_name = trim($_POST['first_name'] ?? '');
    $last_name = trim($_POST['last_name'] ?? '');

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name)) {
        $error_message = 'Veuillez remplir tous les champs';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Les mots de passe ne correspondent pas';
    } elseif (strlen($password) < 6) {
        $error_message = 'Le mot de passe doit contenir au moins 6 caractères';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Adresse email invalide';
    } else {
        $user = new User();
        $result = $user->register($username, $email, $password, $first_name, $last_name);
        
        if ($result['success']) {
            $success_message = 'Inscription réussie ! Vous pouvez maintenant vous connecter.';
            
            // Connexion automatique après inscription
            $login_result = $user->login($username, $password);
            if ($login_result['success']) {
                // Vérifier s'il y a un quiz en paramètre pour redirection
                $redirect_quiz = $_GET['quiz'] ?? null;
                
                if ($redirect_quiz) {
                    header('Location: ../player/join_quiz.php?id=' . urlencode($redirect_quiz));
                } else {
                    header('Location: ../player/dashboard.php');
                }
                exit();
            }
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
    <title>Inscription - Kuizu Sapeurs-Pompiers</title>
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
                        <p>Rejoignez la plateforme de quiz pour jeunes sapeurs-pompiers</p>
                    </div>
                </div>
                <h2>Inscription</h2>
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
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">Prénom *</label>
                        <input type="text" id="first_name" name="first_name" required 
                               value="<?php echo htmlspecialchars($first_name ?? ''); ?>"
                               placeholder="Votre prénom">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Nom *</label>
                        <input type="text" id="last_name" name="last_name" required 
                               value="<?php echo htmlspecialchars($last_name ?? ''); ?>"
                               placeholder="Votre nom">
                    </div>
                </div>

                <div class="form-group">
                    <label for="username">Nom d'utilisateur *</label>
                    <input type="text" id="username" name="username" required 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>"
                           placeholder="Choisissez un nom d'utilisateur unique">
                </div>

                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>"
                           placeholder="votre.email@exemple.com">
                </div>

                <div class="form-group">
                    <label for="password">Mot de passe *</label>
                    <input type="password" id="password" name="password" required 
                           placeholder="Au moins 6 caractères">
                    <small class="form-help">Le mot de passe doit contenir au moins 6 caractères</small>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirmer le mot de passe *</label>
                    <input type="password" id="confirm_password" name="confirm_password" required 
                           placeholder="Répétez votre mot de passe">
                </div>

                <button type="submit" class="btn btn-primary btn-full">
                    S'inscrire
                </button>
            </form>

            <div class="auth-links">
                <p>Déjà un compte ? <a href="login.php<?php echo isset($_GET['quiz']) ? '?quiz=' . urlencode($_GET['quiz']) : ''; ?>">Se connecter</a></p>
            </div>
        </div>
    </div>

    <script src="../assets/js/auth.js"></script>
</body>
</html>
