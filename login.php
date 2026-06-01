<?php

session_start();

require_once './include/connecte.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {

        $error = "Veuillez remplir tous les champs.";

    } else {

        mysqli_query($conn, "SET NAMES 'utf8'");

        // Récupère l'utilisateur correspondant à l'email, avec le nom de sa société
        $stmt = $conn->prepare("
            SELECT u.IdUtilisateur, u.nom, u.role, u.mdp, u.IdOperateur, s.nomSociete
            FROM utilisateur u
            INNER JOIN societe s ON u.IdOperateur = s.IdOperateur
            WHERE u.email = ?
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {

            $user = $result->fetch_assoc();

            // Vérifie le mot de passe contre le hash stocké en BDD
            if (password_verify($password, $user['mdp'])) {

                // Stocke les infos utilisateur en session et redirige vers l'accueil
                $_SESSION['user_id'] = $user['IdUtilisateur'];
                $_SESSION['user_nom'] = $user['nom'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_IdOperateur'] = $user['IdOperateur'];
                $_SESSION['user_societe'] = $user['nomSociete'];

                header("Location: index.php");
                exit();

            } else {
                $error = "Email ou mot de passe incorrect.";
            }
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion</title>
    <link rel="stylesheet" href="./include/style.css">
</head>

<body>

    <div class="card">

        <div class="card-header">
            <h1>Connexion</h1>
            <p>Entrez vos identifiants pour accéder à votre compte.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php">

            <div class="form-group">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email" placeholder="exemple@domaine.fr"
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Mot de passe</label>
                <input type="password" id="password" name="password" placeholder="••••••••" required
                    autocomplete="current-password">
            </div>

            <button type="submit" class="btn-primary">Se connecter</button>

        </form>

    </div>

</body>

</html>