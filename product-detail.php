<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    redirect('products.php');
}

require_once 'includes/header.php';

$database = new Database();
$db = $database->connect();

$product = null;
$commission = 10;
$isInWishlist = false;
$cartCount = 0;

// Get messages from session
$wishlistMessage = $_SESSION['wishlist_message'] ?? null;
$cartMessage = $_SESSION['cart_message'] ?? null;
unset($_SESSION['wishlist_message'], $_SESSION['cart_message']);

if ($db) {
    $commission = getGlobalCommission($db);
    $stmt = $db->prepare("SELECT p.*, c.name as category_name, u.full_name as creator_name FROM products p LEFT JOIN categories c ON p.category_id = c.id LEFT JOIN users u ON p.created_by = u.id WHERE p.id = :id AND p.status = 'enabled' AND p.stock > 0");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $product = $stmt->fetch();
    
    // Check if in wishlist (for logged in users)
    if ($product && isLoggedIn()) {
        $userId = $_SESSION['user_id'];
        $wishStmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = :user_id AND product_id = :product_id");
        $wishStmt->bindParam(':user_id', $userId);
        $wishStmt->bindParam(':product_id', $id);
        $wishStmt->execute();
        $isInWishlist = $wishStmt->rowCount() > 0;
        
        // Get cart count
        $cartStmt = $db->prepare("SELECT SUM(quantity) as count FROM cart WHERE user_id = :user_id");
        $cartStmt->bindParam(':user_id', $userId);
        $cartStmt->execute();
        $cartCount = $cartStmt->fetch()['count'] ?? 0;
    }
}

if (!$product) {
    redirect('products.php');
}

$finalPrice = calculateFinalPrice($product['price'], $commission);
$platformCharge = $finalPrice - $product['price'];
$pageTitle = $product['name'];
$currentPage = 'products';
$isLoggedIn = isLoggedIn();
$userRole = getUserRole();
?>

<section class="section">
    <div class="container">
        <!-- Success Messages -->
        <?php if ($wishlistMessage): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: clamp(0.75rem, 2vw, 1rem); border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: clamp(0.9rem, 1.5vw, 1rem);">
                <i class="fas fa-heart" style="color: #e74c3c; flex-shrink: 0;"></i>
                <span><?php echo htmlspecialchars($wishlistMessage); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($cartMessage): ?>
            <div class="alert alert-success" style="background: #d1ecf1; color: #0c5460; padding: clamp(0.75rem, 2vw, 1rem); border-radius: 8px; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; font-size: clamp(0.9rem, 1.5vw, 1rem);">
                <i class="fas fa-shopping-cart" style="color: #3498db; flex-shrink: 0;"></i>
                <span><?php echo htmlspecialchars($cartMessage); ?></span>
            </div>
        <?php endif; ?>
        
        <div class="product-detail">
            <div class="product-detail-image" style="background: linear-gradient(135deg, rgba(102, 126, 234, 0.7), rgba(118, 75, 162, 0.7)); border-radius: 10px; overflow: hidden;">
                <?php if ($product['image']): ?>
                    <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width: 90%; height: auto; object-fit: contain; border-radius: 8px; display: block; margin: 5% auto;">
                <?php endif; ?>
            </div>
            <div class="product-detail-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <p class="product-category"><?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?></p>
                <p class="product-price" style="font-size: 1.5rem; font-weight: 700; color: #27ae60; margin: 1rem 0;">₹<?php echo number_format($finalPrice, 2); ?></p>
                <p class="product-stock"><?php echo $product['stock'] > 0 ? 'In Stock: ' . $product['stock'] : 'Out of Stock'; ?></p>
                <div class="product-description">
                    <?php echo nl2br(htmlspecialchars($product['description'] ?? 'No description available.')); ?>
                </div>
                
                <!-- Action Buttons -->
                <div class="product-actions" style="margin-top: 2rem; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center;">
                            <!-- Quantity Selector -->
                            <div style="display: flex; align-items: center; gap: 0.5rem;">
                                <label style="font-weight: 600;">Qty:</label>
                                <input type="number" id="product-qty" value="1" min="1" max="<?php echo $product['stock']; ?>" style="width: 70px; padding: 0.5rem; border: 2px solid #ddd; border-radius: 5px; font-size: 1rem;">
                            </div>
                            
                            <!-- Wishlist Button (Heart Icon) -->
                            <form method="POST" action="wishlist-action.php" style="display: inline; margin: 0;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="<?php echo $isInWishlist ? 'remove' : 'add'; ?>">
                                <button type="submit" style="background: none; border: none; cursor: pointer; padding: 8px;" title="<?php echo $isInWishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>">
                                    <i class="fas fa-heart" style="font-size: 28px; color: <?php echo $isInWishlist ? '#e74c3c' : '#95a5a6'; ?>"></i>
                                </button>
                            </form>
                            
                            <!-- Add to Cart Button (Cart Icon) -->
                            <form method="POST" action="cart-action.php" style="display: inline; margin: 0;">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add">
                                <input type="hidden" name="quantity" id="cart-qty" value="1">
                                <button type="submit" style="background: none; border: none; cursor: pointer; padding: 8px;" title="Add to Cart" onclick="document.getElementById('cart-qty').value = document.getElementById('product-qty').value;">
                                    <i class="fas fa-shopping-cart" style="font-size: 28px; color: #3498db;"></i>
                                </button>
                            </form>
                            
                            <!-- Buy Now Button -->
                            <a href="buy-now.php?id=<?php echo $product['id']; ?>&qty=1" id="buy-now-link" class="btn btn-success" style="padding: 0.6rem 1.2rem;">
                                <i class="fas fa-bolt"></i> Buy Now
                            </a>
                            
                            <!-- Inquiry Button -->
                            <a href="inquiry.php?product_id=<?php echo $product['id']; ?>" class="btn btn-secondary" style="padding: 0.6rem 1.2rem;">
                                <i class="fas fa-question-circle"></i> Inquiry
                            </a>
                        </div>
                        
                        <script>
                            document.getElementById('product-qty').addEventListener('change', function() {
                                var qty = this.value;
                                document.getElementById('buy-now-link').href = 'buy-now.php?id=<?php echo $product['id']; ?>&qty=' + qty;
                            });
                        </script>
                    <?php else: ?>
                        <div style="display: flex; gap: 1rem; flex-wrap: wrap; align-items: center; justify-content: center;">
                            <p style="margin: 0; color: #666;">
                                <i class="fas fa-info-circle"></i>
                                Please <a href="login.php" style="color: var(--accent-color);">login</a> to purchase.
                            </p>
                            <a href="inquiry.php?product_id=<?php echo $product['id']; ?>" class="btn btn-secondary" style="padding: 0.6rem 1.2rem;">
                                <i class="fas fa-question-circle"></i> Inquiry
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div style="margin-top: 1.5rem;">
                    <a href="products.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Products
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
