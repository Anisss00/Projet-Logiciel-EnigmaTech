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
        <img src="./image/logo.png" alt="Logo" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
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
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
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
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="8" r="4"/>
                <path d="M20 21a8 8 0 1 0-16 0"/>
            </svg>
            <?php if (!empty($_SESSION['user_nom'])): ?>
                <span class="profile-name"><?= htmlspecialchars($_SESSION['user_nom']) ?></span>
            <?php endif; ?>

            <!-- DROPDOWN PROFIL -->
            <div class="dropdown profile-dropdown" id="profileDropdown">
                <div class="dropdown-header">
                    <?= htmlspecialchars($_SESSION['user_nom'] ?? 'Utilisateur') ?>
                </div>
                <a class="dropdown-item" href="profil.php">Mon profil</a>
                <div class="dropdown-divider"></div>
                <a class="dropdown-item dropdown-item--danger" href="logout.php">Se déconnecter</a>
            </div>
        </div>

    </div>
</header>
