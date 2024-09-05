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
        header("Location: ./game.php?game_id=".$game_id);
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

        $stmt = $pdo->prepare("UPDATE joueurs SET team = NULL WHERE ID = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        
        // Rediriger vers la liste des parties
        header("Location: ./room.php");
        exit;
    } catch (PDOException $e) {
        echo "Erreur lors de la mise à jour de la partie : " . $e->getMessage();
    }
}

//Gérer la requête pour rejoindre l'équipe A
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_team_a'])) {
    try {
        // Récupérer les détails de la partie
        $stmt_game = $pdo->prepare("SELECT * FROM games INNER JOIN joueurs ON games.creator_id = joueurs.id WHERE games.creator_id = :game_id");
        $stmt_game->execute(['game_id' => $game_id]);
        $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

        // Récupérer le nombre de joueurs dans l'équipe A
        $stmt_team_a_count = $pdo->prepare("SELECT COUNT(*) FROM joueurs WHERE game_joined = :game_id AND team = 'A'");
        $stmt_team_a_count->execute(['game_id' => $game_id]);
        $team_a_count = $stmt_team_a_count->fetchColumn();

        $max_player_team = $game['max_player']/2;

        if($team_a_count<$max_player_team){
            $stmt = $pdo->prepare("UPDATE joueurs SET team = 'A' WHERE ID = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            // Rediriger vers la même page après avoir rejoint l'équipe
            header("Location: ./waiting_room.php?game_id=".$game_id);
            exit;
        }
    } catch (PDOException $e) {
        echo "Erreur lors de la mise à jour de l'équipe : " . $e->getMessage();
    }
}

// Gérer la requête pour rejoindre l'équipe B
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_team_b'])) {
    try {
        // Récupérer les détails de la partie
        $stmt_game = $pdo->prepare("SELECT * FROM games INNER JOIN joueurs ON games.creator_id = joueurs.id WHERE games.creator_id = :game_id");
        $stmt_game->execute(['game_id' => $game_id]);
        $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

        $max_player_team = $game['max_player']/2;

        // Récupérer le nombre de joueurs dans l'équipe A
        $stmt_team_b_count = $pdo->prepare("SELECT COUNT(*) FROM joueurs WHERE game_joined = :game_id AND team = 'B'");
        $stmt_team_b_count->execute(['game_id' => $game_id]);
        $team_b_count = $stmt_team_b_count->fetchColumn();
        if($team_b_count<$max_player_team){
            $stmt = $pdo->prepare("UPDATE joueurs SET team = 'B' WHERE ID = :user_id");
            $stmt->execute(['user_id' => $_SESSION['user_id']]);
            // Rediriger vers la même page après avoir rejoint l'équipe
            header("Location: ./waiting_room.php?game_id=".$game_id);
            exit;
        }
    } catch (PDOException $e) {
        echo "Erreur lors de la mise à jour de l'équipe : " . $e->getMessage();
    }
}

// Gérer la requête pour quitter l'équipe A
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quit_team_a'])) {
    try {
        $stmt = $pdo->prepare("UPDATE joueurs SET team = NULL WHERE ID = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        // Rediriger vers la même page après avoir rejoint l'équipe
        header("Location: ./waiting_room.php?game_id=".$game_id);
        exit;
    } catch (PDOException $e) {
        echo "Erreur lors de la mise à jour de l'équipe : " . $e->getMessage();
    }
}

// Gérer la requête pour quitter l'équipe B
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quit_team_b'])) {
    try {
        $stmt = $pdo->prepare("UPDATE joueurs SET team = NULL WHERE ID = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
        // Rediriger vers la même page après avoir rejoint l'équipe
        header("Location: ./waiting_room.php?game_id=".$game_id);
        exit;
    } catch (PDOException $e) {
        echo "Erreur lors de la mise à jour de l'équipe : " . $e->getMessage();
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

$max_players_per_team = intval($game['max_player'] / 2);
$team_a_players = array_filter($players, function($player) {
    return $player['team'] == 'A';
});
$team_b_players = array_filter($players, function($player) {
    return $player['team'] == 'B';
});

// Vérifier si l'utilisateur est déjà dans une équipe
$user_team = '';
foreach ($players as $player) {
    if ($player['ID'] === $_SESSION['user_id']) {
        $user_team = $player['team'];
        break;
    }
}

// Filtrer les joueurs sans équipe si les équipes sont activées
$players_no_team = array_filter($players, function($player) {
    return empty($player['team']);
});

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
    <h1><?php echo htmlspecialchars($game['name']); ?></h1>
    <a href="logout.php">Se déconnecter</a>

    <h2>Liste des joueurs</h2>
    <?php if (!empty($players_no_team)) : ?>
    <div class="team-no-team-column">
        <div class="team-no-team">
            <?php foreach ($players_no_team as $player): ?>
                <div class="player"><?php echo htmlspecialchars($player['pseudo']); ?></div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php else : ?>
        <div class="team-no-team-column">
            <div class="team-no-team">
            </div>
        </div>
    <?php endif; ?>
    <div class="players-container">
        <?php if ($game['team_activated']) { ?>
            <div class="team-column">
                <h3>Équipe A</h3>
                <p id="team-a-count"><?php echo count($team_a_players) . " / " . $max_players_per_team; ?> joueurs</p>
                <div class="team-a">
                    <?php foreach ($team_a_players as $player): ?>
                        <div class="player"><?php echo htmlspecialchars($player['pseudo']); ?></div>
                    <?php endforeach; ?>
                </div>
                <form id="join_team_a_form" method="post" action="">
                    <input type="hidden" name="join_team_a" value="1">
                    <button id="join-button-a" class="join-button" type="submit" name="join_team_a">Rejoindre</button>
                </form>
                <?php if ($user_team == 'A' && $game['team_activated'] == 1) : ?>
                    <form id="quit_team_a_form" method="post" action="">
                        <input type="hidden" name="quit_team_a" value="1">
                        <button id="quit-button-a" class="quit-button" type="submit" name="quit_team_a">Quitter</button>
                    </form>
                <?php endif; ?>
            </div>
            <div class="team-column">
                <h3>Équipe B</h3>
                <p id="team-b-count"><?php echo count($team_b_players) . " / " . $max_players_per_team; ?> joueurs</p>
                <div class="team-b">
                    <?php foreach ($team_b_players as $player): ?>
                        <div class="player"><?php echo htmlspecialchars($player['pseudo']); ?></div>
                    <?php endforeach; ?>
                </div>
                <form id="join_team_b_form" method="post" action="">
                    <input type="hidden" name="join_team_b" value="1">
                    <button id="join-button-b" class="join-button" type="submit" name="join_team_b">Rejoindre</button>
                </form>
                <?php if ($user_team == 'B' && $game['team_activated'] == 1) : ?>
                    <form id="quit_team_b_form" method="post" action="">
                        <input type="hidden" name="quit_team_b" value="1">
                        <button id="quit-button-b" class="quit-button" type="submit" name="quit_team_b">Quitter</button>
                    </form>
                <?php endif; ?>
            </div>
        <?php } else { ?>
            <div class="team-column">
                <h3>Tous les joueurs</h3>
                <div class="team-all">
                    <?php foreach ($players as $player): ?>
                        <div class="player"><?php echo htmlspecialchars($player['pseudo']); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php } ?>
    </div>

    <h2>Détails de la partie</h2>
    <div class="game-details">
        <div><strong>Nom de la partie:</strong> <span id="game-name"><?php echo htmlspecialchars($game['name']); ?></span></div>
        <div><strong>Points:</strong> <span id="game-points"><?php echo htmlspecialchars($game['points']); ?></span></div>
        <div><strong>Équipe activée:</strong> <span id="game-team"><?php echo $game['team_activated'] ? 'Oui' : 'Non'; ?></span></div>
        <div><strong>Joueurs:</strong> <span id="game-players"><?php echo count($players) . "/" . $game['max_player']; ?></span></div>
        <div><strong>Main de départ:</strong> <span id="game-cards"><?php echo htmlspecialchars($game['max_cards']); ?></span></div>
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
            <button id="back_button" type="submit" name="back_button">Retour</button>
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
                        var max_players_per_team = response.game.max_player/2;
                        var user_team = response.game.user_team;
                        if(response.game.team_activated == 1){
                            var playersHtmlA = '';
                            var count_playerA = 0;
                            var count_playerB = 0;
                            var playersHtmlB = '';
                            var playersNoTeamHtml = '';
                            response.game.players_name.forEach(function(player) {
                                if (player.team === 'A') {
                                    playersHtmlA += '<div class="player">' + player.pseudo + '</div>';
                                    count_playerA++;
                                } else if (player.team === 'B') {
                                    playersHtmlB += '<div class="player">' + player.pseudo + '</div>';
                                    count_playerB++;
                                } else {
                                    playersNoTeamHtml += '<div class="player">' + player.pseudo + '</div>';
                                }
                            });
                            $('.team-a').html(playersHtmlA);
                            $('.team-b').html(playersHtmlB);
                            $('.team-no-team').html(playersNoTeamHtml);
                            $('#team-a-count').text(count_playerA + ' / ' +max_players_per_team+ " joueurs");
                            $('#team-b-count').text(count_playerB + ' / ' +max_players_per_team+ " joueurs");
                        } else {
                            var playersHtmlAll = '';
                            response.game.players_name.forEach(function(player) {
                                playersHtmlAll += '<div class="player">' + player.pseudo + '</div>';
                            });
                            $('.team-all').html(playersHtmlAll);
                        }
                        if(response.game.game_launched == 1) {
                            window.location.href = 'game.php?game_id='+response.game.creator_id;
                        }

                        updateLaunchButtonClass(response.game.max_players_reached);
                        updateJoinButtonClass(max_players_per_team,count_playerA,count_playerB,user_team);
                        
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

        function updateJoinButtonClass(MaxPlayerPerTeam,PlayerTeamA,PlayerTeamB,UserTeam) {
            if (UserTeam == 'A') {
                console.log("In Team A");
                $('#join-button-a').removeClass('join-button').addClass('join-button-desactivate');
            }
            if (UserTeam == 'B') {
                console.log("In Team B");
                $('#join-button-b').removeClass('join-button').addClass('join-button-desactivate');
            }
            if(PlayerTeamA == MaxPlayerPerTeam){
                console.log("Team A Full");
                $('#join-button-a').removeClass('join-button').addClass('join-button-desactivate');
            }
            if(PlayerTeamB == MaxPlayerPerTeam){
                console.log("Team B Full");
                $('#join-button-b').removeClass('join-button').addClass('join-button-desactivate');
            }
            if (UserTeam == 'B' && PlayerTeamA < MaxPlayerPerTeam) {
                console.log("Possible to join team A");
                $('#join-button-a').removeClass('join-button-desactivate').addClass('join-button');
            }
            if (UserTeam == 'A' && PlayerTeamB < MaxPlayerPerTeam) {
                console.log("Possible to join team B");
                $('#join-button-b').removeClass('join-button-desactivate').addClass('join-button');
            }
            if (UserTeam == null && PlayerTeamA < MaxPlayerPerTeam) {
                console.log("Possible to join team A");
                $('#join-button-a').removeClass('join-button-desactivate').addClass('join-button');
            }
            if (UserTeam == null && PlayerTeamB < MaxPlayerPerTeam) {
                console.log("Possible to join team B");
                $('#join-button-b').removeClass('join-button-desactivate').addClass('join-button');
            }
        }

        // Charger les détails de la partie au chargement initial
        loadGameDetails();

        // Recharger les détails de la partie toutes les 2 secondes
        setInterval(function() {
            loadGameDetails();
        }, 500);

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
