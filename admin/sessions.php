<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/GameSession.php';
require_once __DIR__ . '/../classes/Quiz.php';

requireQuizManager();

$current_user = getCurrentUserOrRedirect();
$gameSession = new GameSession();
$quiz = new Quiz();

// Obtenir toutes les sessions de cet admin
$sessions = $gameSession->getByAdmin($current_user['id']);

$error_message = '';
$success_message = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $session_id = $_POST['session_id'] ?? '';
    
    if ($action === 'delete_session' && $session_id) {
        $result = $gameSession->delete($session_id);
        
        if ($result['success']) {
            $success_message = 'Session supprimée avec succès';
            $sessions = $gameSession->getByAdmin($current_user['id']); // Recharger
        } else {
            $error_message = $result['message'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sessions de jeu - Kuizu Sapeurs-Pompiers</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <nav class="admin-sidebar">
            <div class="sidebar-header">
                <div class="logo">
                    <img src="../assets/images/logo.png" alt="Kuizu" width="40" height="40">
                    <h1>Kuizu</h1>
                </div>
                <p>Administration</p>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-item">
                    <a href="dashboard.php">
                        <span class="menu-icon">📊</span>
                        Tableau de bord
                    </a>
                </li>
                <li class="menu-item">
                    <a href="quizzes.php">
                        <span class="menu-icon">❓</span>
                        Mes Quiz
                    </a>
                </li>
                <li class="menu-item active">
                    <a href="sessions.php">
                        <span class="menu-icon">🎮</span>
                        Sessions de jeu
                    </a>
                </li>
                <li class="menu-item">
                    <a href="users.php">
                        <span class="menu-icon">👥</span>
                        Utilisateurs
                    </a>
                </li>
            </ul>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <strong><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></strong>
                    <small><?php echo $current_user['role'] === 'admin' ? 'Administrateur' : 'Encadrant'; ?></small>
                </div>
                <a href="../auth/logout.php" class="btn btn-outline-primary btn-sm">
                    Déconnexion
                </a>
            </div>
        </nav>

        <!-- Contenu principal -->
        <main class="admin-content">
            <div class="content-header">
                <div class="header-actions">
                    <div>
                        <h1>Sessions de jeu</h1>
                        <p>Gérez vos sessions de quiz en temps réel</p>
                    </div>
                    <a href="session_create.php" class="btn btn-primary">
                        🎮 Nouvelle session
                    </a>
                </div>
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

            <!-- Statistiques des sessions -->
            <div class="sessions-stats">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">🎮</div>
                        <div class="stat-content">
                            <h3><?php echo count($sessions); ?></h3>
                            <p>Sessions créées</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">▶️</div>
                        <div class="stat-content">
                            <h3><?php echo count(array_filter($sessions, fn($s) => in_array($s['status'], ['waiting', 'active', 'paused']))); ?></h3>
                            <p>Sessions actives</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">✅</div>
                        <div class="stat-content">
                            <h3><?php echo count(array_filter($sessions, fn($s) => $s['status'] === 'finished')); ?></h3>
                            <p>Sessions terminées</p>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon">👥</div>
                        <div class="stat-content">
                            <h3><?php echo array_sum(array_column($sessions, 'participant_count')); ?></h3>
                            <p>Participants totaux</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Liste des sessions -->
            <?php if (empty($sessions)): ?>
                <div class="empty-state">
                    <div class="empty-icon">🎮</div>
                    <h3>Aucune session créée</h3>
                    <p>Commencez par créer votre première session de jeu pour animer vos quiz</p>
                    <a href="session_create.php" class="btn btn-primary">Créer ma première session</a>
                </div>
            <?php else: ?>
                <div class="sessions-container">
                    <div class="sessions-header">
                        <h2>Toutes les sessions (<?php echo count($sessions); ?>)</h2>
                        <div class="filter-controls">
                            <select id="statusFilter" onchange="filterSessions()">
                                <option value="all">Tous les statuts</option>
                                <option value="waiting">En attente</option>
                                <option value="active">Actives</option>
                                <option value="paused">En pause</option>
                                <option value="finished">Terminées</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="sessions-list">
                        <?php foreach ($sessions as $session): ?>
                            <div class="session-card" data-status="<?php echo $session['status']; ?>">
                                <div class="session-header">
                                    <div class="session-info">
                                        <h3><?php echo htmlspecialchars($session['quiz_title']); ?></h3>
                                        <div class="session-meta">
                                            <span class="session-code">Code: <strong><?php echo $session['session_code']; ?></strong></span>
                                            <span class="participant-count">👥 <?php echo $session['participant_count']; ?> participant(s)</span>
                                            <span class="session-date">📅 <?php echo date('d/m/Y à H:i', strtotime($session['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    
                                    <div class="session-status">
                                        <span class="status-badge status-<?php echo $session['status']; ?>">
                                            <?php 
                                            $status_labels = [
                                                'waiting' => '⏳ En attente',
                                                'active' => '▶️ Active',
                                                'paused' => '⏸️ En pause',
                                                'finished' => '✅ Terminée'
                                            ];
                                            echo $status_labels[$session['status']] ?? $session['status'];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="session-actions">
                                    <?php if (in_array($session['status'], ['waiting', 'active', 'paused'])): ?>
                                        <a href="session_manage.php?id=<?php echo $session['id']; ?>" 
                                           class="btn btn-primary btn-sm">
                                            🎮 Gérer
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($session['status'] === 'finished'): ?>
                                        <button onclick="showSessionResults(<?php echo $session['id']; ?>)" 
                                                class="btn btn-info btn-sm">
                                            📊 Résultats
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button onclick="showSessionQR('<?php echo $session['session_code']; ?>')" 
                                            class="btn btn-secondary btn-sm">
                                        📱 QR Code
                                    </button>
                                    
                                    <?php if (in_array($session['status'], ['waiting', 'finished'])): ?>
                                        <button onclick="deleteSession(<?php echo $session['id']; ?>)" 
                                                class="btn btn-danger btn-sm">
                                            🗑️ Supprimer
                                        </button>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($session['started_at']): ?>
                                    <div class="session-timeline">
                                        <div class="timeline-item">
                                            <span class="timeline-label">Démarrée :</span>
                                            <span class="timeline-value"><?php echo date('d/m/Y à H:i', strtotime($session['started_at'])); ?></span>
                                        </div>
                                        <?php if ($session['finished_at']): ?>
                                            <div class="timeline-item">
                                                <span class="timeline-label">Terminée :</span>
                                                <span class="timeline-value"><?php echo date('d/m/Y à H:i', strtotime($session['finished_at'])); ?></span>
                                            </div>
                                            <div class="timeline-item">
                                                <span class="timeline-label">Durée :</span>
                                                <span class="timeline-value">
                                                    <?php 
                                                    $duration = strtotime($session['finished_at']) - strtotime($session['started_at']);
                                                    echo gmdate("H:i:s", $duration);
                                                    ?>
                                                </span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal QR Code -->
    <div id="qrModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>QR Code de la session</h3>
                <button onclick="closeQRModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div class="qr-display">
                    <div class="qr-code" id="qrCodeContainer">
                        <!-- QR Code sera inséré ici -->
                    </div>
                    <div class="session-details">
                        <div class="detail-item">
                            <strong>Code de session:</strong>
                            <span class="session-code-large" id="sessionCodeDisplay"></span>
                        </div>
                        <div class="detail-item">
                            <strong>URL directe:</strong>
                            <input type="text" id="sessionUrlInput" readonly onclick="this.select()" class="url-input">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Résultats -->
    <div id="resultsModal" class="modal">
        <div class="modal-content modal-large">
            <div class="modal-header">
                <h3>Résultats de la session</h3>
                <button onclick="closeResultsModal()" class="modal-close">&times;</button>
            </div>
            <div class="modal-body">
                <div id="resultsContainer">
                    <!-- Les résultats seront chargés ici -->
                </div>
            </div>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
        function filterSessions() {
            const filter = document.getElementById('statusFilter').value;
            const cards = document.querySelectorAll('.session-card');
            
            cards.forEach(card => {
                const status = card.dataset.status;
                if (filter === 'all' || status === filter) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        async function deleteSession(sessionId) {
            if (!confirm('Êtes-vous sûr de vouloir supprimer cette session ? Cette action est irréversible.')) {
                return;
            }

            try {
                const formData = new FormData();
                formData.append('action', 'delete_session');
                formData.append('session_id', sessionId);

                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });

                if (response.ok) {
                    window.location.reload();
                } else {
                    alert('Erreur lors de la suppression');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            }
        }

        function showSessionQR(sessionCode) {
            const modal = document.getElementById('qrModal');
            const qrContainer = document.getElementById('qrCodeContainer');
            const codeDisplay = document.getElementById('sessionCodeDisplay');
            const urlInput = document.getElementById('sessionUrlInput');
            
            // Obtenir l'URL de base dynamiquement
            const baseUrl = window.location.protocol + '//' + window.location.host + window.location.pathname.replace(/\/[^\/]*$/, '');
            const sessionUrl = baseUrl.replace('/admin', '') + `/auth/login.php?session=${sessionCode}`;
            const qrCodeUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(sessionUrl)}`;
            
            qrContainer.innerHTML = `<img src="${qrCodeUrl}" alt="QR Code de la session" style="max-width: 100%; height: auto;">`;
            codeDisplay.textContent = sessionCode;
            urlInput.value = sessionUrl;
            
            modal.style.display = 'block';
        }

        function closeQRModal() {
            document.getElementById('qrModal').style.display = 'none';
        }

        async function showSessionResults(sessionId) {
            const modal = document.getElementById('resultsModal');
            const container = document.getElementById('resultsContainer');
            
            try {
                // Charger les résultats de la session
                const response = await fetch('../api/game_session.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        action: 'get_leaderboard',
                        session_id: sessionId
                    })
                });

                const result = await response.json();
                if (result.success) {
                    displaySessionResults(result.leaderboard);
                    modal.style.display = 'block';
                } else {
                    alert('Erreur lors du chargement des résultats');
                }
            } catch (error) {
                console.error('Erreur:', error);
                alert('Erreur de connexion');
            }
        }

        function displaySessionResults(leaderboard) {
            const container = document.getElementById('resultsContainer');
            
            if (leaderboard.length === 0) {
                container.innerHTML = '<p>Aucun participant dans cette session.</p>';
                return;
            }

            container.innerHTML = `
                <div class="results-leaderboard">
                    <h4>Classement final</h4>
                    <div class="leaderboard-list">
                        ${leaderboard.map((participant, index) => `
                            <div class="leaderboard-item ${index < 3 ? 'top-3' : ''}">
                                <div class="leaderboard-position">${index + 1}</div>
                                <div class="leaderboard-info">
                                    <div class="leaderboard-name">${participant.first_name} ${participant.last_name}</div>
                                    <div class="leaderboard-details">
                                        <span>🏆 ${participant.total_score} points</span>
                                        <span>✅ ${participant.correct_answers || 0} bonnes réponses</span>
                                        <span>📝 ${participant.total_answers || 0} réponses</span>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                </div>
            `;
        }

        function closeResultsModal() {
            document.getElementById('resultsModal').style.display = 'none';
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
    </script>
</body>
</html>

<style>
.sessions-stats {
    margin-bottom: 3rem;
}

.sessions-container {
    background: var(--white);
    border-radius: var(--border-radius-lg);
    box-shadow: var(--shadow);
    overflow: hidden;
}

.sessions-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--gray-200);
    background: var(--gray-50);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.sessions-header h2 {
    margin: 0;
    color: var(--gray-800);
}

.filter-controls select {
    padding: 0.5rem 1rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    background: var(--white);
}

.sessions-list {
    padding: 1rem;
}

.session-card {
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    margin-bottom: 1rem;
    overflow: hidden;
    transition: var(--transition);
}

.session-card:hover {
    box-shadow: var(--shadow);
}

.session-header {
    padding: 1.5rem;
    background: var(--gray-50);
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
}

.session-info h3 {
    color: var(--gray-800);
    margin-bottom: 0.75rem;
    font-size: 1.2rem;
}

.session-meta {
    display: flex;
    gap: 1.5rem;
    flex-wrap: wrap;
    font-size: 0.9rem;
    color: var(--gray-600);
}

.session-code {
    background: var(--white);
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-300);
    font-family: 'Courier New', monospace;
}

.status-badge {
    padding: 0.5rem 1rem;
    border-radius: var(--border-radius);
    font-weight: 600;
    font-size: 0.85rem;
}

.status-waiting {
    background: var(--secondary-color);
    color: var(--white);
}

.status-active {
    background: var(--success-color);
    color: var(--white);
}

.status-paused {
    background: var(--gray-500);
    color: var(--white);
}

.status-finished {
    background: var(--primary-color);
    color: var(--white);
}

.session-actions {
    padding: 1rem 1.5rem;
    background: var(--white);
    border-top: 1px solid var(--gray-200);
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.session-timeline {
    padding: 1rem 1.5rem;
    background: var(--gray-50);
    border-top: 1px solid var(--gray-200);
}

.timeline-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
    font-size: 0.9rem;
}

.timeline-item:last-child {
    margin-bottom: 0;
}

.timeline-label {
    color: var(--gray-600);
    font-weight: 500;
}

.timeline-value {
    color: var(--gray-800);
    font-weight: 600;
}

/* Modal styles */
.modal-large .modal-content {
    max-width: 800px;
}

.qr-display {
    text-align: center;
}

.qr-code {
    margin-bottom: 2rem;
}

.session-details {
    display: grid;
    gap: 1rem;
    text-align: left;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.detail-item strong {
    color: var(--gray-800);
    font-size: 0.9rem;
}

.session-code-large {
    font-size: 2rem;
    font-weight: 700;
    color: var(--primary-color);
    letter-spacing: 0.2rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    text-align: center;
}

.url-input {
    font-size: 0.9rem;
    padding: 0.75rem;
    border: 1px solid var(--gray-300);
    border-radius: var(--border-radius);
    background: var(--gray-50);
}

.results-leaderboard h4 {
    color: var(--gray-800);
    margin-bottom: 1rem;
    text-align: center;
}

.leaderboard-list {
    display: grid;
    gap: 0.75rem;
}

.leaderboard-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--gray-50);
    border-radius: var(--border-radius);
    border: 1px solid var(--gray-200);
}

.leaderboard-item.top-3 {
    background: linear-gradient(135deg, #fff4f0 0%, #ffe6dc 100%);
    border-color: var(--secondary-color);
}

.leaderboard-position {
    width: 32px;
    height: 32px;
    background: var(--primary-color);
    color: var(--white);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
}

.leaderboard-item.top-3 .leaderboard-position {
    background: var(--secondary-color);
}

.leaderboard-info {
    flex: 1;
}

.leaderboard-name {
    font-weight: 600;
    color: var(--gray-800);
    margin-bottom: 0.5rem;
}

.leaderboard-details {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: var(--gray-600);
    flex-wrap: wrap;
}

@media (max-width: 768px) {
    .sessions-header {
        flex-direction: column;
        gap: 1rem;
        align-items: stretch;
    }
    
    .session-header {
        flex-direction: column;
        align-items: stretch;
        gap: 1rem;
    }
    
    .session-meta {
        justify-content: center;
        gap: 1rem;
    }
    
    .session-actions {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    .session-actions .btn {
        flex: 1;
        min-width: 120px;
    }
    
    .timeline-item {
        flex-direction: column;
        gap: 0.25rem;
        text-align: center;
    }
    
    .leaderboard-details {
        justify-content: center;
        text-align: center;
    }
}
</style>
