<?php
session_start();
require_once 'config.php';

$response = ['success' => false, 'message' => '', 'game' => [], 'players' => []];

if (!isset($_SESSION['user_id'])) {
    $response['message'] = 'Utilisateur non connecté.';
    echo json_encode($response);
    exit;
}

if (!isset($_POST['action'])) {
    $response['message'] = 'Action non spécifiée.';
    echo json_encode($response);
    exit;
}

$action = $_POST['action'];
$game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : null;

if ($action === 'get_game_details' && $game_id) {
    try {
        $stmt_game = $pdo->prepare("SELECT * FROM games INNER JOIN joueurs ON games.creator_id = joueurs.id WHERE games.ID = :game_id");
        $stmt_game->execute(['game_id' => $game_id]);
        $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

        $stmt_players = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id");
        $stmt_players->execute(['game_id' => $game_id]);
        $players = $stmt_players->fetchAll(PDO::FETCH_ASSOC);

        $response['success'] = true;
        $response['game'] = $game;
        $response['players'] = $players;
    } catch (PDOException $e) {
        $response['message'] = "Erreur lors de la récupération des données : " . $e->getMessage();
    }
} elseif ($action === 'launch_game' && $game_id) {
    try {
        $stmt = $pdo->prepare("UPDATE games SET launched = 1 WHERE creator_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);

        $response['success'] = true;
        $response['redirect'] = './game.php?id=' . $game_id;
    } catch (PDOException $e) {
        $response['message'] = "Erreur lors de la mise à jour de la partie : " . $e->getMessage();
    }
} elseif ($action === 'leave_game') {
    try {
        $stmt = $pdo->prepare("UPDATE joueurs SET game_joined = NULL WHERE ID = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);

        $response['success'] = true;
    } catch (PDOException $e) {
        $response['message'] = "Erreur lors de la mise à jour de la partie : " . $e->getMessage();
    }
}

echo json_encode($response);
?>
