<?php
// checkout.php
require_once 'api_helper.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];

// If cart is empty and we aren't showing the success page, redirect to cart.php
$is_success_view = (isset($_GET['action']) && $_GET['action'] === 'success');
if (empty($cart) && !$is_success_view) {
    header("Location: cart.php");
    exit;
}

$default_name = '';
$default_email = '';
$default_phone = '';
$default_address = '';

if (isset($_SESSION['user'])) {
    $default_name = $_SESSION['user']['name'] ?? '';
    $default_email = $_SESSION['user']['email'] ?? '';
    $default_phone = $_SESSION['user']['phone'] ?? '';
    $default_address = $_SESSION['user']['address'] ?? '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $default_name = $_POST['name'] ?? $default_name;
    $default_email = $_POST['email'] ?? $default_email;
    $default_phone = $_POST['phone'] ?? $default_phone;
    $default_address = $_POST['address'] ?? $default_address;
}

$error_msg = null;

// Process Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');

    if (empty($name) || empty($email) || empty($phone) || empty($address) || empty($payment_method)) {
        $error_msg = "Please fill in all shipping and payment details.";
    } else {
        // Compile items payload for Node.js Express server
        $itemsPayload = [];
        foreach ($cart as $id => $item) {
            $itemsPayload[] = [
                'id' => $id,
                'quantity' => $item['quantity']
            ];
        }

        $orderData = [
            'customer_name' => $name,
            'customer_email' => $email,
            'customer_phone' => $phone,
            'customer_address' => $address,
            'payment_method' => $payment_method,
            'items' => $itemsPayload
        ];

        // Hit Node.js API to process order and subtract stock
        $api_response = createOrder($orderData);

        if ($api_response['success']) {
            $order = $api_response['data'];
            // Clear session cart
            $_SESSION['cart'] = [];
            // Redirect to success view
            header("Location: checkout.php?action=success&order_id=" . urlencode($order['id']) . "&total=" . urlencode($order['total_amount']) . "&name=" . urlencode($order['customer_name']));
            exit;
        } else {
            $error_msg = "Failed to place order: " . $api_response['error'];
        }
    }
}

$page_title = $is_success_view ? "Order Confirmed" : "Checkout";
require_once 'header.php';
?>

<?php if ($is_success_view): ?>
    <!-- Success Page Layout -->
    <div class="status-card">
        <div class="status-icon-success">
            <i data-lucide="check-circle-2"></i>
        </div>
        <h2 class="status-title">Thank You for Your Order!</h2>
        <p class="status-text">
            Hi <strong><?php echo htmlspecialchars($_GET['name'] ?? ''); ?></strong>, your transaction has been successfully processed.<br>
            Order Reference: <strong><?php echo htmlspecialchars($_GET['order_id'] ?? ''); ?></strong><br>
            Total Amount Paid: <strong><?php echo formatRupiah($_GET['total'] ?? 0); ?></strong>
        </p>
        <p class="status-text" style="font-size: 13px; margin-top: -15px;">
            We've sent a receipt to your email. We will process your shipment shortly.
        </p>
        <div style="display: flex; gap: 15px; justify-content: center;">
            <a href="index.php" class="btn-primary" style="display: inline-flex; width: auto; padding: 12px 24px;">
                <i data-lucide="shopping-bag" style="margin-right: 8px;"></i> Continue Shopping
            </a>
            <a href="orders.php" class="btn-primary" style="display: inline-flex; width: auto; padding: 12px 24px; background-color: var(--bg-secondary); color: var(--text-primary); border: 1px solid var(--border-color);">
                <i data-lucide="list-checks" style="margin-right: 8px;"></i> View All Orders
            </a>
        </div>
    </div>

<?php else: ?>
    <!-- Standard Checkout Form -->
    <div class="section-title">
        <div>
            Checkout Details
            <p class="section-subtitle">Please enter your shipping address and choose a payment method.</p>
        </div>
    </div>

    <?php if ($error_msg): ?>
        <div class="alert alert-danger">
            <i data-lucide="alert-triangle" style="width: 16px; display: inline-block; vertical-align: middle; margin-right: 6px;"></i>
            <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php endif; ?>

    <div class="checkout-layout">
        <!-- Left: Form Box -->
        <div class="form-box">
            <form action="checkout.php" method="POST" id="checkout-form">
                <h3 class="summary-title" style="margin-top: 0; font-size: 18px;">Shipping Address</h3>
                
                <div class="form-group">
                    <label class="form-label" for="name">Full Name</label>
                    <input type="text" name="name" id="name" class="form-control" placeholder="e.g. Farrel Hartono" required value="<?php echo htmlspecialchars($default_name); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" name="email" id="email" class="form-control" placeholder="e.g. farrel@example.com" required value="<?php echo htmlspecialchars($default_email); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="phone">Phone Number</label>
                        <input type="tel" name="phone" id="phone" class="form-control" placeholder="e.g. 08123456789" required value="<?php echo htmlspecialchars($default_phone); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="address">Shipping Address</label>
                    <textarea name="address" id="address" class="form-control" placeholder="Street Name, Building/House Number, City, Province, Zip Code" required><?php echo htmlspecialchars($default_address); ?></textarea>
                </div>

                <h3 class="summary-title" style="margin-top: 35px; font-size: 18px; border-bottom: 1px solid var(--border-color); padding-bottom: 12px;">Payment Method</h3>
                
                <div class="radio-group">
                    <label class="radio-card" id="payment-bank">
                        <input type="radio" name="payment_method" value="Bank Transfer" required <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Bank Transfer') ? 'checked' : ''; ?>>
                        <span class="radio-label">
                            <i data-lucide="landmark" style="width: 14px; vertical-align: middle; margin-right: 4px;"></i> Bank Transfer
                        </span>
                    </label>

                    <label class="radio-card" id="payment-credit">
                        <input type="radio" name="payment_method" value="Credit Card" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'Credit Card') ? 'checked' : ''; ?>>
                        <span class="radio-label">
                            <i data-lucide="credit-card" style="width: 14px; vertical-align: middle; margin-right: 4px;"></i> Credit Card
                        </span>
                    </label>

                    <label class="radio-card" id="payment-wallet">
                        <input type="radio" name="payment_method" value="E-Wallet" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] === 'E-Wallet') ? 'checked' : ''; ?>>
                        <span class="radio-label">
                            <i data-lucide="smartphone" style="width: 14px; vertical-align: middle; margin-right: 4px;"></i> E-Wallet
                        </span>
                    </label>
                </div>
            </form>
        </div>

        <!-- Right: Checkout Sidebar Summary -->
        <div class="summary-box">
            <h3 class="summary-title">Order Items</h3>
            
            <div style="max-height: 250px; overflow-y: auto; margin-bottom: 20px; padding-right: 5px;">
                <?php 
                $subtotal = 0;
                foreach ($cart as $item): 
                    $subtotal += $item['price'] * $item['quantity'];
                ?>
                    <div style="display: flex; gap: 10px; margin-bottom: 15px; align-items: center;">
                        <img src="<?php echo htmlspecialchars($item['image_url']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 44px; height: 44px; object-fit: cover; border-radius: var(--border-radius-sm); border: 1px solid var(--border-color);">
                        <div style="flex-grow: 1; min-width: 0;">
                            <h4 style="font-size: 13px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; margin-bottom: 2px;">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h4>
                            <span style="font-size: 12px; color: var(--text-secondary);"><?php echo $item['quantity']; ?> &times; <?php echo formatRupiah($item['price']); ?></span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="summary-row" style="border-top: 1px solid var(--border-color); padding-top: 15px;">
                <span>Subtotal</span>
                <span><?php echo formatRupiah($subtotal); ?></span>
            </div>
            <div class="summary-row">
                <span>Shipping Fee</span>
                <span style="color: var(--success); font-weight: 500;">Free Shipping</span>
            </div>
            <div class="summary-row total">
                <span>Grand Total</span>
                <span><?php echo formatRupiah($subtotal); ?></span>
            </div>

            <!-- Submit trigger for Left Form -->
            <button type="submit" form="checkout-form" class="btn-checkout" style="border: none; cursor: pointer; font-family: var(--font-body); font-size: 15px;">
                <i data-lucide="lock" style="display: inline-block; vertical-align: middle; margin-right: 8px; width: 15px;"></i>
                Place Order (<?php echo formatRupiah($subtotal); ?>)
            </button>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Visual highlight for payment radio cards
            const radioCards = document.querySelectorAll('.radio-card');
            
            function updateCardSelection() {
                radioCards.forEach(card => {
                    const input = card.querySelector('input[type="radio"]');
                    if (input.checked) {
                        card.classList.add('selected');
                    } else {
                        card.classList.remove('selected');
                    }
                });
            }

            radioCards.forEach(card => {
                card.addEventListener('click', () => {
                    const input = card.querySelector('input[type="radio"]');
                    input.checked = true;
                    updateCardSelection();
                });
            });

            // Run initial check (in case browser auto-restores form check)
            updateCardSelection();
        });
    </script>
<?php endif; ?>

<?php
require_once 'footer.php';
?>
