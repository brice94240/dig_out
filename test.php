<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Map</title>
    <style>
        .map-container {
            position: relative;
            width: 100%;
            max-width: 1000px;
            margin: auto;
        }
        .map-image {
            width: 100%;
            display: block;
        }
        .map-interactive-area {
            position: absolute;
            border: 2px solid transparent; /* Initialement transparent, peut être coloré pour débogage */
            cursor: pointer;
        }
        /* Styles pour les différentes zones des gangs */
        #gang1 { top: 0%; left: 0%; width: 20%; height: 17%; }  /* CREW */
        #gang2 { top: 18%; left: 0%; width: 20%; height: 15%; } /* CARTEL */
        #gang3 { top: 34%; left: 0%; width: 20%; height: 15%; } /* BIKERS */
        #gang4 { top: 50%; left: 0%; width: 20%; height: 16%; } /* BRATVA */
        #gang5 { top: 67%; left: 0%; width: 20%; height: 15%; } /* TRIAD */
        #gang6 { top: 83%; left: 0%; width: 20%; height: 16%; } /* QUEERS */
        
        /* Styles pour les différentes zones des pièces */
        #piece1 { top: 3%; left: 23%; width: 33%; height: 24%; } /* Douches */
        #piece2 { top: 3%; left: 58%; width: 39%; height: 23%; } /* Cellules */
        #piece3 { top: 29%; left: 23%; width: 24%; height: 23%; } /* Infirmerie */
        #piece4 { top: 29%; left: 49%; width: 47%; height: 23%; } /* Réfectoire */
        #piece5 { top: 53%; left: 22%; width: 16%; height: 24%; } /* Isolement */
        #piece6 { top: 53%; left: 40%; width: 57%; height: 21%; } /* Promenade */
    </style>
</head>
<body>
    <div class="map-container">
        <img src="./img/map.png" alt="Game Map" class="map-image">
        
        <!-- Zones des gangs -->
        <div class="map-interactive-area" id="gang1" onclick="zoneClicked('Gang CREW')"></div>
        <div class="map-interactive-area" id="gang2" onclick="zoneClicked('Gang CARTEL')"></div>
        <div class="map-interactive-area" id="gang3" onclick="zoneClicked('Gang BIKERS')"></div>
        <div class="map-interactive-area" id="gang4" onclick="zoneClicked('Gang BRATVA')"></div>
        <div class="map-interactive-area" id="gang5" onclick="zoneClicked('Gang TRIAD')"></div>
        <div class="map-interactive-area" id="gang6" onclick="zoneClicked('Gang QUEERS')"></div>
        
        <!-- Zones des pièces -->
        <div class="map-interactive-area" id="piece1" onclick="zoneClicked('Douches')"></div>
        <div class="map-interactive-area" id="piece2" onclick="zoneClicked('Cellules')"></div>
        <div class="map-interactive-area" id="piece3" onclick="zoneClicked('Infirmerie')"></div>
        <div class="map-interactive-area" id="piece4" onclick="zoneClicked('Réfectoire')"></div>
        <div class="map-interactive-area" id="piece5" onclick="zoneClicked('Isolement')"></div>
        <div class="map-interactive-area" id="piece6" onclick="zoneClicked('Promenade')"></div>
    </div>

    <script>
        function zoneClicked(zoneName) {
            alert(zoneName + ' cliquée!');
            // Ajoutez ici le code JavaScript pour gérer les interactions spécifiques
        }
    </script>
</body>
</html>