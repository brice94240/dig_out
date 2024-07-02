<?php
require_once 'config.php';

// Démarrer la session
session_start();

// Obtenir les données du formulaire
$pseudo = $_POST['pseudo'];
$password = $_POST['password'];

// Vérifier si les données sont présentes
if (empty($pseudo) || empty($password)) {
    echo "Veuillez remplir tous les champs.";
    exit;
}

try {
    // Vérifier si l'utilisateur existe et récupérer son mot de passe haché
    $stmt = $pdo->prepare("SELECT id, password FROM joueurs WHERE pseudo = :pseudo");
    $stmt->execute(['pseudo' => $pseudo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Connexion réussie, créer une session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['pseudo'] = $pseudo;

        echo "Connexion réussie !";
        // Rediriger vers une page protégée
        header("Location: ./room.php");
        exit;
    } else {
        header("Location: ./?login");
        exit;
    }
} catch (PDOException $e) {
    echo "Erreur lors de la connexion : " . $e->getMessage();
}
?>