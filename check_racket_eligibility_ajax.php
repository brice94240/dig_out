<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'check_racket_eligibility') {
    $game_id = intval($_POST['game_id']);
    $attacker_id = intval($_SESSION['user_id']);
    $target_id = intval($_POST['target_id']);

    try {
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

            // Vérifier si le joueur cible a des objets racketables
            $items_available = [];
            foreach ($target_deck as $card) {
                if (in_array($card['name'], ['Pelle', 'Pioche', 'Cuillère'])) {
                    $items_available[] = $card;
                }
            }

            if ($can_attack) {
                // Mettre à jour le fight dans la BDD
                $stmt_fight = $pdo->prepare("INSERT INTO fights (game_id, item, attacker_id, defender_id, status, fight_turn) VALUES (:game_id, :item, :attacker_id, :defender_id, :status, :fight_turn)");
                $stmt_fight->execute([
                    'game_id' => $game_id,
                    'item' => json_encode($items_available),
                    'attacker_id' => $attacker_id,
                    'defender_id' => $target_id,
                    'status' => 'procedeed',
                    'fight_id_turn' => $target_id,
                ]);

                echo json_encode(['success' => true, 'message' => 'Vous pouvez initier un racket.', 'canRacket' => true , 'can_defend' => $can_defend, 'items' => $items_available]);
            } else {
                echo json_encode(['success' => false , 'message' => "Vous ne pouvez pas racketter, vous n'avez pas de surin ou de lame."]);
            }
        } else {
            echo json_encode(['success' => false , 'message' => "Vous etes deja en combat."]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
