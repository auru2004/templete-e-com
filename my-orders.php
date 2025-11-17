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

// Get buyer orders
$stmt = $connection->prepare("
    SELECT bo.*, u.name as seller_name, u.whatsapp
    FROM buyer_orders bo
    JOIN users u ON bo.seller_id = u.id
    WHERE bo.buyer_id = ?
    ORDER BY bo.created_at DESC
");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$orders = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pesanan Saya - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .order-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            border-left: 4px solid #00a8d8;
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .order-number {
            font-weight: 600;
            color: #006994;
            font-size: 15px;
        }
        
        .order-status {
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
        
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 15px 0;
            padding: 15px 0;
            border-top: 1px solid #e0e0e0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .detail-item {
            font-size: 13px;
        }
        
        .detail-label {
            color: #666;
            margin-bottom: 3px;
        }
        
        .detail-value {
            color: #006994;
            font-weight: 600;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-action {
            flex: 1;
            padding: 10px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            text-align: center;
        }
        
        .btn-view {
            background: #00a8d8;
            color: white;
        }
        
        .btn-chat {
            background: #25d366;
            color: white;
        }
        
        .empty-orders {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
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
                <a href="index.php" class="nav-btn"><i class="fas fa-home"></i> Beranda</a>
                <a href="profile.php" class="nav-btn"><i class="fas fa-user"></i> Profil</a>
                <a href="api/logout.php" class="nav-btn nav-btn-login"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="orders-container">
        <h1 style="color: #006994; margin-bottom: 30px;">Pesanan Saya</h1>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success']; ?>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        
        <?php if (count($orders) > 0): ?>
            <?php foreach ($orders as $order): ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <div class="order-number">Pesanan #<?php echo htmlspecialchars($order['order_number']); ?></div>
                            <div style="font-size: 12px; color: #999; margin-top: 3px;">
                                <?php echo date('d M Y H:i', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <div class="order-status status-<?php echo $order['order_status']; ?>">
                                <?php echo ucfirst($order['order_status']); ?>
                            </div>
                            <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                Pembayaran: <strong><?php echo ucfirst($order['payment_status']); ?></strong>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-details">
                        <div class="detail-item">
                            <div class="detail-label">Penjual</div>
                            <div class="detail-value"><?php echo htmlspecialchars($order['seller_name']); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Total Pesanan</div>
                            <div class="detail-value">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></div>
                        </div>
                        <div class="detail-item">
                            <div class="detail-label">Metode Pembayaran</div>
                            <div class="detail-value"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></div>
                        </div>
                    </div>
                    
                    <div class="order-actions">
                        <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="btn-action btn-view">
                            <i class="fas fa-eye"></i> Lihat Detail
                        </a>
                        <?php if ($order['whatsapp']): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $order['whatsapp']); ?>" target="_blank" class="btn-action btn-chat">
                                <i class="fab fa-whatsapp"></i> Chat Penjual
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-orders">
                <i class="fas fa-package" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                <h2 style="color: #999; margin-bottom: 20px;">Belum Ada Pesanan</h2>
                <p style="color: #ccc; margin-bottom: 30px;">Mulai berbelanja sekarang!</p>
                <a href="index.php" style="display: inline-block; background: #00a8d8; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600;">Lihat Produk</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
