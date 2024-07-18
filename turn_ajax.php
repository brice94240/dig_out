<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_turn') {
    try {
        $stmt = $pdo->query("SELECT * FROM games INNER JOIN joueurs ON joueurs.ID = games.creator_id");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(intval($row['turn']) !== intval($_POST['turn'])){
            $test = "OK";
            // Update the dice data in the database
            $stmt_update_dice_data_to_empty = $pdo->prepare("UPDATE joueurs SET dice_data = :dice_data WHERE `game_joined` = :game_id");
            $stmt_update_dice_data_to_empty->execute(['dice_data' => '', 'game_id' => $_POST['game_id']]);

            // Update the point_turn in the database
            $stmt_update_point_turn_to_empty = $pdo->prepare("UPDATE joueurs SET point_turn = :point_turn WHERE `game_joined` = :game_id");
            $stmt_update_point_turn_to_empty->execute(['point_turn' => 0, 'game_id' => $_POST['game_id']]);

             // Update the action to all players
             $stmt_update_action_to_players = $pdo->prepare("UPDATE joueurs SET nb_action = :nb_action WHERE `game_joined` = :game_id");
             $stmt_update_action_to_players->execute(['nb_action' => 0, 'game_id' => $_POST['game_id']]);

             if($row['turn'] >= 2) {
                $real_turn = $row['turn']-2;
                if($real_turn >= $row['max_player']){
                    $real_turn = $real_turn % $row['max_player'];
                }
                $player_tab = json_decode($row['turn_data'], true);
                $player_turn_id = $player_tab[$real_turn];
            }

            // Update the action to players playing
            $stmt_update_action_to_player_playing = $pdo->prepare("UPDATE joueurs SET nb_action = :nb_action WHERE `game_joined` = :game_id AND ID = :player_id");
            $stmt_update_action_to_player_playing->execute(['nb_action' => 2, 'game_id' => $_POST['game_id'], 'player_id' => $player_turn_id]);

        }
        $turn = $row['turn'];
        if($row['turn'] >= 2) {
            $real_turn = $row['turn']-2;

            if($real_turn >= $row['max_player']){
                $real_turn = $real_turn % $row['max_player'];
            }
            $player_tab = json_decode($row['turn_data'], true);
            $player_turn_id = $player_tab[$real_turn];
            $stmt_joueur_turn = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :ID");
            $stmt_joueur_turn->execute(['ID' => $player_turn_id]);
            $row_joueur_turn = $stmt_joueur_turn->fetch(PDO::FETCH_ASSOC);
            $player_turn_name = $row_joueur_turn['pseudo'];
        } else {
            $real_turn = 0;
            $player_turn_id = "";
            $player_turn_name = "";
        }
        // Requête pour récupérer toutes les localisations
        $stmt_localisations = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id");
        $stmt_localisations->execute(['game_id' => $_POST['game_id']]);
        $localisations = [];
        while ($row_localisation = $stmt_localisations->fetch(PDO::FETCH_ASSOC)) {
            $localisations[$row_localisation['ID']] = $row_localisation['localisation'];
        }

        // Construction du tableau avec les informations des joueurs
        $playerData = [];

        // Récupération des informations (pseudo, team) en fonction des localisations
        foreach ($localisations as $playerID => $localisation) {
            $stmt_info_joueur = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
            $stmt_info_joueur->execute(['player_id' => $playerID]);
            $row_info_joueur = $stmt_info_joueur->fetch(PDO::FETCH_ASSOC);

            if($row_info_joueur['nb_action'] == 2){
                // Update localisation
                $stmt_update_last_localisation_to_players = $pdo->prepare("UPDATE joueurs SET last_localisation = :last_localisation WHERE `game_joined` = :game_id");
                $stmt_update_last_localisation_to_players->execute(['last_localisation' => $row_info_joueur['localisation'], 'game_id' => $_POST['game_id']]);
                $stmt_info_joueur = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
                $stmt_info_joueur->execute(['player_id' => $playerID]);
                $row_info_joueur = $stmt_info_joueur->fetch(PDO::FETCH_ASSOC);
            }

            if ($row_info_joueur) {
                $playerData[$playerID] = [
                    'pseudo' => $row_info_joueur['pseudo'],
                    'team' => $row_info_joueur['team'],
                    'dice_data' => $row_info_joueur['dice_data'],
                    'localisation' => $localisation,
                    'last_localisation' => $row_info_joueur['last_localisation']
                ];
            }
        }

        $stmt_player_data = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
        $stmt_player_data->execute(['player_id' => $_SESSION['user_id']]);
        $row_player_data = $stmt_player_data->fetch(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'turn' => $turn, 'new_turn' => '1', 'last_turn' => $_POST['turn'], 'real_turn' => $real_turn, 'player_turn_id' => $player_turn_id, 'player_turn_name' => $player_turn_name, 'player_tab' => $player_tab, 'playerData' => $playerData, 'nb_action' => $row_player_data['nb_action'], 'player_id' => $row_player_data['ID'], 'localisation' => $row_player_data['localisation'], 'team' => $row_player_data['team'], 'test' => $test]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
