<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_localisation') {
    try {
        $stmt_info_joueur = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :player_id");
        $stmt_info_joueur->execute(['player_id' => $_SESSION['user_id']]);
        $row_info_joueur = $stmt_info_joueur->fetch(PDO::FETCH_ASSOC);

        $zone = intval($_POST['zone']);
        $turn_dice = json_decode($_POST['turn_dice']);
        $game_id = $_POST['game_id'];
        $last_localisation = $row_info_joueur['last_localisation'];
        $localisation = $row_info_joueur['localisation'];
        $dice_data = $row_info_joueur['dice_data'];

        if((intval($zone) !== intval($last_localisation)) && ($last_localisation == $localisation)){
            $diceMapping = [
                'zones' => [
                    1 => [1, 2, 3],
                    2 => [5, 6],
                    3 => [1, 2, 3],
                    4 => [3, 5],
                    5 => [1, 4],
                    7 => [2, 4, 6],
                ],
                'fouilles' => [
                    1 => [1],
                    2 => [3],
                    3 => [1],
                    4 => [1],
                    5 => [2],
                    7 => [2],
                ],
            ];
            // if($row_info_joueur['nb_action'] > 0){
            if($dice_data !== "" && $localisation == $last_localisation){
                if(array_search(intval($turn_dice[0]) , $diceMapping['zones'][$zone]) !== false){
                    $stmt_update_localisation_to_players = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE `ID` = :player_id");
                    $stmt_update_localisation_to_players->execute(['localisation' => $zone, 'player_id' => $_SESSION['user_id']]);
    
                    // Récupérer le deck actuel du joueur
                    $stmt_get_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :player_id");
                    $stmt_get_deck->execute(['player_id' => $_SESSION['user_id']]);
                    $row_deck = $stmt_get_deck->fetch(PDO::FETCH_ASSOC);
                    $deck = json_decode($row_deck['deck'], true) ?: [];
    
                    // Récupérer les détails des fouilles
                    $stmt_fouille = $pdo->prepare("SELECT fouille_data FROM games WHERE creator_id = :game_id");
                    $stmt_fouille->execute(['game_id' => $game_id]);
                    $row_fouille = $stmt_fouille->fetch(PDO::FETCH_ASSOC);
    
                    if ($row_fouille && $row_fouille['fouille_data']) {
                        $fouilles = json_decode($row_fouille['fouille_data'], true);
                        //PRENDRE A CHAQUE FOIS LES PREMIERES CARTES DU TABLEAU FOUILLES ET LES METTRE DANS CHAQUE DECK
                        $fouilles_win = $diceMapping['fouilles'][$zone];
                        for ($i = 0; $i < $fouilles_win[0]; $i++) {
                            if (!empty($fouilles)) {
                                $deck[] = array_shift($fouilles);
                            }
                        }
                        $deck_json = json_encode($deck);
                        $fouilles_json = json_encode($fouilles);
    
                        // Mettre à jour les détails des fouilles
                        $stmt_update_fouille = $pdo->prepare("UPDATE games SET fouille_data = :fouille WHERE creator_id = :game_id");
                        $stmt_update_fouille->execute(['fouille' => $fouilles_json, 'game_id' => $game_id]);
    
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
                    }
    
                    echo json_encode(['success' => true, 'zone' =>  $zone, 'last_localisation' =>  $last_localisation, 'turn_dice' =>  $turn_dice[0], 'fouilles_win' =>  $fouilles_win, 'deck' =>  $deck]);
                    
                } else {
                    echo json_encode(['success' => false, 'message' => "Ne peux pas allez ici"]);
                }
            } elseif($dice_data == "" && $localisation == $last_localisation) {
                echo json_encode(['success' => false, 'message' => "Vous devez d'abord lancer le dé"]);
            } else {
                echo json_encode(['success' => false, 'message' => "Vous n'avez pas d'action disponible"]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => "Ne peux pas allez ici"]);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
