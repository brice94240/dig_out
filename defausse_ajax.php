<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_defausse') {
    try {
        // Récupérer le defausse actuel du joueur
        $stmt_get_defausse = $pdo->prepare("SELECT defausse_data FROM games WHERE creator_id = :game_id");
        $stmt_get_defausse->execute(['game_id' => $_POST['game_id']]);
        $row_defausse = $stmt_get_defausse->fetch(PDO::FETCH_ASSOC);
        $defausse = json_decode($row_defausse['defausse_data'], true) ?: [];
        
        echo json_encode(['success' => true, 'defausse' =>  $defausse]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
