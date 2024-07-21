<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'fin_turn_action') {
    try {

        // Récupérer le deck actuel du joueur
        $stmt_get_deck = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
        $stmt_get_deck->execute(['player_id' => $_SESSION['user_id']]);
        $row_deck = $stmt_get_deck->fetch(PDO::FETCH_ASSOC);
        $deck = json_decode($row_deck['deck'], true) ?: [];

        $count_deck = count($deck);

        if($count_deck <= 10) {
            // Update turn in the database
            if($row_deck['dice_data'] !== "" && $row_deck['localisation'] !== $row_deck['last_localisation']){
                $stmt_update_turn = $pdo->prepare("UPDATE games SET turn = turn + 1 WHERE `creator_id` = :game_id");
                $stmt_update_turn->execute(['game_id' => $_POST['game_id']]);
                echo json_encode(['success' => false, 'message' => "Nouveau tour !"]);
            } else if($row_deck['dice_data'] == ""){
                $stmt_update_turn = $pdo->prepare("UPDATE games SET turn = turn + 1 WHERE `creator_id` = :game_id");
                $stmt_update_turn->execute(['game_id' => $_POST['game_id']]);
                echo json_encode(['success' => false, 'message' => "Nouveau tour !"]);
            } else {
                echo json_encode(['success' => false, 'message' => "Veuillez finir votre déplacement, sélectionner la zone souhaitée!"]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Vous devez avoir moins de 10 cartes"]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
