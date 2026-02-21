<?php
$pageTitle = 'Customers';
$activePage = 'customers';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';

$success = '';
$error = '';

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $userId = intval($_GET['delete']);

    // Check if user has orders
    $checkStmt = $db->prepare("SELECT COUNT(*) FROM customer_orders WHERE user_id = :user_id");
    $checkStmt->bindParam(':user_id', $userId);
    $checkStmt->execute();
    $orderCount = $checkStmt->fetchColumn();

    if ($orderCount > 0) {
        $error = 'Cannot delete customer with existing orders. Please deactivate instead.';
    } else {
        // Delete related records first
        $db->beginTransaction();
        try {
            // Delete from cart
            $cartStmt = $db->prepare("DELETE FROM cart WHERE user_id = :user_id");
            $cartStmt->bindParam(':user_id', $userId);
            $cartStmt->execute();

            // Delete from wishlist
            $wishStmt = $db->prepare("DELETE FROM wishlist WHERE user_id = :user_id");
            $wishStmt->bindParam(':user_id', $userId);
            $wishStmt->execute();

            // Delete from product_inquiries
            $inquiryStmt = $db->prepare("DELETE FROM product_inquiries WHERE customer_id = :user_id");
            $inquiryStmt->bindParam(':user_id', $userId);
            $inquiryStmt->execute();

            // Delete user
            $stmt = $db->prepare("DELETE FROM users WHERE id = :id AND role = 'customer'");
            $stmt->bindParam(':id', $userId);
            $stmt->execute();

            $db->commit();
            $success = 'Customer deleted successfully.';
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Failed to delete customer: ' . $e->getMessage();
        }
    }
}

// Handle Status Toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $userId = intval($_GET['toggle']);
    $stmt = $db->prepare("SELECT status FROM users WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    $stmt->execute();
    $currentStatus = $stmt->fetchColumn();
    $newStatus = $currentStatus === 'active' ? 'inactive' : 'active';

    $updateStmt = $db->prepare("UPDATE users SET status = :status WHERE id = :id");
    $updateStmt->bindParam(':status', $newStatus);
    $updateStmt->bindParam(':id', $userId);
    if ($updateStmt->execute()) {
        $success = 'Customer status updated to ' . $newStatus . '.';
    } else {
        $error = 'Failed to update status.';
    }
}

$customers = [];
if ($db) {
    $stmt = $db->query("SELECT id, full_name, email, phone, status, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC");
    $customers = $stmt->fetchAll();
}
?>

<?php if ($success): ?>
<div class="alert alert-success" style="background: #d4edda; color: #155724; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger" style="background: #f8d7da; color: #721c24; padding: 1rem; border-radius: 8px; margin-bottom: 1rem;">
    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
</div>
<?php endif; ?>

<div class="content-card">
    <div class="card-header">
        <h2 class="card-title"><i class="fas fa-users" style="color: var(--primary-color);"></i> All Customers</h2>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Status</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?php echo $customer['id']; ?></td>
                        <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($customer['email']); ?></td>
                        <td><?php echo htmlspecialchars($customer['phone'] ?? ''); ?></td>
                        <td><span class="badge badge-<?php echo $customer['status']; ?>"><?php echo ucfirst($customer['status']); ?></span></td>
                        <td><?php echo date('Y-m-d', strtotime($customer['created_at'])); ?></td>
                        <td>
                            <a href="user-edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-sm btn-primary" style="margin-right: 0.5rem;">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="customers.php?toggle=<?php echo $customer['id']; ?>" class="btn btn-sm btn-secondary" style="margin-right: 0.5rem;">
                                <i class="fas fa-power-off"></i> <?php echo $customer['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                            </a>
                            <a href="customers.php?delete=<?php echo $customer['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this customer? This action cannot be undone.');">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
