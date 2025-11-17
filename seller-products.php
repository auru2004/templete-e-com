<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance();
$connection = $db->connect();
$auth = new Auth($connection);

if (!$auth->isSeller()) {
    header('Location: index.php');
    exit;
}

$seller_id = $_SESSION['user_id'];

// Get seller products
$stmt = $connection->prepare("SELECT * FROM seller_products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produk Saya - Seller</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
        }
        .product-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        .product-image {
            width: 100%;
            height: 200px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .product-info {
            padding: 15px;
        }
        .product-name {
            font-weight: 600;
            color: #006994;
            margin-bottom: 8px;
            min-height: 40px;
        }
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #00a8d8;
            margin-bottom: 8px;
        }
        .product-stock {
            font-size: 13px;
            color: #666;
            margin-bottom: 10px;
        }
        .product-actions {
            display: flex;
            gap: 5px;
        }
        .btn-action {
            flex: 1;
            padding: 8px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-edit {
            background: #00a8d8;
            color: white;
        }
        .btn-delete {
            background: #ff6b35;
            color: white;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container-nav">
            <div class="logo-section">
                <i class="fas fa-water"></i>
                <h1>Lumbung Digital</h1>
            </div>
            <div class="nav-buttons">
                <a href="seller-dashboard.php" class="nav-btn"><i class="fas fa-home"></i> Dashboard</a>
                <a href="api/logout.php" class="nav-btn nav-btn-login"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="page-container">
        <h1 style="color: #006994; margin-bottom: 30px;">Produk Saya</h1>
        
        <?php if (count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image']): ?>
                                <img src="<?php echo $product['image']; ?>" alt="<?php echo $product['name']; ?>">
                            <?php else: ?>
                                <i class="fas fa-image" style="font-size: 48px; color: #999;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                            <div class="product-price">Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></div>
                            <div class="product-stock">Stok: <?php echo $product['stock']; ?> unit</div>
                            <div style="font-size: 12px; color: #00a8d8; margin-bottom: 10px;">
                                <i class="fas fa-eye"></i> <?php echo $product['views']; ?> views
                            </div>
                            <div class="product-actions">
                                <button class="btn-action btn-edit">Edit</button>
                                <form style="flex: 1;" method="POST" action="seller-dashboard.php" onsubmit="return confirm('Hapus produk?')">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <button type="submit" name="delete_product" class="btn-action btn-delete" style="width: 100%;">Hapus</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 80px 20px; background: white; border-radius: 15px;">
                <i class="fas fa-box-open" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                <h2 style="color: #999; margin-bottom: 20px;">Belum Ada Produk</h2>
                <p style="color: #ccc; margin-bottom: 30px;">Mulai jual produk Anda sekarang!</p>
                <a href="seller-dashboard.php" class="nav-btn" style="display: inline-block; background: #00a8d8; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none;">Tambah Produk</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
