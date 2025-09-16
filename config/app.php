<?php
/**
 * Configuration de l'application
 * Kuizu - Système de quiz pour sapeurs-pompiers
 */

/**
 * Obtenir l'URL de base de l'application
 */
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $script_name = $_SERVER['SCRIPT_NAME'];
    
    // Obtenir le chemin du dossier de l'application
    $app_path = dirname(dirname($script_name));
    if ($app_path === '/' || $app_path === '\\') {
        $app_path = '';
    }
    
    return $protocol . '://' . $host . $app_path;
}

/**
 * Obtenir l'URL complète vers une page
 */
function getUrl($path = '') {
    $base_url = getBaseUrl();
    $path = ltrim($path, '/');
    return $base_url . '/' . $path;
}

/**
 * Obtenir l'URL pour rejoindre un quiz
 */
function getQuizJoinUrl($quiz_id) {
    return getUrl('auth/login.php?quiz=' . urlencode($quiz_id));
}

/**
 * Obtenir l'URL pour rejoindre une session
 */
function getSessionJoinUrl($session_code) {
    return getUrl('auth/login.php?session=' . urlencode($session_code));
}

/**
 * Rediriger vers une URL relative
 */
function redirect($path) {
    header('Location: ' . getUrl($path));
    exit();
}

/**
 * Configuration générale de l'application
 */
define('APP_NAME', 'Kuizu');
define('APP_DESCRIPTION', 'Plateforme de quiz pour jeunes sapeurs-pompiers');
define('APP_VERSION', '1.0.0');

// Configuration des sessions et cookies
define('SESSION_LIFETIME', 86400 * 7); // 7 jours
define('COOKIE_LIFETIME', 86400 * 7); // 7 jours

// Configuration des quiz
define('DEFAULT_QUESTION_TIME', 30); // secondes
define('DEFAULT_QUESTION_POINTS', 100);
define('MAX_ANSWERS_PER_QUESTION', 6);
define('MIN_ANSWERS_PER_QUESTION', 2);

// Configuration des sessions de jeu
define('SESSION_CODE_LENGTH', 6);
define('GAME_REFRESH_INTERVAL', 2000); // millisecondes

// Configuration des QR codes
define('QR_CODE_SIZE', '300x300');
define('QR_CODE_API', 'https://api.qrserver.com/v1/create-qr-code/');
?>
