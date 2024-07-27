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
        $localisation_choose = intval($_POST['localisation_id']);
        $nb_action = $row_info_joueur['nb_action'];
        $team = $row_info_joueur['team'];
        $game_id = $_POST['game_id'];
        $deck = json_decode($row_info_joueur['deck'], true) ?: [];
        if($nb_action > 0){
            if ($sub_type == 2) { //CLEF DU GARDIEN
                if($localisation !== $localisation_choose) {
                    // Mettre à jour la localisation
                    $stmt_update_localisation = $pdo->prepare("UPDATE joueurs SET localisation = :localisation_choose WHERE game_joined = :game_id AND ID = :user_id");
                    $stmt_update_localisation->execute(['localisation_choose' => $localisation_choose, 'game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
    
                    // Mettre à jour la localisation
                    $stmt_update_localisation = $pdo->prepare("UPDATE joueurs SET last_localisation = :localisation_choose WHERE game_joined = :game_id AND ID = :user_id");
                    $stmt_update_localisation->execute(['localisation_choose' => $localisation_choose, 'game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                        

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
                } else {
                    echo json_encode(['success' => false, 'message' => "Vous etes deja dans cette piece !"]);
                }
            } else if ($sub_type == 3) { //TRANSFERT DE PRISONNIERS
                $target_1 = $id_target[0];
                $target_2 = $id_target[1];

                $stmt_target_1 = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id AND `ID` = :id_target");
                $stmt_target_1->execute(['game_id' => $game_id, 'id_target' => $target_1]);
                $row_target_1 = $stmt_target_1->fetchAll(PDO::FETCH_ASSOC);

                $stmt_target_2 = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id AND `ID` = :id_target");
                $stmt_target_2->execute(['game_id' => $game_id, 'id_target' => $target_2]);
                $row_target_2 = $stmt_target_2->fetchAll(PDO::FETCH_ASSOC);

                if($row_target_1[0]['localisation'] !== $row_target_2[0]['localisation']){
                    // Mettre à jour la position de target1
                    $stmt_update_localisation_target_1 = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE ID = :ID");
                    $stmt_update_localisation_target_1->execute(['localisation' => $row_target_2[0]['localisation'], 'ID' => $target_1]);

                    // Mettre à jour la position de target2
                    $stmt_update_localisation_target_2 = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE ID = :ID");
                    $stmt_update_localisation_target_2->execute(['localisation' => $row_target_1[0]['localisation'], 'ID' => $target_2]);

                    // Mettre à jour la position de target1
                    $stmt_update_localisation_target_1 = $pdo->prepare("UPDATE joueurs SET last_localisation = :last_localisation WHERE ID = :ID");
                    $stmt_update_localisation_target_1->execute(['last_localisation' => $row_target_2[0]['localisation'], 'ID' => $target_1]);

                    // Mettre à jour la position de target2
                    $stmt_update_localisation_target_2 = $pdo->prepare("UPDATE joueurs SET last_localisation = :last_localisation WHERE ID = :ID");
                    $stmt_update_localisation_target_2->execute(['last_localisation' => $row_target_1[0]['localisation'], 'ID' => $target_2]);


                    
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

                    echo json_encode(['success' => true, 'deck' =>  $deck, 'deck_target' =>  $deck_target, 'defausse' => $defausse_json]);

                } else {
                    echo json_encode(['success' => false, 'message' => "Les joueurs sont dans la meme piece", "row_target_1" => $row_target_1, "row_target_2" => $row_target_2]);
                }
            } else if($sub_type == 4){ //FOUILLE AU CORPS
                // Récupérer les détails de la target
                $stmt_target = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id AND `ID` = :id_target");
                $stmt_target->execute(['game_id' => $game_id, 'id_target' => $id_target]);
                $row_target = $stmt_target->fetchAll(PDO::FETCH_ASSOC);
                $deck_target = json_decode($row_target[0]['deck']);
                if(count($deck_target) >= 2){
                    shuffle($deck_target);
                    $number_card = 0;
                    foreach ($deck_target as $index => $card_target) {
                        if ($number_card !== 2) {
                            array_unshift($defausseData, $card_target); // Ajouter la carte au début de defausse_data
                            unset($deck_target[$index]); // Enlever la carte
                            $number_card++;
                        }
                    }

                    foreach ($deck as $index => $card) {
                        if (intval($card['ID']) == intval($item_name)) {
                            array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                            unset($deck[$index]); // Enlever la carte
                        }
                    }
    
                    $deck = json_encode(array_values($deck)); // Re-indexer le tableau
                    $deck_target = json_encode(array_values($deck_target)); // Re-indexer le tableau
                    $defausse_json = json_encode($defausseData);
                    
                    // Mettre à jour le deck du joueur
                    $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                    $stmt_update_deck->execute(['deck' => $deck, 'ID' => $_SESSION['user_id']]);
    
                    // Mettre à jour le deck de la target
                    $stmt_update_deck_target = $pdo->prepare("UPDATE joueurs SET deck = :deck_target WHERE ID = :ID");
                    $stmt_update_deck_target->execute(['deck_target' => $deck_target, 'ID' => $id_target]);
    
                    // Récupérer les détails des decks
                    $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                    $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                    $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);

                    // Récupérer les détails de la target
                    $stmt_deck_target = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :id_target");
                    $stmt_deck_target->execute(['game_id' => $game_id, 'id_target' => $id_target]);
                    $row_deck_target = $stmt_deck_target->fetchAll(PDO::FETCH_ASSOC);
    
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

                    // Récupérer les détails de la target
                    $stmt_deck_target = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :id_target");
                    $stmt_deck_target->execute(['game_id' => $game_id, 'id_target' => $id_target]);
                    $row_deck_target = $stmt_deck_target->fetchAll(PDO::FETCH_ASSOC);
    
                    // Réduire le nombre d'actions
                    $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                    $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                    echo json_encode(['success' => true, 'deck' =>  $deck, 'deck_target' =>  $deck_target, 'defausse' => $defausse_json]);
                } else {
                    echo json_encode(['success' => false, 'message' => "La cible n'a pas assez de carte", 'deck_target' => $deck_target]);
                }
            } else if($sub_type == 5){ //ISOLEMENT
                // Récupérer les détails de la target
                $stmt_target = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id AND `ID` = :id_target");
                $stmt_target->execute(['game_id' => $game_id, 'id_target' => $id_target]);
                $row_target = $stmt_target->fetchAll(PDO::FETCH_ASSOC);

                if($row_target['localisation'] !== 6){
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
                } else {
                    echo json_encode(['success' => false, 'message' => "La cible est déja a l'isolement"]);
                }
            } else if($sub_type == 6){ //SAVONETTE
                // Récupérer les détails de la target
                $stmt_target = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id AND `ID` = :id_target");
                $stmt_target->execute(['game_id' => $game_id, 'id_target' => $id_target]);
                $row_target = $stmt_target->fetchAll(PDO::FETCH_ASSOC);

                if($row_target[0]['localisation'] == 2 && $localisation == 2){
                    if($row_target[0]['raclee'] < 2){
                        // Mettre à jour la localisation de la target
                        $stmt_update_target_localisation = $pdo->prepare("UPDATE joueurs SET raclee = raclee + 1 WHERE game_joined = :game_id AND ID = :id_target");
                        $stmt_update_target_localisation->execute(['game_id' => $game_id, 'id_target' => $id_target]);
        
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
                    } else {
                        echo json_encode(['success' => false, 'message' => "Le joueur a deja deux raclées"]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => "Vous et la cible doivent vous trouvez dans la douche"]);
                }
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
