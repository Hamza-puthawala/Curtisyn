<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Order Successful';
$currentPage = 'products';
require_once 'includes/header.php';

$orderId = $_GET['order_id'] ?? '';
$order = null;

if ($orderId) {
    $database = new Database();
    $db = $database->connect();

    $stmt = $db->prepare("
        SELECT co.*, p.name as product_name, p.image as product_image
        FROM customer_orders co
        JOIN products p ON co.product_id = p.id
        WHERE co.order_id = :order_id AND co.user_id = :user_id
    ");
    $stmt->bindParam(':order_id', $orderId);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $order = $stmt->fetch();
}
?>

<section class="section">
    <div class="container" style="max-width: 800px; margin: 0 auto; text-align: center;">
        <div style="background: #d4edda; border-radius: 50%; width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; margin: 0 auto 2rem;">
            <i class="fas fa-check" style="font-size: 50px; color: #28a745;"></i>
        </div>

        <h1 style="color: #28a745; margin-bottom: 1rem;">Order Placed Successfully!</h1>
        <p style="font-size: 1.1rem; color: #666; margin-bottom: 2rem;">
            Thank you for your order. We've sent a confirmation email with your invoice.
        </p>

        <?php if ($order): ?>
        <div class="content-card" style="text-align: left; margin-bottom: 2rem;">
            <div class="card-header">
                <h2 class="card-title">Order Details</h2>
            </div>
            <div class="card-body">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem;">
                    <div>
                        <p style="color: #666; margin-bottom: 0.5rem;">Order ID</p>
                        <p style="font-weight: 600; font-size: 1.1rem;"><?php echo htmlspecialchars($order['order_id']); ?></p>
                    </div>
                    <div>
                        <p style="color: #666; margin-bottom: 0.5rem;">Order Date</p>
                        <p style="font-weight: 600;"><?php echo date('M d, Y', strtotime($order['created_at'] ?? $order['order_date'] ?? 'now')); ?></p>
                    </div>
                    <div>
                        <p style="color: #666; margin-bottom: 0.5rem;">Payment Method</p>
                        <p style="font-weight: 600;">
                            <?php echo ($order['payment_method'] ?? 'cod') === 'cod' ? 'Cash on Delivery' : 'Online Payment (Razorpay)'; ?>
                        </p>
                    </div>
                    <div>
                        <p style="color: #666; margin-bottom: 0.5rem;">Payment Status</p>
                        <p style="font-weight: 600; text-transform: uppercase;">
                            <span style="color: <?php echo ($order['payment_status'] ?? 'pending') === 'paid' ? '#28a745' : '#ffc107'; ?>;">
                                <?php echo $order['payment_status'] ?? 'pending'; ?>
                            </span>
                        </p>
                    </div>
                </div>

                <hr style="margin: 1.5rem 0; border: none; border-top: 1px solid #eee;">

                <div style="display: flex; gap: 1rem; margin-bottom: 1rem;">
                    <?php if ($order['product_image']): ?>
                        <img src="uploads/products/<?php echo htmlspecialchars($order['product_image']); ?>" alt="" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                    <?php endif; ?>
                    <div>
                        <p style="font-weight: 600;"><?php echo htmlspecialchars($order['product_name']); ?></p>
                        <p style="color: #666;">Qty: <?php echo $order['quantity']; ?></p>
                    </div>
                </div>

                <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span>Total Amount:</span>
                        <span style="font-weight: 700; font-size: 1.2rem; color: var(--primary-color);">
                            ₹<?php echo number_format($order['total_amount'], 2); ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div style="display: flex; gap: 1rem; justify-content: center;">
            <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary">
                <i class="fas fa-shopping-bag"></i> Continue Shopping
            </a>
            <a href="<?php echo BASE_URL; ?>my-orders.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> View My Orders
            </a>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
