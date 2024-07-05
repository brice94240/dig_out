<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'])) {
    $game_id = $_POST['game_id'];

    try {
        // Récupérer les détails de la partie
        $stmt_game = $pdo->prepare("SELECT * FROM games INNER JOIN joueurs ON games.creator_id = joueurs.id WHERE games.creator_id = :game_id");
        $stmt_game->execute(['game_id' => $game_id]);
        $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

        // Récupérer les joueurs dans la partie
        $stmt_joueurs = $pdo->prepare("SELECT pseudo, team FROM joueurs WHERE game_joined = :game_id");
        $stmt_joueurs->execute(['game_id' => $game_id]);
        $joueurs = $stmt_joueurs->fetchAll(PDO::FETCH_ASSOC);
        $player_count = count($joueurs);

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
                'game_id' => $game_id,
                'players' => $player_count,
                'max_player' => $game['max_player'],
                'max_players_reached' => $max_players_reached,
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
