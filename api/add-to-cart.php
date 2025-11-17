<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Anda harus login terlebih dahulu');
    }

    if ($_SESSION['user_role'] !== 'buyer') {
        throw new Exception('Hanya pembeli yang bisa menambah ke keranjang');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $product_id = intval($data['product_id'] ?? 0);
    $quantity = intval($data['quantity'] ?? 1);
    $buyer_id = $_SESSION['user_id'];

    if ($product_id <= 0 || $quantity <= 0) {
        throw new Exception('Data tidak valid');
    }

    $db = Database::getInstance();
    $connection = $db->connect();

    $stmt = $connection->prepare("SELECT id, stock, price FROM seller_products WHERE id = ? AND status = 'active'");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $connection->error);
    }
    
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Produk tidak ditemukan atau tidak tersedia');
    }

    $product = $result->fetch_assoc();

    if ($product['stock'] < $quantity) {
        throw new Exception('Stok tidak mencukupi');
    }

    $stmt = $connection->prepare("
        INSERT INTO buyer_cart (buyer_id, seller_product_id, quantity) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE quantity = quantity + ?
    ");
    
    if (!$stmt) {
        throw new Exception('Database error: ' . $connection->error);
    }

    $stmt->bind_param("iiii", $buyer_id, $product_id, $quantity, $quantity);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true, 
            'message' => 'Produk ditambahkan ke keranjang',
            'quantity' => $quantity
        ]);
    } else {
        throw new Exception('Gagal menambahkan ke keranjang: ' . $stmt->error);
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
