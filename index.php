<?php
$pageTitle = 'Home';
$currentPage = 'home';

// Requiring header starts the HTML structure and session
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

$products = [];
$commission = 10;

if ($db) {
    $commission = getGlobalCommission($db);
    $stmt = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status = 'enabled' AND p.stock > 0 ORDER BY p.created_at DESC LIMIT 4");
    $products = $stmt->fetchAll();
}
?>

<style>
    .hero-section {
        background: url('https://images.unsplash.com/photo-1615874959474-d609969a20ed?q=80&w=1600') center/cover no-repeat;
        position: relative;
        margin-bottom: 2rem;
    }
    
    .hero-overlay {
        background: rgba(0,0,0,0.55);
        border-radius: 10px;
        padding: 3rem 0;
    }
    
    .category-card {
        transition: all 0.3s ease;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    }
    
    .category-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.2);
    }
    
    .btn-custom {
        border-radius: 50px;
        padding: 12px 30px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-custom:hover {
        transform: translateY(-3px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    
    .service-icon {
        transition: all 0.3s ease;
    }
    
    .service-icon:hover {
        transform: scale(1.1);
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
    
    .why-choose-us {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 15px;
        padding: 2rem;
    }
</style>

<!-- ================= HERO SECTION ================= -->
<section class="hero-section">
    <div class="container">
        <div class="hero-overlay text-center">
            <h1 class="display-4 fw-bold text-white mb-3">Elegant Curtains for Every Home</h1>
            <p class="lead text-white mb-4">Transform your living spaces with our premium collection</p>
            <a href="products.php" class="btn btn-light btn-custom btn-lg px-4 me-2">Shop Now</a>
            <a href="contact.php" class="btn btn-outline-light btn-custom btn-lg px-4">Contact Us</a>
        </div>
    </div>
</section>

<!-- ================= FEATURED PRODUCTS ================= -->
<section class="section" id="products">
    <div class="container">
        <h2 class="section-title text-center">Featured Products</h2>
        <div class="products-grid">
            <?php foreach ($products as $product): 
                $finalPrice = calculateFinalPrice($product['price'], $commission);
            ?>
            <div class="product-card">
                <div class="product-image">
                    <?php if ($product['image']): ?>
                        <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 8px; z-index: 2; position: relative;">
                    <?php else: ?>
                        <div class="text-white text-center p-4" style="z-index: 2; position: relative;">
                            <i class="fas fa-image fa-3x mb-3"></i>
                            <p>No Image</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="product-price">₹<?php echo number_format($finalPrice, 2); ?></p>
                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary w-100">View Details</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="products.php" class="btn btn-primary btn-custom px-4 py-2">View All Products</a>
        </div>
    </div>
</section>

<!-- ================= CATEGORIES ================= -->
<section class="section py-5">
    <div class="container">
        <h2 class="section-title text-center mb-5">Our Categories</h2>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="category-card h-100">
                    <div class="position-relative">
                        <div style="height: 250px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-window-cover text-white" style="font-size: 4rem;"></i>
                        </div>
                        <div class="card-body text-center p-4">
                            <h5 class="fw-bold mb-3">Curtains</h5>
                            <p class="text-muted">Elegant fabric curtains for modern interiors</p>
                            <a href="products.php?category=curtains" class="btn btn-outline-dark btn-custom">View Collection</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="category-card h-100">
                    <div class="position-relative">
                        <div style="height: 250px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-window-shade text-white" style="font-size: 4rem;"></i>
                        </div>
                        <div class="card-body text-center p-4">
                            <h5 class="fw-bold mb-3">Roller Curtains</h5>
                            <p class="text-muted">Smart sliding blinds for office & home</p>
                            <a href="products.php?category=roller" class="btn btn-outline-dark btn-custom">View Collection</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="category-card h-100">
                    <div class="position-relative">
                        <div style="height: 250px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-bed text-white" style="font-size: 4rem;"></i>
                        </div>
                        <div class="card-body text-center p-4">
                            <h5 class="fw-bold mb-3">Mosquito Nets</h5>
                            <p class="text-muted">Protection with fresh airflow & comfort</p>
                            <a href="products.php?category=nets" class="btn btn-outline-dark btn-custom">View Collection</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ================= SERVICES ================= -->
<section class="py-5" style="background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);">
    <div class="container text-center">
        <h2 class="fw-bold mb-5 section-title">Our Services</h2>

        <div class="row g-4">
            <div class="col-md-3">
                <div class="service-icon">
                    <i class="fas fa-ruler-combined fs-1 text-dark mb-3"></i>
                </div>
                <h5 class="mt-3 fw-bold">Home Measurement</h5>
                <p class="text-muted">Accurate size taken by expert</p>
            </div>

            <div class="col-md-3">
                <div class="service-icon">
                    <i class="fas fa-tools fs-1 text-dark mb-3"></i>
                </div>
                <h5 class="mt-3 fw-bold">Installation</h5>
                <p class="text-muted">Professional fitting service</p>
            </div>

            <div class="col-md-3">
                <div class="service-icon">
                    <i class="fas fa-sync-alt fs-1 text-dark mb-3"></i>
                </div>
                <h5 class="mt-3 fw-bold">Repair</h5>
                <p class="text-muted">Quick repair & replacement</p>
            </div>

            <div class="col-md-3">
                <div class="service-icon">
                    <i class="fas fa-headset fs-1 text-dark mb-3"></i>
                </div>
                <h5 class="mt-3 fw-bold">Support</h5>
                <p class="text-muted">Customer help anytime</p>
            </div>
        </div>
    </div>
</section>

<!-- ================= WHY CHOOSE US ================= -->
<section class="py-5">
    <div class="container">
        <div class="why-choose-us text-center">
            <h2 class="fw-bold mb-4 section-title">Why Choose Curtisyn?</h2>
            <p class="lead text-muted">Quality materials • Affordable price • Expert servicemen • Direct supplier connection</p>
            
            <div class="row mt-5">
                <div class="col-md-3 mb-4">
                    <div class="p-4 rounded-3" style="background: #f8f9fa; border-left: 4px solid #e74c3c;">
                        <i class="fas fa-star fs-2 text-warning mb-3"></i>
                        <h5>Premium Quality</h5>
                        <p class="text-muted">Best materials for durability</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="p-4 rounded-3" style="background: #f8f9fa; border-left: 4px solid #3498db;">
                        <i class="fas fa-tag fs-2 text-primary mb-3"></i>
                        <h5>Best Price</h5>
                        <p class="text-muted">Competitive rates guaranteed</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="p-4 rounded-3" style="background: #f8f9fa; border-left: 4px solid #27ae60;">
                        <i class="fas fa-truck fs-2 text-success mb-3"></i>
                        <h5>Fast Delivery</h5>
                        <p class="text-muted">Quick installation service</p>
                    </div>
                </div>
                
                <div class="col-md-3 mb-4">
                    <div class="p-4 rounded-3" style="background: #f8f9fa; border-left: 4px solid #f39c12;">
                        <i class="fas fa-shield-alt fs-2 text-warning mb-3"></i>
                        <h5>Warranty</h5>
                        <p class="text-muted">Long-term protection</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>