<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirect('login.php');
}

require_once 'includes/header.php';

$customerId = $_SESSION['user_id'];
$database = new Database();
$db = $database->connect();

$inquiries = [];
if ($db) {
    $tableExists = $db->query("SHOW TABLES LIKE 'product_inquiries'")->rowCount() > 0;
    if ($tableExists) {
        $stmt = $db->prepare("SELECT pi.*, p.name as product_name FROM product_inquiries pi JOIN products p ON pi.product_id = p.id WHERE pi.customer_id = :customer_id ORDER BY pi.created_at DESC");
        $stmt->bindParam(':customer_id', $customerId);
        $stmt->execute();
        $inquiries = $stmt->fetchAll();
    }
}

$pageTitle = 'My Inquiries';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">My Inquiries</h1>

        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Inquiry ID</th>
                        <th>Product</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Admin Comment</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($inquiries)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 2rem; color: var(--text-light);">No inquiries found.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($inquiries as $inquiry): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($inquiry['inquiry_id']); ?></td>
                        <td><?php echo htmlspecialchars($inquiry['product_name']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($inquiry['created_at'])); ?></td>
                        <td><span class="badge badge-<?php echo $inquiry['status']; ?>"><?php echo ucwords(str_replace('_', ' ', $inquiry['status'])); ?></span></td>
                        <td><?php echo $inquiry['admin_comment'] ? htmlspecialchars(substr($inquiry['admin_comment'], 0, 50)) . '...' : '-'; ?></td>
                        <td><a href="inquiry-detail.php?id=<?php echo $inquiry['inquiry_id']; ?>" class="btn btn-sm btn-primary">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
