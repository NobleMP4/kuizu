<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/GameSession.php';

echo "<h1>Test de Session</h1>";

// Vérifier si un code est fourni
$test_code = $_GET['code'] ?? '';

if ($test_code) {
    echo "<h2>Test du code: $test_code</h2>";
    
    $gameSession = new GameSession();
    
    // Tester getByCode
    $session = $gameSession->getByCode($test_code);
    
    if ($session) {
        echo "<h3>✅ Session trouvée :</h3>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $session['id'] . "</li>";
        echo "<li><strong>Code:</strong> " . $session['session_code'] . "</li>";
        echo "<li><strong>Quiz:</strong> " . htmlspecialchars($session['quiz_title']) . "</li>";
        echo "<li><strong>Statut:</strong> " . $session['status'] . "</li>";
        echo "<li><strong>Admin:</strong> " . $session['admin_username'] . "</li>";
        echo "<li><strong>Participants:</strong> " . $session['participant_count'] . "</li>";
        echo "</ul>";
        
        // Tester addParticipant avec un utilisateur fictif (ID 1)
        echo "<h3>Test d'ajout de participant (utilisateur ID 1):</h3>";
        $result = $gameSession->addParticipant($session['id'], 1);
        
        if ($result['success']) {
            echo "✅ <strong>Succès:</strong> Participant ajouté<br>";
        } else {
            echo "❌ <strong>Erreur:</strong> " . $result['message'] . "<br>";
        }
        
    } else {
        echo "<h3>❌ Session non trouvée</h3>";
    }
} else {
    // Afficher toutes les sessions disponibles
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT gs.*, q.title as quiz_title, u.username as admin_username,
             (SELECT COUNT(*) FROM participants WHERE session_id = gs.id) as participant_count
             FROM game_sessions gs 
             LEFT JOIN quizzes q ON gs.quiz_id = q.id
             LEFT JOIN users u ON gs.admin_id = u.id
             ORDER BY gs.created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Sessions disponibles pour test :</h2>";
    
    if (empty($sessions)) {
        echo "<p><strong>Aucune session trouvée.</strong></p>";
        echo "<p>Créez d'abord une session depuis l'interface admin.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 10px;'>Code</th>";
        echo "<th style='padding: 10px;'>Quiz</th>";
        echo "<th style='padding: 10px;'>Statut</th>";
        echo "<th style='padding: 10px;'>Admin</th>";
        echo "<th style='padding: 10px;'>Participants</th>";
        echo "<th style='padding: 10px;'>Action</th>";
        echo "</tr>";
        
        foreach ($sessions as $session) {
            $status_color = [
                'waiting' => '#f59e0b',
                'active' => '#10b981', 
                'paused' => '#6b7280',
                'finished' => '#dc2626'
            ];
            
            echo "<tr>";
            echo "<td style='padding: 10px; text-align: center;'><strong>" . $session['session_code'] . "</strong></td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($session['quiz_title']) . "</td>";
            echo "<td style='padding: 10px; color: " . ($status_color[$session['status']] ?? '#000') . ";'><strong>" . $session['status'] . "</strong></td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($session['admin_username']) . "</td>";
            echo "<td style='padding: 10px; text-align: center;'>" . $session['participant_count'] . "</td>";
            echo "<td style='padding: 10px;'>";
            echo "<a href='?code=" . $session['session_code'] . "' style='background: #224d71; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;'>Tester</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Instructions :</h3>";
    echo "<ol>";
    echo "<li>Créez une session depuis l'interface admin si aucune n'existe</li>";
    echo "<li>Cliquez sur 'Tester' pour vérifier si le code fonctionne</li>";
    echo "<li>Utilisez le code dans l'interface joueur pour rejoindre</li>";
    echo "</ol>";
}

echo "<br><a href='index.php' style='background: #224d71; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>← Retour à l'accueil</a>";
?>
