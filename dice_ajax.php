<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id']) && isset($_POST['dice'])) {
    try {
        // Decode the new dice data from the POST request
        $new_dice_data = json_decode($_POST['dice'], true);

        // Ensure that the new dice data is properly decoded
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON data");
        }

        // Retrieve the current dice data for the user
        $stmt_dice = $pdo->prepare("SELECT dice_data FROM joueurs WHERE `ID` = :user_id");
        $stmt_dice->execute(['user_id' => $_SESSION['user_id']]);
        $row_dice = $stmt_dice->fetch(PDO::FETCH_ASSOC);

        // If no existing data, initialize as an empty array
        $current_dice_data = [$_POST['dice']];
        if ($row_dice && $row_dice['dice_data']) {
            // Decode the existing dice data if available
            $current_dice_data = json_decode($row_dice['dice_data'], true);

            // Ensure that the existing dice data is properly decoded
            if (json_last_error() !== JSON_ERROR_NONE) {
                $current_dice_data = [];
            }
        }

        // Combine the new dice data with the existing data by appending each new dice individually
        foreach ($new_dice_data as $new_dice) {
            $current_dice_data[] = $new_dice;
        }

        // Encode the updated dice data to JSON format
        $updated_dice_data_json = json_encode($current_dice_data);

        // Ensure that the updated dice data is properly encoded
        if ($updated_dice_data_json === false) {
            throw new Exception("Failed to encode JSON data");
        }

        // Update the dice data in the database
        $stmt_update_dice = $pdo->prepare("UPDATE joueurs SET dice_data = :dice_data WHERE `ID` = :user_id");
        $stmt_update_dice->execute(['dice_data' => $updated_dice_data_json, 'user_id' => $_SESSION['user_id']]);

        // Count max_player
        $stmt_max_player = $pdo->prepare("SELECT * FROM games WHERE `creator_id` = :game_id");
        $stmt_max_player->execute(['game_id' => $_POST['game_id']]);
        $row_max_player = $stmt_max_player->fetch();
        $max_player = $row_max_player['max_player'];
        $turn = $row_max_player['turn'];

        // Count dice_data
        $stmt_dice_count = $pdo->prepare("SELECT COUNT(*) FROM joueurs WHERE `game_joined` = :game_id AND `dice_data` <> '' AND `dice_data` IS NOT NULL");
        $stmt_dice_count->execute(['game_id' => $_POST['game_id']]);
        $row_dice_count = $stmt_dice_count->fetchColumn();

        if($turn == 1 && $row_dice_count == $max_player) {
            // Récupérer les données dice_data pour l'équipe A
            $stmt_team_a = $pdo->prepare("SELECT * FROM joueurs WHERE team = 'A' AND dice_data IS NOT NULL AND dice_data <> '' AND `game_joined` = :game_id");
            $stmt_team_a->execute(['game_id' => $_POST['game_id']]);
            $team_a_data = $stmt_team_a->fetchAll(PDO::FETCH_ASSOC);

            // Récupérer les données dice_data pour l'équipe B
            $stmt_team_b = $pdo->prepare("SELECT * FROM joueurs WHERE team = 'B' AND dice_data IS NOT NULL AND dice_data <> '' AND `game_joined` = :game_id");
            $stmt_team_b->execute(['game_id' => $_POST['game_id']]);
            $team_b_data = $stmt_team_b->fetchAll(PDO::FETCH_ASSOC);

            // Fonction pour calculer la somme des valeurs dice_data pour une équipe
            function calculateDiceSum($team_data) {
                $total_sum = 0;
                foreach ($team_data as $player_data) {
                    $dice_data = json_decode($player_data['dice_data'], true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $total_sum += array_sum($dice_data);
                    }
                }
                return $total_sum;
            }

            // Calculer les sommes pour chaque équipe
            $team_a_sum = calculateDiceSum($team_a_data);
            $team_b_sum = calculateDiceSum($team_b_data);

            // Fonction pour alterner les joueurs entre les deux équipes
            function alternatePlayers($team_a, $team_b) {
                $alternated_players = [];
                $max_length = max(count($team_a), count($team_b));

                for ($i = 0; $i < $max_length; $i++) {
                    if ($i < count($team_a)) {
                        $alternated_players[] = $team_a[$i]['ID'];
                    }
                    if ($i < count($team_b)) {
                        $alternated_players[] = $team_b[$i]['ID'];
                    }
                }

                return $alternated_players;
            }

            $alternated_players = [];
            if ($team_a_sum > $team_b_sum) {
                // Alterner les joueurs en commençant par l'équipe A
                $alternated_players = alternatePlayers($team_a_data, $team_b_data);
            } elseif($team_a_sum < $team_b_sum) {
                // Alterner les joueurs en commençant par l'équipe b
                $alternated_players = alternatePlayers($team_b_data, $team_a_data);
            } else {
                // Alterner les joueurs en commençant par l'équipe b
                $rand = rand(1,2);
                if($rand == 1){
                    $alternated_players = alternatePlayers($team_a_data, $team_b_data);
                } else {
                    $alternated_players = alternatePlayers($team_b_data, $team_a_data);
                }
            }
            $alternated_players = json_encode($alternated_players);
            // Update the turn data in the database
            $stmt_update_turn_data = $pdo->prepare("UPDATE games SET turn_data = :turn_data WHERE `creator_id` = :game_id");
            $stmt_update_turn_data->execute(['turn_data' => $alternated_players, 'game_id' => $_POST['game_id']]);

            // Update the turn data in the database
            $stmt_update_turn = $pdo->prepare("UPDATE games SET turn = :new_turn WHERE `creator_id` = :game_id");
            $stmt_update_turn->execute(['new_turn' => $turn+1, 'game_id' => $_POST['game_id']]);

            echo json_encode(['success' => true, 'dice' => $current_dice_data, 'dice_count' => $row_dice_count, 'max_player' => $max_player,'team_a_sum' => $team_a_sum,'team_b_sum' => $team_b_sum, 'alternated_player' => $alternated_players]);
        } else {
            // Respond with success and the updated dice data
            echo json_encode(['success' => true, 'dice' => $current_dice_data, 'dice_count' => $row_dice_count, 'max_player' => $max_player]);
        }
        
    } catch (Exception $e) {
        // Respond with an error if there was an exception
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
