<?php
// Inclure les configurations et initialisations nécessaires
require_once 'config.php';

// Vérifier la requête AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'])) {
    $game_id = $_POST['game_id'];

    try {
        // Récupérer les détails de la partie
        $stmt_game = $pdo->prepare("SELECT * FROM games INNER JOIN joueurs ON games.creator_id = joueurs.id WHERE games.creator_id = :game_id");
        $stmt_game->execute(['game_id' => $game_id]);
        $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

        // // Récupérer le nombre de joueurs dans la partie
        // $stmt_joueurs = $pdo->prepare("SELECT COUNT(*) AS player_count FROM joueurs WHERE game_joined = :game_id");
        // $stmt_joueurs->execute(['game_id' => $game_id]);
        // $joueurs = $stmt_joueurs->fetch(PDO::FETCH_ASSOC);


        $stmt_joueurs = $pdo->prepare("SELECT pseudo FROM joueurs WHERE game_joined = :game_id");
        $stmt_joueurs->execute(['game_id' => $game_id]);
        $joueurs = $stmt_joueurs->fetchAll(PDO::FETCH_ASSOC);
        $player_count = count($joueurs);

        // $stmt_players = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id");
        // $stmt_players->execute(['game_id' => $game_id]);
        // $players = $stmt_players->fetchAll(PDO::FETCH_ASSOC);
        // $response['players_name'] = $players;



        // Vérifier si le nombre maximum de joueurs est atteint
        $max_players_reached = ($player_count == $game['max_player']) ? 1 : 0;

        // Préparer la réponse JSON
        $response = [
            'success' => true,
            'game' => [
                'name' => $game['name'],
                'points' => $game['points'],
                'team_activated' => $game['team_activated'],
                'max_cards' => $game['max_cards'],
                'code' => $game['code'],
                'pseudo' => $game['pseudo'],
                'game_id' => $game_id, // Utilisation de $game_id plutôt que $game['game_id'] si c'est l'ID que vous voulez
                'players' => $player_count, // Nombre de joueurs dans la partie
                'max_player' => $game['max_player'], // Nombre maximal de joueurs autorisés
                'max_players_reached' => $max_players_reached, // Indicateur si le nombre maximum de joueurs est atteint
                'players_name' => $joueurs
            ],
        ];

        // Envoyer la réponse JSON
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } catch (PDOException $e) {
        // Gérer les erreurs de base de données
        $response = ['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()];
        echo json_encode($response);
        exit;
    }
} else {
    // Répondre à toute autre demande incorrecte
    $response = ['success' => false, 'message' => 'Requête non autorisée'];
    echo json_encode($response);
    exit;
}
?>
