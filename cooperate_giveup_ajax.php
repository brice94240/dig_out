<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'cooperate' || $_POST['action'] === 'giveup')) {
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

                    if($target_data['raclee'] > 0){
                        // Mettre à jour le deck du joueur
                        $stmt_update_heal= $pdo->prepare("UPDATE joueurs SET raclee = raclee -1 WHERE `ID` = :defender_id");
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


        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération du combat : " . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
