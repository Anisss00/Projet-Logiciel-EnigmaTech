<?php
session_start();
require_once './include/connecte.php';
include './include/verifConnect.php';

$page_title = $page_title ?? "Administration";
include './include/header.php';


/* AJOUT utilisateur */
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_utilisateur'])) {

    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $dateNaiss = $_POST['dateNaiss'] ?? '';
    if ($_SESSION['user_role'] === 'Admin') {
        $idOperateur = $_POST['IdOperateur'] ?? '';
    } else {
        $idOperateur = $_SESSION['user_IdOperateur'];
    }

    $mdpHash = password_hash($password, PASSWORD_DEFAULT);


    $stmt = $conn->prepare("INSERT INTO utilisateur (nom, prenom, pseudo, mdp, email, dateNaissance, role, IdOperateur) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ");
    $pseudo = strtolower($prenom . "." . $nom);

    $stmt->bind_param("sssssssi", $nom, $prenom, $pseudo, $mdpHash, $email, $dateNaiss, $role, $idOperateur);

    if ($stmt->execute()) {

        $message = "Utilisateur ajouté avec succès.";

    } else {

        $error = "Erreur SQL : " . $stmt->error;

    }

    $stmt->close();
}

/* AJOUT SOCIÉTÉ*/
$messageSociete = '';
$errorSociete = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajouter_societe'])) {

    $nomSociete = trim($_POST['nomSociete'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    $stmtSociete = $conn->prepare("INSERT INTO societe(nomSociete, email, adresse, telephone) VALUES (?, ?, ?, ?)");

    $stmtSociete->bind_param("ssss", $nomSociete, $email, $adresse, $telephone);

    if ($stmtSociete->execute()) {
        $messageSociete = "Société ajoutée avec succès.";
    } else {
        $errorSociete = "Erreur SQL : " . $stmtSociete->error;
    }
    $stmtSociete->close();

}



?>

<body class="has-header">

    <div class="container">

        <h1>Ajouter un utilisateur</h1>

        <?php if (!empty($message)): ?>
            <p class="success"><?= $message ?></p>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <p class="error"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST">

            <label>Nom</label>
            <input type="text" name="nom" required>

            <label>Prénom</label>
            <input type="text" name="prenom" required>

            <label>Email</label>
            <input type="email" name="email" required>

            <label>Date de naissance</label>
            <input type="date" name="dateNaiss" required>

            <label>Mot de passe</label>
            <input type="password" name="password" required>

            <label>Rôle</label>
            <select name="role" required>
                <option value="Gamemaster">Gamemaster</option>
                <option value="Opérateur">Opérateur</option>
            </select>


            <?php
            if ($_SESSION['user_role'] === 'Admin') {
                $querySociete = "SELECT IdOperateur, nomSociete FROM societe";
                $resultSociete = $conn->query($querySociete); ?>

                <label>Société</label>
                <select name="IdOperateur" required>
                    <?php while ($societe = $resultSociete->fetch_assoc()) { ?>
                        <option value="<?= $societe['IdOperateur'] ?>">
                            <?= htmlspecialchars($societe['nomSociete']) ?>
                        </option> <?php } ?>
                <?php } ?>
            </select>

            <button type="submit" name="ajouter_utilisateur">
                Ajouter
            </button>

        </form>

    </div>

    <?php if ($_SESSION['user_role'] === 'Admin') { ?>

        <div class="container">

            <h1>Ajouter une société</h1>

            <?php if (!empty($messageSociete)): ?>
                <p class="success"><?= $messageSociete ?></p>
            <?php endif; ?>

            <?php if (!empty($errorSociete)): ?>
                <p class="error"><?= $errorSociete ?></p>
            <?php endif; ?>

            <form method="POST">

                <label>Nom de la société</label>
                <input type="text" name="nomSociete" required>

                <label>Adresse</label>
                <input type="text" name="adresse" required>

                <label>Téléphone</label>
                <input type="text" name="telephone" required>

                <label>Email</label>
                <input type="email" name="email" required>

                <button type="submit" name="ajouter_societe">
                    Ajouter la société
                </button>

            </form>

        </div>

    <?php } ?>



</body>

<?php
include './include/footer.php';
?>