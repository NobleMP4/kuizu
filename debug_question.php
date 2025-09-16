<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/classes/Question.php';

$question_id = $_GET['q'] ?? null;

echo "<h1>Debug Question</h1>";

if ($question_id) {
    $question = new Question();
    $question_data = $question->getById($question_id);
    
    echo "<h2>Question ID: $question_id</h2>";
    
    if ($question_data) {
        echo "<h3>✅ Question trouvée :</h3>";
        echo "<pre>";
        print_r($question_data);
        echo "</pre>";
        
        echo "<h3>JSON pour JavaScript :</h3>";
        echo "<pre>";
        echo json_encode($question_data, JSON_PRETTY_PRINT);
        echo "</pre>";
        
    } else {
        echo "<h3>❌ Question non trouvée</h3>";
    }
} else {
    // Afficher toutes les questions
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT q.*, quiz.title as quiz_title 
              FROM questions q 
              LEFT JOIN quizzes quiz ON q.quiz_id = quiz.id 
              ORDER BY q.quiz_id, q.question_order";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Questions disponibles :</h2>";
    
    if (empty($questions)) {
        echo "<p>Aucune question trouvée</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th style='padding: 10px;'>ID</th>";
        echo "<th style='padding: 10px;'>Quiz</th>";
        echo "<th style='padding: 10px;'>Question</th>";
        echo "<th style='padding: 10px;'>Type</th>";
        echo "<th style='padding: 10px;'>Ordre</th>";
        echo "<th style='padding: 10px;'>Action</th>";
        echo "</tr>";
        
        foreach ($questions as $q) {
            echo "<tr>";
            echo "<td style='padding: 10px;'>" . $q['id'] . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars($q['quiz_title']) . "</td>";
            echo "<td style='padding: 10px;'>" . htmlspecialchars(substr($q['question_text'], 0, 50)) . "...</td>";
            echo "<td style='padding: 10px;'>" . $q['question_type'] . "</td>";
            echo "<td style='padding: 10px;'>" . $q['question_order'] . "</td>";
            echo "<td style='padding: 10px;'>";
            echo "<a href='?q=" . $q['id'] . "' style='background: #224d71; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;'>Tester</a>";
            echo "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<br><a href='index.php' style='background: #224d71; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>← Retour à l'accueil</a>";
?>
