<?php
/**
 * MultiMart - Add to Cart Handler
 */

require_once __DIR__ . '/../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /search.php');
    exit();
}

$product_id = intval($_POST['product_id'] ?? 0);
$quantity = intval($_POST['quantity'] ?? 1);

if (!$product_id || $quantity < 1) {
    die('Invalid request');
}

// Check if user is logged in
if (!is_logged_in()) {
    $_SESSION['redirect_after_login'] = $_SERVER['HTTP_REFERER'];
    header('Location: /auth/login.php');
    exit();
}

$user = get_current_user();

// Get product
$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$product_id]);
$product = $stmt->fetch();

if (!$product || $product['stock_quantity'] < $quantity) {
    die('Product not available');
}

// Check if already in cart
$stmt = $pdo->prepare("SELECT id FROM cart_items WHERE customer_id = ? AND product_id = ?");
$stmt->execute([$user['id'], $product_id]);
$existing = $stmt->fetch();

if ($existing) {
    // Update quantity
    $stmt = $pdo->prepare("UPDATE cart_items SET quantity = quantity + ? WHERE customer_id = ? AND product_id = ?");
    $stmt->execute([$quantity, $user['id'], $product_id]);
} else {
    // Add to cart
    $stmt = $pdo->prepare("INSERT INTO cart_items (customer_id, product_id, quantity, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$user['id'], $product_id, $quantity]);
}

// Redirect back or to cart
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/customer/cart.php'));
exit();
