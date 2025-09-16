/**
 * JavaScript pour le formulaire de création/édition de questions
 * Kuizu - Système de quiz pour sapeurs-pompiers
 */

let answerCount = 0;
let currentQuestionType = 'multiple_choice';

document.addEventListener('DOMContentLoaded', function() {
    currentQuestionType = document.getElementById('question_type').value;
    initializeAnswers();
    setupPreview();
    
    // Initialiser la validation du formulaire
    initFormValidation();
});

/**
 * Initialiser les réponses selon le type de question
 */
function initializeAnswers() {
    const container = document.getElementById('answersContainer');
    container.innerHTML = '';
    answerCount = 0;
    
    if (currentQuestionType === 'true_false') {
        addAnswer('Vrai', false);
        addAnswer('Faux', true); // Faux est souvent la bonne réponse par défaut
        document.getElementById('addAnswerBtn').style.display = 'none';
    } else {
        // Ajouter 4 réponses par défaut pour les QCM
        addAnswer('', false);
        addAnswer('', false);
        addAnswer('', false);
        addAnswer('', false);
        document.getElementById('addAnswerBtn').style.display = 'inline-flex';
    }
    
    updatePreview();
}

/**
 * Mettre à jour le type de question
 */
function updateQuestionType() {
    const newType = document.getElementById('question_type').value;
    
    if (newType !== currentQuestionType) {
        if (confirm('Changer le type de question effacera toutes les réponses actuelles. Continuer ?')) {
            currentQuestionType = newType;
            initializeAnswers();
        } else {
            // Restaurer la sélection précédente
            document.getElementById('question_type').value = currentQuestionType;
        }
    }
}

/**
 * Ajouter une nouvelle réponse
 */
function addAnswer(text = '', isCorrect = false) {
    const container = document.getElementById('answersContainer');
    const index = answerCount++;
    
    const answerDiv = document.createElement('div');
    answerDiv.className = 'answer-input-group';
    answerDiv.dataset.index = index;
    
    const inputType = currentQuestionType === 'true_false' ? 'radio' : 'checkbox';
    const inputName = currentQuestionType === 'true_false' ? 'correct_answers' : 'correct_answers[]';
    
    answerDiv.innerHTML = `
        <input type="${inputType}" 
               name="${inputName}" 
               value="${index}" 
               class="answer-checkbox"
               ${isCorrect ? 'checked' : ''}
               onchange="updateAnswerStatus(this)">
        <input type="text" 
               name="answers[]" 
               value="${text}"
               placeholder="Tapez votre réponse ici..."
               class="answer-text"
               oninput="updatePreview()"
               ${currentQuestionType === 'true_false' ? 'readonly' : ''}>
        ${currentQuestionType !== 'true_false' ? 
            `<button type="button" onclick="removeAnswer(this)" class="remove-answer" title="Supprimer cette réponse">✖</button>` 
            : ''}
    `;
    
    if (isCorrect) {
        answerDiv.classList.add('correct-answer');
    }
    
    container.appendChild(answerDiv);
    
    // Limiter à 6 réponses maximum pour les QCM
    if (currentQuestionType === 'multiple_choice' && answerCount >= 6) {
        document.getElementById('addAnswerBtn').style.display = 'none';
    }
}

/**
 * Supprimer une réponse
 */
function removeAnswer(button) {
    const answerDiv = button.closest('.answer-input-group');
    answerDiv.remove();
    
    // Réafficher le bouton d'ajout si nécessaire
    if (currentQuestionType === 'multiple_choice') {
        const remainingAnswers = document.querySelectorAll('.answer-input-group').length;
        if (remainingAnswers < 6) {
            document.getElementById('addAnswerBtn').style.display = 'inline-flex';
        }
    }
    
    updatePreview();
}

/**
 * Mettre à jour le statut visuel d'une réponse
 */
function updateAnswerStatus(checkbox) {
    const answerDiv = checkbox.closest('.answer-input-group');
    
    if (checkbox.checked) {
        answerDiv.classList.add('correct-answer');
    } else {
        answerDiv.classList.remove('correct-answer');
    }
    
    // Pour les questions vrai/faux, décocher l'autre option
    if (currentQuestionType === 'true_false') {
        const allCheckboxes = document.querySelectorAll('.answer-checkbox');
        allCheckboxes.forEach(cb => {
            if (cb !== checkbox) {
                cb.checked = false;
                cb.closest('.answer-input-group').classList.remove('correct-answer');
            }
        });
    }
    
    updatePreview();
}

/**
 * Configurer l'aperçu en temps réel
 */
function setupPreview() {
    const questionText = document.getElementById('question_text');
    const timeLimit = document.getElementById('time_limit');
    const points = document.getElementById('points');
    
    questionText.addEventListener('input', updatePreview);
    timeLimit.addEventListener('input', updatePreview);
    points.addEventListener('input', updatePreview);
    
    updatePreview();
}

/**
 * Mettre à jour l'aperçu de la question
 */
function updatePreview() {
    const preview = document.getElementById('questionPreview');
    const questionText = document.getElementById('question_text').value.trim();
    const timeLimit = document.getElementById('time_limit').value;
    const points = document.getElementById('points').value;
    
    if (!questionText) {
        preview.innerHTML = '<div class="preview-placeholder">L\'aperçu apparaîtra ici pendant que vous tapez...</div>';
        return;
    }
    
    const answers = Array.from(document.querySelectorAll('.answer-text'))
        .map((input, index) => ({
            text: input.value.trim(),
            isCorrect: input.closest('.answer-input-group').querySelector('.answer-checkbox').checked,
            index: index
        }))
        .filter(answer => answer.text);
    
    const typeIcon = currentQuestionType === 'multiple_choice' ? '🔘' : '✅';
    const typeLabel = currentQuestionType === 'multiple_choice' ? 'QCM' : 'Vrai/Faux';
    
    preview.innerHTML = `
        <div class="preview-header">
            <div class="preview-meta">
                <span class="preview-type">${typeIcon} ${typeLabel}</span>
                <span class="preview-time">⏱️ ${timeLimit}s</span>
                <span class="preview-points">🏆 ${points} pts</span>
            </div>
        </div>
        <div class="preview-question">
            <h4>${escapeHtml(questionText)}</h4>
        </div>
        <div class="preview-answers">
            ${answers.map((answer, index) => `
                <div class="preview-answer ${answer.isCorrect ? 'preview-correct' : ''}">
                    <span class="preview-answer-letter">${String.fromCharCode(65 + index)}</span>
                    <span class="preview-answer-text">${escapeHtml(answer.text)}</span>
                    ${answer.isCorrect ? '<span class="preview-correct-indicator">✓</span>' : ''}
                </div>
            `).join('')}
        </div>
        ${answers.length === 0 ? '<div class="preview-no-answers">Aucune réponse saisie</div>' : ''}
    `;
}

/**
 * Échapper les caractères HTML
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Initialiser la validation du formulaire
 */
function initFormValidation() {
    const form = document.getElementById('questionForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    }
}

/**
 * Valider le formulaire
 */
function validateForm() {
    const questionText = document.getElementById('question_text').value.trim();
    const answers = Array.from(document.querySelectorAll('.answer-text'))
        .map(input => input.value.trim())
        .filter(text => text.length > 0);
    
    const correctAnswers = Array.from(document.querySelectorAll('.answer-checkbox:checked'));
    
    // Vérifications
    if (!questionText) {
        showFormError('Le texte de la question est requis');
        return false;
    }
    
    if (answers.length < 2) {
        showFormError('Au moins 2 réponses sont requises');
        return false;
    }
    
    if (correctAnswers.length === 0) {
        showFormError('Au moins une réponse correcte doit être sélectionnée');
        return false;
    }
    
    if (currentQuestionType === 'true_false' && answers.length !== 2) {
        showFormError('Les questions Vrai/Faux doivent avoir exactement 2 réponses');
        return false;
    }
    
    return true;
}

/**
 * Afficher une erreur de formulaire
 */
function showFormError(message) {
    // Supprimer les anciennes erreurs
    const existingError = document.querySelector('.form-error');
    if (existingError) {
        existingError.remove();
    }
    
    // Créer la nouvelle erreur
    const errorDiv = document.createElement('div');
    errorDiv.className = 'alert alert-error form-error';
    errorDiv.textContent = message;
    
    // Insérer avant le formulaire
    const form = document.getElementById('questionForm');
    form.parentNode.insertBefore(errorDiv, form);
    
    // Faire défiler vers l'erreur
    errorDiv.scrollIntoView({ behavior: 'smooth', block: 'center' });
    
    // Auto-suppression après 5 secondes
    setTimeout(() => {
        if (errorDiv.parentNode) {
            errorDiv.remove();
        }
    }, 5000);
}

/* Styles CSS pour l'aperçu */
const previewStyles = `
<style>
.preview-header {
    margin-bottom: 1rem;
    padding-bottom: 0.75rem;
    border-bottom: 1px solid var(--gray-200);
}

.preview-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.875rem;
    color: var(--gray-600);
    flex-wrap: wrap;
}

.preview-type, .preview-time, .preview-points {
    background: var(--white);
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    border: 1px solid var(--gray-200);
}

.preview-question h4 {
    color: var(--gray-800);
    margin-bottom: 1rem;
    line-height: 1.4;
}

.preview-answers {
    display: grid;
    gap: 0.5rem;
}

.preview-answer {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    transition: var(--transition);
}

.preview-answer.preview-correct {
    background: #d1fae5;
    border-color: var(--success-color);
    color: #065f46;
}

.preview-answer-letter {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    background: var(--gray-200);
    color: var(--gray-700);
    border-radius: 50%;
    font-weight: 600;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.preview-correct .preview-answer-letter {
    background: var(--success-color);
    color: var(--white);
}

.preview-answer-text {
    flex: 1;
}

.preview-correct-indicator {
    color: var(--success-color);
    font-weight: bold;
    flex-shrink: 0;
}

.preview-no-answers {
    text-align: center;
    color: var(--gray-500);
    font-style: italic;
    padding: 1rem;
}
</style>
`;

// Ajouter les styles à la page
document.head.insertAdjacentHTML('beforeend', previewStyles);
