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
            $stmt_attacker = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :attacker_id");
            $stmt_attacker->execute(['attacker_id' => $attacker_id]);
            $attacker_data = $stmt_attacker->fetch(PDO::FETCH_ASSOC);
            $attacker_deck = json_decode($attacker_data['deck'], true);

            // Récupérer le deck du joueur cible
            $stmt_target = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :target_id");
            $stmt_target->execute(['target_id' => $target_id]);
            $target_data = $stmt_target->fetch(PDO::FETCH_ASSOC);
            $target_deck = json_decode($target_data['deck'], true);

            // Vérifier si l'attaquant a une carte "Surin" ou "Lame"
            $can_attack = false;
            foreach ($attacker_deck as $card) {
                if ($card['name'] === 'Surin' || $card['name'] === 'Lame') {
                    $can_attack = true;
                    $weapons_used[] = $card;
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
            } else if($item_requested == "Cuillere") {
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
                // Mettre à jour le fight dans la BDD
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

                //METTRE A JOUR LE DECK

                echo json_encode(['success' => true, 'message' => 'Vous pouvez initier un racket.', 'canRacket' => true , 'can_defend' => $can_defend, 'items' => $items_available, 'have_item' => $have_item]);
            } else {
                echo json_encode(['success' => false , 'message' => "Vous ne pouvez pas racketter, vous n'avez pas de surin ou de lame."]);
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
