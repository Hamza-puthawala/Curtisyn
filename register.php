<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $fullName = sanitizeInput($_POST['full_name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
            $error = 'All fields required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be 6+ characters.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } else {
            $database = new Database();
            $db = $database->connect();
            
            if ($db) {
                try {
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $error = 'Email already registered.';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                        $role = 'customer';
                        
                        $stmt = $db->prepare("INSERT INTO users (full_name, email, password, role) VALUES (:full_name, :email, :password, :role)");
                        $stmt->bindParam(':full_name', $fullName);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':password', $hashedPassword);
                        $stmt->bindParam(':role', $role);
                        
                        if ($stmt->execute()) {
                            $success = 'Registration successful. Please login.';
                        } else {
                            $error = 'Registration failed.';
                        }
                    }
                } catch (PDOException $e) {
                    $error = 'Database error.';
                }
            } else {
                $error = 'Connection failed.';
            }
        }
    }
}

$csrfToken = generateCsrfToken();

$pageTitle = 'Register';
$currentPage = 'register';
require_once 'includes/header.php';
?>

<section class="section">
    <div class="container">
        <h1 class="section-title">Register</h1>
        
        <div class="form-container">
            <?php 
            if ($error) echo displayError($error);
            if ($success) {
                echo displaySuccess($success);
                echo '<p style="text-align: center; margin-top: 1rem;"><a href="login.php">Login here</a></p>';
            }
            ?>
            
            <?php if (!$success): ?>
            <form method="POST" action="" onsubmit="return validateRegisterForm()">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                
                <div class="form-group">
                    <label for="full_name" class="form-label">Full Name</label>
                    <input type="text" id="full_name" name="full_name" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" id="email" name="email" class="form-input" required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="password-field">
                        <input type="password" id="password" name="password" class="form-input" required>
                        <button type="button" class="password-toggle" onclick="togglePwd('password',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">Confirm Password</label>
                    <div class="password-field">
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        <button type="button" class="password-toggle" onclick="togglePwd('confirm_password',this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary" style="width: 100%;">Register</button>
            </form>
            
            <p style="text-align: center; margin-top: 1.5rem;">
                Have an account? <a href="login.php">Login</a>
            </p>
            <?php endif; ?>
        </div>
    </div>
</section>

<style>
.password-field { position: relative; }
.password-toggle {
    position: absolute; right: 1rem; top: 50%;
    transform: translateY(-50%);
    background: none; border: none; cursor: pointer;
    color: var(--text-light); font-size: 1rem;
}
.password-toggle:hover { color: var(--accent-color); }
</style>
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
