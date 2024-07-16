<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_turn') {
    try {
        // Récupérer le turn actuel du joueur
        $stmt_get_turn = $pdo->prepare("SELECT * FROM games WHERE creator_id = :game_id");
        $stmt_get_turn->execute(['game_id' => $_POST['game_id']]);
        $row_turn = $stmt_get_turn->fetch(PDO::FETCH_ASSOC);

        $stmt_get_player_info = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :user_id");
        $stmt_get_player_info->execute(['user_id' => $_SESSION['user_id']]);
        $row_player_info = $stmt_get_player_info->fetch(PDO::FETCH_ASSOC);

        $turn = $row_turn['turn'];
        $dice_data = $row_player_info['dice_data'];
        
        echo json_encode(['success' => true, 'turn' =>  $turn, 'dice_data' =>  $dice_data]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
