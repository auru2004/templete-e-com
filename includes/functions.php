<?php
// FUNGSI-FUNGSI UMUM

// Format Rupiah
function formatRupiah($nilai) {
    return CURRENCY_SYMBOL . ' ' . number_format($nilai, 0, ',', '.');
}

// Format Tanggal Indonesia
function formatTanggal($tanggal) {
    $bulan = array(
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    );
    
    $split = explode('-', $tanggal);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// Sanitize Input
function sanitize($data) {
    global $db;
    return $db->escape(trim($data));
}

// Redirect
function redirect($page) {
    header("Location: " . SITE_URL . $page);
    exit;
}

// Session Check
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// Get All Products
function getAllProducts() {
    global $db;
    $sql = "SELECT * FROM products WHERE status = 'active' ORDER BY id DESC";
    $result = $db->query($sql);
    return $db->fetchAll($result);
}

// Get Product By ID
function getProductById($id) {
    global $db;
    $sql = "SELECT * FROM products WHERE id = " . (int)$id;
    $result = $db->query($sql);
    return $db->fetch($result);
}

// Get Products By Category
function getProductsByCategory($category) {
    global $db;
    $sql = "SELECT * FROM products WHERE category = '" . sanitize($category) . "' AND status = 'active'";
    $result = $db->query($sql);
    return $db->fetchAll($result);
}

// Add To Cart (Session Based)
function addToCart($product_id, $quantity) {
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }
    
    return true;
}

// Remove From Cart
function removeFromCart($product_id) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
        return true;
    }
    return false;
}

// Get Cart Total
function getCartTotal() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    
    global $db;
    $total = 0;
    
    foreach ($_SESSION['cart'] as $product_id => $quantity) {
        $product = getProductById($product_id);
        $total += $product['price'] * $quantity;
    }
    
    return $total;
}

// Get Cart Items Count
function getCartCount() {
    if (!isset($_SESSION['cart'])) {
        return 0;
    }
    return count($_SESSION['cart']);
}

// API Response
function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
    exit;
}
?>
