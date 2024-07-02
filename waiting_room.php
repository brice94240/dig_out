<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
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
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
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
        <div><strong>Joueurs:</strong> <span id="player-count"><?php echo count($players); ?></span> / <?php echo $game['max_player']; ?></div>
        <div><strong>Cartes max:</strong> <?php echo htmlspecialchars($game['max_cards']); ?></div>
        <div><strong>Code:</strong> <?php echo htmlspecialchars($game['code']) ? 'Oui' : 'Non'; ?></div>
        <div><strong>Créateur:</strong> <?php echo htmlspecialchars($game['pseudo']); ?></div>
    </div>

    <div class="launch-button-container">
        <?php if ($game['creator_id'] == $_SESSION['user_id'] && count($players) == $game['max_player']) { ?>
            <form class="form_launch_game" method="post" action="">
                <input type="hidden" name="launch_game" value="1">
                <button type="submit" id="launch_button" <?php if (count($players) != $game['max_player']) echo 'disabled'; ?>>Lancer la partie</button>
            </form>
        <?php
        } else if($game['creator_id'] == $_SESSION['user_id'] && count($players) !== $game['max_player']) { ?>
            <form class="form_launch_game_desactivate" method="post" action="">
                <input type="hidden" name="launch_game" value="1">
                <button type="submit" id="launch_button" <?php if (count($players) != $game['max_player']) echo 'disabled'; ?>>Lancer la partie</button>
            </form>
        <?php
        }?>
    </div>

    <div class="back-button">
        <form method="post" action="">
            <input type="hidden" name="leave_game" value="1">
            <button type="submit" id="back_button">Retour à la liste des parties</button>
        </form>
    </div>

    <script>
        function loadGameDetails() {
            $.ajax({
                url: 'waiting_room_ajax.php',
                type: 'POST',
                data: { action: 'get_game_details', game_id: <?php echo $game_id; ?> },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#player-count').text(response.players.length);
                        if (response.game.creator_id == <?php echo $_SESSION['user_id']; ?>) {
                            $('#launch_button').prop('disabled', response.players.length != response.game.max_player);
                        }

                        var playersHtml = '';
                        response.players.forEach(function(player) {
                            playersHtml += '<div class="player">' + player.pseudo + '</div>';
                        });
                        $('.players-container').html(playersHtml);
                    } else {
                        console.log('Erreur : ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Erreur AJAX : ' + error);
                }
            });
        }

        $(document).ready(function() {
            setInterval(loadGameDetails, 2000); // Répéter toutes les 2 secondes

            $('#launch_button').click(function(event) {
                event.preventDefault(); // Empêcher le rechargement de la page
                $.ajax({
                    url: 'waiting_room_ajax.php',
                    type: 'POST',
                    data: { action: 'launch_game', game_id: <?php echo $game_id; ?> },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success && response.redirect) {
                            window.location.href = response.redirect;
                        } else {
                            console.log('Erreur : ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Erreur AJAX : ' + error);
                    }
                });
            });

            $('#back_button').click(function(event) {
                event.preventDefault(); // Empêcher le rechargement de la page
                $.ajax({
                    url: 'waiting_room_ajax.php',
                    type: 'POST',
                    data: { action: 'leave_game', game_id: <?php echo $game_id; ?> },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = './room.php';
                        } else {
                            console.log('Erreur : ' + response.message);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.log('Erreur AJAX : ' + error);
                    }
                });
            });

            loadGameDetails(); // Charger les détails de la partie au chargement initial
        });
    </script>
</body>
</html>
