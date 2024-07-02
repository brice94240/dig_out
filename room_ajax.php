<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_games') {
    try {
        $stmt = $pdo->query("SELECT * FROM games INNER JOIN joueurs ON joueurs.ID = games.creator_id");
        $games = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '';
        foreach ($games as $game) {
            // Récupération du nombre de joueurs dans la partie
            $joueurs_in_game = $pdo->query("SELECT COUNT(*) as 'players_in_game' FROM joueurs WHERE joueurs.game_joined = " . $game['ID']);
            $count_joueurs_in_game = $joueurs_in_game->fetch(PDO::FETCH_ASSOC);

            // Construction de l'élément HTML pour chaque jeu
            $html .= '<div class="game">';
            $html .= '<div>' . htmlspecialchars($game['name']) . '</div>';
            $html .= '<div>' . $game['points'] . '</div>';
            $html .= '<div>' . ($game['team_activated'] ? 'Oui' : 'Non') . '</div>';
            $html .= '<div>' . $count_joueurs_in_game['players_in_game'] . '/' . $game['max_player'] . '</div>';
            $html .= '<div>' . $game['max_cards'] . '</div>';
            $html .= '<div>' . (htmlspecialchars($game['code']) ? 'Oui' : 'Non') . '</div>';
            $html .= '<div>' . $game['pseudo'] . '</div>';
            $html .= '<form class="form_join" method="post" action="room.php">';
            $html .= '<input type="hidden" name="game_id" value="' . $game['ID'] . '">';
            $html .= '<button type="submit" name="join_game" class="join-button">Rejoindre</button>';
            $html .= '</form>';
            if ($game['creator_id'] == $_SESSION['user_id']) {
                $html .= '<form class="form_join" method="post" action="room.php">';
                $html .= '<input type="hidden" name="game_id" value="' . $game['ID'] . '">';
                $html .= '<button type="submit" name="delete_game" class="delete-button">X</button>';
                $html .= '</form>';
            }
            $html .= '</div>';
        }

        echo json_encode(['success' => true, 'html' => $html]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
