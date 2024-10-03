<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_racket_eligibility') {
    try{

        $game_id = intval($_POST['game_id']);
        $attacker_id = intval($_SESSION['user_id']);

        $item_requested = $_POST['item_requested'];
        $target_id = intval($_POST['target_id']);

        // Vérifier si le joueur est deja en combat
        $stmt_attacker_on_fight = $pdo->prepare("SELECT * FROM fights WHERE (attacker_id = :attacker_id OR defender_id = :defender_id) AND status = :status");
        $stmt_attacker_on_fight->execute(['attacker_id' => $attacker_id, 'defender_id' => $attacker_id, 'status' => 'procedeed']);
        $row_attacker_on_fight = $stmt_attacker_on_fight->fetchAll(PDO::FETCH_ASSOC);
        if(count($row_attacker_on_fight) == 0){
            // Récupérer le deck de l'attaquant
            $stmt_attacker = $pdo->prepare("SELECT nb_action, deck FROM joueurs WHERE ID = :attacker_id");
            $stmt_attacker->execute(['attacker_id' => $attacker_id]);
            $attacker_data = $stmt_attacker->fetch(PDO::FETCH_ASSOC);
            $attacker_deck = json_decode($attacker_data['deck'], true);
            $attacker_nb_action = $attacker_data['nb_action'];

            // Récupérer le deck du joueur cible
            $stmt_target = $pdo->prepare("SELECT raclee, deck FROM joueurs WHERE ID = :target_id");
            $stmt_target->execute(['target_id' => $target_id]);
            $target_data = $stmt_target->fetch(PDO::FETCH_ASSOC);
            $target_deck = json_decode($target_data['deck'], true);

            // Vérifier si l'attaquant a une carte "Surin" ou "Lame"
            $can_attack = false;

            if($attacker_nb_action > 0){
                foreach ($attacker_deck as $index => $card) {
                    if ($card['name'] === 'Surin' || $card['name'] === 'Lame') {
                        $can_attack = true;
                        $weapons_used[] = $card;
                        $weapon_index = $index; // Enregistrer l'index de l'arme utilisée
                        break;
                    }
                }

                // Vérifier si le joueur cible a une carte "Surin" ou "Lame" (peut se défendre)
                $can_defend = false;
                foreach ($target_deck as $card) {
                    if ($card['name'] === 'Surin' || $card['name'] === 'Lame') {
                        $can_defend = true;
                        break;
                    }
                }

                // Récupérer l'objet demandé
                $items_available = [];
                $stmt_item_requested = $pdo->prepare("SELECT * FROM games");
                $stmt_item_requested->execute();
                $row_item_requested = $stmt_item_requested->fetch(PDO::FETCH_ASSOC);

                if($item_requested == "Pelle") {
                    $item_deck = json_decode($row_item_requested['pelle_data'], true);
                } else if($item_requested == "Pioche") {
                    $item_deck = json_decode($row_item_requested['pioche_data'], true);
                } else if($item_requested == "Cuillère") {
                    $item_deck = json_decode($row_item_requested['cuillere_data'], true);
                }
                foreach ($item_deck as $card) {
                    if (in_array($card['name'], [$item_requested])) {
                        $items_available[] = $card;
                        break;
                    }
                }

                //Verifie si le joueur a l'objet demandé
                $have_item = "false";
                foreach ($target_deck as $card) {
                    if (in_array($card['name'], [$item_requested])) {
                        $have_item = "true";
                        break;
                    }
                }

                if ($can_attack) {

                    // Enlever l'arme utilisée du deck de l'attaquant
                    if ($weapons_used[0]['name'] === 'Surin') {
                        // Récupérer les données surin_data actuelles
                        $stmt_surin_data = $pdo->prepare("SELECT surin_data FROM games WHERE creator_id = :game_id");
                        $stmt_surin_data->execute(['game_id' => $game_id]);
                        $surin_data = json_decode($stmt_surin_data->fetch(PDO::FETCH_ASSOC)['surin_data'], true);

                        // Ajouter la carte "Surin" dans surin_data
                        array_unshift($surin_data, $weapons_used[0]);
                        $surin_data_json = json_encode($surin_data);

                        // Mettre à jour surin_data dans la table games
                        $stmt_update_surin = $pdo->prepare("UPDATE games SET surin_data = :surin_data WHERE creator_id = :game_id");
                        $stmt_update_surin->execute(['surin_data' => $surin_data_json, 'game_id' => $game_id]);
                    } else if ($weapons_used[0]['name'] === 'Lame') {
                        // Ajouter la carte "Lame" à la défausse
                        $stmt_defausse_data = $pdo->prepare("SELECT defausse_data FROM games WHERE creator_id = :game_id");
                        $stmt_defausse_data->execute(['game_id' => $game_id]);
                        $defausse_data = json_decode($stmt_defausse_data->fetch(PDO::FETCH_ASSOC)['defausse_data'], true);

                        // Ajouter l'arme "Lame" dans la défausse
                        array_unshift($defausse_data, $weapons_used[0]);
                        $defausse_json = json_encode($defausse_data);

                        // Mettre à jour la défausse dans la table games
                        $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = :defausse_data WHERE creator_id = :game_id");
                        $stmt_update_defausse->execute(['defausse_data' => $defausse_json, 'game_id' => $game_id]);
                    }

                    // Supprimer l'arme du deck de l'attaquant
                    unset($attacker_deck[$weapon_index]);
                    // Réindexer le tableau pour éviter les clés manquantes
                    $attacker_deck = array_values($attacker_deck);

                    // Mettre à jour le deck de l'attaquant dans la base de données
                    $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :attacker_id");
                    $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'attacker_id' => $attacker_id]);

                    echo json_encode(['success' => true, 'attacker_deck' => $attacker_deck]);
                    
                    if($can_defend) {
                        // Mettre à jour le fight dans la BDD
                        // Réduire le nombre d'actions
                        $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                        $stmt_update_nb_action->execute(['user_id' => $attacker_id]);
                        
                        $stmt_fight = $pdo->prepare("INSERT INTO fights (game_id, item_ask, weapons_used, attacker_id, defender_id, status, fight_id_turn, have_item) VALUES (:game_id, :item_ask, :weapons_used, :attacker_id, :defender_id, :status, :fight_id_turn, :have_item)");
                        $stmt_fight->execute([
                            'game_id' => $game_id,
                            'item_ask' => json_encode($items_available),
                            'weapons_used' => json_encode($weapons_used),
                            'attacker_id' => $attacker_id,
                            'defender_id' => $target_id,
                            'status' => 'procedeed',
                            'fight_id_turn' => $target_id,
                            'have_item' => $have_item
                        ]);
                        echo json_encode(['success' => true, 'message' => 'Vous pouvez initier un racket.', 'canRacket' => true , 'can_defend' => $can_defend, 'items' => $items_available, 'have_item' => $have_item]);
                    } else {

                        // Réduire le nombre d'actions
                        // $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                        // $stmt_update_nb_action->execute(['user_id' => $attacker_id]);

                        // Mettre à jour le fight dans la BDD
                        $stmt_fight = $pdo->prepare("INSERT INTO fights (game_id, item_ask, weapons_used, attacker_id, defender_id, status, fight_id_turn, have_item, winner_id) VALUES (:game_id, :item_ask, :weapons_used, :attacker_id, :defender_id, :status, :fight_id_turn, :have_item, :winner_id)");
                        $stmt_fight->execute([
                            'game_id' => $game_id,
                            'item_ask' => json_encode($items_available),
                            'weapons_used' => json_encode($weapons_used),
                            'attacker_id' => $attacker_id,
                            'defender_id' => $target_id,
                            'status' => 'finished',
                            'fight_id_turn' => $target_id,
                            'have_item' => $have_item,
                            'winner_id' => $attacker_id,
                        ]);

                        // Verifier si le joueur a la carte demandé
                        $stmt_target_deck = $pdo->prepare("SELECT raclee, deck FROM joueurs WHERE ID = :target_id");
                        $stmt_target_deck->execute(['target_id' => $target_id]);
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
                                $stmt_update_target->execute(['deck' => json_encode($target_deck), 'id' => $target_id]);
                        
                                $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                                $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'id' => $attacker_id]);

                                if($target_data['raclee'] < 2){
                                    // Mettre à jour le deck du joueur
                                    $stmt_update_heal= $pdo->prepare("UPDATE joueurs SET raclee = raclee +1 WHERE `ID` = :target_id");
                                    $stmt_update_heal->execute(['target_id' => $target_id]);
                                }

                                $message = "à volé un(e) ".$requested_item['name'];

                                $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                                $stmt_logs->execute([
                                    'game_id' => $game_id,
                                    'user_id' => $attacker_id,
                                    'message' => $message,
                                    'target_id' => $target_id
                                ]);
                        
                                echo json_encode(['success' => true, 'message' => "L'objet " . $requested_item['name'] . " a été transféré avec succès."]);
                            } else {
                                echo json_encode(['success' => false, 'message' => "Erreur lors du transfert de l'objet."]);
                            }
                        } else {
                            // Vérifier si le joueur cible a au moins une carte dans son deck
                            if (count($target_deck) > 0) {
                                // Sélectionner une carte aléatoire du deck du joueur cible
                                $random_index = array_rand($target_deck);
                                $stolen_item = $target_deck[$random_index];
                        
                                // Ajouter la carte volée au deck de l'attaquant
                                $attacker_deck[] = $stolen_item;
                        
                                // Supprimer la carte du deck du joueur cible
                                unset($target_deck[$random_index]);
                                // Réindexer le tableau pour éviter les clés manquantes
                                $target_deck = array_values($target_deck);
                        
                                // Mettre à jour les decks dans la base de données
                                $stmt_update_target = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                                $stmt_update_target->execute(['deck' => json_encode($target_deck), 'id' => $target_id]);
                        
                                $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                                $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'id' => $attacker_id]);
                        
                                // Mise à jour de la "raclée" si elle est inférieure à 2
                                if ($target_data['raclee'] < 2) {
                                    $stmt_update_heal = $pdo->prepare("UPDATE joueurs SET raclee = raclee + 1 WHERE ID = :target_id");
                                    $stmt_update_heal->execute(['target_id' => $target_id]);
                                }
                        
                                // Ajouter un message dans les logs
                                $message = "a volé une carte";
                                $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                                $stmt_logs->execute([
                                    'game_id' => $game_id,
                                    'user_id' => $attacker_id,
                                    'message' => $message,
                                    'target_id' => $target_id
                                ]);
                        
                                echo json_encode(['success' => true, 'message' => "Une carte a été volé avec succès."]);
                            } else {
                                // Mise à jour de la "raclée" si elle est inférieure à 2
                                if ($target_data['raclee'] < 2) {
                                    $stmt_update_heal = $pdo->prepare("UPDATE joueurs SET raclee = raclee + 1 WHERE ID = :target_id");
                                    $stmt_update_heal->execute(['target_id' => $target_id]);
                                }
                        
                                // Ajouter un message dans les logs
                                $message = "a mis une raclée";
                                $stmt_logs = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
                                $stmt_logs->execute([
                                    'game_id' => $game_id,
                                    'user_id' => $attacker_id,
                                    'message' => $message,
                                    'target_id' => $target_id
                                ]);
                                echo json_encode(['success' => false, 'message' => "Le joueur cible n'a pas de carte à voler."]);
                            }
                        }

                        echo json_encode(['success' => true, 'message' => 'Vous pouvez initier un racket.', 'canRacket' => true , 'can_defend' => $can_defend, 'items' => $items_available, 'have_item' => $have_item]);
                    }
                } else {
                    echo json_encode(['success' => false , 'message' => "Vous ne pouvez pas racketter, vous n'avez pas de surin ou de lame."]);
                }
            } else {
                echo json_encode(['success' => false , 'message' => "Vous n'avez plus d'action."]);
            }
        } else {
            echo json_encode(['success' => false , 'message' => "Vous etes deja en combat."]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
