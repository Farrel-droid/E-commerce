<?php
// cart.php
require_once 'api_helper.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 1. Process GET Actions (Remove and Adjust Quantity)
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $id = intval($_GET['id'] ?? 0);
    
    if ($action === 'remove' && isset($_SESSION['cart'][$id])) {
        unset($_SESSION['cart'][$id]);
        header("Location: cart.php");
        exit;
    }
    
    if ($action === 'adjust' && isset($_SESSION['cart'][$id])) {
        $change = intval($_GET['change'] ?? 0);
        $new_qty = $_SESSION['cart'][$id]['quantity'] + $change;
        
        if ($new_qty <= 0) {
            unset($_SESSION['cart'][$id]);
        } else {
            // Cap at available stock
            if ($new_qty <= $_SESSION['cart'][$id]['stock']) {
                $_SESSION['cart'][$id]['quantity'] = $new_qty;
            }
        }
        header("Location: cart.php");
        exit;
    }
    
    if ($action === 'clear') {
        $_SESSION['cart'] = [];
        header("Location: cart.php");
        exit;
    }
}

// 2. Process POST Actions (Add to Cart from catalog/details)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add') {
        $product_id = intval($_POST['product_id'] ?? 0);
        $qty = intval($_POST['quantity'] ?? 1);
        
        if ($product_id > 0 && $qty > 0) {
            $api_response = getProductById($product_id);
            
            if ($api_response['success']) {
                $product = $api_response['data'];
                
                if (!isset($_SESSION['cart'])) {
                    $_SESSION['cart'] = [];
                }
                
                $current_qty = isset($_SESSION['cart'][$product_id]) ? $_SESSION['cart'][$product_id]['quantity'] : 0;
                $new_qty = $current_qty + $qty;
                
                // Cap at available product stock
                if ($new_qty > $product['stock']) {
                    $new_qty = $product['stock'];
                }
                
                $_SESSION['cart'][$product_id] = [
                    'id' => $product['id'],
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image_url' => $product['image_url'],
                    'quantity' => $new_qty,
                    'stock' => $product['stock']
                ];
                
                header("Location: cart.php");
                exit;
            }
        }
    }
}

$page_title = "Your Shopping Cart";
require_once 'header.php';

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
?>

<div class="section-title">
    <div>
        Shopping Cart
        <p class="section-subtitle">Review items, quantities, and proceed to complete your order.</p>
    </div>
    <?php if (!empty($cart)): ?>
        <a href="cart.php?action=clear" style="font-size: 13px; color: var(--danger); font-weight: 500; display: inline-flex; align-items: center; gap: 4px;">
            <i data-lucide="trash-2" style="width: 14px;"></i> Clear Cart
        </a>
    <?php endif; ?>
</div>

<?php if (empty($cart)): ?>
    <!-- Empty Cart Template -->
    <div class="empty-state">
        <i data-lucide="shopping-cart" class="empty-icon" style="color: var(--text-muted);"></i>
        <h3 class="empty-title">Your Cart is Empty</h3>
        <p class="empty-text">Looks like you haven't added anything to your cart yet. Explore our curated catalog to find items you love.</p>
        <a href="index.php" class="btn-primary" style="display: inline-flex; width: auto; padding: 10px 20px;">
            <i data-lucide="arrow-left" style="margin-right: 8px;"></i> Start Shopping
        </a>
    </div>
<?php else: ?>
    <!-- Cart Grid Layout -->
    <div class="cart-layout">
        <!-- Left: Items list -->
        <div class="cart-items-container">
            <?php foreach ($cart as $id => $item): ?>
                <div class="cart-item">
                    <div class="cart-item-image">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>">
                    </div>
                    
                    <div class="cart-item-details">
                        <h4 class="cart-item-name">
                            <a href="product.php?id=<?php echo $item['id']; ?>"><?php echo htmlspecialchars($item['name']); ?></a>
                        </h4>
                        <span class="cart-item-price"><?php echo formatRupiah($item['price']); ?></span>
                        <span style="font-size: 11px; color: var(--text-muted); margin-top: 4px;">
                            Subtotal: <?php echo formatRupiah($item['price'] * $item['quantity']); ?>
                        </span>
                    </div>
                    
                    <!-- Quantity controls -->
                    <div class="cart-item-qty">
                        <div class="qty-controls">
                            <!-- Minus -->
                            <a href="cart.php?action=adjust&id=<?php echo $id; ?>&change=-1" class="qty-btn" style="display: flex; align-items: center; justify-content: center;">
                                <i data-lucide="minus" style="width: 12px;"></i>
                            </a>
                            <input type="text" class="qty-input" value="<?php echo $item['quantity']; ?>" readonly>
                            <!-- Plus -->
                            <a href="cart.php?action=adjust&id=<?php echo $id; ?>&change=1" class="qty-btn <?php echo ($item['quantity'] >= $item['stock']) ? 'qty-disabled' : ''; ?>" 
                               style="display: flex; align-items: center; justify-content: center; <?php echo ($item['quantity'] >= $item['stock']) ? 'pointer-events: none; opacity: 0.3;' : ''; ?>">
                                <i data-lucide="plus" style="width: 12px;"></i>
                            </a>
                        </div>
                    </div>
                    
                    <!-- Remove Button -->
                    <div class="cart-item-delete">
                        <a href="cart.php?action=remove&id=<?php echo $id; ?>" class="btn-remove">
                            <i data-lucide="x-circle" style="width: 16px;"></i> Remove
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Right: Summary Box -->
        <div class="summary-box">
            <h3 class="summary-title">Summary</h3>
            <div class="summary-row">
                <span>Subtotal</span>
                <span><?php echo formatRupiah($subtotal); ?></span>
            </div>
            <div class="summary-row">
                <span>Estimated Shipping</span>
                <span style="color: var(--success); font-weight: 500;">Free Shipping</span>
            </div>
            <div class="summary-row total">
                <span>Total Amount</span>
                <span><?php echo formatRupiah($subtotal); ?></span>
            </div>
            
            <a href="checkout.php" class="btn-checkout">
                <i data-lucide="credit-card" style="display: inline-block; vertical-align: middle; margin-right: 8px; width: 16px;"></i>
                Proceed to Checkout
            </a>
            
            <a href="index.php" style="display: block; text-align: center; margin-top: 15px; font-size: 13px; color: var(--text-secondary); font-weight: 500;">
                Continue Shopping
            </a>
        </div>
    </div>
<?php endif; ?>

<?php
require_once 'footer.php';
?>
