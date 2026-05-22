<?php
// $page_title doit être définie AVANT l'include du header
$page_title = $page_title ?? "";

// Notifications fictives pour l'instant (à remplacer par une vraie requête SQL plus tard)
$notifications = [
    // ["message" => "Nouvel utilisateur inscrit", "time" => "il y a 5 min"],
];
$notif_count = count($notifications);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo(htmlspecialchars($page_title)); ?> Connexion</title>
    <link rel="stylesheet" href="./include/style.css">
</head>
<body>
<header class="header">

    <!-- LOGO -->
    <div class="header-logo">
        <a href="./index.php">
        <img src="./image/logo.png" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
        </a>
        <div class="logo-placeholder" style="display:none">LOGO</div>
    </div>

    <!-- TITRE DE LA PAGE -->
    <div class="header-title">
        <h1><?= htmlspecialchars($page_title) ?></h1>
    </div>

    <!-- ACTIONS DROITE -->
    <div class="header-actions">

        <!-- CLOCHE NOTIFICATIONS -->
        <div class="header-btn notif-wrapper" id="notifToggle" title="Notifications">
            <img src="./image/cloche.png" alt="Notifications" width="20" height="20" style="display:block;">
            <?php if ($notif_count > 0): ?>
                <span class="notif-badge"><?= $notif_count ?></span>
            <?php endif; ?>

            <!-- DROPDOWN NOTIFICATIONS -->
            <div class="dropdown notif-dropdown" id="notifDropdown">
                <div class="dropdown-header">Notifications</div>
                <?php if ($notif_count === 0): ?>
                    <div class="dropdown-empty">Aucune notification</div>
                <?php else: ?>
                    <?php foreach ($notifications as $n): ?>
                        <div class="dropdown-item">
                            <span><?= htmlspecialchars($n['message']) ?></span>
                            <small><?= htmlspecialchars($n['time']) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- PROFIL UTILISATEUR -->
        <div class="header-btn profile-wrapper" id="profileToggle" title="Mon profil">
            <img src="./image/profils.png" alt="Profil" width="20" height="20" style="display:block;">
            <?php if (!empty($_SESSION['user_nom'])): ?>
                <span class="profile-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></span>
            <?php endif; ?>

            <!-- DROPDOWN PROFIL -->
            <div class="dropdown profile-dropdown" id="profileDropdown">
                <div class="dropdown-header">
                    <?= htmlspecialchars($_SESSION['user_nom'] ?? 'Utilisateur') ?></br>
                    <?= htmlspecialchars($_SESSION['user_role'] ?? 'Utilisateur') ?></br>
                    Entreprise</br>
                </div>
                <?php if ($_SESSION['user_role'] === 'admin') { ?>
                    <a class="dropdown-item" href="./admin.php">Administration</a>
                    <div class="dropdown-divider"></div>
                <?php } ?>
                <a class="dropdown-item dropdown-item--danger" href="./include/logout.php">Se déconnecter</a>
            </div>
        </div>

    </div>
</header>
