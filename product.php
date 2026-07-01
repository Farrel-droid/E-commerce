<?php
// product.php
require_once 'api_helper.php';

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$api_response = getProductById($product_id);

if (!$api_response['success']) {
    $page_title = "Product Not Found";
    require_once 'header.php';
    ?>
    <div class="empty-state" style="margin-top: 50px;">
        <i data-lucide="alert-circle" class="empty-icon" style="color: var(--danger);"></i>
        <h3 class="empty-title">Error Loading Product</h3>
        <p class="empty-text"><?php echo htmlspecialchars($api_response['error']); ?></p>
        <a href="index.php" class="btn-primary" style="display: inline-flex; width: auto; padding: 10px 20px;">
            <i data-lucide="arrow-left" style="margin-right: 8px;"></i> Back to Shop
        </a>
    </div>
    <?php
    require_once 'footer.php';
    exit;
}

$product = $api_response['data'];
$page_title = htmlspecialchars($product['name']);
require_once 'header.php';
?>

<a href="index.php" class="detail-back-btn">
    <i data-lucide="arrow-left"></i> Back to Catalog
</a>

<div class="detail-layout">
    <!-- Left: Product Image -->
    <div class="detail-image-box">
        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="detail-img">
    </div>

    <!-- Right: Product Metadata & Buy Box -->
    <div class="detail-info-box">
        <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
        <h1 class="detail-title"><?php echo htmlspecialchars($product['name']); ?></h1>
        
        <div class="product-rating" style="font-size: 16px; margin-bottom: 20px;">
            <i data-lucide="star" style="fill: #F39C12; width: 16px; height: 16px;"></i>
            <?php echo number_format($product['rating'], 1); ?> 
            <span style="color: var(--text-secondary); font-size: 14px;">(Custom Review Scale)</span>
        </div>

        <div class="detail-price"><?php echo formatRupiah($product['price']); ?></div>
        
        <p class="detail-description"><?php echo htmlspecialchars($product['description']); ?></p>
        
        <div class="purchase-form">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <span class="qty-label">Availability</span>
                <span class="product-stock <?php echo ($product['stock'] <= 0) ? 'out-of-stock' : ''; ?>" style="font-size: 14px;">
                    <?php echo ($product['stock'] > 0) ? $product['stock'] . ' units available' : 'Currently Out of Stock'; ?>
                </span>
            </div>

            <!-- Buy & Add to Cart Form -->
            <form action="cart.php" method="POST">
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                
                <?php if ($product['stock'] > 0): ?>
                    <div class="qty-selector">
                        <span class="qty-label">Quantity</span>
                        <div class="qty-controls">
                            <button type="button" class="qty-btn" id="minus-btn">
                                <i data-lucide="minus" style="width: 14px;"></i>
                            </button>
                            <input type="text" name="quantity" id="quantity" class="qty-input" value="1" readonly>
                            <button type="button" class="qty-btn" id="plus-btn">
                                <i data-lucide="plus" style="width: 14px;"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="detail-actions">
                        <button type="submit" class="btn-primary">
                            <i data-lucide="shopping-bag"></i> Add to Shopping Cart
                        </button>
                    </div>
                <?php else: ?>
                    <div class="detail-actions">
                        <button type="button" class="btn-primary" disabled>
                            <i data-lucide="alert-octagon"></i> Out of Stock
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const qtyInput = document.getElementById('quantity');
        const minusBtn = document.getElementById('minus-btn');
        const plusBtn = document.getElementById('plus-btn');
        const maxStock = <?php echo intval($product['stock']); ?>;

        if (qtyInput && minusBtn && plusBtn) {
            minusBtn.addEventListener('click', () => {
                let currentVal = parseInt(qtyInput.value) || 1;
                if (currentVal > 1) {
                    qtyInput.value = currentVal - 1;
                }
            });

            plusBtn.addEventListener('click', () => {
                let currentVal = parseInt(qtyInput.value) || 1;
                if (currentVal < maxStock) {
                    qtyInput.value = currentVal + 1;
                }
            });
        }
    });
</script>

<?php
require_once 'footer.php';
?>
