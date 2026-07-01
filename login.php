<?php
// login.php
require_once 'api_helper.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isset($_SESSION['user'])) {
    header("Location: index.php");
    exit;
}

$error_msg = null;
$success_msg = null;

if (isset($_GET['registered'])) {
    $success_msg = "Account created successfully! Please log in to continue.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error_msg = "Please enter both email and password.";
    } else {
        $api_response = loginUser($email, $password);

        if ($api_response['success']) {
            $_SESSION['user'] = $api_response['data'];
            
            // Redirect to desired page or default to index.php
            $redirect = $_GET['redirect'] ?? 'index.php';
            // Simple sanitization to prevent open redirect
            if (strpos($redirect, '/') !== false || strpos($redirect, 'http') !== false) {
                $redirect = 'index.php';
            }
            header("Location: " . $redirect);
            exit;
        } else {
            $error_msg = $api_response['error'];
        }
    }
}

$page_title = "Log In";
require_once 'header.php';
?>

<div style="max-width: 450px; margin: 40px auto;">
    <div class="form-box">
        <div style="text-align: center; margin-bottom: 25px;">
            <i data-lucide="lock" style="width: 40px; height: 40px; color: var(--accent); margin-bottom: 10px;"></i>
            <h2 style="font-family: var(--font-heading); font-size: 26px; font-weight: 700; color: var(--text-primary);">Welcome Back</h2>
            <p style="font-size: 14px; color: var(--text-secondary); margin-top: 5px;">Sign in to access your orders and checkout faster</p>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <i data-lucide="alert-triangle" style="width: 16px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <?php if ($success_msg): ?>
            <div class="alert alert-success" style="margin-bottom: 20px;">
                <i data-lucide="check-circle-2" style="width: 16px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                <?php echo htmlspecialchars($success_msg); ?>
            </div>
        <?php endif; ?>

        <form action="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST">
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <input type="email" name="email" id="email" class="form-control" placeholder="yourname@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;">
                <i data-lucide="log-in" style="width: 18px; height: 18px;"></i> Sign In
            </button>
        </form>

        <div style="text-align: center; margin-top: 25px; border-top: 1px solid var(--border-color); padding-top: 20px; font-size: 14px; color: var(--text-secondary);">
            Don't have an account? 
            <a href="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" style="color: var(--accent); font-weight: 600; text-decoration: underline;">
                Register here
            </a>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>
