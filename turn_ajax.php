<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_turn') {
    try {
        $stmt = $pdo->query("SELECT * FROM games INNER JOIN joueurs ON joueurs.ID = games.creator_id");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $turn = $row['turn'];
        if(intval($turn) !== intval($_POST['turn'])) {
            echo json_encode(['success' => true, 'turn' => $turn, 'new_turn' => '1', 'last_turn' => $_POST['turn']]);
        } else {
            echo json_encode(['success' => true, 'turn' => $turn, 'new_turn' => '0', 'last_turn' => $_POST['turn']]);
        }

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
