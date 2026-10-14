<?php
require_once __DIR__ . '/settings_loader.php';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');
$basePathUrl = htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8');
?>
<!-- Dynamic Site Header -->
<header class="navbar">
    <a href="<?= $basePathUrl ?>/index.php" class="logo"><?= siteSetting('logo_text', 'HR Auto Mobile') ?>
        <span>.</span></a>
    <div class="menu">
        <a href="<?= $basePathUrl ?>/index.php" class="nav-link">Home</a>
        <a href="<?= $basePathUrl ?>/about.php" class="nav-link">About</a>
        <a href="<?= $basePathUrl ?>/services.php" class="nav-link">Services</a>
        <a href="<?= $basePathUrl ?>/contact.php" class="nav-link">Contact</a>
        <div class="user">
            <a href="<?= $basePathUrl ?>/dashboard.php" title="Customer Dashboard" class="user-profile-icon"><i class='bx bx-user'></i></a>
            <?php if (!empty($_SESSION['user_id'])): ?>
                <a href="<?= $basePathUrl ?>/logout.php" class="btn-logout-nav" title="Logout"><i class='bx bx-log-out'></i> Logout</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="menu-icon">
        <div class="line1"></div>
        <div class="line2"></div>
        <div class="line3"></div>
    </div>
</header>
<script src="assets/js/script.js"></script>