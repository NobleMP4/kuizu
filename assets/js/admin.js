/**
 * JavaScript pour l'interface d'administration
 * Kuizu - Système de quiz pour sapeurs-pompiers
 */

document.addEventListener('DOMContentLoaded', function() {
    // Menu mobile
    initMobileMenu();
    
    // Animations des cartes
    initCardAnimations();
    
    // Auto-masquage des alertes
    initAlertAutoHide();
});

/**
 * Initialiser le menu mobile
 */
function initMobileMenu() {
    // Créer le bouton de menu mobile
    const mobileToggle = document.createElement('button');
    mobileToggle.className = 'mobile-menu-toggle';
    mobileToggle.innerHTML = '☰';
    mobileToggle.setAttribute('aria-label', 'Toggle menu');
    document.body.appendChild(mobileToggle);
    
    // Créer l'overlay
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    const sidebar = document.querySelector('.admin-sidebar');
    
    // Gestionnaires d'événements
    mobileToggle.addEventListener('click', function() {
        sidebar.classList.toggle('open');
        overlay.classList.toggle('active');
        document.body.style.overflow = sidebar.classList.contains('open') ? 'hidden' : '';
    });
    
    overlay.addEventListener('click', function() {
        sidebar.classList.remove('open');
        overlay.classList.remove('active');
        document.body.style.overflow = '';
    });
    
    // Fermer le menu lors du redimensionnement
    window.addEventListener('resize', function() {
        if (window.innerWidth > 1024) {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
}

/**
 * Initialiser les animations des cartes
 */
function initCardAnimations() {
    const cards = document.querySelectorAll('.stat-card, .action-card, .quiz-card, .question-card');
    
    // Observer d'intersection pour les animations au scroll
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    });
    
    cards.forEach(card => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(20px)';
        card.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(card);
    });
}

/**
 * Auto-masquage des alertes
 */
function initAlertAutoHide() {
    const alerts = document.querySelectorAll('.alert-success');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.remove();
                }
            }, 500);
        }, 5000);
    });
}

/**
 * Basculer le statut d'un quiz (actif/inactif ou verrouillé/déverrouillé)
 */
async function toggleQuizStatus(quizId, type) {
    const action = type === 'active' ? 'toggle_active' : 'toggle_lock';
    
    try {
        const response = await fetch('../api/quiz_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: action,
                quiz_id: quizId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Recharger la page pour mettre à jour l'interface
            window.location.reload();
        } else {
            showAlert('error', result.message || 'Erreur lors de la modification');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('error', 'Erreur de connexion');
    }
}

/**
 * Générer un QR code pour un quiz
 */
async function generateQRCode(quizId) {
    try {
        const response = await fetch('../api/quiz_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'generate_qr',
                quiz_id: quizId
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showQRModal(result.qr_code_url, result.quiz_url);
        } else {
            showAlert('error', result.message || 'Erreur lors de la génération du QR code');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('error', 'Erreur de connexion');
    }
}

/**
 * Afficher le modal QR code
 */
function showQRModal(qrCodeUrl, quizUrl) {
    const modal = document.getElementById('qrModal');
    const container = document.getElementById('qrCodeContainer');
    
    if (!modal || !container) return;
    
    container.innerHTML = `
        <img src="${qrCodeUrl}" alt="QR Code du quiz" style="max-width: 100%; height: auto; margin-bottom: 1rem;">
        <div class="qr-url">
            <label>URL directe :</label>
            <input type="text" value="${quizUrl}" readonly onclick="this.select()" 
                   style="width: 100%; padding: 0.5rem; border: 1px solid #ddd; border-radius: 4px; margin-top: 0.5rem;">
        </div>
    `;
    
    modal.style.display = 'block';
}

/**
 * Fermer le modal QR code
 */
function closeQRModal() {
    const modal = document.getElementById('qrModal');
    if (modal) {
        modal.style.display = 'none';
    }
}

/**
 * Supprimer une question
 */
async function deleteQuestion(questionId) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer cette question ? Cette action est irréversible.')) {
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('action', 'delete_question');
        formData.append('question_id', questionId);
        
        const response = await fetch(window.location.href, {
            method: 'POST',
            body: formData
        });
        
        if (response.ok) {
            window.location.reload();
        } else {
            showAlert('error', 'Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showAlert('error', 'Erreur de connexion');
    }
}

/**
 * Réorganiser les questions (drag & drop)
 */
function reorderQuestions() {
    const questionsList = document.getElementById('questions-list');
    if (!questionsList) return;
    
    // Activer le mode réorganisation
    questionsList.classList.add('reorder-mode');
    
    const questions = questionsList.querySelectorAll('.question-card');
    
    questions.forEach(question => {
        question.draggable = true;
        question.style.cursor = 'move';
        
        question.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.dataset.questionId);
            this.style.opacity = '0.5';
        });
        
        question.addEventListener('dragend', function() {
            this.style.opacity = '1';
        });
        
        question.addEventListener('dragover', function(e) {
            e.preventDefault();
        });
        
        question.addEventListener('drop', function(e) {
            e.preventDefault();
            const draggedId = e.dataTransfer.getData('text/plain');
            const draggedElement = document.querySelector(`[data-question-id="${draggedId}"]`);
            
            if (draggedElement && draggedElement !== this) {
                const rect = this.getBoundingClientRect();
                const midpoint = rect.top + rect.height / 2;
                
                if (e.clientY < midpoint) {
                    this.parentNode.insertBefore(draggedElement, this);
                } else {
                    this.parentNode.insertBefore(draggedElement, this.nextSibling);
                }
                
                saveQuestionOrder();
            }
        });
    });
    
    // Ajouter un bouton pour terminer la réorganisation
    const finishButton = document.createElement('button');
    finishButton.textContent = '✓ Terminer la réorganisation';
    finishButton.className = 'btn btn-success';
    finishButton.style.margin = '1rem auto';
    finishButton.style.display = 'block';
    
    finishButton.addEventListener('click', function() {
        questionsList.classList.remove('reorder-mode');
        questions.forEach(q => {
            q.draggable = false;
            q.style.cursor = '';
        });
        this.remove();
    });
    
    questionsList.appendChild(finishButton);
}

/**
 * Sauvegarder l'ordre des questions
 */
async function saveQuestionOrder() {
    const questions = document.querySelectorAll('.question-card');
    const order = {};
    
    questions.forEach((question, index) => {
        const questionId = question.dataset.questionId;
        if (questionId) {
            order[questionId] = index + 1;
        }
    });
    
    try {
        const response = await fetch('../api/question_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'reorder',
                question_orders: order
            })
        });
        
        const result = await response.json();
        
        if (!result.success) {
            showAlert('error', 'Erreur lors de la sauvegarde de l\'ordre');
        }
    } catch (error) {
        console.error('Erreur:', error);
    }
}

/**
 * Afficher une alerte
 */
function showAlert(type, message) {
    const alertClass = type === 'error' ? 'alert-error' : 'alert-success';
    
    const alert = document.createElement('div');
    alert.className = `alert ${alertClass}`;
    alert.textContent = message;
    alert.style.position = 'fixed';
    alert.style.top = '20px';
    alert.style.right = '20px';
    alert.style.zIndex = '9999';
    alert.style.minWidth = '300px';
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

// Fermer les modals en cliquant à l'extérieur
window.addEventListener('click', function(event) {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

// Raccourcis clavier
document.addEventListener('keydown', function(event) {
    // Échap pour fermer les modals
    if (event.key === 'Escape') {
        const modals = document.querySelectorAll('.modal');
        modals.forEach(modal => {
            if (modal.style.display === 'block') {
                modal.style.display = 'none';
            }
        });
    }
});
