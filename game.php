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

// Récupérer les détails des defausses
$stmt_defausse = $pdo->prepare("SELECT defausse_data FROM games WHERE creator_id = :game_id");
$stmt_defausse->execute(['game_id' => $game_id]);
$row_defausse = $stmt_defausse->fetchAll(PDO::FETCH_ASSOC);
foreach($row_defausse as $row_defausses){
    if ($row_defausses) {
        $defausses = json_decode($row_defausses['defausse_data'], true);
    } else {
        echo "La defausse n'est pas encore disponibles.";
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
        <div class="map-interactive-area" id="carte6" onclick="showDecksPointsInfo('<?php echo $cuilleres[0]['name']; ?>', '<?php echo $cuilleres[0]['description']; ?>', '<?php echo $cuilleres[0]['img']; ?>')" style="background-image:url('./img/<?php echo $cuilleres[0]['img'] ?>');background-size:cover;transform: rotate(90deg);background-repeat:no-repeat;"></div>

        <!-- Zones des decks -->
        <div class="map-interactive-area" id="deck" onclick="showCardDecksInfo()" style="background-image:url('./img/<?php echo $decks[0]['verso_card'] ?>');background-size:cover;background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="defausse" onclick="showCardDefausseInfo()" style="background-image:url('./img/<?php echo $defausses[0]['img'] ?>');background-size:cover;background-repeat:no-repeat;transform: rotate(90deg);"></div>
        <div class="map-interactive-area" id="dice" onclick="showDice('<?php echo $row_turn['turn']; ?>,<?php echo $row_turn['dice_data']; ?>')" style="background-image:url('./img/Dice6.png');background-size:contain;background-repeat:no-repeat;background-size: contain;background-repeat: no-repeat;background-position: top;"></div>
        <div class="map-interactive-area" id="cigarette" style="background-image:url('./img/cigarette.png');background-size:cover;background-repeat:no-repeat;background-size: cover;background-repeat: no-repeat;background-position: top;"><div class="count_cigarette"><?php echo $verify_info_player['cigarette'] ?></div></div>

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

    <!-- Modal Structure Deck -->
    <div id="deck-modal" class="modal-deck">
        <div class="modal-deck-content">
            <h4>Mon Deck :</h4>
            <div id="deck-cards">
                <!-- Cartes seront affichées ici -->
            </div>
        </div>
        <div class="modal-deck-footer">
            <button id="defausser-deck-modal" class="modal-deck-defausser btn">Defausser</button>
            <button id="sell-deck-modal" class="modal-deck-sell btn">Vendre</button>
            <button id="sell-deck-modal-confirm" class="modal-deck-sell btn">Confirmer</button>
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
    <button id="healButton" class="heal-button">Se soigner</button>
    <button id="creuserButton" class="creuser-button">Creuser</button>
    <button id="FinTurnButton" class="finturn-button">Fin de tour</button>
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
        <div class="map-interactive-area" id="carte6" onclick="showCardsPointsInfo('<?php echo $cuilleres[0]['name']; ?>', '<?php echo $cuilleres[0]['description']; ?>', '<?php echo $cuilleres[0]['img']; ?>')" style="background-image:url('./img/<?php echo $cuilleres[0]['img'] ?>');background-size:cover;transform: rotate(90deg);background-repeat:no-repeat;"></div>

        <!-- Zones des decks -->
        <div class="map-interactive-area" id="deck" onclick="showCardDecksInfo()" style="background-image:url('./img/<?php echo $decks[0]['verso_card'] ?>');background-size:cover;background-repeat:no-repeat;"></div>
        <div class="map-interactive-area" id="defausse" onclick="showCardDefausseInfo()" style="background-image:url('./img/<?php echo $defausses[0]['img'] ?>');background-size:cover;background-repeat:no-repeat;transform: rotate(90deg);"></div>
        <div class="map-interactive-area" id="dice" onclick="showDice('<?php echo $row_turn['turn']; ?>', '<?php echo $dices[0]; ?>')" style="background-image:url('./img/Dice6.png');background-size:contain;background-repeat:no-repeat;background-size: contain;background-repeat: no-repeat;background-position: top;"></div>
        <div class="map-interactive-area" id="cigarette" style="background-image:url('./img/cigarette.png');background-size:cover;background-repeat:no-repeat;background-size: cover;background-repeat: no-repeat;background-position: top;"><div class="count_cigarette"><?php echo $verify_info_player['cigarette'] ?></div></div>
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
            <button id="defausser-deck-modal" class="modal-deck-defausser btn">Defausser</button>
            <button id="sell-deck-modal" class="modal-deck-sell btn">Vendre</button>
            <button id="sell-deck-modal-confirm" class="modal-deck-sell btn">Confirmer</button>
            <button id="close-deck-modal" class="modal-deck-close btn">Fermer</button>
        </div>
    </div>

    <!-- Modal Structure Deck -->
    <div id="deck-modal-target" class="modal-deck-target">
        <div class="modal-deck-target-content">
            <div id="pseudo-target">
                <!-- Cartes seront affichées ici -->
            </div>
            <h4>Deck :</h4>
            <div id="deck-target-cards">
                <!-- Cartes seront affichées ici -->
            </div>
            <h4>Infos :</h4>
            <div id="deck-target-infos">
                <!-- Infos seront affichées ici -->
            </div>
        </div>
        <div class="modal-deck-target-footer">
            <button id="attackButton" class="attack-button">Racketter</button>
            <button id="close-deck-target-modal" class="modal-deck-target-close btn">Fermer</button>
        </div>
    </div>

    <!-- Modal Structure Deck -->
    <div id="action-modal-target" class="modal-action-target" style="display:none;">
        <div class="modal-action-target-content">
            <div id="pseudo-target-action">
                <!-- Pseusos seront affichées ici -->
            </div>
        </div>
        <div class="modal-deck-target-footer">
            <button id="close-deck-target-action-modal" class="modal-deck-target-close btn">Fermer</button>
        </div>
    </div>

    <!-- Modal Structure Deck -->
    <div id="action-modal-localisation" class="modal-action-localisation" style="display:none;">
        <div class="modal-action-localisation-content">
            <div id="name-localisation-action">
                <div class="choose_desination">Ou voulez-vous vous rendre ? :</div>
                <!-- Localisations seront affichées ici -->
            </div>
        </div>
        <div class="modal-deck-localisation-footer">
            <button id="close-deck-localisation-action-modal" class="modal-deck-localisation-close btn">Fermer</button>
        </div>
    </div>

    <!-- Modal Structure Defausse -->
    <div id="defausse-modal" class="modal-defausse">
        <div class="modal-defausse-content">
            <h4>La Defausse :</h4>
            <div id="defausse-cards">
                <!-- Cartes seront affichées ici -->
            </div>
        </div>
        <div class="modal-defausse-footer">
            <button id="close-defausse-modal" class="modal-defausse-close btn">Fermer</button>
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
    <button id="healButton" class="heal-button">Se soigner</button>
    <button id="creuserButton" class="creuser-button">Creuser</button>
    <button id="sellButton" class="sell-button">Vendre</button>
    <button id="FinTurnButton" class="finturn-button">Fin de tour</button>
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

$('#healButton').click(function() {
    useAction('heal','heal');
});

$('#creuserButton').click(function() {
    useAction('creuser','creuser');
});

$('#FinTurnButton').click(function() {
    finTurn();
});

$('#sellButton').click(function() {
    useAction('sell','cigarette');
});

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
        joinButton.setAttribute('onclick', 'useAction("join","'+gangName+'")');
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

    var existingMakeButton = document.querySelector('.buy');
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
        stealButton.setAttribute('onclick', 'useAction("steal","'+cardName+'")');
        // Append the new "Voler" button to the button card div
        buttonCardDiv.appendChild(stealButton);
    } else if (cardName == "Surin" || cardName == "Pioche" || cardName == "Pelle"){
        // Create the new "Fabriquer" button
        var makeButton = document.createElement('span');
        makeButton.className = 'make';
        makeButton.innerText = 'Fabriquer';
        makeButton.setAttribute('onclick', 'useAction("make","'+cardName+'")');

        var buyButton = document.createElement('span');
        buyButton.className = 'buy';
        buyButton.innerText = 'Acheter';
        buyButton.setAttribute('onclick', 'useAction("buy","'+cardName+'")');

        // Append the new "Voler" button to the button card div
        buttonCardDiv.appendChild(makeButton);
        buttonCardDiv.appendChild(buyButton);

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
                        if(card.type !== 2){
                            var card_description = card.description.replace(/\\'/g, "'");
                            $('#deck-cards').append('<div class="card modal-content-deck" style="background-image:url(./img/'+card.img+');background-size:cover;background-repeat:no-repeat;"><div class="deck-carte_name">' + card.name + '</div><div class="deck-carte_description">' + card_description + '</div></div>');
                            $('#deck-modal').show();
                        } else {
                            var card_description = card.description.replace(/\\'/g, "'");
                            $('#deck-cards').append('<div class="card card_action modal-content-deck" style="background-image:url(./img/'+card.img+');background-size:cover;background-repeat:no-repeat;"><div class="deck-carte_name">' + card.name + '</div><div class="deck-carte_description">' + card_description + '</div><button class="card-action-btn" data-card-id="'+card.ID+'">Utiliser</button></div>');
                            $('#deck-modal').show();
                        }
                    });
                    if(deck.length > 10) {
                        $('#defausser-deck-modal').show();
                    } else {
                        $('#defausser-deck-modal').hide();
                    }

                    $('#sell-deck-modal').hide();
                    $('#sell-deck-modal-confirm').hide();

                    // Close modal
                    $('#close-deck-modal').click(function() {
                        $('#deck-modal').hide();
                    });

                    $('#defausser-deck-modal').click(function() {
                        //CHOISIR LES CARTES A DEFAUSSER
                        chooseCardsToDefausser(deck);
                    });

                    $('.card-action-btn').click(function() {
                        var card_action = $(this).data('card-id');
                        useAction('card_action',card_action);
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

// Function to display the modal with specific fouille card info
function showCardDecksForSellInfo() {
    $.ajax({
            url: 'decks_ajax.php',
            type: 'POST',
            data: {
                action: 'get_deck_to_sell',
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
                    if(response.deck.length > 10) {
                        $('#defausser-deck-modal').show();
                    } else {
                        $('#defausser-deck-modal').hide();
                    }

                    if(response.localisation == 7) {
                        $('#sell-deck-modal').show();
                    } else {
                        $('#sell-deck-modal').hide();
                    }
                    $('#sell-deck-modal-confirm').hide();
                    // Close modal
                    $('#close-deck-modal').click(function() {
                        $('#deck-modal').hide();
                    });

                    $('#sell-deck-modal').click(function() {
                        //CHOISIR LES CARTES A DEFAUSSER
                        chooseCardsToSell(deck);
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

// Function to display the modal with specific fouille card info
function showCardDefausseInfo() {
    $.ajax({
            url: 'defausse_ajax.php',
            type: 'POST',
            data: {
                action: 'get_defausse',
                game_id: <?php echo $game_id; ?>,
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    var defausse = response.defausse;
                    $('#defausse-cards').empty();
                    // Montrer la modal lorsque l'utilisateur clique sur le deck
                    defausse.forEach(function(card) {
                        var card_description = card.description.replace(/\\'/g, "'");
                        $('#defausse-cards').append('<div class="card-defausse modal-content-deck" style="background-image:url(./img/'+card.img+');background-size:cover;background-repeat:no-repeat;"><div class="deck-carte_name">' + card.name + '</div><div class="deck-carte_description">' + card_description + '</div></div>');
                        $('#defausse-modal').show();
                    });

                    // Close modal
                    $('#close-defausse-modal').click(function() {
                        $('#defausse-modal').hide();
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

function chooseCardsToDefausser(deck) {
    $('#deck-cards').empty();
    deck.forEach(function(card) {
        var card_description = card.description.replace(/\\'/g, "'");
        var cardDiv = $('<div class="card modal-content-deck" style="background-image:url(./img/'+card.img+');background-size:cover;background-repeat:no-repeat;"><div class="deck-carte_name">' + card.name + '</div><div class="deck-carte_description">' + card_description + '</div></div>');

        var defausserButton = $('<button class="defausser-btn" data-card-id="' + card.ID + '">Défausser</button>');
        cardDiv.append(defausserButton);
        $('#deck-cards').append(cardDiv);
    });

    $('.defausser-btn').click(function() {
        var cardId = $(this).data('card-id');
        defausserCard(cardId);
    });
}

function chooseCardsToSell(deck) {
    $('#deck-cards').empty();
    deck.forEach(function(card) {
        var card_description = card.description.replace(/\\'/g, "'");
        var cardDiv = $('<div class="card modal-content-deck" style="background-image:url(./img/'+card.img+');background-size:cover;background-repeat:no-repeat;"><div class="deck-carte_name">' + card.name + '</div><div class="deck-carte_description">' + card_description + '</div></div>');
        var sellButton = $('<button class="sell-btn" data-card-id="' + card.ID + '" data-card-cigarette="'+ card.val_cigarette +'">Sélectionner</button>');
        var CigaretteNumber = $('<div class="cigarette">Prix</br>'+card.val_cigarette+'</div>');
        cardDiv.append(CigaretteNumber);
        cardDiv.append(sellButton);
        $('#deck-cards').append(cardDiv);
        $('#sell-deck-modal').hide();
        $('#sell-deck-modal-confirm').show();
    });
    $('.deck-carte_description').hide();
    var tab_sell = [];
    $('.sell-btn').click(function() {
        var cardId = $(this).data('card-id');
        // Si la carte n'est pas déjà dans le tableau
        if (!tab_sell.find(element => element === cardId)) {
            tab_sell.push(cardId);
            // Transformer le bouton sell-btn en unsell-btn
            $(this).text('Annuler');
        } else {
            var index = tab_sell.findIndex(element => element === cardId);
            tab_sell.splice(index, 1);
            $(this).text('Sélectionner');
        }
    });
    
    $('#sell-deck-modal-confirm').click(function() {
        SellCards(tab_sell);
    });
}

function SellCards(tab_sell) {
    if(tab_sell.length > 0){
        $.ajax({
            url: 'decks_ajax.php',
            type: 'POST',
            data: {
                action: 'sell_card',
                tab_sell: tab_sell,
                game_id: <?php echo $game_id; ?>
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    tab_sell = [];
                    showCardDecksInfo(); // Refresh the deck
                } else {
                    console.log('Erreur : ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Erreur AJAX : ' + error);
            }
        });
    }
}

function defausserCard(cardId) {
    $.ajax({
        url: 'decks_ajax.php',
        type: 'POST',
        data: {
            action: 'defausser_card',
            card_id: cardId,
            game_id: <?php echo $game_id; ?>
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('Carte défaussée avec succès');
                showCardDecksInfo(); // Refresh the deck
            } else {
                console.log('Erreur : ' + response.message);
            }
        },
        error: function(xhr, status, error) {
            console.log('Erreur AJAX : ' + error);
        }
    });
}

function SellCard(cardId) {
    $.ajax({
        url: 'decks_ajax.php',
        type: 'POST',
        data: {
            action: 'defausser_card',
            card_id: cardId,
            game_id: <?php echo $game_id; ?>
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
                console.log('Cartes vendues avec succès');
                showCardDecksInfo(); // Refresh the deck
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
    $('#LaunchDice').show();
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
                    // var diceLaunched = true;
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
                            $('#LaunchDice').hide();
                            if(turn_action > 0){
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
    var turn = $('.turn')[0].getAttribute('value');
    var zone = zoneName;
    var target = $(event.target);
    if(target[0].className.includes("pawn") == false){
        $.ajax({
                url: 'localisation_ajax.php',
                type: 'POST',
                data: {
                    action: 'get_localisation',
                    turn_dice: turn_dice,
                    zone : zone,
                    turn : turn,
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
}

function useAction(name_action,name) {
    $.ajax({
            url: 'make_action_ajax.php',
            type: 'POST',
            data: {
                action: 'make_action',
                name_action : name_action,
                item_name : name,
                game_id: <?php echo $game_id; ?>,
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    console.log(response);

                } else {
                    console.log('Erreur : ' + response.message);
                }
                if(response.sell == true){
                    showCardDecksForSellInfo(response.deck);
                }
                if(response.make == true || response.buy == true || response.steal == true){
                    showCardDecksInfo(response.deck);
                }
                if(response.sub_type == 1 || response.sub_type == 7 || response.sub_type == 8 || response.sub_type == 9 || response.sub_type == 10 || response.sub_type == 11 || response.sub_type == 12){
                    showCardDecksInfo(response.deck);
                }
                if(response.sub_type == 2 || response.sub_type == 3 || response.sub_type == 4 || response.sub_type == 5 || response.sub_type == 6){
                    console.log(response);
                    SelectTarget(response.sub_type,response.player_data,response.item_name);
                }
            },
            error: function(xhr, status, error) {
                console.log('Erreur AJAX : ' + error);
            }
        });
}

function finTurn() {
    $.ajax({
        url: 'fin_turn_ajax.php',
        type: 'POST',
        data: {
            action: 'fin_turn_action',
            game_id: <?php echo $game_id; ?>,
        },
        dataType: 'json',
        success: function(response) {
            if (response.success) {
            } else {
                console.log('Erreur : ' + response.message);
            }
            if(response.need_defause == true) {
                showCardDecksInfo(response.deck);
            }
        },
        error: function(xhr, status, error) {
            console.log('Erreur AJAX : ' + error);
        }
    });
}

function CardOnTarget(sub_type,player_id,item_name){
    if(sub_type && player_id && item_name){
        $.ajax({
            url: 'make_action_target_ajax.php',
            type: 'POST',
            data: {
                action: 'make_action_target',
                sub_type: sub_type,
                player_id : player_id,
                item_name : item_name,
                game_id: <?php echo $game_id; ?>,
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#action-modal-target').hide();
                    showCardDecksInfo(response.deck);
                } else {
                    console.log('Erreur : ' + response.message);
                }
                console.log(response);

            },
            error: function(xhr, status, error) {
                console.log('Erreur AJAX : ' + error);
            }
        });
    }

}

function ChooseLocation(sub_type,localisation_id,item_name){
    if(sub_type && localisation_id && item_name){
        $.ajax({
            url: 'make_action_target_ajax.php',
            type: 'POST',
            data: {
                action: 'make_action_target',
                sub_type: sub_type,
                localisation_id : localisation_id,
                item_name : item_name,
                game_id: <?php echo $game_id; ?>,
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#action-modal-localisation').hide();
                } else {
                    console.log('Erreur : ' + response.message);
                }
                console.log(response);

            },
            error: function(xhr, status, error) {
                console.log('Erreur AJAX : ' + error);
            }
        });
    }

}

function SelectTarget(sub_type, player_data, item_name) {

    if (sub_type == 4 || sub_type == 5 || sub_type == 6) {
        var modal = $('#action-modal-target');
        var playerList = $('#pseudo-target-action');

        // Clear previous content
        playerList.empty();

        // Iterate over player_data and add each player's info to the modal
        player_data.forEach(function(players) {
            var playerDiv = $('<div class="player"></div>')
            .append('<div class="player_team">Team: ' + players.team + '</div>')
            .append('<div class="player_name">Pseudo: ' + players.pseudo + '</div>')

            // Create a div to hold the deck
            var deckDiv = $('<div class="player_deck"></div>');

            // Parse the deck and append each card to the deckDiv
            var deck = JSON.parse(players.deck);
            var count_deck = deck.length;

            // deck.forEach(function(card) {
                deckDiv.append('<div class="card modal-content-deck" style="background-image:url(./img/' + deck[0].verso_card + '); background-size:contain; background-repeat:no-repeat;"><div class="count_deck_cards">'+count_deck+'</div></div>');
            // });

            // Append the deckDiv to the playerDiv
            playerDiv.append(deckDiv);

            // Create a button and append it to the playerDiv
            var buttonTargetActionDiv = $('<button class="button_player_target_action" onclick="CardOnTarget('+sub_type+','+players.ID+','+item_name+')" value='+players.ID+'>Choisir</button>');
            playerDiv.append(buttonTargetActionDiv);

            // Add the playerDiv to the playerList
            playerList.append(playerDiv);
        });

        // Show the modal
        modal.show();
    } else if (sub_type == 2) {
        var modal = $('#action-modal-localisation');
        var LocalisationList = $('#name-localisation-action');

        // Clear previous content
        LocalisationList.empty();

        // List of localisations
        var localisations = [
            { id: 1, name: "Cellule A" },
            { id: 2, name: "Douche" },
            { id: 3, name: "Cellule B" },
            { id: 4, name: "Infirmerie" },
            { id: 5, name: "Réfèctoire" },
            { id: 8, name: "Promenade" }
        ];

        localisations.forEach(function(localisation) {

            var localisationDiv = $('<div class="localisation"></div>')
                .append('<div class="localisation_name" value='+localisation.id+'>'+ localisation.name + '</div>');

            // Create a button and append it to the localisationDiv
            var buttonTargetActionDiv = $('<button class="button_localisation_target_action" onclick="ChooseLocation('+ sub_type +',' + localisation.id + ',' + item_name + ')" value="' + localisation.id + '">Choisir</button>');
            localisationDiv.append(buttonTargetActionDiv);

            // Add the localisationDiv to the playerList
            LocalisationList.append(localisationDiv);
        });

        // Show the modal
        modal.show();
    }
}

$(document).ready(function() {
    // Close the modal when the user clicks on the close button
    $('#close-deck-target-action-modal').click(function() {
        $('#action-modal-target').hide();
    });
    
    // Close the modal when the user clicks on the close button
    $('#close-deck-target-modal').click(function() {
        $('#action-modal-target').hide();
    });

    // Close the modal when the user clicks anywhere outside of the modal
    $(window).click(function(event) {
        if (event.target.id === 'action-modal-target') {
            $('#action-modal-target').hide();
        }
    });

    // Close the modal when the user clicks anywhere outside of the modal
    $(window).click(function(event) {
        if (event.target.id === 'close-deck-localisation-action-modal') {
            $('#action-modal-localisation').hide();
        }
    });
});


$('.close_dice').click(function() {
    $('#ModalDice').hide();
});


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
                    if((response.localisation == 4) && (response.player_turn_id == response.player_id) ) {
                        $('#healButton').show();
                    } else {
                        $('#healButton').hide();
                    }
                    if((response.localisation == 1 && response.team == "A" && response.player_turn_id == response.player_id) || (response.localisation == 3 && response.team == "B" && response.player_turn_id == response.player_id)) {
                        $('#creuserButton').show();
                    } else {
                        $('#creuserButton').hide();
                    }
                    if((response.localisation == 7) && (response.player_turn_id == response.player_id) ) {
                        $('#sellButton').show();
                    } else {
                        $('#sellButton').hide();
                    }
                    if(response.player_turn_id == response.player_id) {
                        $('#FinTurnButton').show();
                    } else {
                        $('#FinTurnButton').hide();
                    }


                    if(response.defausse_data !== "") {
                        var tab_defausse = JSON.parse(response.defausse_data);
                        var globalDefausse = tab_defausse[0].img;
                        if(globalDefausse !== null){
                            var url_defausse = 'background-image:url("./img/'+globalDefausse+'");background-size:cover;background-repeat:no-repeat;transform: rotate(90deg);';
                            $("#defausse")[0].style = url_defausse;
                        }
                    } else {
                        var globalDefausse = "";
                    }

                    if(response.pelle_data !== "") {
                        var tab_pelle = JSON.parse(response.pelle_data);
                        var globalPelle = tab_pelle[0].img;
                    } else {
                        var globalPelle = "";
                    }
                    var url_pelle = 'background-image:url("./img/'+globalPelle+'");background-size:cover;background-repeat:no-repeat;';
                    $("#carte4")[0].style = url_pelle;

                    if(response.pioche_data !== "") {
                        var tab_pioche = JSON.parse(response.pioche_data);
                        var globalPioche = tab_pioche[0].img;
                    } else {
                        var globalPioche = "";
                    }
                    var url_pioche = 'background-image:url("./img/'+globalPioche+'");background-size:cover;background-repeat:no-repeat;';
                    $("#carte3")[0].style = url_pioche;

                    if(response.cuillere_data !== "") {
                        var tab_cuillere = JSON.parse(response.cuillere_data);
                        var globalCuillere = tab_cuillere[0].img;
                    } else {
                        var globalCuillere = "";
                    }
                    var url_cuillere = 'background-image:url("./img/'+globalCuillere+'");background-size:cover;background-repeat:no-repeat;transform: rotate(90deg);';
                    $("#carte6")[0].style = url_cuillere;

                    if(response.surin_data !== "") {
                        var tab_surin = JSON.parse(response.surin_data);
                        var globalSurin = tab_surin[0].img;
                    } else {
                        var globalSurin = "";
                    }
                    var url_surin = 'background-image:url("./img/'+globalSurin+'");background-size:cover;background-repeat:no-repeat;';
                    $("#carte2")[0].style = url_surin;
                    $(".count_cigarette")[0].innerHTML = response.cigarette;

                    if(response.playerData[<?php echo $_SESSION['user_id']?>].dice_data[0]){
                        $dice_data_player = JSON.parse(response.playerData[<?php echo $_SESSION['user_id']?>].dice_data)[0];
                        $("#dice")[0].style = 'background-image:url("./img/Dice'+$dice_data_player+'.png");background-size:contain;background-repeat:no-repeat;background-size: contain;background-repeat: no-repeat;background-position: top;';
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

    function GetInfoTarget() {
        // GERER LE CLICK SUR UN JOUEUR
        var tab_player = '<?php echo $game['tab_player']; ?>';
        var tab_id_player = tab_player.split(',');
        var turn = $('.turn')[0].getAttribute('value');
        tab_id_player.forEach(function(id) {
            $("#pawn" + id).click(function() {
                if($(this)[0].className.includes("pawn")){
                    $.ajax({
                        url: 'get_menu_ajax.php',
                        type: 'POST',
                        data: {
                            action: 'get_menu',
                            game_id: <?php echo $game_id; ?>,
                            id : id
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $.ajax({
                                    url: 'decks_target_ajax.php',
                                    type: 'POST',
                                    data: {
                                        action: 'get_deck_target',
                                        game_id: <?php echo $game_id; ?>,
                                        response: response
                                    },
                                    dataType: 'json',
                                    success: function(response) {
                                        if (response.success) {
                                            var deck = response.target.deck_target;
                                            var cigarette = response.target.cigarette;
                                            var raclee = response.target.raclee;
                                            var can_fight = response.target.can_fight;
                                            var pseudo = response.target.pseudo;
                                            var team = response.target.team;
                                            var deck_target_number = response.target.deck_target_number;

                                            $('#deck-target-cards').empty();
                                            if($('#pseudo_team_target').length == 0){
                                                $('#pseudo-target').append('<div id="pseudo_team_target"><div class="pseudo_team_target"><div class="team_target">Team : '+team+'</div><div class="pseudo_target">Pseudo : '+pseudo+'</div></div>');
                                                $('#deck-modal-target').show();
                                            }

                                            // Montrer la modal lorsque l'utilisateur clique sur le deck
                                            if(deck) {
                                                deck.forEach(function(card) {
                                                    var card_description = card.description.replace(/\\'/g, "'");
                                                    $('#deck-target-cards').append('<div class="card modal-content-deck-target" style="background-image:url(./img/'+card.img+');background-size:cover;background-repeat:no-repeat;"><div class="deck-carte_name">' + card.name + '</div><div class="deck-carte_description">' + card_description + '</div></div>');
                                                    $('#deck-modal-target').show();
                                                });
                                            } else {
                                                for(var i=0; i< deck_target_number;i++){
                                                    $('#deck-target-cards').append('<div class="card modal-content-deck-target" style="background-image:url(./img/verso_card.png);background-size:cover;background-repeat:no-repeat;"></div>');
                                                    $('#deck-modal-target').show();
                                                }
                                            }
                                            if(can_fight === "true") {
                                                $('#attackButton').show();
                                            } else {
                                                $('#attackButton').hide();
                                            }

                                            $('#deck-target-infos').append('<div class="map-interactive-area" id="cigarette_target" style="background-image:url(./img/cigarette.png);background-size:cover;background-repeat:no-repeat;background-size: cover;background-repeat: no-repeat;background-position: top;"><div class="count_cigarette_target">'+cigarette+'</div></div>');
                                            $('#deck-modal-target').show();

                                            $('#deck-target-infos').append('<div class="map-interactive-area" id="raclee_target" style="background-image:url(./img/raclee.png);background-size:cover;background-repeat:no-repeat;background-size: cover;background-repeat: no-repeat;background-position: top;"><div class="count_raclee_target">'+raclee+'</div></div>');
                                            $('#deck-modal-target').show();

                                            // Close modal
                                            $('#close-deck-target-modal').click(function() {
                                                $('#deck-modal-target').hide();
                                            });

                                        } else {
                                            console.log('Erreur : ' + response.message);
                                        }

                                    },
                                    error: function(xhr, status, error) {
                                        console.log('Erreur AJAX : ' + error);
                                    }
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
            })
        });
    }
    // JavaScript pour gérer les interactions du jeu
    function initializeGameBoard() {
        // Initialisation du plateau de jeu si nécessaire
    }

    // Initialiser le plateau de jeu
    initializeGameBoard();
    RefreshTurn();
    GetInfoTarget();

    setInterval(function() {
        GetInfoTarget(); // Recharger les parties toutes les 2 secondes (2000 ms)
    }, 1000); // Répéter toutes les 2 secondes (2000 ms)

    setInterval(function() {
        RefreshTurn(); // Recharger les parties toutes les 2 secondes (2000 ms)
    }, 50); // Répéter toutes les 2 secondes (2000 ms)
});
</script>
