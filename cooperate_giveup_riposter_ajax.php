<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'cooperate' || $_POST['action'] === 'giveup' || $_POST['action'] === 'riposter')) {
    try {
        $button = $_POST['action'];
        $game_id = intval($_POST['game_id']);
        $user_id = intval($_POST['user_id']);
        $item_requested = $_POST['item_requested'];


        //Recuperer le combat en question
        $stmt_fight = $pdo->prepare("SELECT * FROM fights WHERE defender_id = :user_id AND status = :status");
        $stmt_fight->execute(['user_id' => $user_id, 'status' => "procedeed"]);
        $row_fight = $stmt_fight->fetch(PDO::FETCH_ASSOC);
        $item_ask = $row_fight['item_ask'];
        $attacker_id = $row_fight['attacker_id'];
        $defender_id = $row_fight['defender_id'];
        $item_requested_json = json_decode($item_ask, true);
        $item_requested = $item_requested_json[0]['name'];

        if ($button === 'cooperate') {
            // Verifier si le joueur a la carte demandé
            $stmt_target_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :defender_id");
            $stmt_target_deck->execute(['defender_id' => $defender_id]);
            $target_data = $stmt_target_deck->fetch(PDO::FETCH_ASSOC);
            $target_deck = json_decode($target_data['deck'], true);

            $item_found = false;
            foreach ($target_deck as $index => $card) {
                if ($card['name'] === $item_requested) {
                    $item_found = true;
                    $requested_item = $card;
                    $index_find = $index;
                    break;
                }
            }

            if ($item_found) {
                // Ajouter l'objet au deck de l'attaquant
                $stmt_attacker_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :attacker_id");
                $stmt_attacker_deck->execute(['attacker_id' => $attacker_id]);
                $attacker_data = $stmt_attacker_deck->fetch(PDO::FETCH_ASSOC);
                $attacker_deck = json_decode($attacker_data['deck'], true);
            
                // Supprimer l'objet du deck du défenseur
                $item_transferred = false;
                foreach ($target_deck as $index => $card) {
                    if ($card['name'] === $requested_item['name']) {
                        // Ajouter l'objet au deck de l'attaquant
                        $attacker_deck[] = $card;
            
                        // Supprimer l'objet du deck du défenseur
                        unset($target_deck[$index_find]);
                        // Réindexer le tableau pour éviter les clés manquantes
                        $target_deck = array_values($target_deck);
            
                        $item_transferred = true;
                        break;
                    }
                }

                if ($item_transferred) {
                    // Mettre à jour les decks dans la base de données
                    $stmt_update_target = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                    $stmt_update_target->execute(['deck' => json_encode($target_deck), 'id' => $defender_id]);
            
                    $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                    $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'id' => $attacker_id]);
        
                    // Mettre à jour le fights
                    $stmt_update_fights= $pdo->prepare("UPDATE fights SET status = :status, winner_id = :winner_id, cooperate = :cooperate WHERE defender_id = :defender_id AND status = :last_status");
                    $stmt_update_fights->execute(['status' => 'finished', 'winner_id' => $attacker_id, 'cooperate' => 1, 'defender_id' => $defender_id, 'last_status' => "procedeed"]);
                    
                    $message = "à volé un(e) ".$requested_item['name'];

                    $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                    $stmt_logs->execute([
                        'game_id' => $game_id,
                        'user_id' => $attacker_id,
                        'message' => $message,
                        'target_id' => $defender_id
                    ]);

                    echo json_encode(['success' => true, 'message' => "L'objet " . $requested_item['name'] . " a été transféré avec succès."]);
                } else {
                    echo json_encode(['success' => false, 'message' => "Erreur lors du transfert de l'objet."]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => "L'objet demandé n'est pas disponible."]);
            }


        } elseif ($button === 'giveup') {
            // Verifier si le joueur a la carte demandé
            $stmt_target_deck = $pdo->prepare("SELECT raclee, deck FROM joueurs WHERE ID = :defender_id");
            $stmt_target_deck->execute(['defender_id' => $defender_id]);
            $target_data = $stmt_target_deck->fetch(PDO::FETCH_ASSOC);
            $target_deck = json_decode($target_data['deck'], true);

            $item_found = false;
            foreach ($target_deck as $index => $card) {
                if ($card['name'] === $item_requested) {
                    $item_found = true;
                    $requested_item = $card;
                    $index_find = $index;
                    break;
                }
            }

            if ($item_found) {
                // Ajouter l'objet au deck de l'attaquant
                $stmt_attacker_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :attacker_id");
                $stmt_attacker_deck->execute(['attacker_id' => $attacker_id]);
                $attacker_data = $stmt_attacker_deck->fetch(PDO::FETCH_ASSOC);
                $attacker_deck = json_decode($attacker_data['deck'], true);
            
                // Supprimer l'objet du deck du défenseur
                $item_transferred = false;
                foreach ($target_deck as $index => $card) {
                    if ($card['name'] === $requested_item['name']) {
                        // Ajouter l'objet au deck de l'attaquant
                        $attacker_deck[] = $card;
            
                        // Supprimer l'objet du deck du défenseur
                        unset($target_deck[$index_find]);
                        // Réindexer le tableau pour éviter les clés manquantes
                        $target_deck = array_values($target_deck);
            
                        $item_transferred = true;
                        break;
                    }
                }

                if ($item_transferred) {
                    // Mettre à jour les decks dans la base de données
                    $stmt_update_target = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                    $stmt_update_target->execute(['deck' => json_encode($target_deck), 'id' => $defender_id]);
            
                    $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                    $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'id' => $attacker_id]);

                    if($target_data['raclee'] < 2){
                        // Mettre à jour le deck du joueur
                        $stmt_update_heal= $pdo->prepare("UPDATE joueurs SET raclee = raclee +1 WHERE `ID` = :defender_id");
                        $stmt_update_heal->execute(['defender_id' => $defender_id]);
                    }

                    $message = "à volé un(e) ".$requested_item['name'];

                    $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                    $stmt_logs->execute([
                        'game_id' => $game_id,
                        'user_id' => $attacker_id,
                        'message' => $message,
                        'target_id' => $defender_id
                    ]);
            
                    echo json_encode(['success' => true, 'message' => "L'objet " . $requested_item['name'] . " a été transféré avec succès."]);
                } else {
                    echo json_encode(['success' => false, 'message' => "Erreur lors du transfert de l'objet."]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => "L'objet demandé n'est pas disponible."]);
            }
        } elseif ($button === 'riposter') {
            // Récupérer le deck de l'attaquant
            $stmt_user = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :user_id");
            $stmt_user->execute(['user_id' => $user_id]);
            $user_data = $stmt_user->fetch(PDO::FETCH_ASSOC);
            $user_deck = json_decode($user_data['deck'], true);
            
            // Vérifier si le joueur a une arme
            $weapon_found = false;
            $weapon_index = null;
            
            foreach ($user_deck as $index => $card) {
                if ($card['name'] === 'Surin' || $card['name'] === 'Lame') {
                    $weapon_found = true;
                    $weapon_index = $index;
                    // Ajouter l'arme à weapons_used
                    $fights_data['weapons_used'][] = $card; // Assurez-vous que $fights_data est correctement défini
                    break;
                }
            }
            
            if ($weapon_found) {
                // Récupérer les infos du fight avec le statut 'processed' et le game_id
                $stmt_fight = $pdo->prepare("SELECT * FROM fights WHERE status = 'procedeed' AND game_id = :game_id");
                $stmt_fight->execute(['game_id' => $game_id]);
                $fight_data = $stmt_fight->fetch(PDO::FETCH_ASSOC);
        
                // Enlever l'arme du deck de l'attaquant
                $weapon_used = $user_deck[$weapon_index];
                unset($user_deck[$weapon_index]);
                // Réindexer le tableau
                $user_deck = array_values($user_deck);
        
                // Incrémenter le tour de fight
                if($user_id == $fight_data['defender_id']){
                    $figt_id_turn = $fight_data['attacker_id'];
                } else {
                    $figt_id_turn = $fight_data['defender_id'];
                }
                
                // Mettre à jour le tour et le joueur actif dans la base de données
                $stmt_update_fight = $pdo->prepare("UPDATE fights SET turn = turn +1, fight_id_turn = :fight_id_turn WHERE game_id = :game_id");
                $stmt_update_fight->execute([
                    'fight_id_turn' => $figt_id_turn,
                    'game_id' => $game_id
                ]);
        
                // Gérer l'ajout de l'arme utilisée dans la bonne collection
                if ($weapon_used['name'] === 'Surin') {
                    // Récupérer les données surin_data actuelles
                    $stmt_surin_data = $pdo->prepare("SELECT surin_data FROM games WHERE creator_id = :game_id");
                    $stmt_surin_data->execute(['game_id' => $game_id]);
                    $surin_data = json_decode($stmt_surin_data->fetch(PDO::FETCH_ASSOC)['surin_data'], true);

                    // Ajouter l'arme "Surin" au début de surin_data
                    array_unshift($surin_data, $weapon_used);
                    $surin_data_json = json_encode($surin_data);

                    // Mettre à jour surin_data dans la table games
                    $stmt_update_surin = $pdo->prepare("UPDATE games SET surin_data = :surin_data WHERE creator_id = :game_id");
                    $stmt_update_surin->execute(['surin_data' => $surin_data_json, 'game_id' => $game_id]);
                } else if ($weapon_used['name'] === 'Lame') {
                    // Ajouter la carte "Lame" à la défausse
                    $stmt_defausse_data = $pdo->prepare("SELECT defausse_data FROM games WHERE creator_id = :game_id");
                    $stmt_defausse_data->execute(['game_id' => $game_id]);
                    $defausse_data = json_decode($stmt_defausse_data->fetch(PDO::FETCH_ASSOC)['defausse_data'], true);

                    // Ajouter l'arme "Lame" au début de la défausse
                    array_unshift($defausse_data, $weapon_used);
                    $defausse_json = json_encode($defausse_data);

                    // Mettre à jour la défausse dans la table games
                    $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = :defausse_data WHERE creator_id = :game_id");
                    $stmt_update_defausse->execute(['defausse_data' => $defausse_json, 'game_id' => $game_id]);
                }

                // Mettre à jour le deck de l'attaquant dans la base de données
                $stmt_update_user = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :user_id");
                $stmt_update_user->execute(['deck' => json_encode($user_deck), 'user_id' => $user_id]);
        
                $stmt_update_weapons = $pdo->prepare("UPDATE fights SET weapons_used = :weapons_used WHERE game_id = :game_id");
                $stmt_update_weapons->execute([
                    'weapons_used' => json_encode($fights_data['weapons_used']),
                    'game_id' => $game_id
                ]);
        
                echo json_encode(['success' => true, 'message' => "Vous avez riposté avec succès avec l'arme " . $card['name'] . "."]);
            } else {
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas d'arme pour riposter."]);
            }
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération du combat : " . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
