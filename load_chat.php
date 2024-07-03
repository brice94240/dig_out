<?php

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'])) {
    $game_id = intval($_POST['game_id']);
    try {
        // $stmt = $pdo->prepare("SELECT * FROM chat_room INNER JOIN joueurs ON joueurs.game_joined = chat_room.game_id WHERE chat_room.game_id = :game_id ORDER BY chat_room.timestamp ASC");
        $stmt = $pdo->prepare("SELECT `message`, `pseudo`, `timestamp` FROM chat_room c INNER JOIN joueurs j ON c.user_id = j.id WHERE c.game_id = :game_id ORDER BY c.timestamp ASC");
        $stmt->execute(['game_id' => $game_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $response = [
            'success' => true,
            'messages' => $messages
        ];

        header('Content-Type: application/json');
        echo json_encode($response);
        exit;
    } catch (PDOException $e) {
        $response = ['success' => false, 'message' => 'Erreur de base de données : ' . $e->getMessage()];
        echo json_encode($response);
        exit;
    }
}