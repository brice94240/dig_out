<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'racket_item') {
    $game_id = intval($_POST['game_id']);
    $attacker_id = intval($_SESSION['user_id']);
    $target_id = intval($_POST['target_id']);
    $item_requested = $_POST['item_requested'];
    $target_decision = isset($_POST['target_decision']) ? $_POST['target_decision'] : null; // 'coop' or 'defend'

    // try {
    //     // Récupérer le deck de l'attaquant
    //     $stmt_attacker = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :attacker_id");
    //     $stmt_attacker->execute(['attacker_id' => $attacker_id]);
    //     $attacker_data = $stmt_attacker->fetch(PDO::FETCH_ASSOC);
    //     $attacker_deck = json_decode($attacker_data['deck'], true);

    //     // Récupérer le deck du joueur cible
    //     $stmt_target = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :target_id");
    //     $stmt_target->execute(['target_id' => $target_id]);
    //     $target_data = $stmt_target->fetch(PDO::FETCH_ASSOC);
    //     $target_deck = json_decode($target_data['deck'], true);

    //     // Vérifier si le joueur cible a l'objet demandé
    //     $has_requested_item = false;
    //     foreach ($target_deck as $index => $card) {
    //         if ($card['name'] === $item_requested) {
    //             $has_requested_item = true;
    //             $requested_item_index = $index;
    //             break;
    //         }
    //     }

    //     if ($has_requested_item) {
    //         if ($target_decision === null) {
    //             // Informer le joueur cible de la demande de décision
    //             // Vous devez gérer l'affichage de la décision côté client avec un modal ou autre méthode
    //             echo json_encode([
    //                 'success' => true,
    //                 'request_decision' => true,
    //                 'message' => "Le joueur cible doit choisir de coopérer ou se défendre."
    //             ]);
    //         } elseif ($target_decision === 'coop') {
    //             // Coopération : transférer l'objet
    //             unset($target_deck[$requested_item_index]);
    //             $target_deck = array_values($target_deck);
    //             $attacker_deck[] = ['name' => $item_requested, 'description' => $card['description'], 'verso_card' => $card['verso_card']];

    //             // Mettre à jour les decks dans la base de données
    //             $stmt_update_attacker = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
    //             $stmt_update_attacker->execute(['deck' => json_encode($attacker_deck), 'id' => $attacker_id]);

    //             $stmt_update_target = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :id");
    //             $stmt_update_target->execute(['deck' => json_encode($target_deck), 'id' => $target_id]);

    //             // Log the action
    //             $stmt_log = $pdo->prepare("INSERT INTO logs (game_id, user_id, message, target_id) VALUES (:game_id, :user_id, :message, :target_id)");
    //             $message = "a racketté un(e) $item_requested de";
    //             $stmt_log->execute([
    //                 'game_id' => $game_id,
    //                 'user_id' => $attacker_id,
    //                 'message' => $message,
    //                 'target_id' => $target_id
    //             ]);

    //             echo json_encode(['success' => true, 'message' => "Vous avez racketté un(e) $item_requested avec succès."]);
    //         } elseif ($target_decision === 'defend') {
    //             // Logique de combat ou autre forme de résolution
    //             echo json_encode(['success' => true, 'message' => "Un combat est initié!"]);
    //         }
    //     } else {
    //         echo json_encode(['success' => false, 'message' => "Le joueur cible n'a pas l'objet demandé."]);
    //     }
    // } catch (Exception $e) {
    //     echo json_encode(['success' => false, 'message' => 'Erreur lors de la tentative de racket.', 'error' => $e->getMessage()]);
    // }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
