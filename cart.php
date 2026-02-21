<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'My Cart';
$currentPage = 'cart';
require_once 'includes/header.php';

$database = new Database();
$db = $database->connect();
$userId = $_SESSION['user_id'];
$commission = 10;

$cartItems = [];
$totalAmount = 0;

if ($db) {
    $commission = getGlobalCommission($db);
    
    $stmt = $db->prepare("
        SELECT c.*, p.name, p.image, p.stock, p.price as base_price 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.user_id = :user_id
        ORDER BY c.id DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $cartItems = $stmt->fetchAll();
    
    // Calculate total
    foreach ($cartItems as $item) {
        $finalPrice = calculateFinalPrice($item['base_price'], $commission);
        $totalAmount += $finalPrice * $item['quantity'];
    }
}
?>

<section class="section">
    <div class="container">
        <h1 class="section-title"><i class="fas fa-shopping-cart"></i> My Cart</h1>
        
        <?php if (empty($cartItems)): ?>
            <div style="text-align: center; padding: clamp(1.5rem, 4vw, 3rem); background: #f8f9fa; border-radius: 10px;">
                <i class="fas fa-shopping-cart" style="font-size: clamp(2.5rem, 10vw, 4rem); color: #ddd; margin-bottom: 1rem; display: block;"></i>
                <h3 style="font-size: clamp(1.25rem, 3vw, 1.5rem); margin-bottom: 0.5rem;">Your cart is empty</h3>
                <p style="color: #666; margin-bottom: clamp(1rem, 2vh, 1.5rem); font-size: clamp(0.9rem, 1.5vw, 1rem);">Browse our products and add items to your cart.</p>
                <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary">Browse Products</a>
            </div>
        <?php else: ?>
            <div class="content-card">
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($cartItems as $item): 
                                $finalPrice = calculateFinalPrice($item['base_price'], $commission);
                                $itemTotal = $finalPrice * $item['quantity'];
                            ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 1rem;">
                                        <?php if ($item['image']): ?>
                                            <img src="uploads/products/<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                                        <?php endif; ?>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($item['name']); ?></div>
                                            <div style="font-size: 0.85rem; color: #666;">Stock: <?php echo $item['stock']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>₹<?php echo number_format($finalPrice, 2); ?></td>
                                <td>
                                    <form method="POST" action="<?php echo BASE_URL; ?>cart-action.php" style="display: flex; align-items: center; gap: 0.5rem;">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="hidden" name="action" value="update">
                                        <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>" style="width: 60px; padding: 0.5rem; border: 2px solid #e0e0e0; border-radius: 5px;">
                                        <button type="submit" class="btn btn-sm btn-secondary">Update</button>
                                    </form>
                                </td>
                                <td style="font-weight: 600;">₹<?php echo number_format($itemTotal, 2); ?></td>
                                <td>
                                    <form method="POST" action="<?php echo BASE_URL; ?>cart-action.php" style="display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="hidden" name="action" value="remove">
                                        <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Remove this item?')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 2px solid #eee; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem;">
                        <div>
                            <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left"></i> Continue Shopping
                            </a>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 1.25rem; margin-bottom: 1rem;">
                                Total: <span style="font-weight: 700; color: var(--success-color); font-size: 1.5rem;">₹<?php echo number_format($totalAmount, 2); ?></span>
                            </div>
                            <a href="<?php echo BASE_URL; ?>checkout.php" class="btn btn-success btn-lg">
                                <i class="fas fa-credit-card"></i> Proceed to Checkout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
