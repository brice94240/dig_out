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
        $cigarette = $row_info_joueur['cigarette'];
        $id = $row_info_joueur['ID'];

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

        // Récupérer defausse_data du jeu
        $stmt_game = $pdo->prepare("SELECT defausse_data FROM games WHERE creator_id = :game_id");
        $stmt_game->execute(['game_id' => $game_id]);
        $row_game = $stmt_game->fetch(PDO::FETCH_ASSOC);
        $defausseData = json_decode($row_game['defausse_data'], true) ?: [];


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
                                $PelleIndex = $index;
                            }
                            if ($card['name'] === 'Pioche') {
                                $hasPioche = true;
                                $PiocheIndex = $index;
                            }
                            if ($card['name'] === 'Cuillère') {
                                $hasCuillere = true;
                                $CuillereIndex = $index;
                            }
                        }

                        if($hasPelle) {
                            unset($deck[$PelleIndex]);
                            $points = 3; 
                        }
                        else if($hasPioche) {
                            unset($deck[$PiocheIndex]);
                            $points = 2;
                        }
                        else if($hasCuillere) {
                            unset($deck[$CuillereIndex]);
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
                        echo json_encode(['success' => true, 'message' => 'Vous avez volé une Cuillere.', 'deck' =>  $deck, 'steal' => true]);
                    } else {
                        echo json_encode(['success' => false, 'message' => "Plus de Cuillere disponible"]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => "Vous devez etre dans le réféctoire"]);
                }
            } elseif ($name_action == "sell" && $item_name == "cigarette"){
                if($localisation == 7) {
                    echo json_encode(['success' => true, 'deck' => $deck, 'sell' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => "Vous devez etre dans le réféctoire"]);
                }
            } elseif ($name_action == "buy") {
                if ($localisation == 7){
                    if ($item_name == "Pelle"){
                        $price = 8;
                        if($cigarette >= $price){
                            $deck = array_values($deck);
                
                            $stmt_pelle = $pdo->prepare("SELECT pelle_data FROM games WHERE creator_id = :game_id");
                            $stmt_pelle->execute(['game_id' => $game_id]);
                            $row_pelle = $stmt_pelle->fetch(PDO::FETCH_ASSOC);
                
                            if ($row_pelle && $row_pelle['pelle_data']) {
                                $pelles = json_decode($row_pelle['pelle_data'], true);
                                if (!empty($pelles)) {
                                    $deck[] = array_shift($pelles); // Ajouter une pelle au deck
                                }
                                $deck_json = json_encode($deck);
                                $pelles_json = json_encode($pelles);
                
                                // Mettre à jour les détails des pelles
                                $stmt_update_pelle = $pdo->prepare("UPDATE games SET pelle_data = :pelle WHERE creator_id = :game_id");
                                $stmt_update_pelle->execute(['pelle' => $pelles_json, 'game_id' => $game_id]);
    
                                // Mettre à jour le deck du joueur
                                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                                $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);
                
                                // Réduire le nombre de cigarette
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET cigarette = cigarette - 8 WHERE ID = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
    
                                // Réduire le nombre d'actions
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                
                                echo json_encode(['success' => true, 'message' => 'Vous avez acheté une Pelle.', 'buy' => true]);
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Erreur de récupération des données de Pelle.']);
                            }
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Vous avez besoin de 8 cigarettes.']);
                        }
                    } elseif ($item_name == "Pioche"){
                        $price = 6;
                        if($cigarette >= $price){
                            $deck = array_values($deck);
                
                            $stmt_pioche = $pdo->prepare("SELECT pioche_data FROM games WHERE creator_id = :game_id");
                            $stmt_pioche->execute(['game_id' => $game_id]);
                            $row_pioche = $stmt_pioche->fetch(PDO::FETCH_ASSOC);
                
                            if ($row_pioche && $row_pioche['pioche_data']) {
                                $pioches = json_decode($row_pioche['pioche_data'], true);
                                if (!empty($pioches)) {
                                    $deck[] = array_shift($pioches); // Ajouter une pioche au deck
                                }
                                $deck_json = json_encode($deck);
                                $pioches_json = json_encode($pioches);
                
                                // Mettre à jour les détails des pioches
                                $stmt_update_pioche = $pdo->prepare("UPDATE games SET pioche_data = :pioche WHERE creator_id = :game_id");
                                $stmt_update_pioche->execute(['pioche' => $pioches_json, 'game_id' => $game_id]);
    
                                // Mettre à jour le deck du joueur
                                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                                $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);
                
                                // Réduire le nombre de cigarette
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET cigarette = cigarette - 6 WHERE ID = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
    
                                // Réduire le nombre d'actions
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                
                                echo json_encode(['success' => true, 'message' => 'Vous avez acheté une Pioche.', 'buy' => true]);
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Erreur de récupération des données de Pioche.']);
                            }
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Vous avez besoin de 6 cigarettes.']);
                        }
                    } elseif ($item_name == "Surin"){
                        $price = 5;
                        if($cigarette >= $price){
                            $deck = array_values($deck);
                
                            $stmt_surin = $pdo->prepare("SELECT surin_data FROM games WHERE creator_id = :game_id");
                            $stmt_surin->execute(['game_id' => $game_id]);
                            $row_surin = $stmt_surin->fetch(PDO::FETCH_ASSOC);
                
                            if ($row_surin && $row_surin['surin_data']) {
                                $surins = json_decode($row_surin['surin_data'], true);
                                if (!empty($surins)) {
                                    for($i=0;$i < 2; $i++){
                                        $deck[] = array_shift($surins); // Ajouter deux surins au deck
                                    }
                                }
                                $deck_json = json_encode($deck);
                                $surins_json = json_encode($surins);
                
                                // Mettre à jour les détails des surins
                                $stmt_update_surin = $pdo->prepare("UPDATE games SET surin_data = :surin WHERE creator_id = :game_id");
                                $stmt_update_surin->execute(['surin' => $surins_json, 'game_id' => $game_id]);
    
                                // Mettre à jour le deck du joueur
                                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                                $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);
                
                                // Réduire le nombre de cigarette
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET cigarette = cigarette - 5 WHERE ID = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
    
                                // Réduire le nombre d'actions
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                
                                echo json_encode(['success' => true, 'message' => 'Vous avez acheté deux Surins.', 'buy' => true]);
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Erreur de récupération des données des Surins.']);
                            }
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Vous avez besoin de 5 cigarettes.']);
                        }
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => "Vous devez etre dans la promenade"]);
                }
            } elseif ($name_action == "card_action"){
                $stmt_card_action = $pdo->prepare("SELECT * FROM fouilles WHERE ID = :card_action_id");
                $stmt_card_action->execute(['card_action_id' => $item_name]);
                $row_card_action = $stmt_card_action->fetch(PDO::FETCH_ASSOC);
                $sub_type = $row_card_action['sub_type'];

                $stmt_game_infos = $pdo->prepare("SELECT * FROM games WHERE creator_id = :game_id");
                $stmt_game_infos->execute(['game_id' => $game_id]);
                $row_game_infos = $stmt_game_infos->fetchAll(PDO::FETCH_ASSOC);
                

                $stmt_players_infos = $pdo->prepare("SELECT ID,pseudo,team,deck,localisation,raclee FROM joueurs WHERE game_joined = :game_id AND team != :team");
                $stmt_players_infos->execute(['game_id' => $game_id, 'team' => $team]);
                $row_players_infos = $stmt_players_infos->fetchAll(PDO::FETCH_ASSOC);

                if($sub_type == 1) { //VISITE D'UN PROCHE OK
                    // Récupérer les détails des fouilles
                    $stmt_fouille = $pdo->prepare("SELECT fouille_data FROM games WHERE creator_id = :game_id");
                    $stmt_fouille->execute(['game_id' => $game_id]);
                    $row_fouille = $stmt_fouille->fetch(PDO::FETCH_ASSOC);

                    if ($row_fouille && $row_fouille['fouille_data']) {
                        $fouilles = json_decode($row_fouille['fouille_data'], true);
                        //PRENDRE A CHAQUE FOIS LES PREMIERES CARTES DU TABLEAU FOUILLES ET LES METTRE DANS CHAQUE DECK
                        $fouilles_win = 3;
                        for ($i = 0; $i < $fouilles_win; $i++) {
                            if (!empty($fouilles)) {
                                $deck[] = array_shift($fouilles);
                            }
                        }
                        $deck_json = json_encode($deck);
                        $fouilles_json = json_encode($fouilles);

                        // Mettre à jour les détails des fouilles
                        $stmt_update_fouille = $pdo->prepare("UPDATE games SET fouille_data = :fouille WHERE creator_id = :game_id");
                        $stmt_update_fouille->execute(['fouille' => $fouilles_json, 'game_id' => $game_id]);
    
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
                        
                        // Mettre à jour les cigarettes du joueur
                        $stmt_update_cigarette = $pdo->prepare("UPDATE joueurs SET cigarette = cigarette + :val_cigarette WHERE ID = :user_id");
                        $stmt_update_cigarette->execute(['val_cigarette' => 3, 'user_id' => $_SESSION['user_id']]);
                    
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

                        // Réduire le nombre d'actions
                        $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                        $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);

                        echo json_encode(['success' => false, 'sub_type' => $sub_type, 'deck' =>  $deck, 'item_name' => $item_name, 'defausse' => $defausse_json]);
                    } else {
                        echo json_encode(['success' => false, 'message' => "Plus de carte fouille"]);
                    }
                } else  if($sub_type == 2) { //CLEF DU GARDIEN OK
                    echo json_encode(['success' => false, 'message' => "Clef du gardien", 'sub_type' => $sub_type, 'item_name' => $item_name, 'player_data' => $row_players_infos ]);
                } else  if($sub_type == 3) { //TRANSFERT DE PRISONNIERS
                    echo json_encode(['success' => false, 'message' => "Transfert de prisonniers", 'sub_type' => $sub_type, 'item_name' => $item_name, 'player_data' => $row_players_infos ]);
                } else  if($sub_type == 4) { //FOUILLE AU CORPS OK
                    echo json_encode(['success' => false, 'message' => "Fouille au corps", 'sub_type' => $sub_type, 'item_name' => $item_name, 'player_data' => $row_players_infos ]);
                } else  if($sub_type == 5) { //ISOLEMENT OK
                    echo json_encode(['success' => false, 'message' => "Isolement", 'sub_type' => $sub_type, 'item_name' => $item_name, 'player_data' => $row_players_infos ]);
                } else  if($sub_type == 6) { //SAVONETTE OK
                    echo json_encode(['success' => false, 'message' => "Savonette", 'sub_type' => $sub_type, 'item_name' => $item_name, 'player_data' => $row_players_infos ]);
                } else  if($sub_type == 7) { //CLEF DES CELLULES OK
                    if(($team == "A" && $localisation !== 1) || ($team == "B" && $localisation !== 3)) {
                        // Récupérer les détails des fouilles
                        $stmt_fouille = $pdo->prepare("SELECT fouille_data FROM games WHERE creator_id = :game_id");
                        $stmt_fouille->execute(['game_id' => $game_id]);
                        $row_fouille = $stmt_fouille->fetch(PDO::FETCH_ASSOC);

                        if ($row_fouille && $row_fouille['fouille_data']) {
                            $fouilles = json_decode($row_fouille['fouille_data'], true);
                            //PRENDRE A CHAQUE FOIS LES PREMIERES CARTES DU TABLEAU FOUILLES ET LES METTRE DANS CHAQUE DECK
                            $fouilles_win = 1;
                            for ($i = 0; $i < $fouilles_win; $i++) {
                                if (!empty($fouilles)) {
                                    $deck[] = array_shift($fouilles);
                                }
                            }
                            $deck_json = json_encode($deck);
                            $fouilles_json = json_encode($fouilles);

                            // Mettre à jour les détails des fouilles
                            $stmt_update_fouille = $pdo->prepare("UPDATE games SET fouille_data = :fouille WHERE creator_id = :game_id");
                            $stmt_update_fouille->execute(['fouille' => $fouilles_json, 'game_id' => $game_id]);
        
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

                            if($team == "A") {
                                $localisation_player = 1;
                            } else {
                                $localisation_player = 3;
                            }

                            // Mettre à jour la localisation du joueur
                            $stmt_update_localisation = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE ID = :user_id");
                            $stmt_update_localisation->execute(['localisation' => $localisation_player, 'user_id' => $_SESSION['user_id']]);

                            // Mettre à jour le dice_data
                            $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET dice_data = :dice_data WHERE ID = :ID");
                            $stmt_update_deck->execute(['dice_data' => "", 'ID' => $_SESSION['user_id']]);

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
                            echo json_encode(['success' => false, 'sub_type' => $sub_type, 'deck' =>  $deck, 'item_name' => $item_name, 'defausse' => $defausse_json]);
                        } else {
                            echo json_encode(['success' => false, 'message' => "Plus de carte fouille"]);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous etes deja en cellule"]);
                    }

                } else  if($sub_type == 8) { //CLEF DES DOUCHES OK
                    if($localisation !== 2) {
                        // Récupérer les détails des fouilles
                        $stmt_fouille = $pdo->prepare("SELECT fouille_data FROM games WHERE creator_id = :game_id");
                        $stmt_fouille->execute(['game_id' => $game_id]);
                        $row_fouille = $stmt_fouille->fetch(PDO::FETCH_ASSOC);

                        if ($row_fouille && $row_fouille['fouille_data']) {
                            $fouilles = json_decode($row_fouille['fouille_data'], true);
                            //PRENDRE A CHAQUE FOIS LES PREMIERES CARTES DU TABLEAU FOUILLES ET LES METTRE DANS CHAQUE DECK
                            $fouilles_win = 2;
                            for ($i = 0; $i < $fouilles_win; $i++) {
                                if (!empty($fouilles)) {
                                    $deck[] = array_shift($fouilles);
                                }
                            }
                            $deck_json = json_encode($deck);
                            $fouilles_json = json_encode($fouilles);

                            // Mettre à jour les détails des fouilles
                            $stmt_update_fouille = $pdo->prepare("UPDATE games SET fouille_data = :fouille WHERE creator_id = :game_id");
                            $stmt_update_fouille->execute(['fouille' => $fouilles_json, 'game_id' => $game_id]);
        
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

                            // Mettre à jour le dice_data
                            $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET dice_data = :dice_data WHERE ID = :ID");
                            $stmt_update_deck->execute(['dice_data' => "", 'ID' => $_SESSION['user_id']]);


                            // Récupérer les détails des decks
                            $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                            $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                            $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);

                            $localisation_player = 2;

                            // Mettre à jour la localisation du joueur
                            $stmt_update_localisation = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE ID = :user_id");
                            $stmt_update_localisation->execute(['localisation' => $localisation_player, 'user_id' => $_SESSION['user_id']]);

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
                            echo json_encode(['success' => false, 'sub_type' => $sub_type, 'deck' =>  $deck, 'item_name' => $item_name, 'defausse' => $defausse_json]);
                        } else {
                            echo json_encode(['success' => false, 'message' => "Plus de carte fouille"]);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous etes deja dans les douches"]);
                    }
                } else  if($sub_type == 9) { //CLEF DU REFECTOIRE OK
                    if($localisation !== 5) {
                        // Récupérer les détails des cuilleres
                        $stmt_cuillere = $pdo->prepare("SELECT cuillere_data FROM games WHERE creator_id = :game_id");
                        $stmt_cuillere->execute(['game_id' => $game_id]);
                        $row_cuillere = $stmt_cuillere->fetch(PDO::FETCH_ASSOC);

                        if ($row_cuillere && $row_cuillere['cuillere_data']) {
                            $cuilleres = json_decode($row_cuillere['cuillere_data'], true);
                            //PRENDRE A CHAQUE FOIS LA PREMIERES CARTES DU TABLEAU CUILLERE ET LES METTRE DANS CHAQUE DECK
                            $cuilleres_win = 1;
                            for ($i = 0; $i < $cuilleres_win; $i++) {
                                if (!empty($cuilleres)) {
                                    $deck[] = array_shift($cuilleres);
                                }
                            }
                            $deck_json = json_encode($deck);
                            $cuilleres_json = json_encode($cuilleres);

                            // Mettre à jour les détails des cuilleres
                            $stmt_update_cuillere = $pdo->prepare("UPDATE games SET cuillere_data = :cuillere WHERE creator_id = :game_id");
                            $stmt_update_cuillere->execute(['cuillere' => $cuilleres_json, 'game_id' => $game_id]);
        
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
                            
                            $localisation_player = 5;

                            // Mettre à jour la localisation du joueur
                            $stmt_update_localisation = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE ID = :user_id");
                            $stmt_update_localisation->execute(['localisation' => $localisation_player, 'user_id' => $_SESSION['user_id']]);

                            // Mettre à jour le dice_data
                            $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET dice_data = :dice_data WHERE ID = :ID");
                            $stmt_update_deck->execute(['dice_data' => "", 'ID' => $_SESSION['user_id']]);

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
                            echo json_encode(['success' => false, 'sub_type' => $sub_type, 'deck' =>  $deck, 'item_name' => $item_name, 'defausse' => $defausse_json]);
                        } else {
                            echo json_encode(['success' => false, 'message' => "Plus de carte cuillere"]);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous etes deja dans le réfectoire"]);
                    }
                } else  if($sub_type == 10) { //CLEF DE L'INFIRMERIE OK
                    if($localisation !== 4) {
                        // Récupérer les détails des surins
                        $stmt_surin = $pdo->prepare("SELECT surin_data FROM games WHERE creator_id = :game_id");
                        $stmt_surin->execute(['game_id' => $game_id]);
                        $row_surin = $stmt_surin->fetch(PDO::FETCH_ASSOC);

                        if ($row_surin && $row_surin['surin_data']) {
                            $surins = json_decode($row_surin['surin_data'], true);
                            //PRENDRE A CHAQUE FOIS LA PREMIERES CARTES DU TABLEAU SURIN ET LES METTRE DANS CHAQUE DECK
                            $surins_win = 1;
                            for ($i = 0; $i < $surins_win; $i++) {
                                if (!empty($surins)) {
                                    $deck[] = array_shift($surins);
                                }
                            }
                            $deck_json = json_encode($deck);
                            $surins_json = json_encode($surins);

                            // Mettre à jour les détails des surins
                            $stmt_update_surin = $pdo->prepare("UPDATE games SET surin_data = :surin WHERE creator_id = :game_id");
                            $stmt_update_surin->execute(['surin' => $surins_json, 'game_id' => $game_id]);
        
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
                            
                            $localisation_player = 4;

                            // Mettre à jour la localisation du joueur
                            $stmt_update_localisation = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE ID = :user_id");
                            $stmt_update_localisation->execute(['localisation' => $localisation_player, 'user_id' => $_SESSION['user_id']]);

                            // Mettre à jour le dice_data
                            $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET dice_data = :dice_data WHERE ID = :ID");
                            $stmt_update_deck->execute(['dice_data' => "", 'ID' => $_SESSION['user_id']]);

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
                            echo json_encode(['success' => false, 'sub_type' => $sub_type, 'deck' =>  $deck, 'item_name' => $item_name, 'defausse' => $defausse_json]);
                        } else {
                            echo json_encode(['success' => false, 'message' => "Plus de carte surin"]);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous etes deja dans l'infirmerie"]);
                    }
                } else  if($sub_type == 11) { //CLEF DE LA PROMENADE OK
                    if($localisation !== 7) {
                            $deck_json = json_encode($deck);

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
                            
                            $localisation_player = 7;

                            // Mettre à jour la localisation du joueur
                            $stmt_update_localisation = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE ID = :user_id");
                            $stmt_update_localisation->execute(['localisation' => $localisation_player, 'user_id' => $_SESSION['user_id']]);

                            // Mettre à jour le dice_data
                            $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET dice_data = :dice_data WHERE ID = :ID");
                            $stmt_update_deck->execute(['dice_data' => "", 'ID' => $_SESSION['user_id']]);

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
                            
                            // Mettre à jour les cigarettes du joueur
                            $stmt_update_cigarette = $pdo->prepare("UPDATE joueurs SET cigarette = cigarette + :val_cigarette WHERE ID = :user_id");
                            $stmt_update_cigarette->execute(['val_cigarette' => 2, 'user_id' => $_SESSION['user_id']]);

                            // Réduire le nombre d'actions
                            $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                            $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                            echo json_encode(['success' => false, 'sub_type' => $sub_type, 'deck' =>  $deck, 'item_name' => $item_name, 'defausse' => $defausse_json]);
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous etes deja dans la promenade"]);
                    }
                } else  if($sub_type == 12) { //EMEUTE OK

                    //DEFAUSSER EMEUTE
                    $deck_json = json_encode($deck);


                    foreach ($deck as $index => $card) {
                        if (intval($card['ID']) == intval($item_name)) {
                            array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                            unset($deck[$index]); // Enlever la carte
                        }
                    }

                    $deck = json_encode(array_values($deck)); // Re-indexer le tableau
                    $defausse_json = json_encode($defausseData);

                    $stmt_update_deck_after_defausse = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :player_id");
                    $stmt_update_deck_after_defausse->execute(['deck' => $deck, 'player_id' =>  $_SESSION['user_id']]);

                    // Tableau pour stocker les decks de tous les joueurs
                    $all_cards = [];
                    
                    foreach($row_game_infos as $value){
                        // Récupérer les joueurs du jeu
                        $tab_player = json_decode($value["turn_data"]);

                        foreach($tab_player as $id_player){
                            // Récupérer le deck actuel du joueur
                            $stmt_get_deck_players = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :player_id");
                            $stmt_get_deck_players->execute(['player_id' => $id_player]);
                            $row_get_deck_players = $stmt_get_deck_players->fetch(PDO::FETCH_ASSOC);

                            // Vérification du résultat de la requête
                            if ($row_get_deck_players && $row_get_deck_players['deck']) {
                                // Convertir le deck en tableau
                                $deck_players = json_decode($row_get_deck_players['deck'], true) ?: [];
                                // Fusionner les cartes du deck actuel dans le tableau commun
                                $all_cards = array_merge($all_cards, $deck_players);
                            }
                        }
                    }

                    shuffle($all_cards);

                    // Trouver l'index de votre ID dans le tableau
                    $index = array_search($id, $tab_player);

                    if ($index !== false) {
                        // Réorganiser le tableau
                        $ordered_players = array_merge(array_slice($tab_player, $index), array_slice($tab_player, 0, $index));
                    }

                    // Tableau pour stocker les decks de tous les joueurs
                    $all_decks = [];

                    // Initialiser les decks de chaque joueur
                    foreach ($ordered_players as $player_id) {
                        $all_decks[$player_id] = [];
                    }

                    // Distribuer les cartes
                    $card_count = count($all_cards);
                    $player_count = count($ordered_players);

                    for ($i = 0; $i < $card_count; $i++) {
                        // Trouver le joueur actuel
                        $current_player = $ordered_players[$i % $player_count];
                        
                        // Ajouter la carte au deck du joueur actuel
                        $all_decks[$current_player][] = $all_cards[$i];
                    }

                    // Mettre à jour les decks des joueurs dans la base de données
                    foreach ($all_decks as $player_id => $deck) {
                        $deck_json = json_encode($deck);

                        $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :player_id");
                        $stmt_update_deck->execute(['deck' => $deck_json, 'player_id' => $player_id]);
                    }

                    // Récupérer les détails des decks
                    $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                    $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                    $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);

                    // Mettre à jour defausse_data du jeu
                    $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = :defausse WHERE creator_id = :game_id");
                    $stmt_update_defausse->execute(['defausse' => $defausse_json, 'game_id' => $game_id]);

                    // Réduire le nombre d'actions
                    $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                    $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);

                    echo json_encode(['success' => true, 'tab_player' => $tab_player, 'all_cards' => $all_cards, 'result' => $result, 'deck' =>  $deck, 'sub_type' => $sub_type, 'defausse' => $defausse_json]);
                }
            } else if($raclee == 0){
                if ($name_action == "make" && $item_name == "Pelle") {
                    // SI LE JOUEUR VEUT CONSTRUIRE UNE PELLE
                    if (($localisation == 4) || ($localisation == 5) || ($localisation == 7) || ($localisation == 3 && $team == "A") || ($localisation == 1 && $team == "B")) {
                        $hasRecipient = $hasLien = false; // Initialiser les variables
                
                        foreach ($deck as $index => $card) {
                            if ($card['name'] === 'Récipient' && $hasRecipient == false) {
                                $hasRecipient = true;
                                array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                                unset($deck[$index]); // Enlever le récipient
                            }
                            if ($card['name'] === 'Lien' && $hasLien == false) {
                                $hasLien = true;
                                array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                                unset($deck[$index]); // Enlever le lien
                            }
                        }
                
                        if ($hasRecipient && $hasLien) {
                            $deck = array_values($deck);
                            $defausse_json = json_encode($defausseData);
                
                            $stmt_pelle = $pdo->prepare("SELECT pelle_data FROM games WHERE creator_id = :game_id");
                            $stmt_pelle->execute(['game_id' => $game_id]);
                            $row_pelle = $stmt_pelle->fetch(PDO::FETCH_ASSOC);
                
                            if ($row_pelle && $row_pelle['pelle_data']) {
                                $pelles = json_decode($row_pelle['pelle_data'], true);
                                if (!empty($pelles)) {
                                    $deck[] = array_shift($pelles); // Ajouter une pelle au deck
                                }
                                $deck_json = json_encode($deck);
                                $pelles_json = json_encode($pelles);
                
                                // Mettre à jour les détails des pelles
                                $stmt_update_pelle = $pdo->prepare("UPDATE games SET pelle_data = :pelle WHERE creator_id = :game_id");
                                $stmt_update_pelle->execute(['pelle' => $pelles_json, 'game_id' => $game_id]);
                
                                // Mettre à jour defausse_data du jeu
                                $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = :defausse WHERE creator_id = :game_id");
                                $stmt_update_defausse->execute(['defausse' => $defausse_json, 'game_id' => $game_id]);
                
                                // Mettre à jour le deck du joueur
                                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                                $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);
                
                                // Réduire le nombre d'actions
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                
                                echo json_encode(['success' => true, 'message' => 'Vous avez fabriqué une Pelle.', 'make' => true]);
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Erreur de récupération des données de Pelle.']);
                            }
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les objets nécessaires pour fabriquer une Pelle.']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas fabriquer ici."]);
                    }
                } elseif ($name_action == "make" && $item_name == "Pioche"){
                    if (($localisation == 4) || ($localisation == 5) || ($localisation == 7) || ($localisation == 3 && $team == "A") || ($localisation == 1 && $team == "B")) {
                        foreach ($deck as $index => $card) {
                            if ($card['name'] === 'Tournevis' && $hasTournevis == false) {
                                $hasTournevis = true;
                                array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                                unset($deck[$index]); // Enlever le tournevis
                            }
                            if ($card['name'] === 'Lien' && $hasLien == false) {
                                $hasLien = true;
                                array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                                unset($deck[$index]); // Enlever le lien
                            }
                        }
                        if ($hasTournevis && $hasLien) {
                            $deck = array_values($deck);
                            $defausse_json = json_encode($defausseData);

                            $stmt_pioche = $pdo->prepare("SELECT pioche_data FROM games WHERE creator_id = :game_id");
                            $stmt_pioche->execute(['game_id' => $game_id]);
                            $row_pioche = $stmt_pioche->fetch(PDO::FETCH_ASSOC);

                            if ($row_pioche && $row_pioche['pioche_data']) {
                                $pioches = json_decode($row_pioche['pioche_data'], true);
                                // METTRE UNE PIOCHE DU PLATEAU AU DECK
                                if (!empty($pioches)) {
                                    $deck[] = array_shift($pioches);
                                }
                                $deck_json = json_encode($deck);
                                $pioches_json = json_encode($pioches);

                                // Mettre à jour les détails des pioches
                                $stmt_update_pioche = $pdo->prepare("UPDATE games SET pioche_data = :pioche WHERE creator_id = :game_id");
                                $stmt_update_pioche->execute(['pioche' => $pioches_json, 'game_id' => $game_id]);

                                // Mettre à jour defausse_data du jeu
                                $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = :defausse WHERE creator_id = :game_id");
                                $stmt_update_defausse->execute(['defausse' => $defausse_json, 'game_id' => $game_id]);

                                // Mettre à jour le deck du joueur
                                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                                $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);

                                // Récupérer les détails des decks
                                $stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
                                $stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
                                $row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($row_deck as $row_decks) {
                                    if ($row_decks) {
                                        $decks = json_decode($row_decks['deck'], true);
                                    } else {
                                        echo "Les decks ne sont pas encore disponibles.";
                                    }
                                }
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action -1 WHERE `ID` = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                                echo json_encode(['success' => true, 'message' => 'Vous avez fabriqué une Pioche.', 'make' => true]);
                            }
                            echo json_encode(['success' => true, 'message' => 'Vous avez les deux objets nécessaires.']);
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les objets nécessaires.']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas fabriquer ici"]);
                    }
                } elseif ($name_action == "make" && $item_name == "Surin") {
                    // SI LE JOUEUR VEUT CONSTRUIRE DEUX SURINS
                    if (($localisation == 4) || ($localisation == 5) || ($localisation == 7) || ($localisation == 3 && $team == "A") || ($localisation == 1 && $team == "B")) {
                        $hasTournevis = $hasLien = false; // Initialiser les variables
                
                        foreach ($deck as $index => $card) {
                            if ($card['name'] === 'Lame' && $hasLame == false) {
                                $hasLame = true;
                                array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                                unset($deck[$index]); // Enlever le tournevis
                            }
                            if ($card['name'] === 'Lien' && $hasLien == false) {
                                $hasLien = true;
                                array_unshift($defausseData, $card); // Ajouter la carte au début de defausse_data
                                unset($deck[$index]); // Enlever le lien
                            }
                        }
                
                        if ($hasLame && $hasLien) {
                            $deck = array_values($deck);
                            $defausse_json = json_encode($defausseData);
                
                            $stmt_surin = $pdo->prepare("SELECT surin_data FROM games WHERE creator_id = :game_id");
                            $stmt_surin->execute(['game_id' => $game_id]);
                            $row_surin = $stmt_surin->fetch(PDO::FETCH_ASSOC);
                
                            if ($row_surin && $row_surin['surin_data']) {
                                $surins = json_decode($row_surin['surin_data'], true);
                                if (!empty($surins)) {
                                    for($i=0;$i < 2; $i++){
                                        $deck[] = array_shift($surins); // Ajouter deux surins au deck
                                    }
                                }
                                $deck_json = json_encode($deck);
                                $surins_json = json_encode($surins);
                
                                // Mettre à jour les détails des surins
                                $stmt_update_surin = $pdo->prepare("UPDATE games SET surin_data = :surin WHERE creator_id = :game_id");
                                $stmt_update_surin->execute(['surin' => $surins_json, 'game_id' => $game_id]);
                
                                // Mettre à jour defausse_data du jeu
                                $stmt_update_defausse = $pdo->prepare("UPDATE games SET defausse_data = :defausse WHERE creator_id = :game_id");
                                $stmt_update_defausse->execute(['defausse' => $defausse_json, 'game_id' => $game_id]);
                
                                // Mettre à jour le deck du joueur
                                $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
                                $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $_SESSION['user_id']]);
                
                                // Réduire le nombre d'actions
                                $stmt_update_nb_action = $pdo->prepare("UPDATE joueurs SET nb_action = nb_action - 1 WHERE ID = :user_id");
                                $stmt_update_nb_action->execute(['user_id' => $_SESSION['user_id']]);
                
                                echo json_encode(['success' => true, 'message' => 'Vous avez fabriqué deux Surins.', 'make' => true]);
                            } else {
                                echo json_encode(['success' => false, 'message' => 'Erreur de récupération des données de Surin.']);
                            }
                        } else {
                            echo json_encode(['success' => false, 'message' => 'Vous n\'avez pas les objets nécessaires pour fabriquer deux Surins.']);
                        }
                    } else {
                        echo json_encode(['success' => false, 'message' => "Vous ne pouvez pas fabriquer ici."]);
                    }
                }
            } else {
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
