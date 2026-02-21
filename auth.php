<?php
require_once 'includes/functions.php';

$pageTitle = 'Login/Register';
$currentPage = 'auth';

if (isLoggedIn()) {
    $role = getUserRole();
    switch ($role) {
        case 'admin': redirect(BASE_URL . 'admin/dashboard-new.php');
        case 'employee': redirect(BASE_URL . 'employee/dashboard-new.php');
        case 'supplier': redirect(BASE_URL . 'supplier/dashboard-new.php');
        default: redirect(BASE_URL . 'index.php');
    }
}

// Handle login form submission BEFORE any output
$loginError = '';
if (isset($_POST['login_submit'])) {
    if (!validateCsrfToken($_POST['login_csrf_token'] ?? '')) {
        $loginError = 'Invalid request.';
    } else {
        $email = sanitizeInput($_POST['login_email'] ?? '');
        $password = $_POST['login_password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $loginError = 'Please enter email and password.';
        } else {
            require_once 'config/database.php';
            $database = new Database();
            $db = $database->connect();
            
            if ($db) {
                try {
                    $stmt = $db->prepare("SELECT id, full_name, password, role, status FROM users WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() === 1) {
                        $user = $stmt->fetch();
                        
                        if ($user['status'] !== 'active') {
                            $loginError = 'Account is inactive.';
                        } elseif (password_verify($password, $user['password'])) {
                            session_regenerate_id(true);
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_name'] = $user['full_name'];
                            $_SESSION['user_role'] = $user['role'];
                            
                            switch ($user['role']) {
                                case 'admin': redirect(BASE_URL . 'admin/dashboard-new.php');
                                case 'employee': redirect(BASE_URL . 'employee/dashboard-new.php');
                                case 'supplier': redirect(BASE_URL . 'supplier/dashboard-new.php');
                                default: redirect(BASE_URL . 'index.php');
                            }
                        } else {
                            $loginError = 'Invalid credentials.';
                        }
                    } else {
                        $loginError = 'Invalid credentials.';
                    }
                } catch (PDOException $e) {
                    $loginError = 'Database error.';
                }
            } else {
                $loginError = 'Connection failed.';
            }
        }
    }
}

// Handle registration form submission BEFORE any output
$regError = '';
$regSuccess = '';
if (isset($_POST['register_submit'])) {
    if (!validateCsrfToken($_POST['register_csrf_token'] ?? '')) {
        $regError = 'Invalid request.';
    } else {
        $fullName = sanitizeInput($_POST['reg_full_name'] ?? '');
        $email = sanitizeInput($_POST['reg_email'] ?? '');
        $phone = sanitizeInput($_POST['reg_phone'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $confirmPassword = $_POST['reg_confirm_password'] ?? '';
        
        if (empty($fullName) || empty($email) || empty($phone) || empty($password) || empty($confirmPassword)) {
            $regError = 'All fields required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $regError = 'Invalid email.';
        } elseif (!preg_match('/^[0-9]{10}$/', $phone)) {
            $regError = 'Phone number must be 10 digits.';
        } elseif (strlen($password) < 6) {
            $regError = 'Password must be 6+ characters.';
        } elseif ($password !== $confirmPassword) {
            $regError = 'Passwords do not match.';
        } else {
            require_once 'config/database.php';
            $database = new Database();
            $db = $database->connect();
            
            if ($db) {
                try {
                    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
                    $stmt->bindParam(':email', $email);
                    $stmt->execute();
                    
                    if ($stmt->rowCount() > 0) {
                        $regError = 'Email already registered.';
                    } else {
                        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                        $role = 'customer';
                        
                        $stmt = $db->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (:full_name, :email, :phone, :password, :role)");
                        $stmt->bindParam(':full_name', $fullName);
                        $stmt->bindParam(':email', $email);
                        $stmt->bindParam(':phone', $phone);
                        $stmt->bindParam(':password', $hashedPassword);
                        $stmt->bindParam(':role', $role);
                        
                        if ($stmt->execute()) {
                            $regSuccess = 'Registration successful. Please login.';
                        } else {
                            $regError = 'Registration failed.';
                        }
                    }
                } catch (PDOException $e) {
                    $regError = 'Database error.';
                }
            } else {
                $regError = 'Connection failed.';
            }
        }
    }
}

$loginCsrfToken = generateCsrfToken();
$regCsrfToken = generateCsrfToken();

require_once 'includes/header.php';
?>

<style>
.auth-container {
    max-width: 800px;
    margin: 2rem auto;
    background: white;
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    overflow: hidden;
}

.auth-tabs {
    display: flex;
    border-bottom: 1px solid #e0e0e0;
    background: #f8f9fa;
}

.auth-tab {
    flex: 1;
    padding: 1rem;
    text-align: center;
    cursor: pointer;
    font-weight: 600;
    color: #666;
    transition: all 0.3s ease;
    border-bottom: 3px solid transparent;
}

.auth-tab.active {
    color: var(--secondary-color);
    border-bottom: 3px solid var(--secondary-color);
    background: rgba(231, 76, 60, 0.05);
}

.auth-tab:hover:not(.active) {
    background: rgba(0,0,0,0.03);
}

.auth-content {
    padding: 2rem;
}

.auth-form {
    display: none;
}

.auth-form.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.form-group {
    margin-bottom: 1.5rem;
}

.form-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--text-dark);
}

.form-input {
    width: 100%;
    padding: 0.8rem 1rem;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 1rem;
    transition: all 0.3s ease;
    font-family: inherit;
}

.form-input:focus {
    outline: none;
    border-color: var(--accent-color);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
}

.btn-auth {
    width: 100%;
    padding: 0.8rem;
    border: none;
    border-radius: 8px;
    font-size: 1.1rem;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
}

.btn-login {
    background: var(--primary-color);
    color: white;
}

.btn-login:hover {
    background: #1a252f;
    transform: translateY(-2px);
}

.btn-register {
    background: var(--secondary-color);
    color: white;
}

.btn-register:hover {
    background: #c0392b;
    transform: translateY(-2px);
}

.auth-links {
    text-align: center;
    margin-top: 1.5rem;
    padding-top: 1.5rem;
    border-top: 1px solid #eee;
}

.auth-links a {
    color: var(--accent-color);
    text-decoration: none;
    font-weight: 500;
    transition: color 0.3s ease;
}

.auth-links a:hover {
    color: var(--secondary-color);
    text-decoration: underline;
}

@media (max-width: 768px) {
    .auth-container {
        margin: 1rem;
        border-radius: 10px;
    }
    
    .auth-content {
        padding: 1.5rem;
    }
    
    .auth-tabs {
        flex-direction: column;
    }
    
    .auth-tab {
        border-bottom: 1px solid #e0e0e0;
        border-left: 3px solid transparent;
        border-right: none;
        text-align: left;
        padding: 1rem 1.5rem;
    }
    
    .auth-tab.active {
        border-left: 3px solid var(--secondary-color);
        border-bottom: 1px solid #e0e0e0;
    }
}
</style>

<section class="section">
    <div class="container">
        <h1 class="section-title text-center mb-4">Account Access</h1>
        
        <div class="auth-container">
            <div class="auth-tabs">
                <div class="auth-tab active" data-tab="login">Login</div>
                <div class="auth-tab" data-tab="register">Register</div>
            </div>
            
            <div class="auth-content">
                <!-- Login Form -->
                <div class="auth-form active" id="login-form">
                    <?php if ($loginError) echo displayError($loginError); ?>
                    
                    <form method="POST" action="" id="loginForm">
                        <input type="hidden" name="login_csrf_token" value="<?php echo $loginCsrfToken; ?>">
                        
                        <div class="form-group">
                            <label for="login_email" class="form-label">Email Address</label>
                            <input type="email" id="login_email" name="login_email" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="login_password" class="form-label">Password</label>
                            <input type="password" id="login_password" name="login_password" class="form-input" required>
                        </div>
                        
                        <button type="submit" name="login_submit" class="btn-auth btn-login">Sign In to Your Account</button>
                    </form>
                    
                    <div class="auth-links">
                        <p>Don't have an account? <a href="#" class="switch-tab" data-tab="register">Register here</a></p>
                    </div>
                </div>
                
                <!-- Registration Form -->
                <div class="auth-form" id="register-form">
                    <?php 
                    if ($regError) echo displayError($regError);
                    if ($regSuccess) {
                        echo displaySuccess($regSuccess);
                        echo '<div class="auth-links"><p><a href="#" class="switch-tab" data-tab="login">Login here</a></p></div>';
                    }
                    ?>
                    
                    <?php if (!$regSuccess): ?>
                    <form method="POST" action="" id="registerForm">
                        <input type="hidden" name="register_csrf_token" value="<?php echo $regCsrfToken; ?>">
                        
                        <div class="form-group">
                            <label for="reg_full_name" class="form-label">Full Name</label>
                            <input type="text" id="reg_full_name" name="reg_full_name" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_email" class="form-label">Email Address</label>
                            <input type="email" id="reg_email" name="reg_email" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_phone" class="form-label">Phone Number</label>
                            <input type="tel" id="reg_phone" name="reg_phone" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_password" class="form-label">Password</label>
                            <input type="password" id="reg_password" name="reg_password" class="form-input" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="reg_confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" id="reg_confirm_password" name="reg_confirm_password" class="form-input" required>
                        </div>
                        
                        <button type="submit" name="register_submit" class="btn-auth btn-register">Create Your Account</button>
                    </form>
                    
                    <div class="auth-links">
                        <p>Already have an account? <a href="#" class="switch-tab" data-tab="login">Login here</a></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.auth-tab');
    const forms = document.querySelectorAll('.auth-form');
    const switchTabs = document.querySelectorAll('.switch-tab');
    
    // Tab switching functionality
    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and forms
            tabs.forEach(t => t.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));
            
            // Add active class to clicked tab and corresponding form
            this.classList.add('active');
            document.getElementById(`${tabName}-form`).classList.add('active');
        });
    });
    
    // Switch tab from links
    switchTabs.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const tabName = this.getAttribute('data-tab');
            
            // Remove active class from all tabs and forms
            tabs.forEach(t => t.classList.remove('active'));
            forms.forEach(f => f.classList.remove('active'));
            
            // Add active class to targeted tab and form
            document.querySelector(`.auth-tab[data-tab="${tabName}"]`).classList.add('active');
            document.getElementById(`${tabName}-form`).classList.add('active');
        });
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
