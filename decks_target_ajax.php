<?php
session_start();

require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_deck_target') {
    $target = $_POST['response'];
    echo json_encode(['success' => true, 'target' =>  $target]);
} else {
    echo json_encode(['success' => false, 'message' => "Action non valide"]);
}
?>
