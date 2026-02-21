<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$productId = isset($_GET['product_id']) ? intval($_GET['product_id']) : 0;

if ($productId === 0) {
    redirect('products.php');
}

require_once 'includes/header.php';

$database = new Database();
$db = $database->connect();

$product = null;
if ($db) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = :id AND status = 'enabled'");
    $stmt->bindParam(':id', $productId);
    $stmt->execute();
    $product = $stmt->fetch();
}

if (!$product) {
    redirect('products.php');
}

$customerId = $_SESSION['user_id'];
$customer = null;
if ($db) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->bindParam(':id', $customerId);
    $stmt->execute();
    $customer = $stmt->fetch();
}

$pageTitle = 'Product Inquiry';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Product Inquiry</h1>

        <div class="form-container">
            <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--primary-color);"><?php echo htmlspecialchars($product['name']); ?></h3>
                <p style="color: var(--text-light);">Price: ₹<?php echo number_format($product['price'], 2); ?></p>
            </div>

            <form method="POST" action="process-inquiry.php">
                <input type="hidden" name="product_id" value="<?php echo $productId; ?>">

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <input type="tel" name="mobile" class="form-input" pattern="[0-9]{10}" maxlength="10" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-input" rows="2" required></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Pincode</label>
                        <input type="text" name="pincode" class="form-input" pattern="[0-9]{6}" maxlength="6" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">State</label>
                        <input type="text" name="state" class="form-input" required>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">City</label>
                    <input type="text" name="city" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Message / Requirement (Optional)</label>
                    <textarea name="message" class="form-input" rows="3"></textarea>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Submit Inquiry</button>
                    <a href="product-detail.php?id=<?php echo $productId; ?>" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
