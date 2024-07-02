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

// Gérer la requête de retour pour lancer la partie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['launch_game'])) {
    try {
        // Mettre à jour la colonne game_joined pour l'utilisateur connecté à NULL
        $stmt = $pdo->prepare("UPDATE games SET launched = 1 WHERE creator_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        
        // Rediriger vers la liste des parties
        header("Location: ./game.php");
        exit;
    } catch (PDOException $e) {
        echo "Erreur lors de la mise à jour de la partie : " . $e->getMessage();
    }
}

// Gérer la requête de retour pour quitter la partie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_game'])) {
    try {
        // Mettre à jour la colonne game_joined pour l'utilisateur connecté à NULL
        $stmt = $pdo->prepare("UPDATE joueurs SET game_joined = NULL WHERE ID = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        
        // Rediriger vers la liste des parties
        header("Location: ./room.php");
        exit;
    } catch (PDOException $e) {
        echo "Erreur lors de la mise à jour de la partie : " . $e->getMessage();
    }
}

try {
    // Récupérer les détails de la partie
    $stmt_game = $pdo->prepare("SELECT * FROM games INNER JOIN joueurs ON games.creator_id = joueurs.id WHERE creator_id = :game_id");
    $stmt_game->execute(['game_id' => $game_id]);
    $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

    // Récupérer les joueurs qui ont rejoint cette partie
    $stmt_players = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id");
    $stmt_players->execute(['game_id' => $game_id]);
    $players = $stmt_players->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des données : " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./css/waiting_room.css" rel="stylesheet" />
    <title>Room - <?php echo htmlspecialchars($game['name']); ?></title>
</head>
<body>
    <h1>Bienvenue dans la partie "<?php echo htmlspecialchars($game['name']); ?>"</h1>
    <a href="logout.php">Se déconnecter</a>

    <h2>Liste des joueurs</h2>
    <div class="players-container">
        <?php foreach ($players as $player): ?>
            <div class="player"><?php echo htmlspecialchars($player['pseudo']); ?></div>
        <?php endforeach; ?>
    </div>

    <h2>Détails de la partie</h2>
    <div class="game-details">
        <div><strong>Nom de la partie:</strong> <?php echo htmlspecialchars($game['name']); ?></div>
        <div><strong>Points:</strong> <?php echo htmlspecialchars($game['points']); ?></div>
        <div><strong>Équipe activée:</strong> <?php echo $game['team_activated'] ? 'Oui' : 'Non'; ?></div>
        <div><strong>Joueurs:</strong> <?php echo count($players) . "/" . $game['max_player']; ?></div>
        <div><strong>Cartes max:</strong> <?php echo htmlspecialchars($game['max_cards']); ?></div>
        <div><strong>Code:</strong> <?php echo htmlspecialchars($game['code']) ? 'Oui' : 'Non'; ?></div>
        <div><strong>Créateur:</strong> <?php echo htmlspecialchars($game['pseudo']); ?></div>
    </div>

    <?php
    if($game['creator_id'] == $_SESSION['user_id']){?>
        <form class='form_launch_game' method="post" action="">
            <input type="hidden" name="launch_game" value="1">
            <button type="submit" name="launch_button">Lancer la partie</button>
        </form>
    <?php
    }
    ?>

    <div class="back-button">
        <form method="post" action="">
            <input type="hidden" name="leave_game" value="1">
            <button type="submit" name="back_button">Retour à la liste des parties</button>
        </form>
    </div>
</body>
</html>
