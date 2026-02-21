<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'My Orders';
$currentPage = 'orders';
require_once 'includes/header.php';

$database = new Database();
$db = $database->connect();
$userId = $_SESSION['user_id'];

$orders = [];

if ($db) {
    $stmt = $db->prepare("
        SELECT co.*, p.name as product_name, p.image
        FROM customer_orders co
        JOIN products p ON co.product_id = p.id
        WHERE co.user_id = :user_id
        ORDER BY co.id DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $orders = $stmt->fetchAll();
}
?>

<section class="section">
    <div class="container">
        <h1 class="section-title"><i class="fas fa-box-open"></i> My Orders</h1>
        
        <?php if (empty($orders)): ?>
            <div style="text-align: center; padding: 3rem; background: #f8f9fa; border-radius: 10px;">
                <i class="fas fa-box-open" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3>No orders yet</h3>
                <p style="color: #666; margin-bottom: 1.5rem;">Start shopping to see your orders here.</p>
                <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="content-card">
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Product</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Order Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <?php if ($order['image']): ?>
                                            <img src="uploads/products/<?php echo htmlspecialchars($order['image']); ?>" alt="" style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($order['product_name']); ?>
                                    </div>
                                </td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td style="font-weight: 600;">₹<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['order_status']; ?>">
                                        <?php echo ucfirst($order['order_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($order['updated_at'] ?? $order['created_at'] ?? date('Y-m-d H:i:s'))); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
