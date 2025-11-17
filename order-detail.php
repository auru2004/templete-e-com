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

$buyer_id = $_SESSION['user_id'];
$order_id = intval($_GET['id'] ?? 0);

// Get order
$stmt = $connection->prepare("
    SELECT bo.*, u.name as seller_name, u.whatsapp, u.phone as seller_phone
    FROM buyer_orders bo
    JOIN users u ON bo.seller_id = u.id
    WHERE bo.id = ? AND bo.buyer_id = ?
");
$stmt->bind_param("ii", $order_id, $buyer_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: my-orders.php');
    exit;
}

// Get order items
$stmt = $connection->prepare("
    SELECT * FROM buyer_order_items WHERE order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pesanan - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .detail-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .detail-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .section-title {
            color: #006994;
            font-size: 16px;
            font-weight: 600;
            margin: 20px 0 15px 0;
            border-bottom: 2px solid #00a8d8;
            padding-bottom: 10px;
        }
        
        .section-title:first-child {
            margin-top: 0;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .info-item {
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .info-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        
        .info-value {
            color: #006994;
            font-weight: 600;
            font-size: 15px;
        }
        
        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        .items-table thead th {
            background: #f5f7fa;
            padding: 12px;
            text-align: left;
            color: #006994;
            font-weight: 600;
            border-bottom: 2px solid #e0e0e0;
        }
        
        .items-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .btn-back {
            background: #00a8d8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
        }
        
        .btn-chat {
            background: #25d366;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: inline-block;
            margin-left: 10px;
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
                <a href="my-orders.php" class="nav-btn"><i class="fas fa-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </nav>
    
    <div class="detail-container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h1 style="color: #006994;">Detail Pesanan</h1>
            <div>
                <a href="my-orders.php" class="btn-back"><i class="fas fa-arrow-left"></i> Kembali</a>
                <?php if ($order['whatsapp']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['whatsapp']); ?>" target="_blank" class="btn-chat"><i class="fab fa-whatsapp"></i> Chat Penjual</a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="detail-card">
            <h2 class="section-title">Status Pesanan</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nomor Pesanan</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['order_number']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status Pesanan</div>
                    <div class="status-badge status-<?php echo $order['order_status']; ?>">
                        <?php echo ucfirst($order['order_status']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Status Pembayaran</div>
                    <div class="status-badge status-<?php echo $order['payment_status']; ?>">
                        <?php echo ucfirst($order['payment_status']); ?>
                    </div>
                </div>
                <div class="info-item">
                    <div class="info-label">Tanggal Pesanan</div>
                    <div class="info-value"><?php echo date('d M Y H:i', strtotime($order['created_at'])); ?></div>
                </div>
            </div>
            
            <h2 class="section-title">Penjual</h2>
            
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Nama Toko</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['seller_name']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Kontak</div>
                    <div class="info-value">
                        <?php if ($order['seller_phone']): ?>
                            <?php echo htmlspecialchars($order['seller_phone']); ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <h2 class="section-title">Detail Produk</h2>
            
            <table class="items-table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Harga Satuan</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                            <td>Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></td>
                            <td><?php echo $item['quantity']; ?> unit</td>
                            <td>Rp <?php echo number_format($item['subtotal'], 0, ',', '.'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px; margin-top: 20px;">
                <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: 600; color: #006994;">
                    <span>Total Pembayaran:</span>
                    <span>Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></span>
                </div>
            </div>
            
            <h2 class="section-title">Alamat Pengiriman</h2>
            
            <div style="background: #f9f9f9; padding: 15px; border-radius: 8px;">
                <div style="margin-bottom: 10px;">
                    <strong style="color: #006994;"><?php echo htmlspecialchars($order['buyer_name']); ?></strong>
                </div>
                <div style="margin-bottom: 10px;">
                    <?php echo htmlspecialchars($order['buyer_phone']); ?>
                </div>
                <div style="color: #666; line-height: 1.6;">
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
