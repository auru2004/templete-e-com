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

$user_id = $_SESSION['user_id'];

// Handle product deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    
    // Verify product belongs to seller
    $stmt = $connection->prepare("SELECT id FROM seller_products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $product_id, $user_id);
    $stmt->execute();
    
    if ($stmt->get_result()->num_rows > 0) {
        $stmt = $connection->prepare("DELETE FROM seller_products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $_SESSION['message'] = 'Produk berhasil dihapus';
    }
}

// Get seller products
$stmt = $connection->prepare("SELECT * FROM seller_products WHERE seller_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get seller transactions
$stmt = $connection->prepare("
    SELECT st.*, sp.name as product_name, u.name as buyer_name, u.whatsapp
    FROM seller_transactions st
    JOIN seller_products sp ON st.product_id = sp.id
    JOIN users u ON st.buyer_id = u.id
    WHERE st.seller_id = ?
    ORDER BY st.created_at DESC
    LIMIT 20
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Penjual - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f5f7fa; }
        
        .dashboard-container {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        
        .sidebar {
            background: linear-gradient(135deg, #006994, #00a8d8);
            color: white;
            padding: 30px 20px;
            position: fixed;
            width: 250px;
            height: 100vh;
            overflow-y: auto;
        }
        
        .sidebar h3 {
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
        
        .main-content {
            margin-left: 250px;
            padding: 30px;
        }
        
        .header-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .header-top h1 {
            color: #006994;
            font-size: 24px;
        }
        
        .btn-add-product {
            background: linear-gradient(135deg, #006994, #00a8d8);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .btn-add-product:hover {
            transform: translateY(-2px);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #00a8d8;
        }
        
        .stat-card h4 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #006994;
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .section h2 {
            color: #006994;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #00a8d8;
            padding-bottom: 10px;
        }
        
        .products-table,
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .products-table thead th,
        .transactions-table thead th {
            background: #f5f7fa;
            padding: 12px;
            text-align: left;
            color: #006994;
            font-weight: 600;
            border-bottom: 2px solid #00a8d8;
        }
        
        .products-table tbody td,
        .transactions-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .products-table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
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
            font-size: 12px;
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
        
        .btn-small:hover {
            transform: scale(1.05);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            color: #006994;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00a8d8;
        }
        
        .btn-save {
            background: linear-gradient(135deg, #006994, #00a8d8);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        @media (max-width: 768px) {
            .dashboard-container {
                grid-template-columns: 1fr;
            }
            .sidebar {
                width: 100%;
                height: auto;
                position: relative;
            }
            .main-content {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <h3><i class="fas fa-store"></i> Dashboard Penjual</h3>
            <ul class="sidebar-menu">
                <li><a href="seller-dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="seller-products.php"><i class="fas fa-box"></i> Produk Saya</a></li>
                <li><a href="seller-transactions.php"><i class="fas fa-exchange-alt"></i> Transaksi</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profil</a></li>
                <li><a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="header-top">
                <div>
                    <h1>Dashboard Penjual</h1>
                    <p style="color: #666;">Selamat datang, <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                </div>
                <button class="btn-add-product" onclick="showAddProductModal()">
                    <i class="fas fa-plus"></i> Tambah Produk
                </button>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <!-- STATISTICS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Produk</h4>
                    <div class="number"><?php echo count($products); ?></div>
                </div>
                <div class="stat-card">
                    <h4>Transaksi Bulan Ini</h4>
                    <div class="number"><?php echo count(array_filter($transactions, function($t) {
                        return strtotime($t['created_at']) > strtotime('-30 days');
                    })); ?></div>
                </div>
                <div class="stat-card">
                    <h4>Total Penjualan</h4>
                    <div class="number">Rp <?php 
                        $total = array_reduce($transactions, function($carry, $t) {
                            return $carry + $t['total_price'];
                        }, 0);
                        echo number_format($total, 0, ',', '.');
                    ?></div>
                </div>
            </div>
            
            <!-- PRODUK TERBARU -->
            <div class="section">
                <h2>Produk Terbaru</h2>
                <?php if (count($products) > 0): ?>
                    <table class="products-table">
                        <thead>
                            <tr>
                                <th>Gambar</th>
                                <th>Nama Produk</th>
                                <th>Harga</th>
                                <th>Stok</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($products, 0, 5) as $product): ?>
                                <tr>
                                    <td>
                                        <?php if ($product['image']): ?>
                                            <img src="<?php echo $product['image']; ?>" class="product-image" alt="<?php echo $product['name']; ?>">
                                        <?php else: ?>
                                            <div class="product-image" style="background: #e0e0e0; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-image" style="color: #999;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                                    <td>Rp <?php echo number_format($product['price'], 0, ',', '.'); ?></td>
                                    <td><?php echo $product['stock']; ?> unit</td>
                                    <td>
                                        <span class="status-badge status-<?php echo $product['status']; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-small btn-edit" onclick="editProduct(<?php echo $product['id']; ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form style="display: inline;" method="POST">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <button type="submit" name="delete_product" class="btn-small btn-delete" onclick="return confirm('Apakah Anda yakin?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">
                        <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 20px;"></i><br>
                        Anda belum menambahkan produk. <a href="seller-products.php">Tambah produk sekarang</a>
                    </p>
                <?php endif; ?>
            </div>
            
            <!-- TRANSAKSI TERBARU -->
            <div class="section">
                <h2>Transaksi Terbaru</h2>
                <?php if (count($transactions) > 0): ?>
                    <table class="transactions-table">
                        <thead>
                            <tr>
                                <th>Pembeli</th>
                                <th>Produk</th>
                                <th>Qty</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($transactions, 0, 5) as $trans): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($trans['buyer_name']); ?></td>
                                    <td><?php echo htmlspecialchars($trans['product_name']); ?></td>
                                    <td><?php echo $trans['quantity']; ?></td>
                                    <td>Rp <?php echo number_format($trans['total_price'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge" style="background: #00a8d8; color: white;">
                                            <?php echo ucfirst($trans['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($trans['whatsapp']): ?>
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $trans['whatsapp']); ?>" target="_blank" class="btn-small" style="background: #25d366; color: white; text-decoration: none;">
                                                <i class="fab fa-whatsapp"></i> Chat
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">
                        Belum ada transaksi
                    </p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- MODAL TAMBAH PRODUK -->
    <div id="productModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Produk Baru</h3>
                <span class="close" onclick="closeProductModal()">&times;</span>
            </div>
            <form id="productForm" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="name">Nama Produk</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="category">Kategori</label>
                    <select id="category" name="category">
                        <option value="lele">Ikan Lele</option>
                        <option value="nila">Ikan Nila</option>
                        <option value="gurame">Ikan Gurame</option>
                        <option value="mas">Ikan Mas</option>
                        <option value="lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="price">Harga (Rp)</label>
                    <input type="number" id="price" name="price" required>
                </div>
                <div class="form-group">
                    <label for="stock">Stok</label>
                    <input type="number" id="stock" name="stock" required>
                </div>
                <div class="form-group">
                    <label for="image">Foto Produk</label>
                    <input type="file" id="image" name="image" accept="image/*">
                </div>
                <button type="submit" class="btn-save">Tambah Produk</button>
            </form>
        </div>
    </div>
    
    <script>
        function showAddProductModal() {
            document.getElementById('productModal').classList.add('show');
        }
        
        function closeProductModal() {
            document.getElementById('productModal').classList.remove('show');
        }
        
        function editProduct(productId) {
            alert('Edit feature coming soon');
        }
        
        document.getElementById('productForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(e.target);
            
            try {
                const response = await fetch('api/add-product.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert('Produk berhasil ditambahkan!');
                    location.reload();
                } else {
                    alert(data.message);
                }
            } catch (error) {
                alert('Terjadi kesalahan: ' + error.message);
            }
        });
        
        window.onclick = function(event) {
            const modal = document.getElementById('productModal');
            if (event.target === modal) {
                closeProductModal();
            }
        }
    </script>
</body>
</html>
