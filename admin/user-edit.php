<?php
$pageTitle = 'Edit User';
$activePage = 'customers';
require_once 'layout.php';
require_once __DIR__ . '/../config/database.php';

$userId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($userId === 0) {
    header('Location: customers.php');
    exit();
}

$success = '';
$error = '';

// Get user data
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $userId);
$stmt->execute();
$user = $stmt->fetch();

if (!$user) {
    header('Location: customers.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'customer';
    $status = $_POST['status'] ?? 'active';
    $password = $_POST['password'] ?? '';

    if (empty($fullName) || empty($email)) {
        $error = 'Name and email are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (!empty($phone) && !preg_match('/^[0-9]{10}$/', $phone)) {
        $error = 'Phone number must be 10 digits.';
    } else {
        // Check if email already exists for other users
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
        $checkStmt->bindParam(':email', $email);
        $checkStmt->bindParam(':id', $userId);
        $checkStmt->execute();

        if ($checkStmt->rowCount() > 0) {
            $error = 'Email address is already in use by another user.';
        } else {
            // Update user
            if (!empty($password)) {
                // Update with new password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $updateStmt = $db->prepare("UPDATE users SET full_name = :full_name, email = :email, phone = :phone, role = :role, status = :status, password = :password WHERE id = :id");
                $updateStmt->bindParam(':password', $hashedPassword);
            } else {
                // Update without changing password
                $updateStmt = $db->prepare("UPDATE users SET full_name = :full_name, email = :email, phone = :phone, role = :role, status = :status WHERE id = :id");
            }

            $updateStmt->bindParam(':full_name', $fullName);
            $updateStmt->bindParam(':email', $email);
            $updateStmt->bindParam(':phone', $phone);
            $updateStmt->bindParam(':role', $role);
            $updateStmt->bindParam(':status', $status);
            $updateStmt->bindParam(':id', $userId);

            if ($updateStmt->execute()) {
                $success = 'User updated successfully.';
                // Refresh user data
                $stmt->execute();
                $user = $stmt->fetch();
            } else {
                $error = 'Failed to update user.';
            }
        }
    }
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
        <h2 class="card-title"><i class="fas fa-user-edit" style="color: var(--primary-color);"></i> Edit User</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Full Name <span style="color: #e74c3c;">*</span></label>
                    <input type="text" name="full_name" class="form-input" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
            
                <div class="form-group">
                    <label class="form-label">Email <span style="color: #e74c3c;">*</span></label>
                    <input type="email" name="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
            </div>
                        
            <div class="form-group">
                <label class="form-label">Phone Number</label>
                <input type="tel" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone']); ?>" placeholder="Enter 10-digit phone number" maxlength="10" pattern="[0-9]{10}">
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Role <span style="color: #e74c3c;">*</span></label>
                    <select name="role" class="form-input" required>
                        <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                        <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        <option value="employee" <?php echo $user['role'] === 'employee' ? 'selected' : ''; ?>>Employee</option>
                        <option value="supplier" <?php echo $user['role'] === 'supplier' ? 'selected' : ''; ?>>Supplier</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Status <span style="color: #e74c3c;">*</span></label>
                    <select name="status" class="form-input" required>
                        <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">New Password <small style="color: #666;">(Leave blank to keep current password)</small></label>
                <input type="password" name="password" class="form-input" placeholder="Enter new password">
            </div>

            <div class="form-actions" style="margin-top: 2rem;">
                <a href="customers.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Customers
                </a>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<!-- User Info Card -->
<div class="content-card" style="margin-top: 1.5rem;">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-info-circle" style="color: var(--accent-color);"></i> User Information</h3>
    </div>
    <div class="card-body">
        <p><strong>User ID:</strong> <?php echo $user['id']; ?></p>
        <p><strong>Registered:</strong> <?php echo date('M d, Y H:i', strtotime($user['created_at'])); ?></p>
        <p><strong>Current Role:</strong> <span class="badge badge-<?php echo $user['role']; ?>"><?php echo ucfirst($user['role']); ?></span></p>
        <p><strong>Current Status:</strong> <span class="badge badge-<?php echo $user['status']; ?>"><?php echo ucfirst($user['status']); ?></span></p>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
