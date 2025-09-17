<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/GameSession.php';

requirePlayer();

$current_user = getCurrentUserOrRedirect();
$error_message = '';
$success_message = '';

// Traitement du formulaire de rejoindre une session
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $session_code = trim($_POST['session_code'] ?? '');
    
    if (empty($session_code)) {
        $error_message = 'Veuillez entrer un code de session';
    } elseif (!preg_match('/^\d{6}$/', $session_code)) {
        $error_message = 'Le code de session doit contenir exactement 6 chiffres';
    } else {
        $gameSession = new GameSession();
        
        // D'abord, trouver la session par code
        $session = $gameSession->getByCode($session_code);
        
        if (!$session) {
            $error_message = 'Code de session invalide ou session non trouvée';
        } else {
            // Ensuite, ajouter le participant avec l'ID de session
            $result = $gameSession->addParticipant($session['id'], $current_user['id']);
            
            if ($result['success']) {
                $success_message = 'Session rejointe avec succès !';
                
                // Rediriger vers la session
                header('Location: game_session.php?session_id=' . $session['id']);
                exit();
            } else {
                $error_message = $result['message'];
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rejoindre une session - Kuizu Sapeurs-Pompiers</title>
    <link rel="stylesheet" href="../assets/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="../assets/css/player.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="player-layout">
        <!-- Header -->
        <header class="player-header">
            <div class="header-content">
                <div class="logo">
                    <img src="../assets/images/logo.png" alt="Kuizu" width="50" height="50">
                    <div class="logo-text">
                        <h1>Kuizu</h1>
                        <span>Jeunes Sapeurs-Pompiers</span>
                    </div>
                </div>
                
                <nav class="header-nav">
                    <a href="dashboard.php" class="nav-link">Tableau de bord</a>
                    <a href="join_session.php" class="nav-link active">Rejoindre une session</a>
                    <a href="history.php" class="nav-link">Mon historique</a>
                </nav>
                
                <div class="user-menu">
                    <div class="user-info">
                        <span>👤 <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                    </div>
                    <button onclick="toggleBurgerMenu()" class="burger-menu" id="burgerBtn" 
                            style="display: inline-block; background: rgba(255,255,255,0.2); border: none; color: white; padding: 0.5rem; border-radius: 6px; cursor: pointer; font-size: 1rem; margin-left: 0.5rem;">
                        ☰
                    </button>
                    <div class="burger-dropdown" id="burgerDropdown" 
                         style="position: absolute; top: 100%; right: 0; background: white; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); min-width: 200px; z-index: 1000; display: none; margin-top: 0.5rem;">
                        <a href="dashboard.php" style="display: block; padding: 0.75rem 1rem; color: #374151; text-decoration: none; border-bottom: 1px solid #e5e7eb; transition: background 0.2s;">🏠 Tableau de bord</a>
                        <a href="join_session.php" class="active" style="display: block; padding: 0.75rem 1rem; color: white; background: #224d71; text-decoration: none; border-bottom: 1px solid #e5e7eb;">🎮 Rejoindre une session</a>
                        <a href="history.php" style="display: block; padding: 0.75rem 1rem; color: #374151; text-decoration: none;">📊 Mon historique</a>
                    </div>
                    <a href="../auth/logout.php" class="btn btn-outline-primary btn-sm">
                        Déconnexion
                    </a>
                </div>
            </div>
        </header>

        <!-- Contenu principal -->
        <main class="player-content">
            <div class="join-session-container">
                <div class="join-session-card">
                    <div class="card-header">
                        <h1>🎮 Rejoindre une session</h1>
                        <p>Entrez le code de session fourni par votre instructeur</p>
                    </div>

                    <?php if ($error_message): ?>
                        <div class="alert alert-error">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <?php echo htmlspecialchars($success_message); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" class="join-form" id="joinForm">
                        <div class="form-group">
                            <label for="session_code">Code de session</label>
                            <input type="text" 
                                   id="session_code" 
                                   name="session_code" 
                                   required 
                                   maxlength="6" 
                                   pattern="\d{6}"
                                   placeholder="000000"
                                   class="session-code-input"
                                   autocomplete="off"
                                   value="<?php echo htmlspecialchars($session_code ?? ''); ?>">
                            <small class="form-help">Le code contient exactement 6 chiffres</small>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg">
                            🚀 Rejoindre la session
                        </button>
                    </form>
                </div>

                <!-- Instructions -->
                <div class="instructions-card">
                    <h3>💡 Comment rejoindre une session ?</h3>
                    
                    <div class="instruction-methods">
                        <div class="method-card">
                            <div class="method-icon">🔢</div>
                            <div class="method-content">
                                <h4>Par code de session</h4>
                                <ol>
                                    <li>Demandez le code à votre instructeur</li>
                                    <li>Entrez les 6 chiffres dans le champ ci-dessus</li>
                                    <li>Cliquez sur "Rejoindre la session"</li>
                                </ol>
                            </div>
                        </div>

                    </div>

                    <div class="tips-section">
                        <h4>📋 Conseils</h4>
                        <ul>
                            <li>Assurez-vous d'avoir une connexion internet stable</li>
                            <li>Le code de session est valable uniquement pendant la session</li>
                            <li>Vous pouvez rejoindre la session tant qu'elle n'a pas commencé</li>
                            <li>En cas de problème, contactez votre instructeur</li>
                        </ul>
                    </div>

                    <div class="help-section">
                        <h4>🆘 Problèmes fréquents</h4>
                        <div class="faq-item">
                            <strong>Le code ne fonctionne pas</strong>
                            <p>Vérifiez que vous avez bien saisi les 6 chiffres et que la session est toujours active.</p>
                        </div>
                        <div class="faq-item">
                            <strong>La session est pleine</strong>
                            <p>Contactez votre instructeur, il pourra peut-être augmenter la limite de participants.</p>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal QR Scanner -->
    <div id="qrScannerModal" class="modal">
        <div class="modal-content qr-scanner-content">
            <div class="modal-header">
                <h3>Scanner QR Code</h3>
                <button onclick="closeQRScanner()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="qrScannerContainer">
                    <div class="scanner-placeholder">
                        <div class="scanner-icon">📷</div>
                        <p>Initialisation de la caméra...</p>
                        <small>Autorisez l'accès à votre caméra pour scanner le QR Code</small>
                    </div>
                </div>
                <div class="scanner-instructions">
                    <p>Pointez votre caméra vers le QR Code affiché par votre instructeur</p>
                </div>
                <div class="scanner-actions">
                    <button onclick="closeQRScanner()" class="btn btn-secondary">
                        Annuler
                    </button>
                    <button onclick="manualCodeEntry()" class="btn btn-outline-primary">
                        Saisir le code manuellement
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/player.js"></script>
    <script>
        // Formatage automatique du code de session
        document.getElementById('session_code').addEventListener('input', function(e) {
            // Ne garder que les chiffres
            this.value = this.value.replace(/\D/g, '');
            
            // Limiter à 6 chiffres
            if (this.value.length > 6) {
                this.value = this.value.substring(0, 6);
            }
            
            // Auto-submit si 6 chiffres sont saisis
            if (this.value.length === 6) {
                // Petite pause pour que l'utilisateur voie le code complet
                setTimeout(() => {
                    document.getElementById('joinForm').dispatchEvent(new Event('submit'));
                }, 500);
            }
        });

        // Focus automatique sur l'input
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('session_code').focus();
        });

        // Scanner QR Code (simulation pour cette démo)
        function startQRScanner() {
            document.getElementById('qrScannerModal').style.display = 'block';
            
            // Simulation d'initialisation de caméra
            setTimeout(() => {
                document.getElementById('qrScannerContainer').innerHTML = `
                    <div class="scanner-active">
                        <div class="scanner-frame">
                            <div class="scanner-corners"></div>
                            <div class="scanner-line"></div>
                        </div>
                        <p>Pointez votre caméra vers le QR Code</p>
                    </div>
                `;
            }, 1500);
            
            // Dans une vraie implémentation, on utiliserait une bibliothèque comme QuaggaJS
            // Pour cette démo, on simule après 5 secondes
            setTimeout(() => {
                const code = prompt('Simulation du scanner QR\n\nEntrez le code de session trouvé dans le QR Code :');
                if (code && /^\d{6}$/.test(code)) {
                    document.getElementById('session_code').value = code;
                    closeQRScanner();
                    document.getElementById('joinForm').submit();
                } else if (code) {
                    alert('Code invalide. Le code doit contenir exactement 6 chiffres.');
                }
                closeQRScanner();
            }, 5000);
        }

        function closeQRScanner() {
            document.getElementById('qrScannerModal').style.display = 'none';
        }

        function manualCodeEntry() {
            closeQRScanner();
            document.getElementById('session_code').focus();
        }

        // Fermer le modal en cliquant à l'extérieur
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('qrScannerModal');
            if (event.target === modal) {
                closeQRScanner();
            }
        });

        // Gestion des touches
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeQRScanner();
            }
        });
    </script>
</body>
</html>

<style>
.join-session-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 3rem;
    max-width: 1200px;
    margin: 0 auto;
}

.join-session-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow-lg);
    overflow: hidden;
}

.card-header {
    background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
    color: var(--white);
    padding: 2rem;
    text-align: center;
}

.card-header h1 {
    margin-bottom: 0.5rem;
    font-size: 2rem;
}

.card-header p {
    margin: 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

.join-form {
    padding: 2rem;
}

.session-code-input {
    font-size: 2rem;
    text-align: center;
    letter-spacing: 0.5rem;
    font-weight: 700;
    padding: 1.5rem;
    background: var(--gray-50);
    border: 3px solid var(--gray-200);
    font-family: 'Courier New', monospace;
}

.session-code-input:focus {
    border-color: var(--primary-color);
    background: var(--white);
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
}

.alternative-methods {
    padding: 0 2rem 2rem;
}

.divider {
    text-align: center;
    margin: 2rem 0;
    position: relative;
}

.divider::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 0;
    right: 0;
    height: 1px;
    background: var(--gray-300);
}

.divider span {
    background: var(--white);
    padding: 0 1rem;
    color: var(--gray-500);
    font-size: 0.9rem;
}

.instructions-card {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    padding: 2rem;
}

.instructions-card h3 {
    color: var(--gray-800);
    margin-bottom: 2rem;
    text-align: center;
}

.instruction-methods {
    display: grid;
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.method-card {
    display: flex;
    gap: 1rem;
    padding: 1.5rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    border-left: 4px solid var(--primary-color);
}

.method-icon {
    font-size: 2rem;
    flex-shrink: 0;
}

.method-content h4 {
    color: var(--gray-800);
    margin-bottom: 1rem;
    font-size: 1.1rem;
}

.method-content ol {
    margin: 0;
    padding-left: 1rem;
}

.method-content li {
    color: var(--gray-600);
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.tips-section,
.help-section {
    margin-bottom: 2rem;
}

.tips-section h4,
.help-section h4 {
    color: var(--gray-800);
    margin-bottom: 1rem;
    font-size: 1rem;
}

.tips-section ul {
    margin: 0;
    padding-left: 1rem;
}

.tips-section li {
    color: var(--gray-600);
    margin-bottom: 0.5rem;
    line-height: 1.4;
}

.faq-item {
    margin-bottom: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
}

.faq-item strong {
    display: block;
    color: var(--gray-800);
    margin-bottom: 0.5rem;
}

.faq-item p {
    margin: 0;
    color: var(--gray-600);
    font-size: 0.9rem;
    line-height: 1.4;
}

/* Modal QR Scanner */
.qr-scanner-content {
    max-width: 500px;
}

#qrScannerContainer {
    height: 300px;
    background: var(--gray-100);
    border-radius: var(--border-radius);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 1rem;
}

.scanner-placeholder {
    text-align: center;
    color: var(--gray-500);
}

.scanner-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.7;
}

.scanner-active {
    text-align: center;
    color: var(--gray-700);
    width: 100%;
}

.scanner-frame {
    width: 200px;
    height: 200px;
    border: 2px solid var(--primary-color);
    border-radius: var(--border-radius);
    margin: 0 auto 1rem;
    position: relative;
    overflow: hidden;
}

.scanner-corners {
    position: absolute;
    top: 10px;
    left: 10px;
    right: 10px;
    bottom: 10px;
    border: 2px solid var(--primary-color);
    border-radius: 4px;
}

.scanner-line {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 2px;
    background: var(--primary-color);
    animation: scannerMove 2s ease-in-out infinite;
}

@keyframes scannerMove {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(196px); }
}

.scanner-instructions {
    text-align: center;
    margin-bottom: 1rem;
    color: var(--gray-600);
}

.scanner-actions {
    display: flex;
    gap: 1rem;
    justify-content: center;
}

/* Responsive */
@media (max-width: 1024px) {
    .join-session-container {
        grid-template-columns: 1fr;
        gap: 2rem;
    }
}

@media (max-width: 768px) {
    .join-session-container {
        gap: 1.5rem;
    }
    
    .card-header {
        padding: 1.5rem;
    }
    
    .card-header h1 {
        font-size: 1.5rem;
    }
    
    .join-form {
        padding: 1.5rem;
    }
    
    .session-code-input {
        font-size: 1.5rem;
        letter-spacing: 0.3rem;
    }
    
    .method-card {
        flex-direction: column;
        text-align: center;
    }
    
    .scanner-actions {
        flex-direction: column;
    }
}
</style>

<script>
// Fonction pour toggle le menu burger
function toggleBurgerMenu() {
    console.log('toggleBurgerMenu appelée');
    const dropdown = document.getElementById('burgerDropdown');
    const burgerBtn = document.getElementById('burgerBtn');
    
    if (dropdown && burgerBtn) {
        const isShown = dropdown.style.display === 'block';
        dropdown.style.display = isShown ? 'none' : 'block';
        burgerBtn.textContent = isShown ? '☰' : '✕';
        console.log('Menu toggled, display:', dropdown.style.display);
    } else {
        console.error('Éléments non trouvés');
    }
}

// Fermer le menu si on clique ailleurs
document.addEventListener('click', function(event) {
    const burgerBtn = document.getElementById('burgerBtn');
    const dropdown = document.getElementById('burgerDropdown');
    
    if (burgerBtn && dropdown && 
        !burgerBtn.contains(event.target) && 
        !dropdown.contains(event.target)) {
        dropdown.style.display = 'none';
        burgerBtn.textContent = '☰';
        console.log('Menu fermé par clic extérieur');
    }
});
</script>
</body>
</html>
