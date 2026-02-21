<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

// Restrict to logged-in customers only
if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}
if (getUserRole() !== 'customer') {
    header('Location: index.php');
    exit();
}

$pageTitle = 'My Account';
$currentPage = 'account';
require_once 'includes/header.php';

$database = new Database();
$db = $database->connect();
$userId = $_SESSION['user_id'];

$success = '';
$error   = '';

// ── Fetch current user ─────────────────────────────────────────────────────
$stmt = $db->prepare("SELECT * FROM users WHERE id = :id");
$stmt->bindParam(':id', $userId);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// ── Handle Profile Update ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $newName = trim(htmlspecialchars($_POST['full_name'] ?? ''));
        if (empty($newName)) {
            $error = 'Name cannot be empty.';
        } elseif (strlen($newName) < 2) {
            $error = 'Name must be at least 2 characters.';
        } else {
            $stmt = $db->prepare("UPDATE users SET full_name = :name WHERE id = :id");
            $stmt->bindParam(':name', $newName);
            $stmt->bindParam(':id', $userId);
            if ($stmt->execute()) {
                $_SESSION['user_name'] = $newName;
                $user['full_name'] = $newName;
                $success = 'Profile updated successfully!';
            } else {
                $error = 'Failed to update profile. Please try again.';
            }
        }
    }
}

// ── Handle Password Update ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $currentPwd = $_POST['current_password'] ?? '';
        $newPwd     = $_POST['new_password'] ?? '';
        $confirmPwd = $_POST['confirm_password'] ?? '';

        if (empty($currentPwd) || empty($newPwd) || empty($confirmPwd)) {
            $error = 'All password fields are required.';
        } elseif (!password_verify($currentPwd, $user['password'])) {
            $error = 'Current password is incorrect.';
        } elseif (strlen($newPwd) < 6) {
            $error = 'New password must be at least 6 characters.';
        } elseif ($newPwd !== $confirmPwd) {
            $error = 'New password and confirm password do not match.';
        } else {
            $hashed = password_hash($newPwd, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password = :pwd WHERE id = :id");
            $stmt->bindParam(':pwd', $hashed);
            $stmt->bindParam(':id', $userId);
            if ($stmt->execute()) {
                $user['password'] = $hashed;
                $success = 'Password changed successfully!';
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}

// ── Fetch Order History ────────────────────────────────────────────────────
$orders = [];
if ($db) {
    $stmt = $db->prepare("
        SELECT co.order_id, co.quantity, co.total_amount, co.order_status, co.id as order_date,
               p.name as product_name, p.image
        FROM customer_orders co
        JOIN products p ON co.product_id = p.id
        WHERE co.user_id = :user_id
        ORDER BY co.id DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$csrfToken = generateCsrfToken();
?>

<style>
/* ── Account Page Layout ── */
.account-wrapper {
    max-width: 900px;
    margin: 2.5rem auto;
    padding: 0 1rem;
}
.account-header {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    margin-bottom: 2.5rem;
}
.account-avatar {
    width: 70px; height: 70px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--accent-color));
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; color: white; font-weight: 700; flex-shrink: 0;
}
.account-header h1 { font-size: 1.6rem; color: var(--primary-color); margin-bottom: 0.2rem; }
.account-header p  { color: var(--text-light); font-size: 0.95rem; }

.account-section {
    background: var(--white);
    border-radius: 12px;
    box-shadow: var(--shadow);
    padding: 2rem;
    margin-bottom: 2rem;
}
.account-section-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--primary-color);
    margin-bottom: 1.5rem;
    padding-bottom: 0.75rem;
    border-bottom: 2px solid var(--bg-light);
    display: flex; align-items: center; gap: 0.6rem;
}
.account-section-title i { color: var(--accent-color); }

/* form inputs inherit from style.css */
.input-readonly {
    background: var(--bg-light) !important;
    color: var(--text-light) !important;
    cursor: not-allowed;
}
.password-field { position: relative; }
.password-toggle {
    position: absolute; right: 1rem; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: var(--text-light); font-size: 1rem;
}
.password-toggle:hover { color: var(--accent-color); }

/* ── Order history table ── */
.order-status {
    display: inline-block; padding: 0.25rem 0.75rem;
    border-radius: 20px; font-size: 0.8rem; font-weight: 600;
}
.status-pending   { background: #fff3cd; color: #856404; }
.status-confirmed { background: #d1ecf1; color: #0c5460; }
.status-shipped   { background: #cce5ff; color: #004085; }
.status-delivered { background: #d4edda; color: #155724; }
.status-cancelled { background: #f8d7da; color: #721c24; }

.alert-msg {
    padding: 0.9rem 1.2rem;
    border-radius: 8px;
    margin-bottom: 1.5rem;
    font-weight: 500;
    display: flex; align-items: center; gap: 0.6rem;
}
.alert-msg.success { background: #d4edda; color: #155724; border-left: 4px solid var(--success-color); }
.alert-msg.error   { background: #f8d7da; color: #721c24; border-left: 4px solid var(--danger-color); }

.order-product-thumb {
    width: 42px; height: 42px; object-fit: cover;
    border-radius: 6px; margin-right: 0.5rem; vertical-align: middle;
}
@media (max-width: 600px) {
    .account-section { padding: 1.25rem; }
    .form-row { grid-template-columns: 1fr !important; }
}
</style>

<div class="account-wrapper">

    <!-- ── Page Header ── -->
    <div class="account-header">
        <div class="account-avatar"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></div>
        <div>
            <h1>Hello, <?php echo htmlspecialchars($user['full_name']); ?>!</h1>
            <p><?php echo htmlspecialchars($user['email']); ?> &nbsp;·&nbsp; Customer Account</p>
        </div>
    </div>

    <!-- ── Global messages ── -->
    <?php if ($success): ?>
        <div class="alert-msg success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert-msg error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- ── Section 1: Profile Information ── -->
    <div class="account-section">
        <div class="account-section-title"><i class="fas fa-user"></i> Profile Information</div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="update_profile" value="1">
            <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="full_name" class="form-input"
                           value="<?php echo htmlspecialchars($user['full_name']); ?>"
                           required minlength="2" placeholder="Your full name">
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" class="form-input input-readonly"
                           value="<?php echo htmlspecialchars($user['email']); ?>"
                           readonly title="Email cannot be changed">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Member Since</label>
                <input type="text" class="form-input input-readonly"
                       value="<?php echo date('F d, Y', strtotime($user['created_at'])); ?>"
                       readonly>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Profile</button>
        </form>
    </div>

    <!-- ── Section 2: Change Password ── -->
    <div class="account-section">
        <div class="account-section-title"><i class="fas fa-lock"></i> Change Password</div>
        <form method="POST" action="">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            <input type="hidden" name="update_password" value="1">
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <div class="password-field">
                    <input type="password" name="current_password" id="currentPwd"
                           class="form-input" required placeholder="Enter current password">
                    <button type="button" class="password-toggle" onclick="togglePwd('currentPwd',this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-row" style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="password-field">
                        <input type="password" name="new_password" id="newPwd"
                               class="form-input" required minlength="6" placeholder="Min. 6 characters">
                        <button type="button" class="password-toggle" onclick="togglePwd('newPwd',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Confirm New Password</label>
                    <div class="password-field">
                        <input type="password" name="confirm_password" id="confirmPwd"
                               class="form-input" required placeholder="Repeat new password">
                        <button type="button" class="password-toggle" onclick="togglePwd('confirmPwd',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> Change Password</button>
        </form>
    </div>

    <!-- ── Section 3: Order History ── -->
    <div class="account-section">
        <div class="account-section-title"><i class="fas fa-history"></i> Order History</div>

        <?php if (empty($orders)): ?>
            <div style="text-align:center; padding:2.5rem; color: var(--text-light);">
                <i class="fas fa-box-open" style="font-size:3rem; opacity:0.3; display:block; margin-bottom:1rem;"></i>
                <p>You haven't placed any orders yet.</p>
                <a href="<?php echo BASE_URL; ?>products.php" class="btn btn-primary" style="margin-top:1rem;">
                    <i class="fas fa-shopping-bag"></i> Browse Products
                </a>
            </div>
        <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($order['order_id']); ?></strong></td>
                            <td>
                                <?php if ($order['image']): ?>
                                    <img src="<?php echo BASE_URL; ?>uploads/products/<?php echo htmlspecialchars($order['image']); ?>"
                                         alt="" class="order-product-thumb">
                                <?php endif; ?>
                                <?php echo htmlspecialchars($order['product_name']); ?>
                            </td>
                            <td><?php echo $order['quantity']; ?></td>
                            <td><strong>₹<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                            <td>
                                <span class="order-status status-<?php echo $order['order_status']; ?>">
                                    <?php echo ucfirst($order['order_status']); ?>
                                </span>
                            </td>
                            <td>
                                <a href="<?php echo BASE_URL; ?>order-success.php?order_id=<?php echo urlencode($order['order_id']); ?>"
                                   class="btn btn-sm btn-secondary">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /.account-wrapper -->

<script>
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    const icon  = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
