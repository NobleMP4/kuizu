/**
 * Utilitaires JavaScript pour Kuizu
 * Fonctions communes pour gérer les URLs dynamiques
 */

/**
 * Obtenir l'URL de base de l'application
 */
function getBaseUrl() {
    const protocol = window.location.protocol;
    const host = window.location.host;
    const pathname = window.location.pathname;
    
    // Obtenir le chemin de base en supprimant la page actuelle et les dossiers spécifiques
    let basePath = pathname.replace(/\/[^\/]*$/, ''); // Supprimer la page actuelle
    basePath = basePath.replace(/\/(admin|player|auth|api)$/, ''); // Supprimer les dossiers spécifiques
    
    return protocol + '//' + host + basePath;
}

/**
 * Obtenir une URL complète vers une page
 */
function getUrl(path) {
    const baseUrl = getBaseUrl();
    const cleanPath = path.replace(/^\//, ''); // Supprimer le slash initial si présent
    return baseUrl + '/' + cleanPath;
}

/**
 * Obtenir l'URL pour rejoindre un quiz
 */
function getQuizJoinUrl(quizId) {
    return getUrl('auth/login.php?quiz=' + encodeURIComponent(quizId));
}

/**
 * Obtenir l'URL pour rejoindre une session
 */
function getSessionJoinUrl(sessionCode) {
    return getUrl('auth/login.php?session=' + encodeURIComponent(sessionCode));
}

/**
 * Générer une URL de QR code
 */
function getQRCodeUrl(data, size = '300x300') {
    const qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/';
    return qrApiUrl + '?size=' + size + '&data=' + encodeURIComponent(data);
}

/**
 * Rediriger vers une page relative
 */
function redirectTo(path) {
    window.location.href = getUrl(path);
}

/**
 * Obtenir le chemin relatif vers un dossier
 */
function getRelativePath(targetFolder) {
    const currentPath = window.location.pathname;
    const currentFolder = currentPath.split('/').slice(-2, -1)[0]; // Dossier actuel
    
    // Si on est déjà dans le bon dossier
    if (currentFolder === targetFolder) {
        return './';
    }
    
    // Si on est dans un sous-dossier, remonter
    if (['admin', 'player', 'auth', 'api'].includes(currentFolder)) {
        return '../' + targetFolder + '/';
    }
    
    // Si on est à la racine
    return targetFolder + '/';
}

/**
 * Créer un lien dynamique
 */
function createDynamicLink(text, path, className = '') {
    const link = document.createElement('a');
    link.href = getUrl(path);
    link.textContent = text;
    if (className) {
        link.className = className;
    }
    return link;
}

/**
 * Mettre à jour tous les liens avec des URLs absolues
 */
function updateStaticLinks() {
    // Mettre à jour les liens qui pointent vers des URLs codées en dur
    const links = document.querySelectorAll('a[href*="localhost"], a[href*="127.0.0.1"]');
    
    links.forEach(link => {
        const href = link.getAttribute('href');
        
        // Extraire le chemin relatif
        const matches = href.match(/\/kuizu\/(.+)$/);
        if (matches) {
            const relativePath = matches[1];
            link.href = getUrl(relativePath);
        }
    });
}

/**
 * Initialiser les utilitaires
 */
document.addEventListener('DOMContentLoaded', function() {
    // Mettre à jour les liens statiques si nécessaire
    updateStaticLinks();
    
    // Exposer les fonctions globalement pour faciliter l'usage
    window.kuizu = {
        getBaseUrl,
        getUrl,
        getQuizJoinUrl,
        getSessionJoinUrl,
        getQRCodeUrl,
        redirectTo,
        getRelativePath,
        createDynamicLink
    };
});

/**
 * Configuration globale de l'application
 */
window.KUIZU_CONFIG = {
    REFRESH_INTERVAL: 2000, // millisecondes
    QR_CODE_SIZE: '300x300',
    SESSION_CODE_LENGTH: 6,
    DEFAULT_QUESTION_TIME: 30,
    MAX_ANSWERS: 6,
    MIN_ANSWERS: 2
};
