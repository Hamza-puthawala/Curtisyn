<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

$inquiryId = $_GET['id'] ?? '';
$customerId = $_SESSION['user_id'];

if (empty($inquiryId)) {
    redirect('my-inquiries.php');
}

require_once 'includes/header.php';

$database = new Database();
$db = $database->connect();

$inquiry = null;
if ($db) {
    $stmt = $db->prepare("SELECT pi.*, p.name as product_name, p.price as product_price FROM product_inquiries pi JOIN products p ON pi.product_id = p.id WHERE pi.inquiry_id = :inquiry_id AND pi.customer_id = :customer_id");
    $stmt->bindParam(':inquiry_id', $inquiryId);
    $stmt->bindParam(':customer_id', $customerId);
    $stmt->execute();
    $inquiry = $stmt->fetch();
}

if (!$inquiry) {
    redirect('my-inquiries.php');
}

$pageTitle = 'Inquiry Details';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Inquiry Details</h1>

        <div class="form-container">
            <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Product Information</h3>
                <p><strong>Product:</strong> <?php echo htmlspecialchars($inquiry['product_name']); ?></p>
                <p><strong>Price:</strong> ₹<?php echo number_format($inquiry['product_price'], 2); ?></p>
            </div>

            <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Inquiry Information</h3>
                <p><strong>Inquiry ID:</strong> <?php echo htmlspecialchars($inquiry['inquiry_id']); ?></p>
                <p><strong>Status:</strong> <span class="badge badge-<?php echo $inquiry['status']; ?>"><?php echo ucwords(str_replace('_', ' ', $inquiry['status'])); ?></span></p>
                <p><strong>Submitted On:</strong> <?php echo date('M d, Y H:i', strtotime($inquiry['created_at'])); ?></p>
                <?php if ($inquiry['updated_at']): ?>
                <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($inquiry['updated_at'])); ?></p>
                <?php endif; ?>
            </div>

            <div style="background: var(--bg-light); padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem;">
                <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Your Details</h3>
                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($inquiry['full_name']); ?></p>
                <p><strong>Mobile:</strong> <?php echo htmlspecialchars($inquiry['mobile']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($inquiry['email']); ?></p>
                <p><strong>Address:</strong> <?php echo htmlspecialchars($inquiry['address']); ?></p>
                <p><strong>City:</strong> <?php echo htmlspecialchars($inquiry['city']); ?></p>
                <p><strong>State:</strong> <?php echo htmlspecialchars($inquiry['state']); ?></p>
                <p><strong>Pincode:</strong> <?php echo htmlspecialchars($inquiry['pincode']); ?></p>
                <?php if ($inquiry['message']): ?>
                <p><strong>Message:</strong> <?php echo nl2br(htmlspecialchars($inquiry['message'])); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($inquiry['admin_comment']): ?>
            <div style="background: #e8f4f8; padding: 1.5rem; border-radius: 10px; margin-bottom: 2rem; border-left: 4px solid var(--accent-color);">
                <h3 style="margin-bottom: 1rem; color: var(--primary-color);">Admin Response</h3>
                <p><?php echo nl2br(htmlspecialchars($inquiry['admin_comment'])); ?></p>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <a href="my-inquiries.php" class="btn btn-secondary">Back to My Inquiries</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
