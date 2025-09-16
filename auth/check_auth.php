<?php
/**
 * Middleware d'authentification
 * À inclure dans les pages protégées
 */

require_once __DIR__ . '/../classes/User.php';

// Vérifier la connexion automatique via cookie
if (!User::isLoggedIn() && isset($_COOKIE['remember_token']) && isset($_COOKIE['user_id'])) {
    $user = new User();
    $user->checkRememberToken($_COOKIE['user_id'], $_COOKIE['remember_token']);
}

/**
 * Vérifier si l'utilisateur est connecté
 * Rediriger vers login si non connecté
 */
function requireAuth($quiz_id = null) {
    if (!User::isLoggedIn()) {
        $current_dir = dirname($_SERVER['SCRIPT_NAME']);
        $auth_path = str_replace('/admin', '', $current_dir) . '/auth/login.php';
        $auth_path = str_replace('/player', '', $auth_path);
        $auth_path = str_replace('/api', '', $auth_path);
        
        $redirect_url = $auth_path;
        if ($quiz_id) {
            $redirect_url .= '?quiz=' . urlencode($quiz_id);
        }
        header('Location: ' . $redirect_url);
        exit();
    }
}

/**
 * Vérifier si l'utilisateur est admin
 * Rediriger vers dashboard approprié si non admin
 */
function requireAdmin() {
    requireAuth();
    
    if (!User::isAdmin()) {
        $current_dir = dirname($_SERVER['SCRIPT_NAME']);
        if (User::isEncadrant()) {
            $dashboard_path = str_replace('/admin', '', $current_dir) . '/admin/dashboard.php';
            header('Location: ' . $dashboard_path);
        } else {
            $dashboard_path = str_replace('/admin', '', $current_dir) . '/player/dashboard.php';
            header('Location: ' . $dashboard_path);
        }
        exit();
    }
}

/**
 * Vérifier si l'utilisateur peut gérer les quiz (admin ou encadrant)
 * Rediriger vers dashboard joueur si pas autorisé
 */
function requireQuizManager() {
    requireAuth();
    
    if (!User::canManageQuizzes()) {
        $current_dir = dirname($_SERVER['SCRIPT_NAME']);
        $dashboard_path = str_replace('/admin', '', $current_dir) . '/player/dashboard.php';
        header('Location: ' . $dashboard_path);
        exit();
    }
}

/**
 * Vérifier si l'utilisateur peut gérer les utilisateurs (admin uniquement)
 * Rediriger vers dashboard approprié si pas autorisé
 */
function requireUserManager() {
    requireAuth();
    
    if (!User::canManageUsers()) {
        $current_dir = dirname($_SERVER['SCRIPT_NAME']);
        if (User::isEncadrant()) {
            $dashboard_path = str_replace('/admin', '', $current_dir) . '/admin/dashboard.php';
            header('Location: ' . $dashboard_path);
        } else {
            $dashboard_path = str_replace('/admin', '', $current_dir) . '/player/dashboard.php';
            header('Location: ' . $dashboard_path);
        }
        exit();
    }
}

/**
 * Vérifier si l'utilisateur est joueur
 * Rediriger vers dashboard admin si admin
 */
function requirePlayer() {
    requireAuth();
    
    if (User::isAdmin()) {
        header('Location: /kuizu/admin/dashboard.php');
        exit();
    }
}

/**
 * Obtenir l'utilisateur connecté ou rediriger
 */
function getCurrentUserOrRedirect() {
    requireAuth();
    return User::getCurrentUser();
}
?>
