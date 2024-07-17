<?php
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: ./?login");
    exit;
}

require_once 'config.php';

$stmt_verify_info_player = $pdo->prepare("SELECT * FROM joueurs WHERE ID = :user_id");
$stmt_verify_info_player->execute(['user_id' => $_SESSION['user_id']]);
$verify_info_player = $stmt_verify_info_player->fetch(PDO::FETCH_ASSOC);
$game_joined_player = $verify_info_player['game_joined'];

// Vérifier si game_id est passé en paramètre d'URL
if (!isset($_GET['game_id'])) {
    echo "Identifiant de partie manquant.";
    exit;
} elseif(intval($game_joined_player) !== intval($_GET['game_id'])) {
    header("Location: ./room.php");
    exit;
}

$game_id = intval($_GET['game_id']);

// Récupérer les informations de la partie
$stmt_game = $pdo->prepare("SELECT * FROM games WHERE creator_id = :game_id");
$stmt_game->execute(['game_id' => $game_id]);
$game = $stmt_game->fetch(PDO::FETCH_ASSOC);

if (!$game) {
    echo "La partie n'existe pas.";
    exit;
}
$tab_player = explode(',', $game['tab_player']);

if (!in_array($_SESSION['user_id'], $tab_player)) {
    
    $tab_player[] = $_SESSION['user_id'];
    $tab_player_str = implode(',', $tab_player);
    if($tab_player_str[0]==","){
        $tab_player_str = substr($tab_player_str,1);
    }

    $stmt_update = $pdo->prepare("UPDATE games SET tab_player = :tab_player WHERE creator_id = :game_id");
    $stmt_update->execute(['tab_player' => $tab_player_str, 'game_id' => $game_id]);

    // Mettre à jour la localisation pour l'équipe A
    $stmt_update_localisation_a = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE game_joined = :game_id AND team = 'A'");
    $stmt_update_localisation_a->execute(['localisation' => 1, 'game_id' => $game_id]);
    $stmt_update_last_localisation_a = $pdo->prepare("UPDATE joueurs SET last_localisation = :last_localisation WHERE game_joined = :game_id AND team = 'A'");
    $stmt_update_last_localisation_a->execute(['last_localisation' => 1, 'game_id' => $game_id]);

    // Mettre à jour la localisation pour l'équipe B
    $stmt_update_localisation_b = $pdo->prepare("UPDATE joueurs SET localisation = :localisation WHERE game_joined = :game_id AND team = 'B'");
    $stmt_update_localisation_b->execute(['localisation' => 3, 'game_id' => $game_id]);
    $stmt_update_last_localisation_b = $pdo->prepare("UPDATE joueurs SET last_localisation = :last_localisation WHERE game_joined = :game_id AND team = 'B'");
    $stmt_update_last_localisation_b->execute(['last_localisation' => 3, 'game_id' => $game_id]);


    // Récupérer les informations de la partie
    $stmt_game = $pdo->prepare("SELECT * FROM games WHERE creator_id = :game_id");
    $stmt_game->execute(['game_id' => $game_id]);
    $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

    if (!$game) {
        echo "La partie n'existe pas.";
        exit;
    }
    $tab_player = explode(',', $game['tab_player']);

    if(count($tab_player) == 1){
        // echo "MISE EN PLACE DE TOUT";

        // Tableaux pour stocker les gangs
        $gangs = array(); // Tableau principal pour tous les gangs
        $gang1 = array();
        $gang2 = array();
        $gang3 = array();
        $gang4 = array();
        $gang5 = array();
        $gang6 = array();

        $stmt = $pdo->query("SELECT * FROM gangs");
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $stmt_cuillere = $pdo->query("SELECT * FROM fouilles WHERE `point` = 1");
        $result_cuillere = $stmt_cuillere->fetchAll(PDO::FETCH_ASSOC);

        $stmt_pelle = $pdo->query("SELECT * FROM fouilles WHERE `point` = 3");
        $result_pelle = $stmt_pelle->fetchAll(PDO::FETCH_ASSOC);

        $stmt_pioche = $pdo->query("SELECT * FROM fouilles WHERE `point` = 2");
        $result_pioche = $stmt_pioche->fetchAll(PDO::FETCH_ASSOC);

        $stmt_surin = $pdo->query("SELECT * FROM fouilles WHERE `type` = 4 AND `sub_type` = 1");
        $result_surin = $stmt_surin->fetchAll(PDO::FETCH_ASSOC);

        $stmt_fouille = $pdo->query("SELECT * FROM fouilles WHERE `type` = 6 OR `type` = 3 OR `type` = 7 OR `type` = 5 OR `type` = 2");
        $result_fouille = $stmt_fouille->fetchAll(PDO::FETCH_ASSOC);

        // Parcourir les résultats et recuperer les cuilleres
        foreach ($result_cuillere as $row_cuillere) {
            $cuillere[] = $row_cuillere;
        }

         // Parcourir les résultats et recuperer les pelles
         foreach ($result_pelle as $row_pelle) {
            $pelle[] = $row_pelle;
        }

         // Parcourir les résultats et recuperer les pioches
         foreach ($result_pioche as $row_pioche) {
            $pioche[] = $row_pioche;
        }

        // Parcourir les résultats et recuperer les surins
        foreach ($result_surin as $row_surin) {
            $surin[] = $row_surin;
        }

        // Parcourir les résultats et recuperer les fouilles
        foreach ($result_fouille as $row_fouille) {
            $fouille[] = $row_fouille;
        }

        // Mélanger les cartes fouilles
        shuffle($fouille);


        $cuillere_data_json = json_encode($cuillere);
        $stmt_update_cuillere = $pdo->prepare("UPDATE games SET cuillere_data = :cuillere WHERE creator_id = :game_id");
        $stmt_update_cuillere->execute(['cuillere' => $cuillere_data_json, 'game_id' => $game_id]);

        $pelle_data_json = json_encode($pelle);
        $stmt_update_pelle = $pdo->prepare("UPDATE games SET pelle_data = :pelle WHERE creator_id = :game_id");
        $stmt_update_pelle->execute(['pelle' => $pelle_data_json, 'game_id' => $game_id]);

        $pioche_data_json = json_encode($pioche);
        $stmt_update_pioche = $pdo->prepare("UPDATE games SET pioche_data = :pioche WHERE creator_id = :game_id");
        $stmt_update_pioche->execute(['pioche' => $pioche_data_json, 'game_id' => $game_id]);

        $surin_data_json = json_encode($surin);
        $stmt_update_surin = $pdo->prepare("UPDATE games SET surin_data = :surin WHERE creator_id = :game_id");
        $stmt_update_surin->execute(['surin' => $surin_data_json, 'game_id' => $game_id]);

        $fouille_data_json = json_encode($fouille);
        $stmt_update_fouille = $pdo->prepare("UPDATE games SET fouille_data = :fouille WHERE creator_id = :game_id");
        $stmt_update_fouille->execute(['fouille' => $fouille_data_json, 'game_id' => $game_id]);


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

        // Mélanger les tableaux
        shuffle($gang1);
        shuffle($gang2);
        shuffle($gang3);
        shuffle($gang4);
        shuffle($gang5);
        shuffle($gang6);

        // Stocker tous les gangs dans un tableau principal
        $gangs = array(
            'gang1' => $gang1,
            'gang2' => $gang2,
            'gang3' => $gang3,
            'gang4' => $gang4,
            'gang5' => $gang5,
            'gang6' => $gang6,
        );

        $gang_data_json = json_encode($gangs);
        $stmt_update = $pdo->prepare("UPDATE games SET gang_data = :gangs WHERE creator_id = :game_id");
        $stmt_update->execute(['gangs' => $gang_data_json, 'game_id' => $game_id]);
    }
}

// Récupérer les détails des gangs
$stmt = $pdo->prepare("SELECT gang_data FROM games WHERE creator_id = :game_id");
$stmt->execute(['game_id' => $game_id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row && $row['gang_data']) {
    $gangs = json_decode($row['gang_data'], true);
} else {
    echo "Les gangs ne sont pas encore disponibles.";
}

// Récupérer les détails des cuilleres
$stmt_cuillere = $pdo->prepare("SELECT cuillere_data FROM games WHERE creator_id = :game_id");
$stmt_cuillere->execute(['game_id' => $game_id]);
$row_cuillere = $stmt_cuillere->fetch(PDO::FETCH_ASSOC);
if ($row_cuillere && $row_cuillere['cuillere_data']) {
    $cuilleres = json_decode($row_cuillere['cuillere_data'], true);
} else {
    echo "Les cuilleres ne sont pas encore disponibles.";
}

// Récupérer les détails des pelles
$stmt_pelle = $pdo->prepare("SELECT pelle_data FROM games WHERE creator_id = :game_id");
$stmt_pelle->execute(['game_id' => $game_id]);
$row_pelle = $stmt_pelle->fetch(PDO::FETCH_ASSOC);
if ($row_pelle && $row_pelle['pelle_data']) {
    $pelles = json_decode($row_pelle['pelle_data'], true);
} else {
    echo "Les pelles ne sont pas encore disponibles.";
}

// Récupérer les détails des pioches
$stmt_pioche = $pdo->prepare("SELECT pioche_data FROM games WHERE creator_id = :game_id");
$stmt_pioche->execute(['game_id' => $game_id]);
$row_pioche = $stmt_pioche->fetch(PDO::FETCH_ASSOC);
if ($row_pioche && $row_pioche['pioche_data']) {
    $pioches = json_decode($row_pioche['pioche_data'], true);
} else {
    echo "Les pioches ne sont pas encore disponibles.";
}

// Récupérer les détails des surins
$stmt_surin = $pdo->prepare("SELECT surin_data FROM games WHERE creator_id = :game_id");
$stmt_surin->execute(['game_id' => $game_id]);
$row_surin = $stmt_surin->fetch(PDO::FETCH_ASSOC);
if ($row_surin && $row_surin['surin_data']) {
    $surins = json_decode($row_surin['surin_data'], true);
} else {
    echo "Les surins ne sont pas encore disponibles.";
}

// Récupérer les détails des fouilles
$stmt_fouille = $pdo->prepare("SELECT fouille_data FROM games WHERE creator_id = :game_id");
$stmt_fouille->execute(['game_id' => $game_id]);
$row_fouille = $stmt_fouille->fetch(PDO::FETCH_ASSOC);
if ($row_fouille && $row_fouille['fouille_data']) {
    $fouilles = json_decode($row_fouille['fouille_data'], true);
} else {
    echo "Les fouilles ne sont pas encore disponibles.";
}

// Récupérer les nombre de tour
$stmt_turn = $pdo->prepare("SELECT turn FROM games WHERE creator_id = :game_id");
$stmt_turn->execute(['game_id' => $game_id]);
$row_turn = $stmt_turn->fetch(PDO::FETCH_ASSOC);
if ($row_turn['turn'] == 0) {
    //RECUPERER LE NOMBRE DE JOUEUR DANS LA GAME
    $stmt_joueur_deck = $pdo->prepare("SELECT * FROM joueurs WHERE game_joined = :game_id");
    $stmt_joueur_deck->execute(['game_id' => $game_id]);
    $row_joueur_deck = $stmt_joueur_deck->fetchAll(PDO::FETCH_ASSOC);
    
    // Parcourir les résultats et recuperer les cuilleres
    foreach ($row_joueur_deck as $row_joueurs_decks) {
        // Initialiser un deck vide pour chaque joueur
        $deck = [];

        // Récupérer les détails des fouilles
        $stmt_fouille = $pdo->prepare("SELECT fouille_data FROM games WHERE creator_id = :game_id");
        $stmt_fouille->execute(['game_id' => $game_id]);
        $row_fouille = $stmt_fouille->fetch(PDO::FETCH_ASSOC);
        if ($row_fouille && $row_fouille['fouille_data']) {
            $fouilles = json_decode($row_fouille['fouille_data'], true);
            //PRENDRE A CHAQUE FOIS LES 3 PREMIERE CARTE DU TABLEAU FOUILLES ET LES METTRE DANS CHAQUE DECK
            for ($i = 0; $i < 3; $i++) {
                if (!empty($fouilles)) {
                    $deck[] = array_shift($fouilles);
                    $deck_json = json_encode($deck);
                    $fouilles_json = json_encode($fouilles);
                    
                    $stmt_update_fouille = $pdo->prepare("UPDATE games SET fouille_data = :fouille WHERE creator_id = :game_id");
                    $stmt_update_fouille->execute(['fouille' => $fouilles_json, 'game_id' => $game_id]);
                }
            }
            $stmt_update_deck = $pdo->prepare("UPDATE joueurs SET deck = :deck WHERE ID = :ID");
            $stmt_update_deck->execute(['deck' => $deck_json, 'ID' => $row_joueurs_decks['ID']]);
            
        } else {
            echo "Les fouilles ne sont pas encore disponibles.";
        }
    }
    //METTRE LE TOUR A 1
    $turn = 1;
    $stmt_update_turn_1 = $pdo->prepare("UPDATE games SET turn = :turn WHERE creator_id = :game_id");
    $stmt_update_turn_1->execute(['turn' => $turn, 'game_id' => $game_id]);
}

// Récupérer les data des dés
$stmt_dice = $pdo->prepare("SELECT dice_data FROM joueurs WHERE `ID` = :user_id");
$stmt_dice->execute(['user_id' => $_SESSION['user_id']]);
$row_dice = $stmt_dice->fetch(PDO::FETCH_ASSOC);
$dices = json_decode($row_dice['dice_data'], true);

    

// Récupérer les détails des decks
$stmt_deck = $pdo->prepare("SELECT deck FROM joueurs WHERE game_joined = :game_id AND `ID` = :user_id");
$stmt_deck->execute(['game_id' => $game_id, 'user_id' => $_SESSION['user_id']]);
$row_deck = $stmt_deck->fetchAll(PDO::FETCH_ASSOC);
foreach($row_deck as $row_decks){
    if ($row_decks) {
        $decks = json_decode($row_decks['deck'], true);
    } else {
        echo "Les decks ne sont pas encore disponibles.";
    }
}

try {
    $stmt_game = $pdo->prepare("SELECT * FROM games WHERE creator_id = :game_id");
    $stmt_game->execute(['game_id' => $game_id]);
    $game = $stmt_game->fetch(PDO::FETCH_ASSOC);

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
    <link href="css/game.css" rel="stylesheet">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <title>Game - <?php echo htmlspecialchars($game['name']); ?></title>
</head>
<body>
<?php
if($game['team_activated'] == 0){ ?>
   
    <div class="map-container">
        <img src="./img/map.png" alt="Game Map" class="map-image">
        <!-- Zones des gangs -->
        <div class="map-interactive-area" id="gang1" onclick="showGangInfo('<?php echo $gangs['gang6'][0]['gang_name']; ?>', '<?php echo $gangs['gang6'][0]['name']; ?>', '<?php echo $gangs['gang6'][0]['description']; ?>')"></div>
        <div class="map-interactive-area" id="gang2" onclick="showGangInfo('<?php echo $gangs['gang5'][0]['gang_name']; ?>', '<?php echo $gangs['gang5'][0]['name']; ?>', '<?php echo $gangs['gang5'][0]['description']; ?>')"></div>
        <div class="map-interactive-area" id="gang3" onclick="showGangInfo('<?php echo $gangs['gang4'][0]['gang_name']; ?>', '<?php echo $gangs['gang4'][0]['name']; ?>', '<?php echo $gangs['gang4'][0]['description']; ?>')"></div>
        <div class="map-interactive-area" id="gang4" onclick="showGangInfo('<?php echo $gangs['gang3'][0]['gang_name']; ?>', '<?php echo $gangs['gang3'][0]['name']; ?>', '<?php echo $gangs['gang3'][0]['description']; ?>')"></div>
        <div class="map-interactive-area" id="gang5" onclick="showGangInfo('<?php echo $gangs['gang2'][0]['gang_name']; ?>', '<?php echo $gangs['gang2'][0]['name']; ?>', '<?php echo $gangs['gang2'][0]['description']; ?>')"></div>
        <div class="map-interactive-area" id="gang6" onclick="showGangInfo('<?php echo $gangs['gang1'][0]['gang_name']; ?>', '<?php echo $gangs['gang1'][0]['name']; ?>', '<?php echo $gangs['gang1'][0]['description']; ?>')"></div>
        
        <!-- Zones des pièces -->
        <div class="map-interactive-area" id="piece1" onclick="zoneClicked(1)"></div>
        <div class="map-interactive-area" id="piece2" onclick="zoneClicked(2)"></div>
        <div class="map-interactive-area" id="piece3" onclick="zoneClicked(3)"></div>
        <div class="map-interactive-area" id="piece4" onclick="zoneClicked(4)"></div>
        <div class="map-interactive-area" id="piece5" onclick="zoneClicked(5)"></div>
        <div class="map-interactive-area" id="piece6" onclick="zoneClicked(6)"></div>

        <!-- Zones des cartes -->
        <div class="map-interactive-area" id="carte1" onclick="showCardsFouillesInfo('<?php echo $fouilles[0]['name']; ?>', '<?php echo $fouilles[0]['description']; ?>', '<?php echo $fouilles[0]['img']; ?>', '<?php echo $fouilles[0]['verso_card']; ?>')" style="background-image:url('./img/<?php echo $fouilles[0]['verso_card'] ?>');background-size:cover;transform: rotate(90deg);background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="carte2" onclick="showCardsPointsInfo('<?php echo $surins[0]['name']; ?>', '<?php echo $surins[0]['description']; ?>', '<?php echo $surins[0]['img']; ?>')" style="background-image:url('./img/<?php echo $surins[0]['img'] ?>');background-size:cover;background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="carte3" onclick="showCardsPointsInfo('<?php echo $pioches[0]['name']; ?>', '<?php echo $pioches[0]['description']; ?>', '<?php echo $pioches[0]['img']; ?>')" style="background-image:url('./img/<?php echo $pioches[0]['img'] ?>');background-size:cover;transform: rotate(90deg);background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="carte4" onclick="showCardsPointsInfo('<?php echo $pelles[0]['name']; ?>', '<?php echo $pelles[0]['description']; ?>', '<?php echo $pelles[0]['img']; ?>')" style="background-image:url('./img/<?php echo $pelles[0]['img'] ?>');background-size:cover;transform: rotate(90deg);background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="carte5" onclick="zoneClicked('Defausse')"></div>
        <div class="map-interactive-area" id="carte6" onclick="showDecksPointsInfo('<?php echo $cuilleres[0]['name']; ?>', '<?php echo $cuilleres[0]['description']; ?>', '<?php echo $cuilleres[0]['img']; ?>')" style="background-image:url('./img/<?php echo $cuilleres[0]['img'] ?>');background-size:cover;transform: rotate(90deg);background-repeat:no-repeat;"></div>

        <!-- Zones des decks -->
        <div class="map-interactive-area" id="deck" onclick="showCardDecksInfo()" style="background-image:url('./img/<?php echo $decks[0]['verso_card'] ?>');background-size:cover;background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="dice" onclick="showDice('<?php echo $row_turn['turn']; ?>,<?php echo $row_turn['dice_data']; ?>')" style="background-image:url('./img/Dice6.png');background-size:contain;background-repeat:no-repeat;background-size: contain;background-repeat: no-repeat;background-position: top;"></div>
        <div class="turn" value=<?php echo $row_turn['turn'] ?>></div>
        <input type="hidden" class="turn" value=<?php echo $row_turn['turn'] ?>/>
        <input type="hidden" class="turn_id"/>
        <input type="hidden" class="turn_action"/>
        <input type="hidden" class="turn_dice"/>
        <div id="turnMessage"></div>
    <!-- The Modal -->
    <div id="Modal" class="modal">
        <div class="modal-content">
            <div id="modalGangName" class="gang-carte_gang_name"></div>
            <div id="modalCardName" class="gang-carte_name"></div>
            <div id="modalDescription" class="gang-carte_description"></div>
            <div class="button_card">
                <span class="close">Quitter</span>
                <span class="close">Quitter</span>
            </div>
        </div>
    </div>

    <!-- Modal Structure Deck -->
    <div id="deck-modal" class="modal-deck">
        <div class="modal-deck-content">
            <h4>Mon Deck :</h4>
            <div id="deck-cards">
                <!-- Cartes seront affichées ici -->
            </div>
        </div>
        <div class="modal-deck-footer">
            <button id="close-deck-modal" class="modal-deck-close btn">Fermer</button>
        </div>
    </div>

    <!-- The Modal Dice -->
    <div id="ModalDice" class="modal">
        <div class="modal-dice-content">
            <div class="button_dice_card">
                <span id ="LaunchDice" class="close_dice">Lancer</span>
                <span class="close_dice">Quitter</span>
            </div>
        </div>
    </div>

<?php }
else if($game['team_activated'] == 1) { ?>
    <div class="map-container">
        <img src="./img/map_team.png" alt="Game Map" class="map-image">
        <!-- Zones des gangs -->
        <div class="map-interactive-area" id="gang1" onclick="showGangInfo('<?php echo $gangs['gang6'][0]['gang_name']; ?>', '<?php echo $gangs['gang6'][0]['name']; ?>', '<?php echo $gangs['gang6'][0]['description']; ?>', '<?php echo $gangs['gang6'][0]['verso_card']; ?>')"></div>
        <div class="map-interactive-area" id="gang2" onclick="showGangInfo('<?php echo $gangs['gang5'][0]['gang_name']; ?>', '<?php echo $gangs['gang5'][0]['name']; ?>', '<?php echo $gangs['gang5'][0]['description']; ?>')"></div>
        <div class="map-interactive-area" id="gang3" onclick="showGangInfo('<?php echo $gangs['gang4'][0]['gang_name']; ?>', '<?php echo $gangs['gang4'][0]['name']; ?>', '<?php echo $gangs['gang4'][0]['description']; ?>')"></div>
        <div class="map-interactive-area" id="gang4" onclick="showGangInfo('<?php echo $gangs['gang3'][0]['gang_name']; ?>', '<?php echo $gangs['gang3'][0]['name']; ?>', '<?php echo $gangs['gang3'][0]['description']; ?>')"></div>
        <div class="map-interactive-area" id="gang5" onclick="showGangInfo('<?php echo $gangs['gang2'][0]['gang_name']; ?>', '<?php echo $gangs['gang2'][0]['name']; ?>', '<?php echo $gangs['gang2'][0]['description']; ?>')"></div>
        <div class="map-interactive-area" id="gang6" onclick="showGangInfo('<?php echo $gangs['gang1'][0]['gang_name']; ?>', '<?php echo $gangs['gang1'][0]['name']; ?>', '<?php echo $gangs['gang1'][0]['description']; ?>')"></div>
        
        <!-- Zones des pièces -->

        <div class="map-interactive-area" id="piece7" onclick="zoneClicked(1)"></div>
        <div class="map-interactive-area" id="piece8" onclick="zoneClicked(2)"></div>
        <div class="map-interactive-area" id="piece9" onclick="zoneClicked(3)"></div>

        <div class="map-interactive-area" id="piece3" onclick="zoneClicked(4)"></div>
        <div class="map-interactive-area" id="piece4" onclick="zoneClicked(5)"></div>
        <div class="map-interactive-area" id="piece5" onclick="zoneClicked(6)"></div>
        <div class="map-interactive-area" id="piece6" onclick="zoneClicked(7)"></div>

        <!-- Zones des cartes -->
        <div class="map-interactive-area" id="carte1" onclick="showCardsFouillesInfo('<?php echo $fouilles[0]['name']; ?>', '<?php echo $fouilles[0]['description']; ?>', '<?php echo $fouilles[0]['img']; ?>', '<?php echo $fouilles[0]['verso_card']; ?>')" style="background-image:url('./img/<?php echo $fouilles[0]['verso_card'] ?>');background-size:cover;transform: rotate(90deg);background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="carte2" onclick="showCardsPointsInfo('<?php echo $surins[0]['name']; ?>', '<?php echo $surins[0]['description']; ?>', '<?php echo $surins[0]['img']; ?>')" style="background-image:url('./img/<?php echo $surins[0]['img'] ?>');background-size:cover;background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="carte3" onclick="showCardsPointsInfo('<?php echo $pioches[0]['name']; ?>', '<?php echo $pioches[0]['description']; ?>', '<?php echo $pioches[0]['img']; ?>')" style="background-image:url('./img/<?php echo $pioches[0]['img'] ?>');background-size:cover;background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="carte4" onclick="showCardsPointsInfo('<?php echo $pelles[0]['name']; ?>', '<?php echo $pelles[0]['description']; ?>', '<?php echo $pelles[0]['img']; ?>')" style="background-image:url('./img/<?php echo $pelles[0]['img'] ?>');background-size:cover;background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="carte5" onclick="zoneClicked('Defausse')"></div>
        <div class="map-interactive-area" id="carte6" onclick="showCardsPointsInfo('<?php echo $cuilleres[0]['name']; ?>', '<?php echo $cuilleres[0]['description']; ?>', '<?php echo $cuilleres[0]['img']; ?>')" style="background-image:url('./img/<?php echo $cuilleres[0]['img'] ?>');background-size:cover;transform: rotate(90deg);background-repeat:no-repeat;"></div>

        <!-- Zones des decks -->
        <div class="map-interactive-area" id="deck" onclick="showCardDecksInfo()" style="background-image:url('./img/<?php echo $decks[0]['verso_card'] ?>');background-size:cover;background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="dice" onclick="showDice('<?php echo $row_turn['turn']; ?>', '<?php echo $dices[0]; ?>')" style="background-image:url('./img/Dice6.png');background-size:contain;background-repeat:no-repeat;background-size: contain;background-repeat: no-repeat;background-position: top;"></div>
        <input type="hidden" class="turn" value=<?php echo $row_turn['turn'] ?>/>
        <input type="hidden" class="turn_id"/>
        <input type="hidden" class="turn_action"/>
        <input type="hidden" class="turn_dice"/>
        <div id="turnMessage"></div>
    <!-- The Modal -->
    <div id="Modal" class="modal">
        <div class="modal-content">
            <div id="modalGangName" class="gang-carte_gang_name"></div>
            <div id="modalCardName" class="gang-carte_name"></div>
            <div id="modalDescription" class="gang-carte_description"></div>
            <div class="button_card">
                <span class="close">Quitter</span>
            </div>
        </div>
    </div>

    <!-- Modal Structure Deck -->
    <div id="deck-modal" class="modal-deck">
        <div class="modal-deck-content">
            <h4>Mon Deck :</h4>
            <div id="deck-cards">
                <!-- Cartes seront affichées ici -->
            </div>
        </div>
        <div class="modal-deck-footer">
            <button id="close-deck-modal" class="modal-deck-close btn">Fermer</button>
        </div>
    </div>

     <!-- The Modal Dice -->
     <div id="ModalDice" class="modal">
        <div class="modal-dice-content">
            <div class="button_dice_card">
                <span id ="LaunchDice" class="launch_dice">Lancer</span>
                <span class="close_dice">Quitter</span>
            </div>
        </div>
    </div>
<?php } ?>

</body>
</html>
<script>
// Get the modal
var modal = document.getElementById("Modal");

var modalDice = document.getElementById("ModalDice");

// Get the <span> element that closes the modal
var span = document.getElementsByClassName("close")[0];

// When the user clicks on <span> (x), close the modal
span.onclick = function() {
    modal.style.display = "none";
}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
    if (event.target == modal) {
        modal.style.display = "none";
    }
}

// Function to display the modal with specific gang info
function showGangInfo(gangName, cardName, description) {
    var url = 'background-image:url("./img/'+gangName+'.png")';
    document.getElementsByClassName("modal-content")[0].style = url;
    document.getElementById("modalGangName").innerText = gangName;
    document.getElementById("modalCardName").innerText = cardName;
    document.getElementById("modalDescription").innerText = description;
    modal.style.display = "block";
    var buttonCardDiv = document.querySelector('.button_card');

    // Remove existing "Voler" and "Fabriquer" buttons if they exist
    var existingStealButton = document.querySelector('.steal');
    if (existingStealButton) {
        existingStealButton.remove();
    }
    
    var existingMakeButton = document.querySelector('.make');
    if (existingMakeButton) {
        existingMakeButton.remove();
    }

    var existingMakeButton = document.querySelector('.join');
    if (existingMakeButton) {
        existingMakeButton.remove();
    }

    if(gangName == "Crew" || gangName == "Cartel" || gangName == "Bikers" || gangName == "Bratva" || gangName == "Triad" || gangName == "Queers"){
        // Create the new "Rejoindre" button
        var joinButton = document.createElement('span');
        joinButton.className = 'join';
        joinButton.innerText = 'Rejoindre';
        // Append the new "Voler" button to the button card div
        buttonCardDiv.appendChild(joinButton);
    }
}

// Function to display the modal with specific card point info
function showCardsPointsInfo(cardName, description, cardImage) {
    var url = 'background-image:url("./img/'+cardImage+'")';
    document.getElementsByClassName("modal-content")[0].style = url;
    document.getElementById("modalCardName").innerText = cardName;
    document.getElementById("modalDescription").innerText = description;
    modal.style.display = "block";
    var buttonCardDiv = document.querySelector('.button_card');

   // Remove existing "Voler" and "Fabriquer" buttons if they exist
    var existingStealButton = document.querySelector('.steal');
    if (existingStealButton) {
        existingStealButton.remove();
    }
    
    var existingMakeButton = document.querySelector('.make');
    if (existingMakeButton) {
        existingMakeButton.remove();
    }

    var existingMakeButton = document.querySelector('.join');
    if (existingMakeButton) {
        existingMakeButton.remove();
    }

    if(cardName == "Cuillère"){
        // Create the new "Voler" button
        var stealButton = document.createElement('span');
        stealButton.className = 'steal';
        stealButton.innerText = 'Voler';
        // Append the new "Voler" button to the button card div
        buttonCardDiv.appendChild(stealButton);
    } else {
        // Create the new "Fabriquer" button
        var makeButton = document.createElement('span');
        makeButton.className = 'make';
        makeButton.innerText = 'Fabriquer';
        // Append the new "Voler" button to the button card div
        buttonCardDiv.appendChild(makeButton);
    }
}

// Function to display the modal with specific fouille card info
function showCardsFouillesInfo(cardName, description, cardImage, versoCard) {
    var url = 'background-image:url("./img/'+cardImage+'")';
    document.getElementsByClassName("modal-content")[0].style = url;
    document.getElementById("modalCardName").innerText = cardName;
    document.getElementById("modalDescription").innerText = description;
    modal.style.display = "block";
}

// Function to display the modal with specific fouille card info
function showCardDecksInfo() {
    $.ajax({
            url: 'decks_ajax.php',
            type: 'POST',
            data: {
                action: 'get_deck',
                game_id: <?php echo $game_id; ?>,
             },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var deck = response.deck;
                    $('#deck-cards').empty();
                    // Montrer la modal lorsque l'utilisateur clique sur le deck
                    deck.forEach(function(card) {
                        var card_description = card.description.replace(/\\'/g, "'");
                        $('#deck-cards').append('<div class="card modal-content-deck" style="background-image:url(./img/'+card.img+');background-size:cover;background-repeat:no-repeat;"><div class="deck-carte_name">' + card.name + '</div><div class="deck-carte_description">' + card_description + '</div></div>');
                        $('#deck-modal').show();
                    });

                    // Close modal
                    $('#close-deck-modal').click(function() {
                        $('#deck-modal').hide();
                    });
                } else {
                    console.log('Erreur : ' + response.message);
                }

            },
            error: function(xhr, status, error) {
                console.log('Erreur AJAX : ' + error);
            }
        });
}

// Function to open dice menu
//TOUR
var diceLaunched = false;
function showDice(Turn,Dice) {
    if($('#dice').val()){
        var url = 'background-image:url("./img/Dice'+$('#dice').val()+'.png")';
    }else if(Dice > 0){
        var url = 'background-image:url("./img/Dice'+Dice+'.png")';
    } else {
        var url = 'background-image:url("./img/Dice6.png")';
    }
    var style = 'background-size:contain;background-repeat:no-repeat;background-size: contain;background-repeat: no-repeat;background-position: top;';
    document.getElementsByClassName("modal-dice-content")[0].style = url;
    modalDice.style.display = "block";

    // Variable pour suivre si le dé a déjà été lancé

    // Close modal
    $('.close_dice').click(function() {
        $('#ModalDice').hide();
    });

    // Close modal
    $('.launch_dice').click(function() {
        $.ajax({
            url: 'get_turn_ajax.php',
            type: 'POST',
            data: {
                action: 'get_turn',
                game_id: <?php echo $game_id; ?>,
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log(response);
                    var Turn = response.turn;
                    if(Turn == 1 && !Dice && diceLaunched == false) {
                        diceLaunched = true; // Marquer le dé comme lancé
                        const interval = 100; // intervalle entre chaque changement d'image (en millisecondes)
                        const totalFrames = 10; // nombre total de frames d'animation
                        let currentFrame = 0;
                    
                        const faces = [
                            './img/Dice1.png', // chemin vers vos images de faces de dé
                            './img/Dice2.png',
                            './img/Dice3.png',
                            './img/Dice4.png',
                            './img/Dice5.png',
                            './img/Dice6.png'
                        ];
                    
                        var animateDice = () => {
                
                            // Choisir aléatoirement une face du dé
                            var randomFaceIndex = Math.floor(Math.random() * faces.length);
                            var randomFace = faces[randomFaceIndex];
                    
                            // Changer l'image du dé avec une animation de transition
                            $('.modal-dice-content').css('background-image', `url('${randomFace}')`);
                    
                            currentFrame++;
                            if (currentFrame < totalFrames) {
                                setTimeout(animateDice, interval);
                            } else {
                                $.ajax({
                                    url: 'dice_ajax.php',
                                    type: 'POST',
                                    data: {
                                        game_id: <?php echo $game_id; ?>,
                                        dice: randomFaceIndex+1,
                                    },
                                    dataType: 'json',
                                    success: function(response) {
                                        if (response.success) {
                                            $('#dice').val(randomFaceIndex+1);
                                        } else {
                                            console.log('Erreur : ' + response.message);
                                        }
                                    },
                                    error: function(xhr, status, error) {
                                        console.log('Erreur AJAX : ' + error);
                                    }
                                });
                            }
                        };

                        // Démarrer l'animation
                        animateDice();
                    } else {
                        var turn_id = $('.turn_id')[0].getAttribute('value');
                        var turn_action = $('.turn_action')[0].getAttribute('value');
                        if(turn_id == <?php echo $_SESSION['user_id']; ?> && response.dice_data == '') {
                            //ICI GERER LE LANCER DE DE SI C'EST VOTRE TOUR
                            if(Dice == '' && turn_action > 0){
                                diceLaunched = true; // Marquer le dé comme lancé
                                const interval = 100; // intervalle entre chaque changement d'image (en millisecondes)
                                const totalFrames = 10; // nombre total de frames d'animation
                                let currentFrame = 0;
                            
                                const faces = [
                                    './img/Dice1.png', // chemin vers vos images de faces de dé
                                    './img/Dice2.png',
                                    './img/Dice3.png',
                                    './img/Dice4.png',
                                    './img/Dice5.png',
                                    './img/Dice6.png'
                                ];
                            
                                var animateDice = () => {
                        
                                    // Choisir aléatoirement une face du dé
                                    var randomFaceIndex = Math.floor(Math.random() * faces.length);
                                    var randomFace = faces[randomFaceIndex];
                            
                                    // Changer l'image du dé avec une animation de transition
                                    $('.modal-dice-content').css('background-image', `url('${randomFace}')`);
                            
                                    currentFrame++;
                                    if (currentFrame < totalFrames) {
                                        setTimeout(animateDice, interval);
                                    } else {
                                        $.ajax({
                                            url: 'dice_ajax.php',
                                            type: 'POST',
                                            data: {
                                                game_id: <?php echo $game_id; ?>,
                                                dice: randomFaceIndex+1,
                                            },
                                            dataType: 'json',
                                            success: function(response) {
                                                if (response.success) {
                                                    $('#dice').val(randomFaceIndex+1);
                                                } else {
                                                    console.log('Erreur : ' + response.message);
                                                }
                                            },
                                            error: function(xhr, status, error) {
                                                console.log('Erreur AJAX : ' + error);
                                            }
                                        });
                                    }
                                };

                                // Démarrer l'animation
                                animateDice();
                            }
                        }
                    }
                } else {
                    console.log('Erreur : ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Erreur AJAX : ' + error);
            }
        });
        
    });
}

function zoneClicked(zoneName) {
    var turn_dice = $('.turn_dice')[0].getAttribute('value');
    var zone = zoneName;

    $.ajax({
            url: 'localisation_ajax.php',
            type: 'POST',
            data: {
                action: 'get_localisation',
                turn_dice: turn_dice,
                zone : zone,
                game_id: <?php echo $game_id; ?>,
             },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showCardDecksInfo(response.deck);
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
    function RefreshTurn() {
        var globalDeck = <?php echo json_encode($decks); ?>;
        var turn = $('.turn')[0].getAttribute('value');
        $.ajax({
            url: 'turn_ajax.php',
            type: 'POST',
            data: {
                action: 'get_turn',
                turn: turn,
                game_id: <?php echo $game_id; ?>,
             },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    if(parseInt(turn) !== parseInt(response.turn)){
                        $('.turn')[0].setAttribute('value', response.turn);
                        // Afficher le message au milieu de l'écran
                        var messageElement = $('#turnMessage');
                        if(response.turn == 1) {
                            messageElement.html('<div class="display_turn">Tour : ' + response.turn + '</div><div class="info_turn">Lancez le Dé</div>');
                        } else {
                            messageElement.html('<div class="display_turn">Tour : ' + response.turn + '</div><div class="info_turn">Tour de : '+ response.player_turn_name +'</div>');
                            $('.turn_id')[0].setAttribute('value', response.player_turn_id);
                        }
                        messageElement.fadeIn().delay(2000).fadeOut();
                    } else {
                        $('.turn')[0].setAttribute('value', response.turn);
                        $('.turn_id')[0].setAttribute('value', response.player_turn_id);
                        $('.turn_action')[0].setAttribute('value', response.nb_action);
                    }
                    // Mapping des localisations aux pièces de la carte
                    const roomMapping = {
                        1: 'piece7',
                        2: 'piece8',
                        3: 'piece9',
                        4: 'piece3',
                        5: 'piece4',
                        6: 'piece5',
                        7: 'piece6',
                        
                    };
                    $.each(response.playerData, function(playerID, playerInfo) {
                        const pawnId = 'pawn' + playerID;
                        const roomElementId = roomMapping[playerInfo.localisation];
                        if (roomElementId) {
                            // Vérifier le nombre actuel de pions dans la pièce
                            const currentPawns = $('#' + roomElementId).find('.pawn').length;

                            if (currentPawns < 6) {
                                if ($('#' + roomElementId).find('#' + pawnId).length === 0) {
                                    // Calculer la classe numérotée pour le pion
                                    const pawnClass = 'pawn team' + playerInfo.team + ' pawn-' + (currentPawns + 1);

                                    // Créer le pion avec la classe correspondante
                                    $('#'+pawnId).remove();
                                    const pawn = $('<div class="' + pawnClass + '" id="' + pawnId + '" value="' + playerInfo.pseudo + '"></div>');
                                    $('#' + roomElementId).append(pawn);
                                }
                            } else {
                                console.log('Nombre maximum de pions atteint dans la pièce', roomElementId);
                            }
                        } else {
                            console.log('Aucune correspondance de pièce pour la localisation', playerInfo.localisation);
                        }
                    });
                    if(response.playerData[response.player_id]['dice_data'] !== ''){
                        if(response.playerData[response.player_id]['localisation'] == response.playerData[response.player_id]['last_localisation']){
                            $('.turn_dice')[0].setAttribute('value', response.playerData[response.player_id]['dice_data']);
                        }
                    }
                } else {
                    console.log('Erreur : ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Erreur AJAX : ' + error);
            }
        });
    }
    // JavaScript pour gérer les interactions du jeu
    function initializeGameBoard() {
        // Initialisation du plateau de jeu si nécessaire
    }

    // Initialiser le plateau de jeu
    initializeGameBoard();
    RefreshTurn();
    setInterval(function() {
        RefreshTurn(); // Recharger les parties toutes les 2 secondes (2000 ms)
    }, 2000); // Répéter toutes les 2 secondes (2000 ms)
});
</script>
