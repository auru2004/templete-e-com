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

// Handle update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = trim($_POST['new_status']);
    
    $allowed_statuses = ['confirmed', 'processing', 'shipped', 'delivered', 'cancelled'];
    
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $connection->prepare("UPDATE buyer_orders SET order_status = ? WHERE id = ? AND seller_id = ?");
        $stmt->bind_param("sii", $new_status, $order_id, $seller_id);
        $stmt->execute();
        $_SESSION['message'] = 'Status pesanan berhasil diperbarui';
    }
}

// Get seller orders
$stmt = $connection->prepare("
    SELECT bo.*, u.name as buyer_name, u.phone as buyer_phone, u.whatsapp
    FROM buyer_orders bo
    JOIN users u ON bo.buyer_id = u.id
    WHERE bo.seller_id = ?
    ORDER BY bo.created_at DESC
");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaksi Saya - Seller</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        
        .header-top h1 {
            color: #006994;
            font-size: 24px;
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
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .orders-table thead th {
            background: #f5f7fa;
            padding: 12px;
            text-align: left;
            color: #006994;
            font-weight: 600;
            border-bottom: 2px solid #00a8d8;
        }
        
        .orders-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .orders-table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-confirmed {
            background: #cfe2ff;
            color: #084298;
        }
        
        .status-processing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-shipped {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
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
        
        .btn-view {
            background: #00a8d8;
            color: white;
        }
        
        .btn-chat {
            background: #25d366;
            color: white;
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
            max-width: 400px;
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
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
        
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
        }
        
        .btn-submit {
            background: #00a8d8;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- SIDEBAR -->
        <aside class="sidebar">
            <h3><i class="fas fa-store"></i> Dashboard Penjual</h3>
            <ul class="sidebar-menu">
                <li><a href="seller-dashboard.php"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <li><a href="seller-products.php"><i class="fas fa-box"></i> Produk Saya</a></li>
                <li><a href="seller-transactions.php" class="active"><i class="fas fa-exchange-alt"></i> Transaksi</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> Profil</a></li>
                <li><a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="header-top">
                <h1>Transaksi Penjualan</h1>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <div class="section">
                <h2>Pesanan Masuk</h2>
                
                <?php if (count($orders) > 0): ?>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>No. Pesanan</th>
                                <th>Pembeli</th>
                                <th>Total</th>
                                <th>Status Pesanan</th>
                                <th>Pembayaran</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(substr($order['order_number'], 0, 20)); ?></td>
                                    <td><?php echo htmlspecialchars($order['buyer_name']); ?></td>
                                    <td>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['order_status']; ?>">
                                            <?php echo ucfirst($order['order_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge" style="background: <?php echo $order['payment_status'] === 'paid' ? '#d4edda' : '#fff3cd'; ?>; color: <?php echo $order['payment_status'] === 'paid' ? '#155724' : '#856404'; ?>;">
                                            <?php echo ucfirst($order['payment_status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn-small btn-view" onclick="openUpdateModal(<?php echo $order['id']; ?>, '<?php echo $order['order_status']; ?>')">
                                            <i class="fas fa-edit"></i> Update
                                        </button>
                                        <?php if ($order['whatsapp']): ?>
                                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['whatsapp']); ?>" target="_blank" class="btn-small btn-chat">
                                                <i class="fab fa-whatsapp"></i> Chat
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">Belum ada pesanan</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- MODAL UPDATE STATUS -->
    <div id="updateModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Status Pesanan</h3>
                <span class="close" onclick="closeUpdateModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" id="order_id" name="order_id">
                
                <div class="form-group">
                    <label for="new_status">Status Baru</label>
                    <select id="new_status" name="new_status" required>
                        <option value="confirmed">Dikonfirmasi</option>
                        <option value="processing">Diproses</option>
                        <option value="shipped">Dikirim</option>
                        <option value="delivered">Terkirim</option>
                        <option value="cancelled">Dibatalkan</option>
                    </select>
                </div>
                
                <button type="submit" name="update_status" class="btn-submit">Update Status</button>
            </form>
        </div>
    </div>
    
    <script>
        function openUpdateModal(orderId, currentStatus) {
            document.getElementById('order_id').value = orderId;
            document.getElementById('new_status').value = currentStatus;
            document.getElementById('updateModal').classList.add('show');
        }
        
        function closeUpdateModal() {
            document.getElementById('updateModal').classList.remove('show');
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('updateModal');
            if (event.target === modal) {
                closeUpdateModal();
            }
        }
    </script>
</body>
</html>
