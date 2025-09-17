<?php
require_once __DIR__ . '/../auth/check_auth.php';
require_once __DIR__ . '/../classes/User.php';

// Vérifier les permissions (encadrants et admins)
requireQuizManager();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'ID joueur manquant']);
    exit();
}

$player_id = intval($_GET['id']);

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Récupérer les informations du joueur
    $playerQuery = "SELECT 
        id, username, email, first_name, last_name, created_at
        FROM users 
        WHERE id = :player_id AND role = 'player'";
    
    $playerStmt = $conn->prepare($playerQuery);
    $playerStmt->bindParam(':player_id', $player_id);
    $playerStmt->execute();
    $player = $playerStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$player) {
        echo json_encode(['success' => false, 'message' => 'Joueur non trouvé']);
        exit();
    }
    
    // Récupérer les statistiques détaillées
    $statsQuery = "SELECT 
        COUNT(*) as total_games,
        AVG(final_score) as avg_score,
        MAX(final_score) as best_score,
        MIN(final_score) as worst_score,
        AVG(correct_answers * 100.0 / total_questions) as avg_success_rate,
        SUM(correct_answers) as total_correct_answers,
        SUM(total_questions) as total_questions_answered,
        AVG(completion_time) as avg_completion_time,
        COUNT(DISTINCT quiz_id) as unique_quizzes_played
        FROM game_history 
        WHERE user_id = :player_id";
    
    $statsStmt = $conn->prepare($statsQuery);
    $statsStmt->bindParam(':player_id', $player_id);
    $statsStmt->execute();
    $stats = $statsStmt->fetch(PDO::FETCH_ASSOC);
    
    // Récupérer l'historique des parties récentes (10 dernières)
    $historyQuery = "SELECT 
        gh.final_score,
        gh.correct_answers,
        gh.total_questions,
        gh.completion_time,
        gh.played_at,
        q.title as quiz_title,
        q.description as quiz_description
        FROM game_history gh
        JOIN quizzes q ON gh.quiz_id = q.id
        WHERE gh.user_id = :player_id
        ORDER BY gh.played_at DESC
        LIMIT 10";
    
    $historyStmt = $conn->prepare($historyQuery);
    $historyStmt->bindParam(':player_id', $player_id);
    $historyStmt->execute();
    $history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer les quiz favoris (les plus joués)
    $favoritesQuery = "SELECT 
        q.title,
        q.description,
        COUNT(*) as play_count,
        AVG(gh.final_score) as avg_score_on_quiz,
        MAX(gh.final_score) as best_score_on_quiz
        FROM game_history gh
        JOIN quizzes q ON gh.quiz_id = q.id
        WHERE gh.user_id = :player_id
        GROUP BY gh.quiz_id, q.title, q.description
        ORDER BY play_count DESC, avg_score_on_quiz DESC
        LIMIT 5";
    
    $favoritesStmt = $conn->prepare($favoritesQuery);
    $favoritesStmt->bindParam(':player_id', $player_id);
    $favoritesStmt->execute();
    $favorites = $favoritesStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Récupérer l'évolution des scores (dernières 20 parties)
    $evolutionQuery = "SELECT 
        final_score,
        played_at,
        correct_answers * 100.0 / total_questions as success_rate
        FROM game_history
        WHERE user_id = :player_id
        ORDER BY played_at ASC
        LIMIT 20";
    
    $evolutionStmt = $conn->prepare($evolutionQuery);
    $evolutionStmt->bindParam(':player_id', $player_id);
    $evolutionStmt->execute();
    $evolution = $evolutionStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculer le rang du joueur
    $rankQuery = "SELECT 
        COUNT(*) + 1 as player_rank
        FROM (
            SELECT user_id, AVG(final_score) as avg_score
            FROM game_history
            GROUP BY user_id
            HAVING AVG(final_score) > (
                SELECT AVG(final_score)
                FROM game_history
                WHERE user_id = :player_id
            )
        ) as better_players";
    
    $rankStmt = $conn->prepare($rankQuery);
    $rankStmt->bindParam(':player_id', $player_id);
    $rankStmt->execute();
    $rank = $rankStmt->fetch(PDO::FETCH_ASSOC);
    
    $response = [
        'success' => true,
        'player' => $player,
        'stats' => array_merge($stats, ['rank' => $rank['player_rank']]),
        'history' => $history,
        'favorites' => $favorites,
        'evolution' => $evolution
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur lors de la récupération des données: ' . $e->getMessage()
    ]);
}
?>
