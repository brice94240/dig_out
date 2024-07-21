<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_deck') {
    try {
        // Récupérer le deck actuel du joueur
        $stmt_get_deck = $pdo->prepare("SELECT deck, cigarette FROM joueurs WHERE ID = :player_id");
        $stmt_get_deck->execute(['player_id' => $_SESSION['user_id']]);
        $row_deck = $stmt_get_deck->fetch(PDO::FETCH_ASSOC);
        $deck = json_decode($row_deck['deck'], true) ?: [];
        
        echo json_encode(['success' => true, 'deck' =>  $deck]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'defausser_card') {
    // New code to handle card defausse
    if (isset($_POST['card_id']) && isset($_POST['game_id'])) {
        $cardId = $_POST['card_id'];
        $gameId = $_POST['game_id'];

        try {
            // Récupérer le deck du joueur
            $stmt = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $deck = json_decode($row['deck'], true);

            // Récupérer defausse_data du jeu
            $stmt_game = $pdo->prepare("SELECT defausse_data FROM games WHERE creator_id = :game_id");
            $stmt_game->execute(['game_id' => $gameId]);
            $row_game = $stmt_game->fetch(PDO::FETCH_ASSOC);
            $defausseData = json_decode($row_game['defausse_data'], true) ?: [];

            foreach ($deck as $index => $card) {
                if ($card['ID'] == $cardId) {
                    array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                    unset($deck[$index]); // Enlever la carte du deck
                    break;
                }
            }

            $deck_json = json_encode(array_values($deck)); // Re-indexer le tableau
            $defausse_json = json_encode($defausseData);

            // Mettre à jour le deck du joueur
            $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :user_id");
            $stmt_update_deck->execute(['deck' => $deck_json, 'user_id' => $_SESSION['user_id']]);

            // Mettre à jour defausse_data du jeu
            $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = :defausse WHERE creator_id = :game_id");
            $stmt_update_defausse->execute(['defausse' => $defausse_json, 'game_id' => $gameId]);

            echo json_encode(['success' => true, 'message' => 'Carte défaussée avec succès.']);
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => "Erreur lors de la défausse de la carte : " . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Paramètres manquants.']);
    }

} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_deck_to_sell') {
    try {
        // Récupérer le deck actuel du joueur
        $stmt_get_deck = $pdo->prepare("SELECT deck, localisation FROM joueurs WHERE ID = :player_id");
        $stmt_get_deck->execute(['player_id' => $_SESSION['user_id']]);
        $row_deck = $stmt_get_deck->fetch(PDO::FETCH_ASSOC);
        $deck = json_decode($row_deck['deck'], true) ?: [];
        $localisation = $row_deck['localisation'];
        
        echo json_encode(['success' => true, 'deck' =>  $deck, 'localisation' =>  $localisation]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sell_card') { 
    $gameId = $_POST['game_id'];
    $tab_sell = $_POST['tab_sell'];
    // Récupérer le deck et les cigarettes du joueur
    $stmt = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $deck = json_decode($row['deck'], true);
    if($row['nb_action'] > 0){
        if($tab_sell && $tab_sell !== ""){
            try {
                // Récupérer defausse_data du jeu
                $stmt_game = $pdo->prepare("SELECT defausse_data FROM games WHERE creator_id = :game_id");
                $stmt_game->execute(['game_id' => $gameId]);
                $row_game = $stmt_game->fetch(PDO::FETCH_ASSOC);
                $defausseData = json_decode($row_game['defausse_data'], true) ?: [];

                foreach ($deck as $index => $card) {
                    if (in_array($card['ID'], $tab_sell)) {
                        $card_id = $card['ID'];
                        array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data

                        // Récupérer la valeur de cigarette de la carte
                        $stmt = $pdo->prepare("SELECT * FROM fouilles WHERE ID = :card_id");
                        $stmt->execute(['card_id' => $card['ID']]);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        $val_cigarette = $row['val_cigarette'];

                        // Mettre à jour les cigarettes du joueur
                        $stmt_update_cigarette = $pdo->prepare("UPDATE joueurs SET cigarette = cigarette + :val_cigarette WHERE ID = :user_id");
                        $stmt_update_cigarette->execute(['val_cigarette' => $val_cigarette, 'user_id' => $_SESSION['user_id']]);

                        unset($deck[$index]); // Enlever la carte du deck
                    }
                }

                if($card_id){
                    $deck_json = json_encode(array_values($deck)); // Re-indexer le tableau
                    $defausse_json = json_encode($defausseData);
    
                    // Mettre à jour le deck du joueur
                    $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :user_id");
                    $stmt_update_deck->execute(['deck' => $deck_json, 'user_id' => $_SESSION['user_id']]);
    
                    // Mettre à jour defausse_data du jeu
                    $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = :defausse WHERE creator_id = :game_id");
                    $stmt_update_defausse->execute(['defausse' => $defausse_json, 'game_id' => $gameId]);
    
                    // Récupérer le deck actuel du joueur
                    $stmt_get_infos = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
                    $stmt_get_infos->execute(['player_id' => $_SESSION['user_id']]);
                    $row_get_infos = $stmt_get_infos->fetch(PDO::FETCH_ASSOC);
    
                    if($row_deck['cigarette'] !== $row_get_infos['cigarette']){
                        // Réduire le nombre d'actions
                        $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                        $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                        echo json_encode(['success' => true, 'message' => 'Cartes vendues avec succès.']);
                    }
                } else {
                    echo json_encode(['success' => true, 'message' => 'Veuillez sélectionner des cartes.']);
                }

            } catch (PDOException $e) {
                echo json_encode(['success' => false, 'message' => "Erreur lors de la vente des cartes : " . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => true, 'message' => 'Veuillez sélectionner des cartes a vendre.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => "Plus d'action"]);
    }

} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
