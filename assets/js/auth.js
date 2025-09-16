/**
 * JavaScript pour les pages d'authentification
 * Kuizu - Système de quiz pour sapeurs-pompiers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Validation en temps réel des formulaires
    const forms = document.querySelectorAll('.auth-form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required]');
        
        inputs.forEach(input => {
            // Validation au blur
            input.addEventListener('blur', function() {
                validateField(this);
            });
            
            // Suppression des erreurs au focus
            input.addEventListener('focus', function() {
                clearFieldError(this);
            });
        });
        
        // Validation à la soumission
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });
            
            // Validation spéciale pour la confirmation de mot de passe
            const password = form.querySelector('#password');
            const confirmPassword = form.querySelector('#confirm_password');
            
            if (password && confirmPassword) {
                if (password.value !== confirmPassword.value) {
                    showFieldError(confirmPassword, 'Les mots de passe ne correspondent pas');
                    isValid = false;
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                
                // Faire défiler vers la première erreur
                const firstError = form.querySelector('.field-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    });
    
    // Animation des alertes
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-10px)';
        
        setTimeout(() => {
            alert.style.transition = 'all 0.3s ease';
            alert.style.opacity = '1';
            alert.style.transform = 'translateY(0)';
        }, 100);
        
        // Auto-masquage des alertes de succès
        if (alert.classList.contains('alert-success')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    alert.remove();
                }, 300);
            }, 5000);
        }
    });
    
    // Animation des boutons au clic
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('click', function(e) {
            // Effet ripple
            const ripple = document.createElement('span');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.clientX - rect.left - size / 2;
            const y = e.clientY - rect.top - size / 2;
            
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = x + 'px';
            ripple.style.top = y + 'px';
            ripple.classList.add('ripple');
            
            this.appendChild(ripple);
            
            setTimeout(() => {
                ripple.remove();
            }, 600);
        });
    });
});

/**
 * Valider un champ
 */
function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const name = field.name;
    
    // Supprimer les erreurs précédentes
    clearFieldError(field);
    
    // Champ requis
    if (field.hasAttribute('required') && !value) {
        showFieldError(field, 'Ce champ est requis');
        return false;
    }
    
    if (!value) return true; // Si pas requis et vide, c'est OK
    
    // Validation par type
    switch (type) {
        case 'email':
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                showFieldError(field, 'Adresse email invalide');
                return false;
            }
            break;
            
        case 'password':
            if (name === 'password' && value.length < 6) {
                showFieldError(field, 'Le mot de passe doit contenir au moins 6 caractères');
                return false;
            }
            break;
            
        case 'text':
            if (name === 'username' && value.length < 3) {
                showFieldError(field, 'Le nom d\'utilisateur doit contenir au moins 3 caractères');
                return false;
            }
            if ((name === 'first_name' || name === 'last_name') && value.length < 2) {
                showFieldError(field, 'Ce champ doit contenir au moins 2 caractères');
                return false;
            }
            break;
    }
    
    return true;
}

/**
 * Afficher une erreur sur un champ
 */
function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('field-error');
    field.style.borderColor = 'var(--error-color)';
    
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error-message';
    errorDiv.textContent = message;
    errorDiv.style.color = 'var(--error-color)';
    errorDiv.style.fontSize = '0.875rem';
    errorDiv.style.marginTop = '0.25rem';
    errorDiv.style.animation = 'slideDown 0.3s ease';
    
    field.parentNode.appendChild(errorDiv);
}

/**
 * Supprimer l'erreur d'un champ
 */
function clearFieldError(field) {
    field.classList.remove('field-error');
    field.style.borderColor = '';
    
    const errorMessage = field.parentNode.querySelector('.field-error-message');
    if (errorMessage) {
        errorMessage.remove();
    }
}

/* Styles CSS pour les erreurs (ajoutés dynamiquement) */
if (!document.querySelector('#auth-error-styles')) {
    const style = document.createElement('style');
    style.id = 'auth-error-styles';
    style.textContent = `
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-5px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .ripple {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: scale(0);
            animation: ripple-animation 0.6s linear;
            pointer-events: none;
        }
        
        @keyframes ripple-animation {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
        
        .btn {
            position: relative;
            overflow: hidden;
        }
    `;
    document.head.appendChild(style);
}
