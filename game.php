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
    <div class="map-interactive-area" id="piece1" onclick="zoneClicked('Douches')"></div>
    <div class="map-interactive-area" id="piece2" onclick="zoneClicked('Cellules')"></div>
    <div class="map-interactive-area" id="piece3" onclick="zoneClicked('Infirmerie')"></div>
    <div class="map-interactive-area" id="piece4" onclick="zoneClicked('Réfectoire')"></div>
    <div class="map-interactive-area" id="piece5" onclick="zoneClicked('Isolement')"></div>
    <div class="map-interactive-area" id="piece6" onclick="zoneClicked('Promenade')"></div>

    <!-- Zones des cartes -->
    <div class="map-interactive-area" id="carte1" onclick="zoneClicked('Fouilles')"></div>
    <div class="map-interactive-area" id="carte2" onclick="zoneClicked('Armes')"></div>
    <div class="map-interactive-area" id="carte3" onclick="zoneClicked('Pioches')"></div>
    <div class="map-interactive-area" id="carte4" onclick="zoneClicked('Pelles')"></div>
    <div class="map-interactive-area" id="carte5" onclick="zoneClicked('Defausse')"></div>
</div>

<!-- The Modal -->
<div id="gangModal" class="modal">
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
</body>
</html>

<script>
// Get the modal
var modal = document.getElementById("gangModal");

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
}

function zoneClicked(zoneName) {
    alert(zoneName + ' cliquée!');
    // Ajoutez ici le code JavaScript pour gérer les interactions spécifiques
}
$(document).ready(function() {
    // JavaScript pour gérer les interactions du jeu
    function initializeGameBoard() {
        // Initialisation du plateau de jeu si nécessaire
    }

    // Initialiser le plateau de jeu
    initializeGameBoard();
});
</script>
