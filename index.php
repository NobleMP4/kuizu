<?php
/**
 * Point d'entrée principal de l'application Kuizu
 * Routeur principal qui gère toutes les requêtes
 */

session_start();

// Configuration de base
define('ROOT', dirname(__FILE__));
define('BASE_URL', 'http://localhost/kuizu');

// Autoloader simple pour les classes
spl_autoload_register(function ($class) {
    $paths = [
        ROOT . '/app/models/' . $class . '.php',
        ROOT . '/app/controllers/' . $class . '.php',
        ROOT . '/app/core/' . $class . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Récupération de la route
$url = isset($_GET['url']) ? $_GET['url'] : 'home';
$url = rtrim($url, '/');
$url = explode('/', $url);

// Contrôleur et action par défaut
$controller = !empty($url[0]) ? $url[0] : 'home';
$action = !empty($url[1]) ? $url[1] : 'index';
$params = array_slice($url, 2);

// Nom de la classe du contrôleur
$controllerClass = ucfirst($controller) . 'Controller';
$controllerFile = ROOT . '/app/controllers/' . $controllerClass . '.php';

// Vérification de l'existence du contrôleur
if (file_exists($controllerFile)) {
    require_once $controllerFile;
    
    if (class_exists($controllerClass)) {
        $controllerInstance = new $controllerClass();
        
        // Vérification de l'existence de la méthode
        if (method_exists($controllerInstance, $action)) {
            call_user_func_array([$controllerInstance, $action], $params);
        } else {
            // Action non trouvée
            header("HTTP/1.0 404 Not Found");
            require_once ROOT . '/app/views/errors/404.php';
        }
    } else {
        // Classe non trouvée
        header("HTTP/1.0 404 Not Found");
        require_once ROOT . '/app/views/errors/404.php';
    }
} else {
    // Contrôleur non trouvé
    header("HTTP/1.0 404 Not Found");
    require_once ROOT . '/app/views/errors/404.php';
}
?>
