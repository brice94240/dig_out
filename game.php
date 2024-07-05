<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: ./?login");
    exit;
}

require_once 'config.php';

// Vérifier si game_id est passé en paramètre d'URL
if (!isset($_GET['game_id'])) {
    echo "Identifiant de partie manquant.";
    exit;
}

$game_id = intval($_GET['game_id']);

// Récupérer les détails de la partie et les joueurs
try {
    $stmt_game = $pdo->prepare("SELECT * FROM games WHERE creator_id = :game_id");
    $stmt_game->execute(['game_id' => $game_id]);
    $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

    // Déterminer le nombre de pièces en fonction de team_activated
    $piece_count = $game['team_activated'] ? 7 : 6;

    $stmt_players = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id");
    $stmt_players->execute(['game_id' => $game_id]);
    $players = $stmt_players->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des données : " . $e->getMessage();
    exit;
}

// Tableaux pour stocker les gangs
$gangs = array(); // Tableau principal pour tous les gangs
$gang1 = array();
$gang2 = array();
$gang3 = array();
$gang4 = array();
$gang5 = array();
$gang6 = array();

// Récupérer tous les gangs de la base de données
try {
    $stmt = $pdo->query("SELECT * FROM gangs");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Parcourir les résultats et organiser par gang_id
    foreach ($result as $row) {
        $gang_id = $row['gang_id'];
        switch ($gang_id) {
            case 1:
                $gang1[] = $row;
                break;
            case 2:
                $gang2[] = $row;
                break;
            case 3:
                $gang3[] = $row;
                break;
            case 4:
                $gang4[] = $row;
                break;
            case 5:
                $gang5[] = $row;
                break;
            case 6:
                $gang6[] = $row;
                break;
            default:
                break;
        }
    }

    // Stocker tous les gangs dans un tableau principal
    $gangs = array(
        'gang1' => $gang1,
        'gang2' => $gang2,
        'gang3' => $gang3,
        'gang4' => $gang4,
        'gang5' => $gang5,
        'gang6' => $gang6,
    );

} catch (PDOException $e) {
    echo "Erreur lors de la récupération des gangs : " . $e->getMessage();
    exit;
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="css/game.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <title>Game - <?php echo htmlspecialchars($game['name']); ?></title>
</head>
<body>
    <?php
    // Déterminer le schéma en fonction de $piece_count
    if ($piece_count == 7) {
        $schema = array(3, 2, 2);
    } elseif ($piece_count == 6) {
        $schema = array(2, 2, 2);
    } else {
        // Gérer le cas où $piece_count n'est ni 7 ni 6, par exemple avec une erreur ou une autre logique de traitement
        echo "Nombre de pièces non géré.";
        exit;
    }

    // Générer dynamiquement les pièces en fonction de $schema
    $piece_ids = array_map(function($i) {
        return "piece" . $i;
    }, range(1, $piece_count));
    ?>
    <?php
    var_dump($gangs['gang1'][0]['name']);
    ?>
    <div class="gang-container">
        <div class="gang">
            <div class="gang-carte"><?php echo $gangs['gang1'][0]['name']?></div>
            <div class="gang-carte"><?php echo $gangs['gang2'][0]['name']?></div>
            <div class="gang-carte"><?php echo $gangs['gang3'][0]['name']?></div>
            <div class="gang-carte"><?php echo $gangs['gang4'][0]['name']?></div>
            <div class="gang-carte"><?php echo $gangs['gang5'][0]['name']?></div>
            <div class="gang-carte"><?php echo $gangs['gang6'][0]['name']?></div>
        </div>
    </div>
    <div class="game-container">
        <div id="game-board">
        <?php foreach ($schema as $row_size): ?>
            <div class="row">
                <?php for ($i = 0; $i < $row_size; $i++): ?>
                    <div id="<?php echo $piece_ids[$i]; ?>" class="piece"></div>
                <?php endfor; ?>
                <?php $piece_ids = array_slice($piece_ids, $row_size); ?>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>

<script>
$(document).ready(function() {
    // JavaScript pour gérer les interactions du jeu
    function initializeGameBoard() {
        // Initialisation du plateau de jeu si nécessaire
    }

    // Initialiser le plateau de jeu
    initializeGameBoard();
});
</script>
