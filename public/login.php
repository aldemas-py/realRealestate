<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) {
    header('Location: /work_folder/realRealestate/index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (empty($email) || empty($password)) {
        $_SESSION['error'] = 'Please fill in all fields.';
    } else {
        $result = loginUser($email, $password);
        if ($result['success']) {
            $redirect = $_SESSION['redirect_after_login'] ?? '/work_folder/realRealestate/index.php';
            unset($_SESSION['redirect_after_login']);
            $_SESSION['success'] = 'Welcome back, ' . $result['user']['full_name'] . '!';
            header("Location: $redirect");
            exit;
        } else {
            $_SESSION['error'] = $result['message'];
        }
    }
    header('Location: /work_folder/realRealestate/public/login.php');
    exit;
}
$pageTitle = 'Login - Zahara Co-Working Space';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="auth-page">
    <div class="auth-card">
        <h1>Welcome Back</h1>
        <p>Sign in to your Zahara Co-Working Space account</p>
        <form method="POST" action="">
            <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email"
                    placeholder="you@example.com" required></div>
            <div class="form-group"><label for="password">Password</label><input type="password" id="password"
                    name="password" placeholder="Enter your password" required></div>
            <div class="form-actions"><label
                    style="display: flex; align-items: center; gap: 8px; font-size: 0.85rem; color: var(--text-mid);"><input
                        type="checkbox" name="remember"> Remember me</label></div>
            <button type="submit" class="btn btn-primary"
                style="width: 100%; justify-content: center; margin-top: 16px;">Sign In</button>
        </form>
        <div class="form-footer">
            <p>Don't have an account? <a href="/work_folder/realRealestate/public/register.php">Create Account</a></p>
        </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>