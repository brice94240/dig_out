<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'], $_POST['user_id'], $_POST['message'])) {
    $game_id = intval($_POST['game_id']);
    $user_id = intval($_POST['user_id']);
    $message = trim($_POST['message']);

    if ($message === '') {
        $response = ['success' => false, 'message' => 'Message vide'];
        echo json_encode($response);
        exit;
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO chat_room (game_id, user_id, message, timestamp) VALUES (:game_id, :user_id, :message, NOW())");
        $stmt->execute([
            'game_id' => $game_id,
            'user_id' => $user_id,
            'message' => $message
        ]);

        $response = ['success' => true];
        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()];
        echo json_encode($response);
        exit;
    }
} else {
    $response = ['success' => false, 'message' => 'Requête non autorisée'];
    echo json_encode($response);
    exit;
}
?>
