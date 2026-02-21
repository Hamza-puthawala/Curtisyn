<?php
// Process form BEFORE including layout.php to allow header() redirects
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$database = new Database();
$db = $database->connect();

$product = [
    'id' => 0,
    'name' => '',
    'category_id' => '',
    'description' => '',
    'price' => '',
    'stock' => '',
    'status' => 'enabled'
];

$error = '';
$success = '';

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);

    if ($db) {
        $stmt = $db->prepare("SELECT * FROM products WHERE id = :id");
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $product = $stmt->fetch();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request.';
    } else {
        $name = sanitizeInput($_POST['name'] ?? '');
        $categoryId = intval($_POST['category_id'] ?? 0);
        $description = sanitizeInput($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $status = $_POST['status'] ?? 'enabled';

        if (empty($name) || $price <= 0) {
            $error = 'Name and valid price required.';
        } else {
            $imageName = $product['image'] ?? '';

            if (!empty($_FILES['image']['name'])) {
                $upload = uploadFile($_FILES['image'], __DIR__ . '/../uploads/products/');
                if (isset($upload['error'])) {
                    $error = $upload['error'];
                } else {
                    $imageName = $upload['filename'];
                }
            }

            if (empty($error)) {
                if ($product['id'] > 0) {
                    $stmt = $db->prepare("UPDATE products SET name = :name, category_id = :category_id, description = :description, price = :price, stock = :stock, status = :status, image = :image WHERE id = :id");
                    $stmt->bindParam(':id', $product['id']);
                } else {
                    $stmt = $db->prepare("INSERT INTO products (name, category_id, description, price, stock, status, image, created_by) VALUES (:name, :category_id, :description, :price, :stock, :status, :image, :created_by)");
                    $createdBy = $_SESSION['user_id'];
                    $stmt->bindParam(':created_by', $createdBy);
                }

                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':category_id', $categoryId);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':stock', $stock);
                $stmt->bindParam(':status', $status);
                $stmt->bindParam(':image', $imageName);

                if ($stmt->execute()) {
                    $success = $product['id'] > 0 ? 'Product updated.' : 'Product added.';
                    if ($product['id'] === 0) {
                        // Redirect BEFORE any HTML output
                        header('Location: ' . BASE_URL . 'admin/products.php');
                        exit();
                    }
                } else {
                    $error = 'Failed to save product.';
                }
            }
        }
    }
}

// Set page variables and include layout AFTER processing (so redirect above works)
$pageTitle = ($product['id'] > 0) ? 'Edit Product' : 'Add Product';
$activePage = ($product['id'] > 0) ? 'products' : 'add-product';
require_once 'layout.php';

$categories = [];
if ($db) {
    $stmt = $db->query("SELECT * FROM categories ORDER BY name");
    $categories = $stmt->fetchAll();
}

$csrfToken = generateCsrfToken();
?>

<div class="content-card" style="max-width: 700px;">
    <div class="card-header">
        <h2 class="card-title"><?php echo $pageTitle; ?></h2>
    </div>
    <div class="card-body">
        <?php 
        if ($error) echo displayError($error);
        if ($success) echo displaySuccess($success);
        ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
            
            <div class="form-group">
                <label class="form-label">Product Name</label>
                <input type="text" name="name" class="form-input" value="<?php echo htmlspecialchars($product['name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-input">
                    <option value="">Select Category</option>
                    <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo $product['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="4"><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            
            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                <div class="form-group">
                    <label class="form-label">Price (₹)</label>
                    <input type="number" name="price" class="form-input" step="0.01" min="0" value="<?php echo $product['price']; ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Stock</label>
                    <input type="number" name="stock" class="form-input" min="0" value="<?php echo $product['stock']; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" class="form-input">
                    <option value="enabled" <?php echo $product['status'] === 'enabled' ? 'selected' : ''; ?>>Enabled</option>
                    <option value="disabled" <?php echo $product['status'] === 'disabled' ? 'selected' : ''; ?>>Disabled</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Image</label>
                <input type="file" name="image" class="form-input" accept="image/*">
            </div>
            
            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save Product</button>
                <a href="<?php echo BASE_URL; ?>admin/products.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'layout-footer.php'; ?>
