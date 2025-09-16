<?php
/**
 * Configuration dynamique pour Kuizu
 * Ce fichier sera automatiquement inclus pour obtenir les URLs dynamiques
 */

// Inclure la configuration de l'application
require_once __DIR__ . '/app.php';

// Variables globales pour les templates
$GLOBALS['app_config'] = [
    'base_url' => getBaseUrl(),
    'app_name' => APP_NAME,
    'app_description' => APP_DESCRIPTION,
    'app_version' => APP_VERSION
];

/**
 * Fonction helper pour les templates
 */
function app_url($path = '') {
    return getUrl($path);
}

function quiz_join_url($quiz_id) {
    return getQuizJoinUrl($quiz_id);
}

function session_join_url($session_code) {
    return getSessionJoinUrl($session_code);
}

function qr_code_url($data, $size = null) {
    $size = $size ?: QR_CODE_SIZE;
    return QR_CODE_API . '?size=' . $size . '&data=' . urlencode($data);
}
?>
