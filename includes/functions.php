<?php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCsrfToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return $_SESSION['user_role'] ?? null;
}

function hasRole($role) {
    return getUserRole() === $role;
}

function redirect($url) {
    header('Location: ' . $url);
    exit();
}

function displayError($message) {
    return '<div class="alert alert-error">' . htmlspecialchars($message) . '</div>';
}

function displaySuccess($message) {
    return '<div class="alert alert-success">' . htmlspecialchars($message) . '</div>';
}

function uploadFile($file, $directory) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 5 * 1024 * 1024;
    
    if (!in_array($file['type'], $allowedTypes)) {
        return ['error' => 'Invalid file type. Only JPG, PNG, GIF, WebP allowed.'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['error' => 'File too large. Max 5MB allowed.'];
    }
    
    $filename = uniqid() . '_' . basename($file['name']);
    $targetPath = $directory . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['error' => 'Failed to upload file.'];
}

function getGlobalCommission($db) {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'global_commission'");
    $stmt->execute();
    $result = $stmt->fetch();
    return $result ? (float)$result['setting_value'] : 10;
}

function calculateFinalPrice($basePrice, $commission = null) {
    if ($commission === null) {
        $database = new Database();
        $db = $database->connect();
        $commission = getGlobalCommission($db);
    }
    return $basePrice + ($basePrice * $commission / 100);
}

function generateOrderId() {
    return 'ORD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function syncSupplierProductToInventory($db, $orderId) {
    $stmt = $db->prepare("
        SELECT so.*, sp.name, sp.description, sp.category_id, sp.image, sp.dimensions, sp.price_per_unit
        FROM supplier_orders so
        JOIN supplier_products sp ON so.supplier_product_id = sp.id
        WHERE so.id = :order_id AND so.is_synced = 0
        AND so.status IN ('accepted', 'partially_accepted')
    ");
    $stmt->bindParam(':order_id', $orderId);
    $stmt->execute();
    $order = $stmt->fetch();
    
    if (!$order) return false;
    
    $approvedQty = $order['approved_quantity'] ?? $order['requested_quantity'];
    
    $checkStmt = $db->prepare("SELECT id, stock FROM products WHERE supplier_product_id = :supplier_product_id");
    $checkStmt->bindParam(':supplier_product_id', $order['supplier_product_id']);
    $checkStmt->execute();
    $existingProduct = $checkStmt->fetch();
    
    if ($existingProduct) {
        $newStock = $existingProduct['stock'] + $approvedQty;
        $updateStmt = $db->prepare("UPDATE products SET stock = :stock WHERE id = :id");
        $updateStmt->bindParam(':stock', $newStock);
        $updateStmt->bindParam(':id', $existingProduct['id']);
        $updateStmt->execute();
    } else {
        $insertStmt = $db->prepare("
            INSERT INTO products (name, category_id, description, price, stock, image, status, supplier_product_id, source_type, created_by)
            VALUES (:name, :category_id, :description, :price, :stock, :image, 'enabled', :supplier_product_id, 'supplier', 1)
        ");
        $insertStmt->bindParam(':name', $order['name']);
        $insertStmt->bindParam(':category_id', $order['category_id']);
        $insertStmt->bindParam(':description', $order['description']);
        $insertStmt->bindParam(':price', $order['price_per_unit']);
        $insertStmt->bindParam(':stock', $approvedQty);
        $insertStmt->bindParam(':image', $order['image']);
        $insertStmt->bindParam(':supplier_product_id', $order['supplier_product_id']);
        $insertStmt->execute();
    }
    
    $syncStmt = $db->prepare("UPDATE supplier_orders SET is_synced = 1 WHERE id = :order_id");
    $syncStmt->bindParam(':order_id', $orderId);
    $syncStmt->execute();
    
    return true;
}
