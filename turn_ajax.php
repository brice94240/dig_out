<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_turn') {
    try {
        $stmt = $pdo->query("SELECT * FROM games INNER JOIN joueurs ON joueurs.ID = games.creator_id");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $turn = $row['turn'];
        if($row['turn'] >= 2) {
            $real_turn = $row['turn']-2;

            if($real_turn >= $row['max_player']){
                $real_turn = $real_turn % $row['max_player'];
            }
            $player_tab = json_decode($row['turn_data'], true);
            $player_turn_id = $player_tab[$real_turn];
            $stmt_joueur_turn = $pdo->prepare("SELECT * FROM joueurs  WHERE ID = :ID");
            $stmt_joueur_turn->execute(['ID' => $player_turn_id]);
            $row_joueur_turn = $stmt_joueur_turn->fetch(PDO::FETCH_ASSOC);
            $player_turn_name = $row_joueur_turn['pseudo'];
        } else {
            $real_turn = 0;
            $player_turn_id = "";
            $player_turn_name = "";
        }
        echo json_encode(['success' => true, 'turn' => $turn, 'new_turn' => '1', 'last_turn' => $_POST['turn'], 'real_turn' => $real_turn, 'player_turn_id' => $player_turn_id, 'player_turn_name' => $player_turn_name, 'player_tab' => $player_tab]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
