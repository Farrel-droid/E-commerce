<?php
// orders.php
$page_title = "Order History Log";
require_once 'api_helper.php';
require_once 'header.php';

// Fetch orders list from the Node.js database server
$api_response = getOrders();
?>

<div class="section-title">
    <div>
        Transactions & Orders Log
        <p class="section-subtitle">A list of all transactions submitted to the Node.js database backend.</p>
    </div>
</div>

<?php if (!$api_response['success']): ?>
    <!-- Database server offline -->
    <div class="status-card" style="margin: 40px auto; max-width: 650px;">
        <div class="status-icon-success" style="background-color: rgba(192, 108, 108, 0.1); color: var(--danger);">
            <i data-lucide="wifi-off"></i>
        </div>
        <h2 class="status-title" style="color: var(--danger);">Database Server Offline</h2>
        <p class="status-text">
            Unable to connect to the Node.js Express API.<br>
            Please start the database API server on <strong>http://localhost:3000</strong> to view transaction logs.<br>
            <span style="font-size: 13px; color: var(--text-muted);">Error: <?php echo htmlspecialchars($api_response['error']); ?></span>
        </p>
        <a href="orders.php" class="btn-primary" style="display: inline-flex; align-items: center; gap: 8px; width: auto; padding: 12px 24px;">
            <i data-lucide="rotate-cw"></i> Refresh Orders
        </a>
    </div>
<?php else: ?>
    <?php 
    $orders = $api_response['data'];
    if (empty($orders)): 
    ?>
        <!-- Empty Orders State -->
        <div class="empty-state">
            <i data-lucide="receipt" class="empty-icon"></i>
            <h3 class="empty-title">No Transactions Recorded</h3>
            <p class="empty-text">There are currently no transactions inside the Node.js database server. Place a new checkout order to see it logged here.</p>
            <a href="index.php" class="btn-primary" style="display: inline-flex; width: auto; padding: 10px 20px;">
                <i data-lucide="shopping-bag" style="margin-right: 8px;"></i> Go to Shop
            </a>
        </div>
    <?php else: ?>
        <!-- Order Logs Table -->
        <div class="order-table-container">
            <table class="order-table">
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date & Time</th>
                        <th>Customer Details</th>
                        <th>Ordered Items</th>
                        <th>Total Paid</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <!-- Order ID -->
                            <td style="font-weight: 700; color: var(--text-primary);">
                                <?php echo htmlspecialchars($order['id']); ?>
                            </td>
                            
                            <!-- Date & Time -->
                            <td style="white-space: nowrap; color: var(--text-secondary);">
                                <?php 
                                    $timestamp = strtotime($order['order_date']);
                                    echo date('d M Y', $timestamp) . '<br><span style="font-size: 12px; color: var(--text-muted);">' . date('h:i A', $timestamp) . '</span>';
                                ?>
                            </td>
                            
                            <!-- Customer Details -->
                            <td>
                                <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                <span style="font-size: 13px; color: var(--text-secondary);"><?php echo htmlspecialchars($order['customer_email']); ?></span><br>
                                <span style="font-size: 13px; color: var(--text-secondary);"><?php echo htmlspecialchars($order['customer_phone']); ?></span><br>
                                <span style="font-size: 12px; color: var(--text-muted); display: block; margin-top: 4px; line-height: 1.4; max-width: 250px;">
                                    Address: <?php echo htmlspecialchars($order['customer_address']); ?>
                                </span>
                            </td>
                            
                            <!-- Items List -->
                            <td>
                                <ul class="order-items-list">
                                    <?php foreach ($order['items'] as $item): ?>
                                        <li style="font-size: 13px; margin-bottom: 6px;">
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <span style="color: var(--text-secondary);"><?php echo $item['quantity']; ?> &times; <?php echo formatRupiah($item['price']); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                            
                            <!-- Total Paid -->
                            <td style="font-weight: 700; color: var(--accent); font-size: 15px; white-space: nowrap;">
                                <?php echo formatRupiah($order['total_amount']); ?>
                            </td>
                            
                            <!-- Payment Method -->
                            <td style="white-space: nowrap; font-size: 13px; font-weight: 500; color: var(--text-secondary);">
                                <?php echo htmlspecialchars($order['payment_method']); ?>
                            </td>
                            
                            <!-- Status Badge -->
                            <td>
                                <span style="display: inline-block; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; background-color: rgba(96, 125, 104, 0.15); color: var(--success);">
                                    Processed
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once 'footer.php';
?>
