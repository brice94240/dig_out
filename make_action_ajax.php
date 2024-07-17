<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'make_action') {
    try {
        $name_action = $_POST['name_action'];
        $item_name = $_POST['item_name'];

        $stmt_info_joueur = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
        $stmt_info_joueur->execute(['player_id' => $_SESSION['user_id']]);
        $row_info_joueur = $stmt_info_joueur->fetch(PDO::FETCH_ASSOC);
        $localisation = $row_info_joueur['localisation'];
        $nb_action = $row_info_joueur['nb_action'];
        $raclee = $row_info_joueur['raclee'];
        $game_id = $_POST['game_id'];
        $team = $row_info_joueur['team'];
        $point_turn = $row_info_joueur['point_turn'];

        // Récupérer le deck actuel du joueur
        $stmt_get_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :player_id");
        $stmt_get_deck->execute(['player_id' => $_SESSION['user_id']]);
        $row_deck = $stmt_get_deck->fetch(PDO::FETCH_ASSOC);
        $deck = json_decode($row_deck['deck'], true) ?: [];

        $hasTournevis = false;
        $hasLien = false;
        $hasRecipient = false;
        $hasLame = false;

        $hasPelle = false;
        $hasPioche = false;
        $hasCuillere = false;

        if ($name_action == "join"){
            //VEUT REJOINDRE UN GANG
        } else if($name_action == "heal" && $nb_action > 0) {
            if($raclee > 0) {
                // Mettre à jour le deck du joueur
                $stmt_update_heal= $pdo->prepare("UPDATE joueurs SET raclee = raclee -1 WHERE `ID` = :user_id");
                $stmt_update_heal->execute(['user_id' => $_SESSION['user_id']]);
                // Update nb_action in the database
                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action -1 WHERE `ID` = :user_id");
                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);

                echo json_encode(['success' => false, 'message' => "Vous vous etes soigné une raclée"]);
            } else {
                echo json_encode(['success' => false, 'message' => "Vous avez aucune raclée"]);
            }
        
        } else if($name_action == "creuser" && $nb_action > 0) {
            if(($localisation == 1 && $team == "A") || ($localisation == 3 && $team == "B")){
                if($point_turn == 0){
                    if($raclee < 2) {
                        // Chercher les points du deck
                        foreach ($deck as $index => $card) {
                            if ($card['name'] === 'Pelle') {
                                $hasPelle = true;
                                $PelleIndex = $deck[$index];
                            }
                            if ($card['name'] === 'Pioche') {
                                $hasPioche = true;
                                $PiocheIndex = $deck[$index];
                            }
                            if ($card['name'] === 'Cuillère') {
                                $hasCuillere = true;
                                $CuillereIndex = $deck[$index];
                            }
                        }

                        if($hasPelle) {
                            unset($deck[$index]);
                            $points = 3; 
                        }
                        else if($hasPioche) {
                            unset($deck[$index]);
                            $points = 2;
                        }
                        else if($hasCuillere) {
                            unset($deck[$index]);
                            $points = 1;
                        }
                        if($points > 0) {
                            // Réindexer le deck pour enlever les trous laissés par unset
                            $deck = array_values($deck);
                            $deck_json = json_encode($deck);
                            // Mettre à jour le deck du joueur
                            $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                            $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);
    
                            // Update nb_point in the database
                            $stmt_update_nb_point = $pdo->prepare("UPDATE joueurs SET nb_point = nb_point + :points WHERE `ID` = :user_id");
                            $stmt_update_nb_point->execute(['points' => $points , 'user_id' => $_SESSION['user_id']]);
    
                            // Update point_turn in the database
                            $stmt_update_point_turn = $pdo->prepare("UPDATE joueurs SET point_turn = point_turn + :points WHERE `ID` = :user_id");
                            $stmt_update_point_turn->execute(['points' => $points , 'user_id' => $_SESSION['user_id']]);
    
                            // Update nb_action in the database
                            $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action -1 WHERE `ID` = :user_id");
                            $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
            
                            echo json_encode(['success' => false, 'message' => "Vous avez creuser pour ".$points." points"]);
                        } else {
                            echo json_encode(['success' => false, 'message' => "Vous n'avez aucun point en main."]);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas creuser, soignez vous."]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => "Vous ne pouvez creuser qu'une fois par tour."]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas creuser ici"]);
            }
        } else if($nb_action > 0) {
            if ($raclee > 0 && $name_action == "steal" && $item_name == "Cuillère"){
                if($localisation == 5) {
                    //RACLEE MAIS VEUT VOLER UNE CUILLERE
                    //VOLER UNE CUILLERE
                    // Récupérer les détails des cuillere
                    $stmt_cuillere = $pdo->prepare("SELECT cuillere_data FROM games WHERE creator_id = :game_id");
                    $stmt_cuillere->execute(['game_id' => $game_id]);
                    $row_cuillere = $stmt_cuillere->fetch(PDO::FETCH_ASSOC);

                    if ($row_cuillere && $row_cuillere['cuillere_data']) {
                        $cuilleres = json_decode($row_cuillere['cuillere_data'], true);
                        //METTRE UNE CUILLERE DU PLATEAU AU DECK
                        if (!empty($cuilleres)) {
                            $deck[] = array_shift($cuilleres);
                        }
                        $deck_json = json_encode($deck);
                        $cuilleres_json = json_encode($cuilleres);
    
                        // Mettre à jour les détails des cuilleres
                        $stmt_update_cuillere = $pdo->prepare("UPDATE games SET cuillere_data = :cuillere WHERE creator_id = :game_id");
                        $stmt_update_cuillere->execute(['cuillere' => $cuilleres_json, 'game_id' => $game_id]);
    
                        // Mettre à jour le deck du joueur
                        $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                        $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);
    
                        // Récupérer les détails des decks
                        $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                        $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                        $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);
                        foreach($row_deck as $row_decks){
                            if ($row_decks) {
                                $decks = json_decode($row_decks['deck'], true);
                            } else {
                                echo "Les decks ne sont pas encore disponibles.";
                            }
                        }
                        $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action -1 WHERE `ID` = :user_id");
                        $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                    }
                    echo json_encode(['success' => true, 'deck' =>  $deck]);
                } else {
                    echo json_encode(['success' => false, 'message' => "Vous devez etre dans le réféctoire"]);
                }
            } else if($raclee == 0){
                //SI LE JOUEUR A PAS DE RACLEE
                //SI LE JOUEUR VEUT VOLER UNE CUILLERE
                if ($name_action == "steal" && $item_name == "Cuillère"){
                    if($localisation == 5) {
                        //RACLEE MAIS VEUT VOLER UNE CUILLERE
                        //VOLER UNE CUILLERE
                        // Récupérer les détails des cuillere
                        $stmt_cuillere = $pdo->prepare("SELECT cuillere_data FROM games WHERE creator_id = :game_id");
                        $stmt_cuillere->execute(['game_id' => $game_id]);
                        $row_cuillere = $stmt_cuillere->fetch(PDO::FETCH_ASSOC);
    
                        if ($row_cuillere && $row_cuillere['cuillere_data']) {
                            $cuilleres = json_decode($row_cuillere['cuillere_data'], true);
                            //METTRE UNE CUILLERE DU PLATEAU AU DECK
                            if (!empty($cuilleres)) {
                                $deck[] = array_shift($cuilleres);
                            }
                            $deck_json = json_encode($deck);
                            $cuilleres_json = json_encode($cuilleres);
        
                            // Mettre à jour les détails des cuilleres
                            $stmt_update_cuillere = $pdo->prepare("UPDATE games SET cuillere_data = :cuillere WHERE creator_id = :game_id");
                            $stmt_update_cuillere->execute(['cuillere' => $cuilleres_json, 'game_id' => $game_id]);
        
                            // Mettre à jour le deck du joueur
                            $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                            $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);
        
                            // Récupérer les détails des decks
                            $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                            $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                            $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);
                            foreach($row_deck as $row_decks){
                                if ($row_decks) {
                                    $decks = json_decode($row_decks['deck'], true);
                                } else {
                                    echo "Les decks ne sont pas encore disponibles.";
                                }
                            }
                            $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action -1 WHERE `ID` = :user_id");
                            $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                        }
                        echo json_encode(['success' => true, 'deck' =>  $deck]);
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous devez etre dans le réféctoire"]);
                    }
                } elseif ($name_action == "make" && $item_name == "Pelle"){
                    //SI LE JOUEUR VEUT CONSTRUIRE UNE PELLE
                    if(($localisation == 4) || ($localisation == 5) || ($localisation == 7) || ($localisation == 3 && $team == "A") || ($localisation == 1 && $team == "B")) {
                        // Chercher et enlever le tournevis et le lien du deck
                        foreach ($deck as $index => $card) {
                            if ($card['name'] === 'Récipient') {
                                $hasRecipient = true;
                                unset($deck[$index]); // Enlever le tournevis
                            }
                            if ($card['name'] === 'Lien') {
                                $hasLien = true;
                                unset($deck[$index]); // Enlever le lien
                            }
                        }
                        if ($hasRecipient && $hasLien) {
                            // Réindexer le deck pour enlever les trous laissés par unset
                            $deck = array_values($deck);
                            // CONSTRUIRE LA PELLE
                            // Récupérer les détails des cuillere
                            $stmt_pelle = $pdo->prepare("SELECT pelle_data FROM games WHERE creator_id = :game_id");
                            $stmt_pelle->execute(['game_id' => $game_id]);
                            $row_pelle = $stmt_pelle->fetch(PDO::FETCH_ASSOC);
        
                            if ($row_pelle && $row_pelle['pelle_data']) {
                                $pelles = json_decode($row_pelle['pelle_data'], true);
                                //METTRE UNE PELLE DU PLATEAU AU DECK
                                if (!empty($pelles)) {
                                    $deck[] = array_shift($pelles);
                                }
                                $deck_json = json_encode($deck);
                                $pelles_json = json_encode($pelles);
            
                                // Mettre à jour les détails des pelles
                                $stmt_update_pelle = $pdo->prepare("UPDATE games SET pelle_data = :pelle WHERE creator_id = :game_id");
                                $stmt_update_pelle->execute(['pelle' => $pelles_json, 'game_id' => $game_id]);

                                // Mettre à jour le deck du joueur
                                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                                $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);

                                // Récupérer les détails des decks
                                $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                                $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                                $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);
                                foreach($row_deck as $row_decks){
                                    if ($row_decks) {
                                        $decks = json_decode($row_decks['deck'], true);
                                    } else {
                                        echo "Les decks ne sont pas encore disponibles.";
                                    }
                                }
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action -1 WHERE `ID` = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                            }
                            echo json_encode(['success' => true, 'message' => 'Vous avez les deux objets nécessaires.']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les objets nécessaires.']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas fabriquer ici"]);
                    }
                } elseif ($name_action == "make" && $item_name == "Pioche"){
                    //SI LE JOUEUR VEUT CONSTRUIRE UNE PIOCHE
                    if(($localisation == 4) || ($localisation == 5) || ($localisation == 7) || ($localisation == 3 && $team == "A") || ($localisation == 1 && $team == "B")) {
                        // Chercher et enlever le tournevis et le lien du deck
                        foreach ($deck as $index => $card) {
                            if ($card['name'] === 'Tournevis') {
                                $hasTournevis = true;
                                unset($deck[$index]); // Enlever le tournevis
                            }
                            if ($card['name'] === 'Lien') {
                                $hasLien = true;
                                unset($deck[$index]); // Enlever le lien
                            }
                        }
                        if ($hasTournevis && $hasLien) {
                            // Réindexer le deck pour enlever les trous laissés par unset
                            $deck = array_values($deck);
                            // CONSTRUIRE LA PELLE
                            // Récupérer les détails des cuillere
                            $stmt_pioche = $pdo->prepare("SELECT pioche_data FROM games WHERE creator_id = :game_id");
                            $stmt_pioche->execute(['game_id' => $game_id]);
                            $row_pioche = $stmt_pioche->fetch(PDO::FETCH_ASSOC);
        
                            if ($row_pioche && $row_pioche['pioche_data']) {
                                $pioches = json_decode($row_pioche['pioche_data'], true);
                                //METTRE UNE PELLE DU PLATEAU AU DECK
                                if (!empty($pioches)) {
                                    $deck[] = array_shift($pioches);
                                }
                                $deck_json = json_encode($deck);
                                $pioches_json = json_encode($pioches);

                                // Mettre à jour les détails des pioches
                                $stmt_update_pioche = $pdo->prepare("UPDATE games SET pioche_data = :pioche WHERE creator_id = :game_id");
                                $stmt_update_pioche->execute(['pioche' => $pioches_json, 'game_id' => $game_id]);

                                // Mettre à jour le deck du joueur
                                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                                $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);

                                // Récupérer les détails des decks
                                $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                                $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                                $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);
                                foreach($row_deck as $row_decks){
                                    if ($row_decks) {
                                        $decks = json_decode($row_decks['deck'], true);
                                    } else {
                                        echo "Les decks ne sont pas encore disponibles.";
                                    }
                                }
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action -1 WHERE `ID` = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                            }
                            echo json_encode(['success' => true, 'message' => 'Vous avez les deux objets nécessaires.']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les objets nécessaires.']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas fabriquer ici"]);
                    }
                } elseif ($name_action == "make" && $item_name == "Surin"){
                    //SI LE JOUEUR VEUT CONSTRUIRE UNE PIOCHE
                    if(($localisation == 4) || ($localisation == 5) || ($localisation == 7) || ($localisation == 3 && $team == "A") || ($localisation == 1 && $team == "B")) {
                        // Chercher et enlever le tournevis et le lien du deck
                        foreach ($deck as $index => $card) {
                            if ($card['name'] === 'Lame') {
                                $hasLame = true;
                                unset($deck[$index]); // Enlever le tournevis
                            }
                            if ($card['name'] === 'Lien') {
                                $hasLien = true;
                                unset($deck[$index]); // Enlever le lien
                            }
                        }
                        if ($hasLame && $hasLien) {
                            // Réindexer le deck pour enlever les trous laissés par unset
                            $deck = array_values($deck);
                            // CONSTRUIRE LA PELLE
                            // Récupérer les détails des cuillere
                            $stmt_surin = $pdo->prepare("SELECT surin_data FROM games WHERE creator_id = :game_id");
                            $stmt_surin->execute(['game_id' => $game_id]);
                            $row_surin = $stmt_surin->fetch(PDO::FETCH_ASSOC);
        
                            if ($row_surin && $row_surin['surin_data']) {
                                $surins = json_decode($row_surin['surin_data'], true);
                                //METTRE UNE PELLE DU PLATEAU AU DECK
                                if (!empty($surins)) {
                                    $deck[] = array_shift($surins);
                                }
                                $deck_json = json_encode($deck);
                                $surins_json = json_encode($surins);

                                // Mettre à jour les détails des pioches
                                $stmt_update_surin = $pdo->prepare("UPDATE games SET surin_data = :surin WHERE creator_id = :game_id");
                                $stmt_update_surin->execute(['surin' => $surins_json, 'game_id' => $game_id]);

                                // Mettre à jour le deck du joueur
                                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                                $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);

                                // Récupérer les détails des decks
                                $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                                $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                                $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);
                                foreach($row_deck as $row_decks){
                                    if ($row_decks) {
                                        $decks = json_decode($row_decks['deck'], true);
                                    } else {
                                        echo "Les decks ne sont pas encore disponibles.";
                                    }
                                }
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action -1 WHERE `ID` = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                            }
                            echo json_encode(['success' => true, 'message' => 'Vous avez les deux objets nécessaires.']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les objets nécessaires.']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas fabriquer ici"]);
                    }
                }
            }  else {
                //RACLEE MAIS VEUT CONSTRUIRE
                echo json_encode(['success' => false, 'message' => "Vous devez vous soigner"]);
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
