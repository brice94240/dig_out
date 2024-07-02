<?php
require_once 'config.php';

// Obtenir les données du formulaire
$pseudo = $_POST['pseudo'];
$email = $_POST['email'];
$password = $_POST['password'];

// Vérifier si les données sont présentes
if (empty($pseudo) || empty($email) || empty($password)) {
    echo "Veuillez remplir tous les champs.";
    exit;
}

try {
    // Vérifier si l'utilisateur ou l'email existe déjà
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM joueurs WHERE pseudo = :pseudo OR email = :email");
    $stmt->execute(['pseudo' => $pseudo, 'email' => $email]);
    $userExists = $stmt->fetchColumn();

    if ($userExists) {
        header("Location: ./?signin");
        exit;
    } else {
        // Ajouter un nouvel utilisateur
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO joueurs (pseudo, email, password) VALUES (:pseudo, :email, :password)");
        $stmt->execute(['pseudo' => $pseudo, 'email' => $email, 'password' => $hashedPassword]);
        
        header("Location: ./?login");
        exit;
    }
} catch (PDOException $e) {
    echo "Erreur lors de l'inscription : " . $e->getMessage();
}
?>
