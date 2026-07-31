<?php

/**
 * SITE HEADER - Zahara Co-Working Space
 */
require_once __DIR__ . '/auth.php';
$currentUser = getCurrentUser();
$isAdmin = isAdmin();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Zahara Co-Working Space - Premium Workspaces in Westlands, Nairobi' ?></title>
    <meta name="description"
        content="Premium co-working spaces for rent in Westlands, Nairobi. Private offices, open workspaces, boardrooms, and virtual office packages at Krishna Centre.">
    <link rel="stylesheet" href="/work_folder/realRealestate/assets/css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <header class="site-header">
        <div class="container">
            <a href="/work_folder/realRealestate/index.php" class="logo">
                <span class="logo-icon" style="color: #1565C0;">Z</span>
                <span class="logo-text">Zahara <span style="color: #1565C0; font-weight: 300;">Co-Working</span>
            </a>
            <nav class="main-nav">
                <ul>
                    <li><a href="/work_folder/realRealestate/public/spaces.php">Spaces</a></li>
                    <li><a href="/work_folder/realRealestate/public/testimonials.php">Testimonials</a></li>
                    <li><a href="/work_folder/realRealestate/public/about.php">About</a></li>
                    <li><a href="/work_folder/realRealestate/public/contact.php">Contact</a></li>
                </ul>
            </nav>
            <div class="header-actions">
                <?php if ($currentUser): ?>
                    <div class="user-dropdown">
                        <button class="btn btn-ghost user-btn">
                            <span class="user-avatar"><?= strtoupper(substr($currentUser['full_name'], 0, 1)) ?></span>
                            <?= htmlspecialchars($currentUser['full_name']) ?>
                        </button>
                        <div class="dropdown-menu">
                            <a href="/work_folder/realRealestate/public/profile.php">My Profile</a>
                            <?php if ($isAdmin): ?>
                                <a href="/work_folder/realRealestate/admin/index.php">Admin Panel</a>
                            <?php endif; ?>
                            <a href="/work_folder/realRealestate/public/logout.php">Logout</a>
                        </div>
                    <?php else: ?>
                        <a href="/work_folder/realRealestate/public/login.php" class="btn btn-outline">Login</a>
                        <a href="/work_folder/realRealestate/public/register.php" class="btn btn-primary">Get Started</a>
                    <?php endif; ?>
                    <button class="mobile-menu-toggle" aria-label="Toggle menu">&#9776;</button>
                    </div>
    </header>

    <main class="main-content">
        <div class="container">
            <?php displayFlashMessages(); ?>