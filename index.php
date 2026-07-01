<?php
// index.php
$page_title = "ZenSpace | Curated Workspace Essentials";
require_once 'api_helper.php';
require_once 'header.php';

// Retrieve search & category filter parameters
$category_filter = isset($_GET['category']) ? $_GET['category'] : null;
$search_filter = isset($_GET['search']) ? $_GET['search'] : null;

// Fetch products from the database API
$api_response = getProducts($category_filter, $search_filter);

// Hardcoded categories list for filter chips
$categories = ['Tech', 'Accessories', 'Furniture', 'Lifestyle', 'Lighting'];
?>

<!-- Categories Bar -->
<div class="categories-bar">
    <a href="index.php<?php echo $search_filter ? '?search=' . urlencode($search_filter) : ''; ?>" 
       class="category-chip <?php echo !$category_filter ? 'active' : ''; ?>">
        All Items
    </a>
    <?php foreach ($categories as $cat): ?>
        <?php 
            $query_params = ['category' => $cat];
            if ($search_filter) $query_params['search'] = $search_filter;
            $url = 'index.php?' . http_build_query($query_params);
        ?>
        <a href="<?php echo $url; ?>" 
           class="category-chip <?php echo ($category_filter === $cat) ? 'active' : ''; ?>">
            <?php echo $cat; ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="section-title">
    <div>
        <?php 
        if ($search_filter) {
            echo "Search Results for '" . htmlspecialchars($search_filter) . "'";
        } elseif ($category_filter) {
            echo htmlspecialchars($category_filter);
        } else {
            echo "Curated Collection";
        }
        ?>
        <p class="section-subtitle">Premium essentials designed to elevate your workspace and daily life.</p>
    </div>
</div>

<?php if (!$api_response['success']): ?>
    <!-- API Offline Warning -->
    <div class="status-card" style="margin: 40px auto; max-width: 650px;">
        <div class="status-icon-success" style="background-color: rgba(192, 108, 108, 0.1); color: var(--danger);">
            <i data-lucide="wifi-off"></i>
        </div>
        <h2 class="status-title" style="color: var(--danger);">Database Server Offline</h2>
        <p class="status-text">
            PHP cannot establish a connection to the Node.js database server.<br>
            Please make sure the API server is running on <strong>http://localhost:3000</strong>.<br>
            <span style="font-size: 13px; color: var(--text-muted);">Error: <?php echo htmlspecialchars($api_response['error']); ?></span>
        </p>
        <a href="index.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; width: auto; padding: 12px 24px;">
            <i data-lucide="rotate-cw"></i> Retry Connection
        </a>
    </div>
<?php else: ?>
    <?php 
    $products = $api_response['data']; 
    if (empty($products)): 
    ?>
        <!-- Empty Products List -->
        <div class="empty-state">
            <i data-lucide="inbox" class="empty-icon"></i>
            <h3 class="empty-title">No Products Found</h3>
            <p class="empty-text">We couldn't find any items matching your filters. Try selecting another category or clearing your search.</p>
            <a href="index.php" class="btn-primary" style="display: inline-flex; width: auto; padding: 10px 20px;">Clear Filters</a>
        </div>
    <?php else: ?>
        <!-- Product Grid -->
        <div class="product-grid">
            <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <!-- Link entire card body to details page -->
                    <a href="product.php?id=<?php echo $product['id']; ?>" class="product-image-container">
                        <img src="<?php echo htmlspecialchars($product['image_url']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="product-img">
                        <span class="product-badge"><?php echo htmlspecialchars($product['category']); ?></span>
                    </a>
                    
                    <div class="product-info">
                        <span class="product-category"><?php echo htmlspecialchars($product['category']); ?></span>
                        <h3 class="product-name">
                            <a href="product.php?id=<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></a>
                        </h3>
                        
                        <div class="product-rating">
                            <i data-lucide="star" style="fill: #F39C12; width: 14px; height: 14px;"></i>
                            <?php echo number_format($product['rating'], 1); ?> 
                            <span>/ 5.0</span>
                        </div>
                        
                        <div class="product-price-row">
                            <div class="product-price"><?php echo formatRupiah($product['price']); ?></div>
                            <div class="product-stock <?php echo ($product['stock'] <= 0) ? 'out-of-stock' : ''; ?>">
                                <?php echo ($product['stock'] > 0) ? $product['stock'] . ' in stock' : 'Out of Stock'; ?>
                            </div>
                        </div>
                        
                        <!-- Quick Add to Cart Form -->
                        <form action="cart.php" method="POST" style="margin-top: 15px;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn-add-cart" <?php echo ($product['stock'] <= 0) ? 'disabled' : ''; ?>>
                                <i data-lucide="plus-circle"></i> Add to Cart
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once 'footer.php';
?>
