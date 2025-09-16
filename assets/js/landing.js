/**
 * JavaScript pour la page d'accueil
 * Kuizu - Système de quiz pour sapeurs-pompiers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les animations
    initScrollAnimations();
    
    // Gestion du scroll fluide
    initSmoothScroll();
    
    // Animation des compteurs (si présents)
    initCounterAnimations();
});

/**
 * Initialiser les animations au scroll
 */
function initScrollAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                
                // Pour les cartes, ajouter un délai progressif
                if (entry.target.classList.contains('feature-card') || 
                    entry.target.classList.contains('step-card') ||
                    entry.target.classList.contains('illustration-card')) {
                    
                    const cards = entry.target.parentElement.children;
                    const index = Array.from(cards).indexOf(entry.target);
                    entry.target.style.animationDelay = `${index * 0.1}s`;
                }
            }
        });
    }, observerOptions);

    // Observer tous les éléments à animer
    const animatedElements = document.querySelectorAll(`
        .hero-text,
        .hero-illustration,
        .features-content h2,
        .feature-card,
        .how-content h2,
        .step-card,
        .cta-content,
        .illustration-card
    `);

    animatedElements.forEach(el => {
        el.classList.add('animate-element');
        observer.observe(el);
    });
}

/**
 * Initialiser le scroll fluide
 */
function initSmoothScroll() {
    // Ajouter un comportement de scroll fluide pour les liens d'ancrage
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Initialiser les animations de compteurs
 */
function initCounterAnimations() {
    const counters = document.querySelectorAll('.counter');
    
    if (counters.length === 0) return;
    
    const counterObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                animateCounter(entry.target);
                counterObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.5 });

    counters.forEach(counter => {
        counterObserver.observe(counter);
    });
}

/**
 * Animer un compteur
 */
function animateCounter(element) {
    const target = parseInt(element.getAttribute('data-target'));
    const duration = 2000; // 2 secondes
    const increment = target / (duration / 16); // 60 FPS
    let current = 0;

    const timer = setInterval(() => {
        current += increment;
        if (current >= target) {
            element.textContent = target;
            clearInterval(timer);
        } else {
            element.textContent = Math.floor(current);
        }
    }, 16);
}

/**
 * Effet parallaxe simple pour le héro
 */
function initParallaxEffect() {
    const hero = document.querySelector('.hero-section');
    if (!hero) return;
    
    window.addEventListener('scroll', () => {
        const scrolled = window.pageYOffset;
        const parallaxSpeed = 0.5;
        
        if (scrolled < window.innerHeight) {
            hero.style.transform = `translateY(${scrolled * parallaxSpeed}px)`;
        }
    });
}

/**
 * Animation d'apparition des éléments
 */
function initElementAppearance() {
    // Ajouter les classes d'animation après un court délai
    setTimeout(() => {
        const heroText = document.querySelector('.hero-text');
        const heroImage = document.querySelector('.hero-image');
        
        if (heroText) {
            heroText.style.opacity = '0';
            heroText.style.transform = 'translateY(30px)';
            heroText.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            
            setTimeout(() => {
                heroText.style.opacity = '1';
                heroText.style.transform = 'translateY(0)';
            }, 200);
        }
        
        if (heroImage) {
            heroImage.style.opacity = '0';
            heroImage.style.transform = 'translateY(30px)';
            heroImage.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            
            setTimeout(() => {
                heroImage.style.opacity = '1';
                heroImage.style.transform = 'translateY(0)';
            }, 400);
        }
    }, 100);
}

/**
 * Gestion responsive du menu
 */
function initResponsiveMenu() {
    const header = document.querySelector('.landing-header');
    let lastScrollY = window.scrollY;
    
    window.addEventListener('scroll', () => {
        const currentScrollY = window.scrollY;
        
        if (currentScrollY > lastScrollY && currentScrollY > 100) {
            // Scroll vers le bas - masquer le header
            header.style.transform = 'translateY(-100%)';
        } else {
            // Scroll vers le haut - afficher le header
            header.style.transform = 'translateY(0)';
        }
        
        lastScrollY = currentScrollY;
    });
    
    // Ajouter une transition fluide
    header.style.transition = 'transform 0.3s ease';
}

// Initialiser les effets supplémentaires
document.addEventListener('DOMContentLoaded', function() {
    initElementAppearance();
    
    // Initialiser le menu responsive seulement sur mobile
    if (window.innerWidth <= 768) {
        initResponsiveMenu();
    }
});

// Gestion du redimensionnement de la fenêtre
window.addEventListener('resize', function() {
    // Réinitialiser certains effets si nécessaire
    const header = document.querySelector('.landing-header');
    if (window.innerWidth > 768) {
        header.style.transform = 'translateY(0)';
    }
});

/* Styles CSS pour les animations */
const animationStyles = `
<style>
.animate-element {
    opacity: 0;
    transform: translateY(30px);
    transition: opacity 0.8s ease, transform 0.8s ease;
}

.animate-element.animate-in {
    opacity: 1;
    transform: translateY(0);
}

/* Animation spéciale pour les cartes */
.feature-card.animate-element,
.step-card.animate-element,
.illustration-card.animate-element {
    transform: translateY(50px) scale(0.95);
    transition: opacity 0.6s ease, transform 0.6s ease;
}

.feature-card.animate-in,
.step-card.animate-in,
.illustration-card.animate-in {
    transform: translateY(0) scale(1);
}

/* Effet de hover amélioré */
.feature-card,
.step-card,
.illustration-card {
    transition: all 0.3s ease;
}

.feature-card:hover,
.illustration-card:hover {
    transform: translateY(-8px) scale(1.02);
}

.step-card:hover {
    transform: translateY(-8px) scale(1.05);
}

/* Animation des boutons */
.btn {
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    transform: translate(-50%, -50%);
    transition: width 0.6s ease, height 0.6s ease;
}

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
}

/* Animation du logo */
.logo h1 {
    transition: transform 0.3s ease;
}

.logo:hover h1 {
    transform: scale(1.05);
}

/* Effet de pulsation pour les icônes importantes */
@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
}

.notice-icon {
    animation: pulse 2s ease-in-out infinite;
}

/* Amélioration de l'accessibilité */
@media (prefers-reduced-motion: reduce) {
    .animate-element,
    .feature-card,
    .step-card,
    .illustration-card,
    .btn {
        transition: none !important;
        animation: none !important;
    }
    
    .animate-element {
        opacity: 1;
        transform: none;
    }
}

/* Loading state pour les éléments dynamiques */
.loading {
    opacity: 0.6;
    pointer-events: none;
    position: relative;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid var(--primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>
`;

// Ajouter les styles à la page
document.head.insertAdjacentHTML('beforeend', animationStyles);
