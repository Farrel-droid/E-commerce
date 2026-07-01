<?php
// register.php
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($address)) {
        $error_msg = "Please fill in all details.";
    } else {
        $userData = [
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'address' => $address
        ];

        $api_response = registerUser($userData);

        if ($api_response['success']) {
            $redirect_query = isset($_GET['redirect']) ? '&redirect=' . urlencode($_GET['redirect']) : '';
            header("Location: login.php?registered=1" . $redirect_query);
            exit;
        } else {
            $error_msg = $api_response['error'];
        }
    }
}

$page_title = "Create Account";
require_once 'header.php';
?>

<div style="max-width: 550px; margin: 40px auto;">
    <div class="form-box">
        <div style="text-align: center; margin-bottom: 25px;">
            <i data-lucide="user-plus" style="width: 40px; height: 40px; color: var(--accent); margin-bottom: 10px;"></i>
            <h2 style="font-family: var(--font-heading); font-size: 26px; font-weight: 700; color: var(--text-primary);">Create Account</h2>
            <p style="font-size: 14px; color: var(--text-secondary); margin-top: 5px;">Join ZenSpace to save orders and get seamless checkout</p>
        </div>

        <?php if ($error_msg): ?>
            <div class="alert alert-danger" style="margin-bottom: 20px;">
                <i data-lucide="alert-triangle" style="width: 16px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
                <?php echo htmlspecialchars($error_msg); ?>
            </div>
        <?php endif; ?>

        <form action="register.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" method="POST">
            <div class="form-group">
                <label class="form-label" for="name">Full Name</label>
                <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Farrel Hartono" required value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label class="form-label" for="email">Email Address</label>
                    <input type="email" name="email" id="email" class="form-control" placeholder="yourname@example.com" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input type="tel" name="phone" id="phone" class="form-control" placeholder="e.g. 08123456789" required value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" placeholder="Minimum 6 characters" required>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" for="address">Shipping Address</label>
                <textarea name="address" id="address" class="form-control" placeholder="Street Name, Building/House Number, City, Province, Zip Code" required><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
            </div>

            <button type="submit" class="btn-primary" style="width: 100%; border: none; cursor: pointer; display: flex; justify-content: center; align-items: center; gap: 8px;">
                <i data-lucide="user-check" style="width: 18px; height: 18px;"></i> Sign Up
            </button>
        </form>

        <div style="text-align: center; margin-top: 25px; border-top: 1px solid var(--border-color); padding-top: 20px; font-size: 14px; color: var(--text-secondary);">
            Already have an account? 
            <a href="login.php<?php echo isset($_GET['redirect']) ? '?redirect=' . urlencode($_GET['redirect']) : ''; ?>" style="color: var(--accent); font-weight: 600; text-decoration: underline;">
                Log in here
            </a>
        </div>
    </div>
</div>

<?php
require_once 'footer.php';
?>
