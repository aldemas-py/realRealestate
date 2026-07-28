<?php

/**
 * ADMIN LOGIN
 */
require_once __DIR__ . '/../includes/auth.php';

// Redirect if already admin
if (isAdmin()) {
    header('Location: /work_folder/realRealestate/admin/index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $result = loginUser($email, $password);
    if ($result['success']) {
        if (isAdmin()) {
            $_SESSION['success'] = 'Welcome to the Admin Panel!';
            header('Location: /work_folder/realRealestate/admin/index.php');
        } else {
            logoutUser();
            $_SESSION['error'] = 'Access denied. Admin credentials required.';
            header('Location: /work_folder/realRealestate/admin/login.php');
        }
    } else {
        $_SESSION['error'] = $result['message'];
        header('Location: /work_folder/realRealestate/admin/login.php');
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - FlexiSpace</title>
    <link rel="stylesheet" href="/work_folder/realRealestate/assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <style>
        body {
            background: var(--primary-dark);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .admin-login-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 40px;
            width: 100%;
            max-width: 400px;
            box-shadow: var(--shadow-lg);
        }

        .admin-login-card h1 {
            text-align: center;
            font-size: 1.3rem;
            margin-bottom: 8px;
        }

        .admin-login-card p {
            text-align: center;
            color: var(--text-mid);
            margin-bottom: 24px;
            font-size: 0.9rem;
        }

        .admin-login-card .logo {
            justify-content: center;
            margin-bottom: 16px;
        }
    </style>
</head>

<body>
    <div class="admin-login-card">
        <div class="logo">
            <span class="logo-icon">&#9670;</span>
            <span class="logo-text">FlexiSpace Admin</span>
        </div>
        <h1>Admin Sign In</h1>
        <p>Enter your admin credentials</p>

        <?php
        session_start();
        if (isset($_SESSION['error'])):
        ?>
            <div class="alert alert-error">
                <span class="alert-icon">&#10060;</span>
                <p><?= $_SESSION['error'] ?></p>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
        <?php unset($_SESSION['error']);
        endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="email">Admin Email</label>
                <input type="email" id="email" name="email" placeholder="admin@realestate.co.ke" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; justify-content: center;">Sign In</button>
        </form>
        <div style="text-align: center; margin-top: 16px;">
            <a href="/work_folder/realRealestate/index.php" style="font-size: 0.85rem;">&larr; Back to Website</a>
        </div>
</body>

</html>