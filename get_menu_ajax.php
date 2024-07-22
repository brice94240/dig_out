<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_menu') {
    try {
        $target_id = $_POST['id'];
        // Récupérer le turn actuel du joueur
        $stmt_get_info_target = $pdo->prepare("SELECT * FROM joueurs WHERE id = :target_id");
        $stmt_get_info_target->execute(['target_id' => $target_id]);
        $row_get_info_target = $stmt_get_info_target->fetch(PDO::FETCH_ASSOC);

        $stmt_get_info = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :user_id");
        $stmt_get_info->execute(['user_id' => $_SESSION['user_id']]);
        $row_get_info = $stmt_get_info->fetch(PDO::FETCH_ASSOC);

        $stmt = $pdo->query("SELECT * FROM games INNER JOIN joueurs ON joueurs.ID = games.creator_id");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if($row_get_info_target['ID'] == $row_get_info['ID']){
            echo json_encode(['success' => false, 'message' => "Vous ne pouvez ouvrir votre propre menu"]);
        } else {
            $deck_target_number = count(json_decode($row_get_info_target['deck']));
            $raclee = $row_get_info_target['raclee'];
            $cigarette = $row_get_info_target['cigarette'];
            $localisation = $row_get_info_target['localisation'];
            $team = $row_get_info_target['team'];
            $pseudo = $row_get_info_target['pseudo'];
            $can_fight = false;
            $deck_target = "";
            if($localisation == $row_get_info['localisation']){
                if($team == $row_get_info['team']){
                    $deck_target = json_decode($row_get_info_target['deck']);
                } else { 
                    if($row['turn'] >= 2) {
                        $real_turn = $row['turn']-2;
                        if($real_turn >= $row['max_player']){
                            $real_turn = $real_turn % $row['max_player'];
                        }
                        $player_tab = json_decode($row['turn_data'], true);
                        $player_turn_id = $player_tab[$real_turn];
                    }

                    if($player_turn_id == $row_get_info['ID']) {
                        if($row_get_info['nb_action'] > 0){
                            $can_fight = true;
                        }
                    }
                }
            }
            echo json_encode(['success' => true, 'deck_target_number' =>  $deck_target_number, 'raclee' =>  $raclee, 'cigarette' =>  $cigarette, 'localisation' =>  $localisation, 'team' =>  $team, 'can_fight' =>  $can_fight, 'deck_target' =>  $deck_target, 'pseudo' =>  $pseudo]);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
