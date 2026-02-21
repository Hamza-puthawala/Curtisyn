<?php
$pageTitle = 'Contact Us';
$currentPage = 'contact';
require_once 'includes/header.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $error = 'All fields required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email.';
        } else {
            $success = 'Thank you for your message. We will get back to you soon.';
        }
    }
}

$csrfToken = generateCsrfToken();
?>

<style>
    .contact-form {
        background: white;
        border-radius: 15px;
        padding: 30px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        max-width: 600px;
        margin: 0 auto;
    }
    
    .section-title {
        position: relative;
        display: inline-block;
        margin-bottom: 2rem;
    }
    
    .section-title:after {
        content: '';
        position: absolute;
        bottom: -10px;
        left: 50%;
        transform: translateX(-50%);
        width: 60px;
        height: 4px;
        background: var(--gradient-secondary);
        border-radius: 2px;
    }
    
    .contact-info {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 2rem;
        margin-bottom: 2rem;
    }
</style>

<section class="section py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <h1 class="section-title text-center mb-5">Contact Us</h1>
                
                <div class="contact-info text-center mb-4">
                    <div class="row">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <i class="fas fa-map-marker-alt fs-2 text-primary mb-2"></i>
                            <h5>Address</h5>
                            <p class="mb-0">123 Curtain Street<br>City, State 12345</p>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <i class="fas fa-phone-alt fs-2 text-primary mb-2"></i>
                            <h5>Phone</h5>
                            <p class="mb-0">+91 9427822372<br>Mon-Fri, 9am-6pm</p>
                        </div>
                        <div class="col-md-4">
                            <i class="fas fa-envelope fs-2 text-primary mb-2"></i>
                            <h5>Email</h5>
                            <p class="mb-0">info@curtainsanddrapes.com<br>24/7 Support</p>
                        </div>
                    </div>
                </div>
                
                <div class="contact-form">
                    <?php 
                    if ($error) echo '<div class="alert alert-danger">' . htmlspecialchars($error) . '</div>';
                    if ($success) echo '<div class="alert alert-success">' . htmlspecialchars($success) . '</div>';
                    ?>
                    
                    <form method="POST" action="" onsubmit="return validateContactForm()">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <div class="form-group mb-4">
                            <label for="name" class="form-label fw-bold">Full Name</label>
                            <input type="text" id="name" name="name" class="form-control py-3" placeholder="Enter your name" required>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="email" class="form-label fw-bold">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control py-3" placeholder="Enter your email" required>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="subject" class="form-label fw-bold">Subject</label>
                            <input type="text" id="subject" name="subject" class="form-control py-3" placeholder="Enter subject" required>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="message" class="form-label fw-bold">Message</label>
                            <textarea id="message" name="message" class="form-control" rows="5" placeholder="Enter your message" required style="padding: 15px;"></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 py-3 fw-bold">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

