<?php
require_once 'config/database.php';
require_once 'config/razorpay.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($productId === 0) {
    header('Location: products.php');
    exit();
}

$pageTitle = 'Buy Now';
$currentPage = 'products';
require_once 'includes/header.php';

$database = new Database();
$db = $database->connect();
$userId = $_SESSION['user_id'];
$commission = 10;

$product = null;
$quantity = isset($_GET['qty']) ? intval($_GET['qty']) : 1;
$quantity = max(1, $quantity);

if ($db) {
    $commission = getGlobalCommission($db);
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id AND status = 'enabled' AND stock > 0");
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    $product = $stmt->fetch();
}

if (!$product) {
    header('Location: ' . BASE_URL . 'products.php');
    exit();
}

// Limit quantity to stock
$quantity = min($quantity, $product['stock']);

$finalPrice = calculateFinalPrice($product['price'], $commission);
$platformCharge = $finalPrice - $product['price'];
$totalPlatformCharge = $platformCharge * $quantity;
$totalAmount = $finalPrice * $quantity;

// Handle form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $paymentMethod = $_POST['payment_method'] ?? 'cod';

    if ($paymentMethod === 'cod') {
        $name = sanitizeInput($_POST['name'] ?? '');
        $phone = sanitizeInput($_POST['phone'] ?? '');
        $address = sanitizeInput($_POST['address'] ?? '');
        $city = sanitizeInput($_POST['city'] ?? '');
        $pincode = sanitizeInput($_POST['pincode'] ?? '');
        $orderQuantity = intval($_POST['quantity'] ?? 1);

        if (empty($name) || empty($phone) || empty($address) || empty($city) || empty($pincode)) {
            $error = 'Please fill in all fields.';
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $error = 'Please enter a valid 10-digit phone number.';
        } elseif (!preg_match('/^[0-9]{6}$/', $pincode)) {
            $error = 'Please enter a valid 6-digit pincode.';
        } else {
            $orderId = 'ORD' . date('Ymd') . strtoupper(substr(uniqid(), -6));
            $finalAmount = $finalPrice * $orderQuantity;
            $fullAddress = $address . ', ' . $city . ' - ' . $pincode;

            $stmt = $db->prepare("
                INSERT INTO customer_orders
                (order_id, user_id, product_id, quantity, price, total_amount, customer_name, customer_phone, customer_address, payment_method, payment_status, order_status)
                VALUES (:order_id, :user_id, :product_id, :quantity, :price, :total_amount, :customer_name, :customer_phone, :customer_address, 'cod', 'pending', 'pending')
            ");

            $stmt->bindParam(':order_id', $orderId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':product_id', $productId);
            $stmt->bindParam(':quantity', $orderQuantity);
            $stmt->bindParam(':price', $finalPrice);
            $stmt->bindParam(':total_amount', $finalAmount);
            $stmt->bindParam(':customer_name', $name);
            $stmt->bindParam(':customer_phone', $phone);
            $stmt->bindParam(':customer_address', $fullAddress);

            if ($stmt->execute()) {
                $updateStmt = $db->prepare("UPDATE products SET stock = stock - :qty WHERE id = :id");
                $updateStmt->bindParam(':qty', $orderQuantity);
                $updateStmt->bindParam(':id', $productId);
                $updateStmt->execute();

                require_once __DIR__ . '/includes/OrderEmailTrigger.php';
                $emailTrigger = new OrderEmailTrigger($db);
                try {
                    $emailTrigger->onOrderPlaced($orderId);
                } catch (Exception $e) {
                    error_log('Email sending failed: ' . $e->getMessage());
                }

                header('Location: ' . BASE_URL . 'order-success.php?order_id=' . $orderId);
                exit();
            } else {
                $error = 'Failed to place order. Please try again.';
            }
        }
    }
}
?>

<section class="section">
    <div class="container">
        <h1 class="section-title"><i class="fas fa-bolt"></i> Buy Now</h1>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1.5rem;">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
            <div style="text-align: center; padding: 2rem;">
                <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary">Continue Shopping</a>
                <a href="<?php echo BASE_URL; ?>my-orders.php" class="btn btn-secondary">View My Orders</a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                <!-- Product Summary -->
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Order Summary</h2>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; gap: 1rem; margin-bottom: 1.5rem;">
                            <?php if ($product['image']): ?>
                                <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
                            <?php endif; ?>
                            <div>
                                <h3 style="margin-bottom: 0.5rem;"><?php echo htmlspecialchars($product['name']); ?></h3>
                                <p style="color: #666; font-size: 0.9rem;">Price: ₹<?php echo number_format($finalPrice, 2); ?></p>
                                <p style="color: #666; font-size: 0.9rem;">Stock: <?php echo $product['stock']; ?></p>
                            </div>
                        </div>
                        
                        <div style="border-top: 1px solid #eee; padding-top: 1rem;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Product Price (each):</span>
                                <span>₹<?php echo number_format($product['price'], 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Platform Charges (<?php echo $commission; ?>%):</span>
                                <span style="color: #e74c3c;">+₹<?php echo number_format($platformCharge, 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Unit Price:</span>
                                <span>₹<?php echo number_format($finalPrice, 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                                <span>Quantity:</span>
                                <span><?php echo $quantity; ?></span>
                            </div>
                            <?php if ($quantity > 1): ?>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; color: #666;">
                                <span>Subtotal:</span>
                                <span>₹<?php echo number_format($product['price'] * $quantity, 2); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem; font-size: 0.9rem; color: #666;">
                                <span>Total Platform Charges:</span>
                                <span style="color: #e74c3c;">+₹<?php echo number_format($totalPlatformCharge, 2); ?></span>
                            </div>
                            <?php endif; ?>
                            <div style="border-top: 2px solid #eee; margin-top: 0.5rem; padding-top: 0.5rem; display: flex; justify-content: space-between; font-size: 1.25rem; font-weight: 700;">
                                <span>Total Amount:</span>
                                <span style="color: var(--success-color);">₹<?php echo number_format($totalAmount, 2); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Delivery Form -->
                <div class="content-card">
                    <div class="card-header">
                        <h2 class="card-title">Delivery Details</h2>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-error" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="product_id" value="<?php echo $productId; ?>">
                            <input type="hidden" name="quantity" value="<?php echo $quantity; ?>">
                            
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
                            
                            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin: 1.5rem 0;">
                                <label style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem; cursor: pointer;">
                                    <input type="radio" name="payment_method" value="cod" checked onchange="togglePaymentMethod()">
                                    <div>
                                        <strong>Cash on Delivery (COD)</strong>
                                        <p style="color: #666; font-size: 0.85rem; margin: 0;">Pay when you receive the product</p>
                                    </div>
                                </label>
                                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer;">
                                    <input type="radio" name="payment_method" value="online" onchange="togglePaymentMethod()">
                                    <div>
                                        <strong>Pay Online (Razorpay)</strong>
                                        <p style="color: #666; font-size: 0.85rem; margin: 0;">Pay securely with UPI, Card, Net Banking</p>
                                    </div>
                                </label>
                            </div>
                            
                            <div id="loadingIndicator" style="display: none; text-align: center; padding: 1rem;">
                                <i class="fas fa-spinner fa-spin" style="font-size: 2rem; color: var(--primary-color);"></i>
                                <p style="margin-top: 0.5rem;">Processing...</p>
                            </div>
                            
                            <div class="form-actions" style="margin-top: 1.5rem;">
                                <a href="<?php echo BASE_URL; ?>product-detail.php?id=<?php echo $productId; ?>" class="btn btn-secondary">Cancel</a>
                                <button type="submit" id="placeOrderBtn" class="btn btn-success btn-lg" style="flex: 1;">
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

<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
const razorpayKey = '<?php echo $razorpayConfig['key_id']; ?>';
const orderAmount = <?php echo $totalAmount * 100; ?>;
const productName = '<?php echo htmlspecialchars($product['name']); ?>';
const productId = <?php echo $productId; ?>;

function togglePaymentMethod() {
    const method = document.querySelector('input[name="payment_method"]:checked').value;
    const btn = document.getElementById('placeOrderBtn');
    if (method === 'online') {
        btn.innerHTML = '<i class="fas fa-credit-card"></i> Pay Now';
    } else {
        btn.innerHTML = '<i class="fas fa-check"></i> Place Order';
    }
}

document.querySelector('form').addEventListener('submit', function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;
    
    if (paymentMethod === 'online') {
        e.preventDefault();
        initiateRazorpayPayment();
    }
});

function initiateRazorpayPayment() {
    const formData = new FormData(document.querySelector('form'));
    formData.append('action', 'create_razorpay_order');
    
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('placeOrderBtn').disabled = true;
    
    fetch('ajax-payment.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('placeOrderBtn').disabled = false;
        
        if (data.success) {
            openRazorpayCheckout(data);
        } else {
            alert(data.message || 'Failed to create order. Please try again.');
        }
    })
    .catch(error => {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('placeOrderBtn').disabled = false;
        console.error('Error:', error);
        alert('Network error: ' + error.message + '. Please check console for details.');
    });
}

function openRazorpayCheckout(orderData) {
    const options = {
        key: razorpayKey,
        amount: orderData.amount,
        currency: orderData.currency,
        name: 'Curtisyn',
        description: productName,
        order_id: orderData.razorpay_order_id,
        handler: function(response) {
            verifyPayment(response, orderData.order_id);
        },
        prefill: {
            name: document.querySelector('input[name="name"]').value,
            email: '<?php echo $_SESSION['user_email'] ?? ''; ?>',
            contact: document.querySelector('input[name="phone"]').value
        },
        theme: {
            color: '#667eea'
        },
        modal: {
            ondismiss: function() {
                console.log('Payment cancelled');
            }
        }
    };
    
    const rzp = new Razorpay(options);
    rzp.open();
}

function verifyPayment(response, orderId) {
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('placeOrderBtn').disabled = true;
    
    const formData = new FormData();
    formData.append('action', 'verify_payment');
    formData.append('razorpay_payment_id', response.razorpay_payment_id);
    formData.append('razorpay_order_id', response.razorpay_order_id);
    formData.append('razorpay_signature', response.razorpay_signature);
    formData.append('order_id', orderId);
    
    fetch('ajax-payment.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        document.getElementById('loadingIndicator').style.display = 'none';
        
        if (data.success) {
            window.location.href = '<?php echo BASE_URL; ?>order-success.php?order_id=' + orderId;
        } else {
            alert(data.message || 'Payment verification failed.');
            document.getElementById('placeOrderBtn').disabled = false;
        }
    })
    .catch(error => {
        document.getElementById('loadingIndicator').style.display = 'none';
        document.getElementById('placeOrderBtn').disabled = false;
        alert('Verification error. Please contact support.');
    });
}
</script>

<?php require_once 'includes/footer.php'; ?>
