<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_deck') {
    try {
        // Récupérer le deck actuel du joueur
        $stmt_get_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE ID = :player_id");
        $stmt_get_deck->execute(['player_id' => $_SESSION['user_id']]);
        $row_deck = $stmt_get_deck->fetch(PDO::FETCH_ASSOC);
        $deck = json_decode($row_deck['deck'], true) ?: [];
        
        echo json_encode(['success' => true, 'deck' =>  $deck]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => "Erreur lors de la récupération des parties : " . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
