<?php
require_once __DIR__ . '/../includes/auth.php';
if (isLoggedIn()) {
    header('Location: /work_folder/realRealestate/index.php');
    exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $errors = [];
    if (empty($fullName)) $errors[] = 'Full name is required.';
    if (!validateEmail($email)) $errors[] = 'Valid email address is required.';
    if (empty($phone)) $errors[] = 'Phone number is required.';
    $passwordErrors = validatePassword($password);
    if (!empty($passwordErrors)) $errors = array_merge($errors, $passwordErrors);
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if (empty($errors)) {
        try {
            $db = getDB();
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'An account with this email already exists.';
            } else {
                $stmt = $db->prepare("INSERT INTO users (role_id, full_name, email, phone, password_hash, status) VALUES (2, ?, ?, ?, ?, 'pending')");
                $stmt->execute([$fullName, $email, $phone, password_hash($password, PASSWORD_DEFAULT)]);
                $userId = $db->lastInsertId();
                logAudit($userId, 'user.register', 'user', $userId, null, ['email' => $email, 'status' => 'pending'], 'pass');
                $_SESSION['success'] = 'Account created! Pending admin approval.';
                header('Location: /work_folder/realRealestate/public/login.php');
                exit;
            }
        } catch (Exception $e) {
            $errors[] = 'System error. Please try again later.';
            error_log("Registration error: " . $e->getMessage());
        }
    }
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        header('Location: /work_folder/realRealestate/public/register.php');
        exit;
    }
}
$pageTitle = 'Create Account - Zahara Co-Working Space';
require_once __DIR__ . '/../includes/header.php';
?>
<section class="auth-page">
    <div class="auth-card">
        <h1>Create Account</h1>
        <p>Register for a Zahara account. Approval required to access rentals.</p>
        <form method="POST" action="">
            <div class="form-group"><label for="full_name">Full Name</label><input type="text" id="full_name"
                    name="full_name" placeholder="John Doe" required></div>
            <div class="form-group"><label for="email">Email Address</label><input type="email" id="email" name="email"
                    placeholder="you@example.com" required></div>
            <div class="form-group"><label for="phone">Phone Number</label><input type="tel" id="phone" name="phone"
                    placeholder="+254 7XX XXX XXX" required></div>
            <div class="form-group"><label for="password">Password</label><input type="password" id="password"
                    name="password" placeholder="Min 8 chars, upper, lower, number" required></div>
            <div class="form-group"><label for="confirm_password">Confirm Password</label><input type="password"
                    id="confirm_password" name="confirm_password" placeholder="Repeat password" required></div>
            <button type="submit" class="btn btn-primary"
                style="width: 100%; justify-content: center; margin-top: 8px;">Create Account</button>
        </form>
        <div class="form-footer">
            <p>Already have an account? <a href="/work_folder/realRealestate/public/login.php">Sign In</a></p>
        </div>
</section>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>