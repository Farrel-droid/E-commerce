<?php
// header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Calculate total cart items
$cart_count = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += intval($item['quantity']);
    }
}

// Helper to check active nav link
function is_active_page($page_name) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return ($current_page === $page_name) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | ZenSpace" : "ZenSpace | Curated Workspace Essentials"; ?></title>
    
    <!-- Link CSS -->
    <link rel="stylesheet" href="style.css">
    
    <!-- Optional: Lucide Icons for premium visual touches -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>

<header>
    <div class="container header-container">
        <a href="index.php" class="logo">
            <i data-lucide="compass"></i> Zen<span>Space</span>
        </a>
        
        <!-- Search form submitting query parameters to index.php -->
        <form action="index.php" method="GET" class="search-form">
            <div class="search-wrapper">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Search premium essentials..." 
                    class="search-input"
                    value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                >
                <button type="submit" class="search-btn">
                    <i data-lucide="search"></i>
                </button>
            </div>
            <?php if (isset($_GET['category'])): ?>
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($_GET['category']); ?>">
            <?php endif; ?>
        </form>

        <nav class="nav-links">
            <a href="index.php" class="nav-item <?php echo is_active_page('index.php'); ?>">Shop</a>
            <a href="orders.php" class="nav-item <?php echo is_active_page('orders.php'); ?>">Orders</a>
            <a href="cart.php" class="cart-icon-wrapper <?php echo is_active_page('cart.php'); ?>">
                <i data-lucide="shopping-cart"></i>
                Cart
                <span class="cart-badge" id="cart-counter"><?php echo $cart_count; ?></span>
            </a>
            <?php if (isset($_SESSION['user'])): ?>
                <span style="font-size: 14px; color: var(--accent); font-weight: 600; display: inline-flex; align-items: center; gap: 4px; border-left: 1px solid var(--border-color); padding-left: 15px; margin-left: 5px;">
                    <i data-lucide="user" style="width: 15px; height: 15px;"></i>
                    Hi, <?php echo htmlspecialchars(explode(' ', $_SESSION['user']['name'])[0]); ?>
                </span>
                <a href="logout.php" class="nav-item">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-item <?php echo is_active_page('login.php'); ?>" style="border-left: 1px solid var(--border-color); padding-left: 15px; margin-left: 5px;">Login</a>
                <a href="register.php" class="nav-item <?php echo is_active_page('register.php'); ?>">Register</a>
            <?php endif; ?>
        </nav>
    </div>
</header>

<main class="container animated-fade-in" style="min-height: 80vh; padding: 20px 15px;">
