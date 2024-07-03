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
        $stmt = $pdo->prepare("UPDATE games SET launched = 1 WHERE creator_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        
        // Rediriger vers la liste des parties
        header("Location: ./game.php?id=".$game_id);
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

    $stmt_game = $pdo->prepare("SELECT * FROM games INNER JOIN joueurs ON games.creator_id = joueurs.id WHERE games.creator_id = :game_id");
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
        <div><strong>Nom de la partie:</strong> <span id="game-name"><?php echo htmlspecialchars($game['name']); ?></span></div>
        <div><strong>Points:</strong> <span id="game-points"><?php echo htmlspecialchars($game['points']); ?></span></div>
        <div><strong>Équipe activée:</strong> <span id="game-team"><?php echo $game['team_activated'] ? 'Oui' : 'Non'; ?></span></div>
        <div><strong>Joueurs:</strong> <span id="game-players"><?php echo $players . "/" . $game['max_player']; ?></span></div>
        <!-- <div><strong>Cartes max:</strong> <span id="game-cards"><?php echo htmlspecialchars($game['max_cards']); ?></span></div> -->
        <div><strong>Code:</strong> <span id="game-code"><?php echo htmlspecialchars($game['code']) ? 'Oui' : 'Non'; ?></span></div>
        <div><strong>Créateur:</strong> <span id="game-creator"><?php echo htmlspecialchars($game['pseudo']); ?></span></div>
    </div>

    <?php if($game['creator_id'] == $_SESSION['user_id']) { ?>
        <form class="form_launch_game" id="launch_form" method="post" action="">
            <input type="hidden" name="launch_game" value="1">
            <button id="launch_button" type="submit" name="launch_button">Lancer la partie</button>
        </form>
    <?php } ?>

    <div class="back-button">
        <form method="post" action="">
            <input type="hidden" name="leave_game" value="1">
            <button id="back_button" type="submit" name="back_button">Retour à la liste des parties</button>
        </form>
    </div>

    <div class="chat-container">
        <div class="chat-messages" id="chat-messages">
            <!-- Les messages de chat seront ajoutés ici -->
        </div>
        <form id="chat-form" method="post" action="">
            <input type="text" id="chat-input" placeholder="Tapez votre message..." autocomplete="off">
            <button type="submit">Envoyer</button>
        </form>
    </div>

</body>
</html>
<script>
    $(document).ready(function() {
    function loadGameDetails() {
        $.ajax({
            url: 'waiting_room_ajax.php',
            type: 'POST',
            data: { game_id: <?php echo $game_id; ?> },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#game-name').text(response.game.name);
                    $('#game-points').text(response.game.points);
                    $('#game-team').text(response.game.team_activated ? 'Oui' : 'Non');
                    $('#game-cards').text(response.game.max_cards);
                    $('#game-code').text(response.game.code ? 'Oui' : 'Non');
                    $('#game-creator').text(response.game.pseudo);
                    $('#game-players').text(response.game.players + '/' + response.game.max_player);
                    var playersHtml = '';
                    response.game.players_name.forEach(function(player) {
                        playersHtml += '<div class="player">' + player.pseudo + '</div>';
                    });
                    $('.players-container').html(playersHtml);

                    updateLaunchButtonClass(response.game.max_players_reached);

                } else {
                    console.log('Erreur : ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Erreur AJAX : ' + error);
            }
        });
    }

    function updateLaunchButtonClass(maxPlayersReached) {
        if (maxPlayersReached == 1) {
            $('#launch_button').removeClass('form_launch_game_desactivate').addClass('form_launch_game');
            $('#launch_button').attr('disabled', false);
        } else {
            $('#launch_button').removeClass('form_launch_game').addClass('form_launch_game_desactivate');
            $('#launch_button').attr('disabled', true);
            
        }
    }

    // Charger les détails de la partie au chargement initial
    loadGameDetails();

    // Recharger les détails de la partie toutes les 2 secondes
    setInterval(function() {
        loadGameDetails();
    }, 2000);

    function timeAgo(timestamp) {
        const now = new Date();
        const messageTime = new Date(timestamp);
        const diffInSeconds = Math.floor((now - messageTime) / 1000);

        if (diffInSeconds < 60) {
            return 'Now';
        } else if (diffInSeconds < 3600) {
            const minutes = Math.floor(diffInSeconds / 60);
            return `${minutes} Min ago`;
        } else if (diffInSeconds < 86400) {
            const hours = Math.floor(diffInSeconds / 3600);
            return `${hours} Hours ago`;
        } else {
            const days = Math.floor(diffInSeconds / 86400);
            return `${days} Days ago`;
        }
    }

    function loadChatMessages() {
        $.ajax({
            url: 'load_chat.php',
            type: 'POST',
            data: { game_id: <?php echo $game_id; ?> },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var chatHtml = '';
                    response.messages.forEach(function(message) {
                        chatHtml += '<div class="chat-message"><div class="time_chatting">' + timeAgo(message.timestamp) + ' :</div><div class="player_chatting"> ' + message.pseudo + ':</div> ' + message.message + '</div>';
                    });
                    $('#chat-messages').html(chatHtml);
                    $('#chat-messages').scrollTop($('#chat-messages')[0].scrollHeight);
                } else {
                    console.log('Erreur : ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Erreur AJAX : ' + error);
            }
        });
    }

    $('#chat-form').submit(function(e) {
        e.preventDefault();
        var message = $('#chat-input').val();
        if (message.trim() !== '') {
            $.ajax({
                url: 'send_chat.php',
                type: 'POST',
                data: { 
                    game_id: <?php echo $game_id; ?>,
                    user_id: <?php echo $_SESSION['user_id']; ?>,
                    message: message 
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#chat-input').val('');
                        loadChatMessages();
                    } else {
                        console.log('Erreur : ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Erreur AJAX : ' + error);
                }
            });
        }
    });

    // Charger les messages de chat au chargement initial
    loadChatMessages();

    // Recharger les messages de chat toutes les 2 secondes
    setInterval(function() {
        loadChatMessages();
    }, 2000);
});

</script>

