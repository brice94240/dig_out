<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: ./?login");
    exit;
}

require_once 'config.php';


// Traitement du formulaire de rejoindre la partie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['join_game'])) {
    $game_id = intval($_POST['game_id']); // Assurez-vous que game_id est un entier

    try {
        $stmt_game = $pdo->prepare("SELECT launched FROM games WHERE creator_id = :game_id");
        $stmt_game->execute(['game_id' => $game_id]);
        $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

        $stmt_player = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :ID");
        $stmt_player->execute(['ID' => $_SESSION['user_id']]);
        $player_info = $stmt_player->fetch(PDO::FETCH_ASSOC);

        if ($game['launched'] == 0) {
            $stmt = $pdo->prepare("UPDATE joueurs SET game_joined = :game_id WHERE ID = :user_id");
            $stmt->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
            echo "Vous avez rejoint la partie avec succès !";

            // Redirection vers la room de la partie
            header("Location: waiting_room.php?game_id=" . $game_id);
            exit;
        } else if($game['launched'] == 1 && $player_info['game_joined'] == $game_id) {
            header("Location: waiting_room.php?game_id=" . $game_id);
            exit;
        }
        
    } catch (PDOException $e) {
        echo "Erreur lors de la mise à jour de la partie : " . $e->getMessage();
    }
}

// Traitement du formulaire de suppression de la partie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_game'])) {
    $game_id = intval($_POST['game_id']); // Assurez-vous que game_id est un entier


    try {
        // Vérifier si l'utilisateur est le créateur de la partie
        $stmt_check_creator = $pdo->prepare("SELECT * FROM games WHERE creator_id = :game_id");
        $stmt_check_creator->execute(['game_id' => $game_id]);
        $game = $stmt_check_creator->fetch(PDO::FETCH_ASSOC);
        if($game){

            $stmt_delete_game = $pdo->prepare("DELETE FROM games WHERE creator_id = :game_id");
            $stmt_delete_game->execute(['game_id' => $game_id]);

            $stmt_update_player_in_this_game = $pdo->prepare("UPDATE joueurs SET game_joined = NULL WHERE game_joined = :game_id");
            $stmt_update_player_in_this_game->execute(['game_id' => $game_id]);

            echo "Partie supprimée avec succès !";
        } else
        {
            echo "Vous ne pouvez pas supprimer cette partie !";
        }
    } catch (PDOException $e) {
        echo "Erreur lors de la suppression de la partie : " . $e->getMessage();
    }
}

// Traitement du formulaire de création de partie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_game'])) {
    $game_name = $_POST['game_name'];
    $points = $_POST['points'];
    $team_activated = isset($_POST['team_activated']) ? $_POST['team_activated'] : 0; // Définir à 0 par défaut si non défini
    $min_player = $_POST['min_player'];
    $max_player = $_POST['max_player'];
    $max_cards = $_POST['max_cards'];
    $code = $_POST['code'];

    // Validation de base des champs
    if (!empty($game_name) && !empty($points) && !empty($max_player) && !empty($max_cards) && !empty($code)) {
        try {
            $verify = $pdo->prepare("SELECT COUNT(*) FROM games WHERE creator_id = :creator_id");
            $verify->execute(['creator_id' => $_SESSION['user_id']]);
            $count = $verify->fetchColumn();
            if($count > 0) {
                echo "Vous avez déjà créé une partie !";
            } else {
                $stmt = $pdo->prepare("INSERT INTO games (name, points, team_activated, min_player, max_player, max_cards, code, creator_id) VALUES (:name, :points, :team_activated, :min_player, :max_player, :max_cards, :code, :creator_id)");
                $stmt->execute([
                    'name' => $game_name,
                    'points' => $points,
                    'team_activated' => $team_activated,
                    'min_player' => $max_player,
                    'max_player' => $max_player,
                    'max_cards' => $max_cards,
                    'code' => $code,
                    'creator_id' => $_SESSION['user_id']
                ]);
                echo "Partie créée avec succès !";
                header("Location: ./room.php");
                exit;
            }
        } catch (PDOException $e) {
            echo "Erreur lors de la création de la partie : " . $e->getMessage();
        }
    } else {
        echo "Veuillez remplir tous les champs.";
    }
}

// Récupérer toutes les parties depuis la base de données
try {
    $stmt = $pdo->query("SELECT * FROM games INNER JOIN joueurs ON joueurs.ID = games.creator_id");
    $games = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "Erreur lors de la récupération des parties : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="./css/room.css" rel="stylesheet" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <title>Dig Out</title>
</head>
<body>
    <h1>Bienvenue, <?php echo htmlspecialchars($_SESSION['pseudo']); ?> !</h1>
    <a href="logout.php">Se déconnecter</a>

    <h2>Liste des parties</h2>
    <div class="games-container">
        <div class="game-header">
            <div>Nom</div>
            <div>Points</div>
            <div>Équipe</div>
            <div>Joueurs</div>
            <div>Cartes max</div>
            <div>Code</div>
            <div>Créateur</div>
            <div>Action</div>
        </div>
        <?php foreach ($games as $game): 
            try {
                $joueurs_in_game = $pdo->query("SELECT COUNT(*) as 'players_in_game' FROM joueurs WHERE joueurs.game_joined = ".$game['ID']);
                $count_joueurs_in_game = $joueurs_in_game->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                echo "Erreur lors de la récupération des parties : " . $e->getMessage();
            }?>
            <div class="game">
                <div><?php echo htmlspecialchars($game['name']); ?></div>
                <div><?php echo $game['points']; ?></div>
                <div><?php echo $game['team_activated'] ? 'Oui' : 'Non'; ?></div>

                <div><?php echo $count_joueurs_in_game[0]['players_in_game']."/".$game['max_player']; ?></div>

                <div><?php echo $game['max_cards']; ?></div>
                <div><?php echo htmlspecialchars($game['code']) ? 'Oui' : 'Non'; ?></div>
                <div><?php echo $game['pseudo']; ?></div>
                <form class="form_join" method="post" action="">
                    <input type="hidden" name="game_id" value="<?php echo $game['ID']; ?>">
                    <button type="submit" name="join_game" class="join-button">Rejoindre</button>
                </form>
                <?php
                if($game['creator_id'] == $_SESSION['user_id']){?>
                    <form class="form_join" method="post" action="">
                        <input type="hidden" name="game_id" value="<?php echo $game['ID']; ?>">
                        <button type="submit" name="delete_game" class="delete-button">X</button>
                    </form>
                <?php
                }?>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Créer une nouvelle partie</h2>
    <form class="create_room" method="post" action="">
        <div>
            <label for="game_name">Nom de la partie:</label>
            <input type="text" id="game_name" name="game_name" required>
        </div>
        <div>
            <label for="points">Points:</label>
            <input type="number" id="points" name="points" required>
        </div>
        <div>
            <label>Équipe activée:</label>
            <button type="button" id="yesButton" class="toggle-button" onclick="activateButton('yes')">OUI</button>
            <button type="button" id="noButton" class="toggle-button" onclick="activateButton('no')">NON</button>
            <input type="hidden" id="team_activated" name="team_activated" value="1">
        </div>
        <div>
            <label for="max_player">Joueurs:</label>
            <input type="number" id="max_player" name="max_player" required>
        </div>
        <div>
            <label for="max_cards">Cartes de départ:</label>
            <input type="number" id="max_cards" name="max_cards" required>
        </div>
        <div>
            <label for="code">Code:</label>
            <input type="text" id="code" name="code" required>
        </div>
        <button type="submit" name="create_game">Créer</button>
    </form>
    <script>
        function loadGames() {
            $.ajax({
                url: 'room_ajax.php',
                type: 'POST',
                data: { action: 'get_games' },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('.games-container').empty().append(response.html);
                    } else {
                        console.log('Erreur : ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Erreur AJAX : ' + error);
                }
            });
        }

        // Charger les parties au chargement de la page et toutes les 2 secondes
        $(document).ready(function() {
            loadGames(); // Charger les parties au chargement initial

            setInterval(function() {
                loadGames(); // Recharger les parties toutes les 2 secondes (2000 ms)
            }, 2000); // Répéter toutes les 2 secondes (2000 ms)
        });

        // Fonction pour activer le bouton OUI ou NON (pour la création de partie)
        // function activateButton(buttonType) {
        //     var yesButton = document.getElementById('yesButton');
        //     var noButton = document.getElementById('noButton');
        //     var teamActivatedInput = document.getElementById('team_activated');

        //     if (buttonType === 'yes') {
        //         yesButton.classList.add('active');
        //         noButton.classList.remove('active');
        //         teamActivatedInput.value = "1";
        //     } else {
        //         noButton.classList.add('active');
        //         yesButton.classList.remove('active');
        //         teamActivatedInput.value = "0";
        //     }
        // }
        // Fonction pour activer le bouton OUI ou NON
        function activateButton(buttonType) {
            var yesButton = document.getElementById('yesButton');
            var noButton = document.getElementById('noButton');
            var teamActivatedInput = document.getElementById('team_activated');

            if (buttonType === 'yes') {
                yesButton.classList.add('active');
                noButton.classList.remove('active');
                teamActivatedInput.value = "1"; // Mettre à jour la valeur du champ caché
            } else {
                noButton.classList.add('active');
                yesButton.classList.remove('active');
                teamActivatedInput.value = "0"; // Mettre à jour la valeur du champ caché
            }
        }
    </script>
</body>
</html>
