<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_turn') {
    try {
        $stmt = $pdo->query("SELECT * FROM games INNER JOIN joueurs ON joueurs.ID = games.creator_id");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if(intval($row['turn']) !== intval($_POST['turn'])){
            // Update the dice data in the database
            $stmt_update_dice_data_to_empty = $pdo->prepare("UPDATE joueurs SET dice_data = :dice_data WHERE `game_joined` = :game_id");
            $stmt_update_dice_data_to_empty->execute(['dice_data' => '', 'game_id' => $_POST['game_id']]);

            // Update the point_turn in the database
            $stmt_update_point_turn_to_empty = $pdo->prepare("UPDATE joueurs SET point_turn = :point_turn WHERE `game_joined` = :game_id");
            $stmt_update_point_turn_to_empty->execute(['point_turn' => 0, 'game_id' => $_POST['game_id']]);

             // Update the action to all players
            //  $stmt_update_action_to_players = $pdo->prepare("UPDATE joueurs SET nb_action = :nb_action WHERE `game_joined` = :game_id");
            //  $stmt_update_action_to_players->execute(['nb_action' => 0, 'game_id' => $_POST['game_id']]);

            if($row['turn'] >= 2) {
                $real_turn = $row['turn']-2;
                if($real_turn >= $row['max_player']){
                    $real_turn = $real_turn % $row['max_player'];
                }
                $player_tab = json_decode($row['turn_data'], true);
                $player_turn_id = $player_tab[$real_turn];
            }

            $stmt_info_joueur = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
            $stmt_info_joueur->execute(['player_id' => $player_turn_id]);
            $row_info_joueur = $stmt_info_joueur->fetch(PDO::FETCH_ASSOC);

            $localisation = $row_info_joueur['localisation'];
            $nb_action = $row_info_joueur['nb_action'];
            $raclee = $row_info_joueur['raclee'];
            $game_id = $_POST['game_id'];
            $team = $row_info_joueur['team'];
            $point_turn = $row_info_joueur['point_turn'];
            $cigarette = $row_info_joueur['cigarette'];
            $id = $row_info_joueur['ID'];

            if($localisation !== 6){
                // Vérifier si le joueur est deja en combat
                $stmt_attacker_on_fight = $pdo->prepare("SELECT * FROM fights WHERE (attacker_id = :attacker_id OR defender_id = :defender_id) AND status = :status");
                $stmt_attacker_on_fight->execute(['attacker_id' => $id, 'defender_id' => $id, 'status' => 'procedeed']);
                $row_attacker_on_fight = $stmt_attacker_on_fight->fetchAll(PDO::FETCH_ASSOC);
                if(count($row_attacker_on_fight) == 0){
                    // Update the action to players playing
                    $stmt_update_action_to_player_playing = $pdo->prepare("UPDATE joueurs SET nb_action = :nb_action WHERE `game_joined` = :game_id AND ID = :player_id");
                    $stmt_update_action_to_player_playing->execute(['nb_action' => 2, 'game_id' => $_POST['game_id'], 'player_id' => $player_turn_id]);
                }
            } else {
                // Update the action to players playing
                $stmt_update_action_to_player_playing = $pdo->prepare("UPDATE joueurs SET nb_action = :nb_action WHERE `game_joined` = :game_id AND ID = :player_id");
                $stmt_update_action_to_player_playing->execute(['nb_action' => 1, 'game_id' => $_POST['game_id'], 'player_id' => $player_turn_id]);
            }

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

            if(($row_info_joueur['nb_action'] == 2) || ($row_info_joueur['localisation'] == 6 && $row_info_joueur['nb_action'] == 1)){
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
        $pelle_data = $row['pelle_data'];
        $pioche_data = $row['pioche_data'];
        $cuillere_data = $row['cuillere_data'];
        $gang_data = $row['gang_data'];
        $surin_data = $row['surin_data'];
        $cigarette = $row_player_data['cigarette'];
        $raclee = $row_player_data['raclee'];
        
        $stmt_logs_data = $pdo->prepare("SELECT *,logs.id AS log_id FROM logs INNER JOIN joueurs ON joueurs.ID = logs.user_id WHERE game_id = :game_id ORDER BY timestamp DESC LIMIT 5");
        $stmt_logs_data->execute(['game_id' => $_POST['game_id']]);
        $row_logs_data = $stmt_logs_data->fetchAll(PDO::FETCH_ASSOC);
        foreach($row_logs_data as $key => $value_log){
            if($value_log['target_id'] !== NULL){
                $stmt_player_logs_target_id = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
                $stmt_player_logs_target_id->execute(['player_id' => $value_log['target_id']]);
                $row_logs_pseudo_target_id = $stmt_player_logs_target_id->fetch(PDO::FETCH_ASSOC);
                $pseudo_target_id = $row_logs_pseudo_target_id['pseudo'];
                $value_log['message'] .= " sur " . $pseudo_target_id; // Ajouter le pseudonyme à chaque message
                //Je veux que $row_logs_data soit bien a la fin de ma boucle foreach
                // Mettre à jour le message dans le tableau original
                $row_logs_data[$key]['message'] .= " sur " . $pseudo_target_id . ".";
            }
        }
        $logs_data_reverse = array_reverse($row_logs_data);


        $stmt_points_data = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id");
        $stmt_points_data->execute(['game_id' => $_POST['game_id']]);
        $row_points_data = $stmt_points_data->fetchAll(PDO::FETCH_ASSOC);
        $team_a = 0;
        $team_b = 0;
        foreach($row_points_data as $value_points){
            if($value_points['team'] == "A"){
                $team_a += $value_points['nb_point'];
            } else if($value_points['team'] == "B") {
                $team_b += $value_points['nb_point'];
            }
        }

        $stmt_verif_card_on_fouille = $pdo->prepare("SELECT * FROM games WHERE creator_id = :game_id");
        $stmt_verif_card_on_fouille->execute(['game_id' => $_POST['game_id']]);
        $row_verif_card_on_fouille = $stmt_verif_card_on_fouille->fetch(PDO::FETCH_ASSOC);
        $fouilles = json_decode($row_verif_card_on_fouille['fouille_data']);
        $count_fouilles = count($fouilles);

        if($count_fouilles <= 5){
            $new_fouille = "test";
            $stmt_recup_defausse = $pdo->prepare("SELECT * FROM games WHERE creator_id = :game_id");
            $stmt_recup_defausse->execute(['game_id' => $_POST['game_id']]);
            $row_recup_defausse = $stmt_recup_defausse->fetch(PDO::FETCH_ASSOC);
            $defausse = json_decode($row_verif_card_on_fouille['defausse_data']);
            
            shuffle($defausse);
            $new_fouille = $defausse;
            foreach($fouilles as $value){
                $new_fouille[] = $value;
            }
            $new_fouille = json_encode($new_fouille);

            $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = '' WHERE creator_id = :game_id");
            $stmt_update_defausse->execute(['game_id' => $_POST['game_id']]);

            $stmt_update_fouille = $pdo->prepare("UPDATE games SET fouille_data = :new_fouille WHERE creator_id = :game_id");
            $stmt_update_fouille->execute(['new_fouille' => $new_fouille, 'game_id' => $_POST['game_id']]);           

        }

        // Vérifier si le joueur est déjà en combat
        $stmt_attacker_on_fight = $pdo->prepare("SELECT * FROM fights WHERE (attacker_id = :attacker_id OR defender_id = :defender_id) AND status = :status");
        $stmt_attacker_on_fight->execute(['attacker_id' => $player_turn_id, 'defender_id' => $player_turn_id, 'status' => 'procedeed']);
        $row_attacker_on_fight = $stmt_attacker_on_fight->fetch(PDO::FETCH_ASSOC);

        if ($row_attacker_on_fight) {

            $attacker_id = $row_attacker_on_fight['attacker_id'];
            $defender_id = $row_attacker_on_fight['defender_id'];
            $on_fight = true;
            $fight_id_turn = $row_attacker_on_fight['fight_id_turn'];

            $stmt_player_turn_details = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :fight_id_turn");
            $stmt_player_turn_details->execute(['fight_id_turn' => $fight_id_turn]);
            $row_player_turn_details = $stmt_player_turn_details->fetch(PDO::FETCH_ASSOC);

            $stmt_attacker_details = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :attacker_id");
            $stmt_attacker_details->execute(['attacker_id' => $attacker_id]);
            $row_attacker_details = $stmt_attacker_details->fetch(PDO::FETCH_ASSOC);

            $stmt_defender_details = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :defender_id");
            $stmt_defender_details->execute(['defender_id' => $defender_id]);
            $row_defender_details = $stmt_defender_details->fetch(PDO::FETCH_ASSOC);

            $attacker_deck = $row_attacker_details['deck'];
            $defender_deck = $row_defender_details['deck'];
            $attacker_weapon = $row_player_turn_details['pseudo'];
            $defender_weapon = $row_attacker_on_fight['defender_weapon'];
            $attacker_id = $row_attacker_on_fight['attacker_id'];
            $defender_id = $row_attacker_on_fight['defender_id'];
            $player_turn_name = $row_player_turn_details['pseudo'];
            $weapons_used = $row_attacker_on_fight['weapons_used'];
            $turn = $row_attacker_on_fight['turn'];
            $have_item = $row_attacker_on_fight['have_item'];
            $item_ask = $row_attacker_on_fight['item_ask'];
            $item_requested_json = json_decode($item_ask, true);
            $item_requested = $item_requested_json[0]['name'];

            if ($fight_id_turn == $_SESSION['user_id']) {
                // Récupérer le deck de l'utilisateur actuel
                $user_id = $_SESSION['user_id'];
                $stmt_user_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :user_id");
                $stmt_user_deck->execute(['user_id' => $user_id]);
                $user_data = $stmt_user_deck->fetch(PDO::FETCH_ASSOC);
                $user_deck = json_decode($user_data['deck'], true);


                // Vérifier si le deck contient une arme (Lame ou Surin)
                $has_weapon = false;
                foreach ($user_deck as $card) {
                    if ($card['name'] === 'Lame' || $card['name'] === 'Surin') {
                        $has_weapon = true;
                        break;
                    }
                }
                if (!$has_weapon) {
                    if ($attacker_id == $_SESSION['user_id']) {
                        // Si l'attaquant est l'utilisateur actuel
                        $stmt_attacker_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :attacker_id");
                        $stmt_attacker_deck->execute(['attacker_id' => $attacker_id]);
                        $attacker_data = $stmt_attacker_deck->fetch(PDO::FETCH_ASSOC);
                        $attacker_deck = json_decode($attacker_data['deck'], true);
                
                        // Vérifier si l'attaquant a au moins une carte
                        if (count($attacker_deck) > 0) {
                            // Sélectionner une carte aléatoire dans le deck de l'attaquant
                            $random_index = array_rand($attacker_deck);
                            $random_card = $attacker_deck[$random_index];
                
                            // Ajouter la carte au deck du défenseur
                            $stmt_defender_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :defender_id");
                            $stmt_defender_deck->execute(['defender_id' => $defender_id]);
                            $defender_data = $stmt_defender_deck->fetch(PDO::FETCH_ASSOC);
                            $defender_deck = json_decode($defender_data['deck'], true);
                
                            $defender_deck[] = $random_card;
                
                            // Supprimer la carte du deck de l'attaquant
                            unset($attacker_deck[$random_index]);
                            $attacker_deck = array_values($attacker_deck);
                
                            // Mettre à jour les decks dans la base de données
                            $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                            $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'id' => $attacker_id]);
                
                            $stmt_update_defender = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                            $stmt_update_defender->execute(['deck' => json_encode($defender_deck), 'id' => $defender_id]);
                
                            // Log de l'action
                            $message = "a donné une carte";
                
                            $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                            $stmt_logs->execute([
                                'game_id' => $game_id,
                                'user_id' => $attacker_id,
                                'message' => $message,
                                'target_id' => $defender_id
                            ]);
                        } else {
                            // Log de l'action
                            $message = "a donné une raclée";
                
                            $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                            $stmt_logs->execute([
                                'game_id' => $game_id,
                                'user_id' => $defender_id,
                                'message' => $message,
                                'target_id' => $attacker_id
                            ]);
                        }
                    } else if ($defender_id == $_SESSION['user_id']) {
                        // Si le défenseur est l'utilisateur actuel
                        $stmt_defender_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :defender_id");
                        $stmt_defender_deck->execute(['defender_id' => $defender_id]);
                        $defender_data = $stmt_defender_deck->fetch(PDO::FETCH_ASSOC);
                        $defender_deck = json_decode($defender_data['deck'], true);
                
                        $item_found = false;
                        foreach ($defender_deck as $index => $card) {
                            if ($card['name'] === $item_requested) {
                                $item_found = true;
                                $requested_item = $card;
                                $index_find = $index;
                                break;
                            }
                        }
                
                        if ($item_found) {
                            // Ajouter l'objet demandé au deck de l'attaquant
                            $stmt_attacker_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :attacker_id");
                            $stmt_attacker_deck->execute(['attacker_id' => $attacker_id]);
                            $attacker_data = $stmt_attacker_deck->fetch(PDO::FETCH_ASSOC);
                            $attacker_deck = json_decode($attacker_data['deck'], true);
                
                            $attacker_deck[] = $requested_item;
                
                            // Supprimer l'objet du deck du défenseur
                            unset($defender_deck[$index_find]);
                            $defender_deck = array_values($defender_deck);
                
                            // Mettre à jour les decks dans la base de données
                            $stmt_update_defender = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                            $stmt_update_defender->execute(['deck' => json_encode($defender_deck), 'id' => $defender_id]);
                
                            $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                            $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'id' => $attacker_id]);
                
                            // Log de l'action
                            $message = "a volé une " . $requested_item['name'];
                
                            $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                            $stmt_logs->execute([
                                'game_id' => $game_id,
                                'user_id' => $attacker_id,
                                'message' => $message,
                                'target_id' => $defender_id
                            ]);
                        } else {
                            // Si l'objet demandé n'est pas disponible mais que le défenseur a d'autres cartes
                            if (count($defender_deck) > 0) {
                                // Sélectionner une carte aléatoire dans le deck du défenseur
                                $random_index = array_rand($defender_deck);
                                $random_card = $defender_deck[$random_index];
                
                                // Ajouter la carte au deck de l'attaquant
                                $stmt_attacker_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :attacker_id");
                                $stmt_attacker_deck->execute(['attacker_id' => $attacker_id]);
                                $attacker_data = $stmt_attacker_deck->fetch(PDO::FETCH_ASSOC);
                                $attacker_deck = json_decode($attacker_data['deck'], true);
                
                                $attacker_deck[] = $random_card;
                
                                // Supprimer la carte du deck du défenseur
                                unset($defender_deck[$random_index]);
                                $defender_deck = array_values($defender_deck);
                
                                // Mettre à jour les decks dans la base de données
                                $stmt_update_defender = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                                $stmt_update_defender->execute(['deck' => json_encode($defender_deck), 'id' => $defender_id]);
                
                                $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                                $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'id' => $attacker_id]);
                
                                // Log de l'action
                                $message = "a donné une carte";
                
                                $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                                $stmt_logs->execute([
                                    'game_id' => $game_id,
                                    'user_id' => $attacker_id,
                                    'message' => $message,
                                    'target_id' => $defender_id
                                ]);
                            } else {
                                // Log de l'action
                                $message = "a donné une raclée";
                    
                                $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                                $stmt_logs->execute([
                                    'game_id' => $game_id,
                                    'user_id' => $attacker_id,
                                    'message' => $message,
                                    'target_id' => $defender_id
                                ]);
                            }
                        }
                    }
                    // Mettre à jour les combats
                    $stmt_update_fights = $pdo->prepare("UPDATE fights SET status = :status, winner_id = :winner_id, cooperate = :cooperate WHERE game_id = :game_id AND status = :last_status");
                    $stmt_update_fights->execute(['status' => 'finished', 'winner_id' => $attacker_id, 'cooperate' => 0, 'game_id' => $game_id, 'last_status' => "procedeed"]);
                    
                    // Incrémenter les raclées du joueur qui gagne
                    if ($user_data['raclee'] < 2) {
                        $stmt_update_heal = $pdo->prepare("UPDATE joueurs SET raclee = raclee + 1 WHERE ID = :user_id");
                        $stmt_update_heal->execute(['user_id' => $_SESSION['user_id']]);
                    }
                }
            }

        } else {
            $on_fight = false;
        }

        echo json_encode(['success' => true, "item_found" => $item_found, 'turn' => $turn, 'new_turn' => '1', 'last_turn' => $_POST['turn'], 'real_turn' => $real_turn, 'player_turn_id' => $player_turn_id, 'player_turn_name' => $player_turn_name, 'player_tab' => $player_tab, 'playerData' => $playerData, 'nb_action' => $row_player_data['nb_action'], 'player_id' => $row_player_data['ID'], 'localisation' => $row_player_data['localisation'], 'team' => $row_player_data['team'], 'defausse_data' => $row['defausse_data'], 'pelle_data' => $pelle_data, 'pioche_data' => $pioche_data, 'cuillere_data' => $cuillere_data, 'surin_data' => $surin_data, 'cigarette' => $cigarette, 'raclee' => $raclee, 'logs_data' => $logs_data_reverse, 'team_a' => $team_a, 'team_b' => $team_b, 'new_fouille' => $new_fouille, 'on_fight' => $on_fight, 'fight_id_turn' => $fight_id_turn, 'attacker_weapon' => $attacker_weapon, 'defender_weapon' => $defender_weapon, 'player_turn_name' => $player_turn_name, 'attacker_deck' => $attacker_deck, 'defender_deck' => $defender_deck, 'attacker_id' => $attacker_id, 'defender_id' => $defender_id, 'item_ask' => $item_ask, 'weapons_used' => $weapons_used, 'turn' => $turn, 'have_item' => $have_item]);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
