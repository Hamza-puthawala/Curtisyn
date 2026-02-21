<?php
$pageTitle = 'Products';
$currentPage = 'products';
require_once 'includes/header.php';
require_once 'config/database.php';
require_once 'includes/functions.php';

$database = new Database();
$db = $database->connect();

$products = [];
$categoryFilter = isset($_GET['category']) ? intval($_GET['category']) : 0;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$commission = 10;

if ($db) {
    $commission = getGlobalCommission($db);
    
    // Build query dynamically
    $whereClause = "p.status = 'enabled' AND p.stock > 0";
    $params = [];
    
    if ($categoryFilter > 0) {
        $whereClause .= " AND p.category_id = :category_id";
        $params[':category_id'] = $categoryFilter;
    }
    
    if (!empty($searchQuery)) {
        $whereClause .= " AND (p.name LIKE :search OR p.description LIKE :search)";
        $params[':search'] = '%' . $searchQuery . '%';
    }
    
    // Sort options
    $orderClause = "p.created_at DESC";
    switch ($sortBy) {
        case 'price_low':
            $orderClause = "p.price ASC";
            break;
        case 'price_high':
            $orderClause = "p.price DESC";
            break;
        case 'name':
            $orderClause = "p.name ASC";
            break;
    }
    
    $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $whereClause ORDER BY $orderClause";
    $stmt = $db->prepare($sql);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    $stmt->execute();
    $products = $stmt->fetchAll();
}

// Get unique categories only
$categories = [];
if ($db) {
    $stmt = $db->query("SELECT DISTINCT id, name FROM categories WHERE id IN (SELECT DISTINCT category_id FROM products WHERE status = 'enabled' AND stock > 0) ORDER BY name");
    $categories = $stmt->fetchAll();
}
?>

<style>
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
    
    .filter-section {
        background: white;
        border-radius: 15px;
        padding: 2rem;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .category-badge {
        background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 50px;
        font-size: 0.9rem;
        font-weight: 500;
    }
</style>

<section class="section py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="section-title text-center mb-5">Our Products</h1>
                
                <!-- Filter Section -->
                <div class="filter-section mb-5">
                    <form method="GET" action="products.php" class="row g-3 align-items-end">
                        <!-- Search by Name -->
                        <div class="col-md-4">
                            <label class="form-label fw-bold mb-2">Search Product</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="Enter product name..." class="form-control py-3">
                        </div>
                        
                        <!-- Filter by Category -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-2">Category</label>
                            <select name="category" class="form-select py-3">
                                <option value="0">All Categories</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" <?php echo $categoryFilter === intval($category['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Sort By -->
                        <div class="col-md-3">
                            <label class="form-label fw-bold mb-2">Sort By</label>
                            <select name="sort" class="form-select py-3">
                                <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                <option value="price_low" <?php echo $sortBy === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                                <option value="price_high" <?php echo $sortBy === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                                <option value="name" <?php echo $sortBy === 'name' ? 'selected' : ''; ?>>Name: A to Z</option>
                            </select>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="col-md-2 d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100 py-3">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                            <a href="products.php" class="btn btn-secondary py-3 px-3">
                                <i class="fas fa-times"></i>
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="row">
                    <?php if(count($products) == 0): ?>
                        <div class="col-12 text-center">
                            <div class="p-5">
                                <i class="fas fa-box-open fa-3x text-muted mb-3"></i>
                                <h3 class="text-muted">No products found</h3>
                                <p class="text-muted">Try adjusting your search criteria or browse our categories</p>
                                <a href="products.php" class="btn btn-primary px-4 py-2">Reset Filters</a>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($products as $product): 
                            $finalPrice = calculateFinalPrice($product['price'], $commission);
                        ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 mb-4">
                            <div class="product-card h-100">
                                <div class="product-image">
                                    <?php if ($product['image']): ?>
                                        <img src="uploads/products/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="max-width: 90%; max-height: 90%; object-fit: contain; border-radius: 8px; z-index: 2; position: relative;">
                                    <?php else: ?>
                                        <div class="text-white text-center p-4" style="z-index: 2; position: relative;">
                                            <i class="fas fa-image fa-2x mb-2"></i>
                                            <p>No Image</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="product-info">
                                    <span class="category-badge">
                                        <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                    </span>
                                    <h3 class="product-title">
                                        <?php echo htmlspecialchars($product['name']); ?>
                                    </h3>
                                    <p class="product-price">₹<?php echo number_format($finalPrice, 2); ?></p>
                                    <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="btn btn-primary w-100">View Details</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

