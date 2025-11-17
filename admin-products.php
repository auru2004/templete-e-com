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

if (!$auth->isAdmin()) {
    header('Location: index.php');
    exit;
}

$admin_id = $_SESSION['user_id'];

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    $reason = trim($_POST['reason'] ?? 'Melanggar kebijakan platform');
    
    // Get seller info
    $stmt = $connection->prepare("SELECT seller_id FROM seller_products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $seller_id = $result['seller_id'];
        
        // Add warning to seller
        $stmt = $connection->prepare("INSERT INTO account_warnings (user_id, admin_id, reason, severity, details) VALUES (?, ?, ?, 'warning', ?)");
        $stmt->bind_param("iiss", $seller_id, $admin_id, $reason, $reason);
        $stmt->execute();
    }
    
    // Delete product
    $stmt = $connection->prepare("DELETE FROM seller_products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    // Log action
    $stmt = $connection->prepare("INSERT INTO admin_logs (admin_id, action, target_product_id, details) VALUES (?, 'delete_product', ?, ?)");
    $stmt->bind_param("iis", $admin_id, $product_id, $reason);
    $stmt->execute();
    
    $_SESSION['message'] = 'Produk berhasil dihapus dan penjual diberi peringatan';
}

// Get all products
$stmt = $connection->prepare("
    SELECT sp.*, u.name as seller_name
    FROM seller_products sp
    JOIN users u ON sp.seller_id = u.id
    ORDER BY sp.created_at DESC
");
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Produk - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        .admin-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        .admin-sidebar {
            background: linear-gradient(135deg, #ff6b35, #c92a2a);
            color: white;
            padding: 30px 20px;
            position: fixed;
            width: 250px;
            height: 100vh;
            overflow-y: auto;
        }
        .admin-sidebar h3 {
            margin-bottom: 30px;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .sidebar-menu {
            list-style: none;
        }
        .sidebar-menu li {
            margin-bottom: 15px;
        }
        .sidebar-menu a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateX(5px);
        }
        .admin-main {
            margin-left: 250px;
            padding: 30px;
        }
        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .section h1 {
            color: #c92a2a;
            margin-bottom: 20px;
        }
        .products-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .products-table thead th {
            background: #f5f7fa;
            padding: 12px;
            text-align: left;
            color: #c92a2a;
            font-weight: 600;
            border-bottom: 2px solid #ff6b35;
        }
        .products-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .products-table tbody tr:hover {
            background: #f9f9f9;
        }
        .product-image {
            width: 40px;
            height: 40px;
            border-radius: 6px;
            object-fit: cover;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-active {
            background: #efe;
            color: #3c3;
        }
        .status-inactive {
            background: #fee;
            color: #c33;
        }
        .btn-small {
            padding: 6px 12px;
            margin: 0 3px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.3s;
        }
        .btn-delete {
            background: #c92a2a;
            color: white;
        }
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        @media (max-width: 768px) {
            .admin-container {
                grid-template-columns: 1fr;
            }
            .admin-sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .admin-main {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <!-- SIDEBAR -->
        <aside class="admin-sidebar">
            <h3><i class="fas fa-shield-alt"></i> Admin Panel</h3>
            <ul class="sidebar-menu">
                <li><a href="admin-dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="admin-users.php"><i class="fas fa-users"></i> Pengguna</a></li>
                <li><a href="admin-products.php" class="active"><i class="fas fa-box"></i> Produk</a></li>
                <li><a href="admin-warnings.php"><i class="fas fa-exclamation-circle"></i> Peringatan</a></li>
                <li><a href="admin-logs.php"><i class="fas fa-history"></i> Log Aktivitas</a></li>
                <li><a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="admin-main">
            <div class="section">
                <h1>Manajemen Produk</h1>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert">
                        <i class="fas fa-check-circle"></i> <?php echo $_SESSION['message']; ?>
                    </div>
                    <?php unset($_SESSION['message']); ?>
                <?php endif; ?>
                
                <?php if (count($products) > 0): ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Penjual</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo $product['image']; ?>" class="product-image">
                                        <?php else: ?>
                                            <div class="product-image" style="background: #e0e0e0;"></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($product['name'], 0, 30)); ?></td>
                                    <td><?php echo htmlspecialchars($product['seller_name']); ?></td>
                                    <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $product['stock']; ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $product['status']; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form style="display: inline;" method="POST" onsubmit="return confirm('Hapus produk ini?')">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="reason" value="Pelanggaran kebijakan">
                                            <button type="submit" name="delete_product" class="btn-small btn-delete">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">Tidak ada produk</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
