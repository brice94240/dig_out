<?php
session_start();
require_once 'config.php';
if (isset($_SESSION['user_id'])) {
    // Rediriger vers la page de connexion si l'utilisateur n'est pas connecté
    header("Location: ./room.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page d'accueil</title>
    <link href="./css/style.css" rel="stylesheet" />
</head>
<body>
<div class="container_accueil">
<?php
if(!isset($_GET['login']) && !isset($_GET['signin'])){
?>
    <h1>DIG OUT ONLINE</h1>
    <h1>Accueil</h1>
    <div class="button-container">
        <a href="./?login"><button class="button">Connexion</button></a>
        <a href="./?signin"><button class="button">Inscription</button></a>
    </div>
<?php
} elseif(isset($_GET['login']) && !isset($_GET['signin'])){
?>
    <h1>DIG OUT ONLINE</h1>
    <h1>Connexion</h1>
    <div class="button-container">
        <a href="./?login"><button class="button">Connexion</button></a>
        <a href="./?signin"><button class="button">Inscription</button></a>
    </div>
    <form class="form_container" action="login_process.php" method="post">
        <div>
            <label for="pseudo">Nom d'utilisateur:</label>
            <input type="text" id="pseudo" name="pseudo" required>
        </div>
        <div>
            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <button type="submit" class="button-submit">Se connecter</button>
        </div>
    </form>
<?php
} elseif(isset($_GET['signin']) && !isset($_GET['login'])){
?>
    <h1>DIG OUT ONLINE</h1>
    <h1>Inscription</h1>
    <div class="button-container">
        <a href="./?login"><button class="button">Connexion</button></a>
        <a href="./?signin"><button class="button">Inscription</button></a>
    </div>
    <form class="form_container" action="signin_process.php" method="post">
        <div>
            <label for="pseudo">Nom d'utilisateur:</label>
            <input type="text" id="pseudo" name="pseudo" required>
        </div>
        <div>
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
        </div>
        <div>
            <label for="password">Mot de passe:</label>
            <input type="password" id="password" name="password" required>
        </div>
        <div>
            <button type="submit" class="button-submit">S'inscrire</button>
        </div>
    </form>
<?php
}
?>
</div>
</body>
</html>
