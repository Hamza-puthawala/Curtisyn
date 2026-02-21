<?php
$pageTitle = 'Inquiry Submitted';
require_once 'includes/header.php';

$inquiryId = $_GET['inquiry_id'] ?? '';
?>

<section class="section">
    <div class="container">
        <div class="form-container" style="text-align: center; max-width: 600px;">
            <div style="font-size: 4rem; color: var(--success-color); margin-bottom: 1rem;">✓</div>
            <h1 class="section-title" style="color: var(--success-color);">Inquiry Submitted Successfully!</h1>
            <p style="margin: 1rem 0;">Your inquiry has been received.</p>
            <p style="color: var(--text-light);">Inquiry ID: <strong><?php echo htmlspecialchars($inquiryId); ?></strong></p>
            <p style="margin: 1rem 0;">We will contact you soon.</p>

            <div class="action-buttons" style="justify-content: center; margin-top: 2rem;">
                <a href="my-inquiries.php" class="btn btn-primary">View My Inquiries</a>
                <a href="products.php" class="btn btn-secondary">Continue Shopping</a>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
