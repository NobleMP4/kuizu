/**
 * JavaScript pour l'interface joueur
 * Kuizu - Système de quiz pour sapeurs-pompiers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les fonctionnalités
    initPlayerInterface();
});

/**
 * Initialiser l'interface joueur
 */
function initPlayerInterface() {
    // Initialiser le header mobile
    initMobileHeader();
    
    // Animation des cartes au chargement
    animateCards();
    
    // Gestion des modals
    setupModalHandlers();
    
    // Vérifier les sessions en cours
    checkActiveSession();
}

/**
 * Initialiser le header mobile optimisé
 */
function initMobileHeader() {
    // Vérifier si on est sur mobile
    const isMobile = window.innerWidth <= 768;
    
    if (isMobile) {
        // Ajouter une classe pour identifier les headers mobiles
        const header = document.querySelector('.player-header');
        if (header) {
            header.classList.add('mobile-optimized');
        }
        
        // Optimiser le bouton de déconnexion
        const logoutBtn = document.querySelector('.btn-sm');
        if (logoutBtn) {
            logoutBtn.setAttribute('title', 'Déconnexion');
        }
    }
    
    // Écouter le redimensionnement de la fenêtre
    window.addEventListener('resize', function() {
        const newIsMobile = window.innerWidth <= 768;
        const header = document.querySelector('.player-header');
        
        if (header) {
            if (newIsMobile) {
                header.classList.add('mobile-optimized');
            } else {
                header.classList.remove('mobile-optimized');
                // Fermer le menu burger si on passe en desktop
                const dropdown = document.getElementById('burgerDropdown');
                if (dropdown) {
                    dropdown.classList.remove('show');
                }
            }
        }
    });
    
    // Fermer le menu burger si on clique ailleurs
    document.addEventListener('click', function(event) {
        const burgerBtn = document.getElementById('burgerBtn');
        const dropdown = document.getElementById('burgerDropdown');
        
        if (burgerBtn && dropdown && !burgerBtn.contains(event.target) && !dropdown.contains(event.target)) {
            dropdown.classList.remove('show');
            burgerBtn.textContent = '☰';
        }
    });
}

/**
 * Toggle du menu burger mobile
 */
function toggleBurgerMenu() {
    const dropdown = document.getElementById('burgerDropdown');
    const burgerBtn = document.getElementById('burgerBtn');
    
    if (dropdown && burgerBtn) {
        dropdown.classList.toggle('show');
        burgerBtn.textContent = dropdown.classList.contains('show') ? '✕' : '☰';
    }
}

/**
 * Animer les cartes au chargement
 */
function animateCards() {
    const cards = document.querySelectorAll('.action-card, .quiz-card, .history-item');
    
    cards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        
        setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 100);
    });
}

/**
 * Configurer les gestionnaires de modals
 */
function setupModalHandlers() {
    // Fermer les modals en cliquant à l'extérieur
    window.addEventListener('click', function(event) {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });
    });
    
    // Gérer la touche Échap
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (modal.style.display === 'block') {
                    modal.style.display = 'none';
                }
            });
        }
    });
}

/**
 * Vérifier s'il y a une session active
 */
async function checkActiveSession() {
    // Vérifier dans le localStorage s'il y a une session en cours
    const activeSession = localStorage.getItem('activeSession');
    if (activeSession) {
        const sessionData = JSON.parse(activeSession);
        
        try {
            // Vérifier si la session est toujours active
            const response = await fetch('../api/game_session.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'get_session',
                    session_id: sessionData.session_id
                })
            });
            
            const result = await response.json();
            
            if (result.success && ['waiting', 'active', 'paused'].includes(result.session.status)) {
                // Afficher une notification pour reprendre la session
                showSessionNotification(sessionData);
            } else {
                // Nettoyer le localStorage
                localStorage.removeItem('activeSession');
            }
        } catch (error) {
            console.error('Erreur lors de la vérification de session:', error);
            localStorage.removeItem('activeSession');
        }
    }
}

/**
 * Afficher une notification de session active
 */
function showSessionNotification(sessionData) {
    const notification = document.createElement('div');
    notification.className = 'session-notification';
    notification.innerHTML = `
        <div class="notification-content">
            <div class="notification-icon">🎮</div>
            <div class="notification-text">
                <strong>Session en cours</strong>
                <p>Vous avez une session active en attente</p>
            </div>
            <div class="notification-actions">
                <button onclick="resumeSession('${sessionData.session_id}')" class="btn btn-primary btn-sm">
                    Reprendre
                </button>
                <button onclick="dismissSessionNotification()" class="btn btn-outline-primary btn-sm">
                    Ignorer
                </button>
            </div>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
}

/**
 * Reprendre une session
 */
function resumeSession(sessionId) {
    window.location.href = `game_session.php?session_id=${sessionId}`;
}

/**
 * Ignorer la notification de session
 */
function dismissSessionNotification() {
    const notification = document.querySelector('.session-notification');
    if (notification) {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 300);
    }
    localStorage.removeItem('activeSession');
}

/**
 * Rejoindre une session de quiz
 */
async function joinQuizSession(quizId) {
    try {
        // D'abord, vérifier s'il y a des sessions actives pour ce quiz
        const response = await fetch('../api/game_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'find_active_session',
                quiz_id: quizId
            })
        });
        
        const result = await response.json();
        
        if (result.success && result.session) {
            // Il y a une session active, proposer de la rejoindre
            if (confirm(`Une session est active pour ce quiz. Voulez-vous la rejoindre ?`)) {
                joinSessionByCode(result.session.session_code);
            }
        } else {
            // Pas de session active, demander le code
            showJoinSessionModal();
        }
    } catch (error) {
        console.error('Erreur:', error);
        showJoinSessionModal();
    }
}

/**
 * Afficher le modal pour rejoindre une session
 */
function showJoinSessionModal() {
    const modal = document.getElementById('joinSessionModal');
    if (modal) {
        modal.style.display = 'block';
        
        // Focus sur l'input
        const input = document.getElementById('sessionCodeInput');
        if (input) {
            input.focus();
            
            // Permettre la soumission avec Entrée
            input.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    joinSessionByCode();
                }
            });
            
            // Formater l'input (que des chiffres)
            input.addEventListener('input', function(e) {
                this.value = this.value.replace(/\D/g, '').substring(0, 6);
            });
        }
    }
}

/**
 * Fermer le modal de session
 */
function closeJoinSessionModal() {
    const modal = document.getElementById('joinSessionModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Rejoindre une session par code
 */
async function joinSessionByCode(code = null) {
    const sessionCode = code || document.getElementById('sessionCodeInput')?.value;
    
    if (!sessionCode || sessionCode.length !== 6) {
        showAlert('error', 'Veuillez entrer un code de session valide (6 chiffres)');
        return;
    }
    
    try {
        const response = await fetch('../api/game_session.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'join',
                session_code: sessionCode
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Sauvegarder la session dans le localStorage
            localStorage.setItem('activeSession', JSON.stringify({
                session_id: result.session.id,
                session_code: sessionCode,
                quiz_title: result.session.quiz_title
            }));
            
            // Rediriger vers la session
            window.location.href = `game_session.php?session_id=${result.session.id}`;
        } else {
            showAlert('error', result.message || 'Impossible de rejoindre la session');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('error', 'Erreur de connexion');
    }
}

/**
 * Démarrer le scanner QR Code (simulation)
 */
function startQRScanner() {
    // Dans une vraie implémentation, on utiliserait une bibliothèque comme QuaggaJS ou ZXing
    // Pour cette démo, on simule avec une invite
    const code = prompt('Scanner QR Code\n\nPour cette démonstration, entrez le code de session manuellement :');
    
    if (code && code.length === 6 && /^\d+$/.test(code)) {
        joinSessionByCode(code);
    } else if (code) {
        showAlert('error', 'Code invalide. Le code doit contenir 6 chiffres.');
    }
}

/**
 * Afficher une alerte
 */
function showAlert(type, message) {
    // Supprimer les anciennes alertes
    const existingAlerts = document.querySelectorAll('.player-alert');
    existingAlerts.forEach(alert => alert.remove());
    
    const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass} player-alert`;
    alert.textContent = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
    alert.style.maxWidth = '500px';
    alert.style.opacity = '0';
    alert.style.transform = 'translateX(100%)';
    alert.style.transition = 'all 0.3s ease';
    
    document.body.appendChild(alert);
    
    // Animation d'entrée
    setTimeout(() => {
        alert.style.opacity = '1';
        alert.style.transform = 'translateX(0)';
    }, 100);
    
    // Auto-suppression
    setTimeout(() => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateX(100%)';
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 300);
    }, 5000);
}

/* Styles CSS pour les notifications */
const notificationStyles = `
<style>
.session-notification {
    position: fixed;
    top: 100px;
    right: 20px;
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-lg);
    border-left: 4px solid var(--primary-color);
    z-index: 1000;
    opacity: 0;
    transform: translateX(100%);
    transition: all 0.3s ease;
    max-width: 400px;
}

.session-notification.show {
    opacity: 1;
    transform: translateX(0);
}

.notification-content {
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 1rem;
}

.notification-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.notification-text {
    flex: 1;
}

.notification-text strong {
    display: block;
    color: var(--gray-800);
    margin-bottom: 0.25rem;
}

.notification-text p {
    margin: 0;
    color: var(--gray-600);
    font-size: 0.9rem;
}

.notification-actions {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .session-notification {
        right: 10px;
        left: 10px;
        max-width: none;
    }
    
    .notification-content {
        flex-direction: column;
        text-align: center;
    }
    
    .notification-actions {
        flex-direction: row;
        width: 100%;
    }
    
    .notification-actions .btn {
        flex: 1;
    }
}
</style>
`;

// Ajouter les styles à la page
document.head.insertAdjacentHTML('beforeend', notificationStyles);
