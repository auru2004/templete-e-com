<?php
session_start();
header('Content-Type: application/json');

try {
    require_once '../includes/config.php';
    require_once '../includes/database.php';
    require_once '../includes/auth.php';

    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'Anda harus login']);
        exit;
    }

    $db = Database::getInstance();
    $connection = $db->connect();
    
    if (!$connection) {
        echo json_encode(['success' => false, 'message' => 'Koneksi database gagal']);
        exit;
    }
    
    $auth = new Auth($connection);

    if (!$auth->isSeller()) {
        echo json_encode(['success' => false, 'message' => 'Hanya penjual yang bisa menambah produk']);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $stock = intval($_POST['stock'] ?? 0);
    $seller_id = $_SESSION['user_id'];
    $image_path = null;

    // Validate inputs
    if (empty($name) || $price <= 0 || $stock < 0) {
        echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
        exit;
    }

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $file = $_FILES['image'];
        $allowed = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($file['type'], $allowed)) {
            echo json_encode(['success' => false, 'message' => 'Format gambar tidak didukung']);
            exit;
        }
        
        if ($file['size'] > 2000000) {
            echo json_encode(['success' => false, 'message' => 'Ukuran gambar maksimal 2MB']);
            exit;
        }
        
        $uploadDir = '../uploads';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $filename = 'product_' . $seller_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $image_path = 'uploads/' . $filename;
        $fullPath = $uploadDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            echo json_encode(['success' => false, 'message' => 'Gagal upload gambar']);
            exit;
        }
    }

    // Insert product
    $stmt = $connection->prepare("INSERT INTO seller_products (seller_id, name, description, category, price, stock, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
    
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $connection->error]);
        exit;
    }
    
    $stmt->bind_param("isssids", $seller_id, $name, $description, $category, $price, $stock, $image_path);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Produk berhasil ditambahkan', 'product_id' => $stmt->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menambahkan produk: ' . $stmt->error]);
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>
