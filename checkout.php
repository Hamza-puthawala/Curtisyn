<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$pageTitle = 'Checkout';
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
        SELECT c.*, p.name, p.image, p.stock, p.price as base_price, p.id as product_id
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

if (empty($cartItems)) {
    header('Location: ' . BASE_URL . 'cart.php');
    exit();
}

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name'] ?? '');
    $phone = sanitizeInput($_POST['phone'] ?? '');
    $address = sanitizeInput($_POST['address'] ?? '');
    $city = sanitizeInput($_POST['city'] ?? '');
    $pincode = sanitizeInput($_POST['pincode'] ?? '');
    
    if (empty($name) || empty($phone) || empty($address) || empty($city) || empty($pincode)) {
        $error = 'Please fill in all fields.';
    } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Please enter a valid 10-digit phone number.';
    } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
        $error = 'Please enter a valid 6-digit pincode.';
    } else {
        $fullAddress = $address . ', ' . $city . ' - ' . $pincode;
        $allSuccess = true;
        
        // Place order for each cart item
        foreach ($cartItems as $item) {
            $orderId = 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
            $finalPrice = calculateFinalPrice($item['base_price'], $commission);
            $itemTotal = $finalPrice * $item['quantity'];
            
            $stmt = $db->prepare("
                INSERT INTO customer_orders 
                (order_id, user_id, product_id, quantity, price, total_amount, customer_name, customer_phone, customer_address) 
                VALUES (:order_id, :user_id, :product_id, :quantity, :price, :total_amount, :customer_name, :customer_phone, :customer_address)
            ");
            
            $stmt->bindParam(':order_id', $orderId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $item['product_id']);
            $stmt->bindParam(':quantity', $item['quantity']);
            $stmt->bindParam(':price', $finalPrice);
            $stmt->bindParam(':total_amount', $itemTotal);
            $stmt->bindParam(':customer_name', $name);
            $stmt->bindParam(':customer_phone', $phone);
            $stmt->bindParam(':customer_address', $fullAddress);
            
            if ($stmt->execute()) {
                // Update stock
                $updateStmt = $db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id");
                $updateStmt->bindParam(':qty', $item['quantity']);
                $updateStmt->bindParam(':id', $item['product_id']);
                $updateStmt->execute();
            } else {
                $allSuccess = false;
            }
        }
        
        if ($allSuccess) {
            // Clear cart
            $clearStmt = $db->prepare("DELETE FROM cart WHERE user_id = :user_id");
            $clearStmt->bindParam(':user_id', $userId);
            $clearStmt->execute();
            
            $success = 'All orders placed successfully! You can view them in My Orders.';
        } else {
            $error = 'Some orders failed. Please contact support.';
        }
    }
}
?>

<section class="section">
    <div class="container">
        <h1 class="section-title"><i class="fas fa-credit-card"></i> Checkout</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: clamp(0.75rem, 2vw, 1rem); border-radius: 8px; margin-bottom: 1.5rem; font-size: clamp(0.9rem, 1.5vw, 1rem);">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <div style="text-align: center; padding: clamp(1rem, 3vw, 2rem); display: flex; gap: clamp(0.5rem, 2vw, 1rem); flex-direction: column; align-items: center;">
                <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary" style="width: clamp(150px, 80%, 300px);">Continue Shopping</a>
                <a href="<?php echo BASE_URL; ?>my-orders.php" class="btn btn-secondary" style="width: clamp(150px, 80%, 300px);">View My Orders</a>
            </div>
        <?php else: ?>
            <style>
                @media (min-width: 768px) {
                    .checkout-wrapper {
                        display: grid;
                        grid-template-columns: 1fr 1fr;
                        gap: 2rem;
                    }
                }
            </style>
            <div class="checkout-wrapper" style="gap: clamp(1rem, 3vw, 2rem);">
                <!-- Order Summary -->
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title" style="font-size: clamp(1.1rem, 2.5vw, 1.25rem);">Order Summary <span style="font-size: clamp(0.85rem, 1.5vw, 1rem); font-weight: normal;">( <?php echo count($cartItems); ?> items)</span></h2>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cartItems as $item): 
                            $finalPrice = calculateFinalPrice($item['base_price'], $commission);
                            $itemTotal = $finalPrice * $item['quantity'];
                        ?>
                        <div style="display: flex; gap: clamp(0.75rem, 2vw, 1rem); margin-bottom: clamp(0.75rem, 2vh, 1rem); padding-bottom: clamp(0.75rem, 2vh, 1rem); border-bottom: 1px solid #eee;">
                            <?php if ($item['image']): ?>
                                <img src="uploads/products/<?php echo htmlspecialchars($item['image']); ?>" alt="" style="width: clamp(50px, 12vw, 60px); height: clamp(50px, 12vw, 60px); object-fit: cover; border-radius: 5px; flex-shrink: 0;">
                            <?php endif; ?>
                            <div style="flex: 1; min-width: 0;">
                                <div style="font-weight: 600; font-size: clamp(0.9rem, 1.5vw, 1rem);"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div style="color: #666; font-size: clamp(0.8rem, 1.4vw, 0.9rem);">
                                    ₹<?php echo number_format($finalPrice, 2); ?> x <?php echo $item['quantity']; ?>
                                </div>
                            </div>
                            <div style="font-weight: 600; font-size: clamp(0.9rem, 1.5vw, 1rem); white-space: nowrap;">₹<?php echo number_format($itemTotal, 2); ?></div>
                        </div>
                        <?php endforeach; ?>
                        
                        <div style="border-top: 2px solid #eee; padding-top: clamp(0.75rem, 2vh, 1rem); margin-top: clamp(0.75rem, 2vh, 1rem);">
                            <div style="display: flex; justify-content: space-between; font-size: clamp(1.1rem, 2vw, 1.25rem); font-weight: 700; gap: 1rem; flex-wrap: wrap;">
                                <span>Total:</span>
                                <span style="color: var(--success-color);">₹<?php echo number_format($totalAmount, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Delivery Form -->
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title" style="font-size: clamp(1.1rem, 2.5vw, 1.25rem);">Delivery Details</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: clamp(0.75rem, 2vw, 1rem); border-radius: 8px; margin-bottom: 1rem; font-size: clamp(0.9rem, 1.5vw, 1rem);">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($_SESSION['user_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Phone Number (10 digits)</label>
                                <input type="tel" name="phone" class="form-input" pattern="[0-9]{10}" placeholder="9876543210" required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Delivery Address</label>
                                <textarea name="address" class="form-input" rows="3" placeholder="Enter your full address" required></textarea>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                                <div class="form-group">
                                    <label class="form-label">City</label>
                                    <input type="text" name="city" class="form-input" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Pincode (6 digits)</label>
                                    <input type="text" name="pincode" class="form-input" pattern="[0-9]{6}" placeholder="123456" required>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: clamp(0.75rem, 2vw, 1rem); border-radius: 8px; margin: clamp(1rem, 3vh, 1.5rem) 0; font-size: clamp(0.9rem, 1.5vw, 1rem);">
                                <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-money-bill-wave" style="color: var(--success-color);"></i>
                                    <strong>Cash on Delivery (COD)</strong>
                                </div>
                                <p style="color: #666; font-size: clamp(0.8rem, 1.4vw, 0.9rem); margin: 0;">Pay when you receive the product.</p>
                            </div>
                            
                            <div class="form-actions" style="margin-top: clamp(1rem, 3vh, 1.5rem); gap: clamp(0.5rem, 2vw, 1rem); flex-direction: column;">
                                <a href="<?php echo BASE_URL; ?>cart.php" class="btn btn-secondary" style="width: 100%; text-align: center;">Back to Cart</a>
                                <button type="submit" class="btn btn-success" style="width: 100%; padding: clamp(0.5rem, 1.5vw, 0.75rem) clamp(1rem, 2vw, 1.5rem);">
                                    <i class="fas fa-check"></i> Place Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
