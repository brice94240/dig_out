<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_action_target') {
    try {
        $stmt_info_joueur = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
        $stmt_info_joueur->execute(['player_id' => $_SESSION['user_id']]);
        $row_info_joueur = $stmt_info_joueur->fetch(PDO::FETCH_ASSOC);

        // Récupérer les détails des fouilles
        $stmt_fouille = $pdo->prepare("SELECT fouille_data FROM games WHERE creator_id = :game_id");
        $stmt_fouille->execute(['game_id' => $game_id]);
        $row_fouille = $stmt_fouille->fetch(PDO::FETCH_ASSOC);

        // Récupérer defausse_data du jeu
        $stmt_game = $pdo->prepare("SELECT defausse_data FROM games WHERE creator_id = :game_id");
        $stmt_game->execute(['game_id' => $game_id]);
        $row_game = $stmt_game->fetch(PDO::FETCH_ASSOC);
        $defausseData = json_decode($row_game['defausse_data'], true) ?: [];

        $sub_type = $_POST['sub_type'];
        $id_target = $_POST['player_id'];
        $item_name = $_POST['item_name'];
        $localisation = $row_info_joueur['localisation'];
        $nb_action = $row_info_joueur['nb_action'];
        $team = $row_info_joueur['team'];
        $game_id = $_POST['game_id'];
        $deck = json_decode($row_info_joueur['deck'], true) ?: [];
        if($nb_action > 0){
            if($sub_type == 5){
                // Mettre à jour la localisation de la target
                $stmt_update_target_localisation = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE game_joined = :game_id AND ID = :id_target");
                $stmt_update_target_localisation->execute(['localisation' => 6, 'game_id' => $game_id, 'id_target' => $id_target]);

                foreach ($deck as $index => $card) {
                    if (intval($card['ID']) == intval($item_name)) {
                        array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                        unset($deck[$index]); // Enlever la carte
                    }
                }

                $deck = json_encode(array_values($deck)); // Re-indexer le tableau
                $defausse_json = json_encode($defausseData);

                // Mettre à jour le deck du joueur
                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                $stmt_update_deck->execute(['deck' => $deck, 'ID' => $_SESSION['user_id']]);

                // Récupérer les détails des decks
                $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);

                // Mettre à jour defausse_data du jeu
                $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = :defausse WHERE creator_id = :game_id");
                $stmt_update_defausse->execute(['defausse' => $defausse_json, 'game_id' => $game_id]);

                foreach($row_deck as $row_decks){
                    if ($row_decks) {
                        $decks = json_decode($row_decks['deck'], true);
                    } else {
                        echo "Les decks ne sont pas encore disponibles.";
                    }
                }

                // Récupérer les détails des decks
                $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);

                // Réduire le nombre d'actions
                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                echo json_encode(['success' => true, 'deck' =>  $deck, 'defausse' => $defausse_json]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Plus d'action"]);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
