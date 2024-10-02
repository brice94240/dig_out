<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && ($_POST['action'] === 'cooperate' || $_POST['action'] === 'giveup')) {
    try {
        $game_id = intval($_POST['game_id']);
        $attacker_id = intval($_SESSION['user_id']);
        $target_id = intval($_POST['target_id']);
        $item_requested = $_POST['item_requested'];
        $button = $_POST['action'];

        if ($button === 'cooperate') {
            // Logique pour coopérer
            // Ex: Transférer l'objet demandé de la cible à l'attaquant

            // Récupérer les cartes du joueur cible
            $stmt_target_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :target_id");
            $stmt_target_deck->execute(['target_id' => $target_id]);
            $target_data = $stmt_target_deck->fetch(PDO::FETCH_ASSOC);
            $target_deck = json_decode($target_data['deck'], true);

            // Vérifier si l'objet demandé existe
            $item_found = false;
            foreach ($target_deck as $index => $card) {
                if ($card['name'] === $item_requested) {
                    $item_found = true;
                    $requested_item = $card;
                    unset($target_deck[$index]);
                    $target_deck = array_values($target_deck);
                    break;
                }
            }

            if ($item_found) {
                // Ajouter l'objet au deck de l'attaquant
                $stmt_attacker_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :attacker_id");
                $stmt_attacker_deck->execute(['attacker_id' => $attacker_id]);
                $attacker_data = $stmt_attacker_deck->fetch(PDO::FETCH_ASSOC);
                $attacker_deck = json_decode($attacker_data['deck'], true);
                $attacker_deck[] = $requested_item;

                // Mettre à jour les decks dans la base de données
                $stmt_update_target = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                $stmt_update_target->execute(['deck' => json_encode($target_deck), 'id' => $target_id]);

                $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
                $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'id' => $attacker_id]);

                echo json_encode(['success' => true, 'message' => "L'objet $item_requested a été transféré avec succès."]);
            } else {
                echo json_encode(['success' => false, 'message' => "L'objet demandé n'est pas disponible."]);
            }

        } elseif ($button === 'giveup') {

            echo json_encode(['success' => true, 'message' => "Le joueur a abandonné."]);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération du combat : " . $e->getMessage()]);
    }

} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
