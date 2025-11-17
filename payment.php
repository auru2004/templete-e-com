<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['order_number'])) {
    header('Location: index.php');
    exit;
}

$order_number = sanitizeInput($_GET['order_number'] ?? '');
$db = Database::getInstance();

// Get order details
$result = $db->query(
    "SELECT * FROM buyer_orders WHERE order_number = ? AND buyer_id = ?",
    [$order_number, $_SESSION['user_id']]
);

if (!$result || $result->num_rows === 0) {
    header('Location: index.php');
    exit;
}

$order = $db->fetch($result);

function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .payment-container { max-width: 800px; margin: 60px auto; padding: 20px; }
        .payment-card { background: white; border-radius: 15px; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .order-header { text-align: center; margin-bottom: 40px; border-bottom: 2px solid #e0e0e0; padding-bottom: 20px; }
        .order-number { color: #006994; font-size: 24px; font-weight: 600; margin-bottom: 10px; }
        .order-amount { color: #00a8d8; font-size: 36px; font-weight: 700; }
        .payment-methods { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 30px 0; }
        .payment-btn { padding: 20px; border: 2px solid #e0e0e0; border-radius: 12px; cursor: pointer; text-align: center; transition: all 0.3s; }
        .payment-btn:hover { border-color: #00a8d8; background: #f0f8ff; }
        .payment-btn i { font-size: 32px; color: #00a8d8; margin-bottom: 10px; }
        .payment-btn strong { display: block; color: #006994; }
        .payment-info { background: #f0f8ff; border-left: 4px solid #00a8d8; padding: 15px; border-radius: 8px; margin: 20px 0; }
        .status-pending { background: #fff3cd; color: #856404; padding: 15px; border-radius: 8px; text-align: center; }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="container-nav">
            <div class="logo-section">
                <i class="fas fa-water"></i>
                <h1>Lumbung Digital</h1>
            </div>
        </div>
    </nav>

    <div class="payment-container">
        <div class="payment-card">
            <div class="order-header">
                <div class="order-number">Pesanan <?php echo sanitizeInput($order['order_number']); ?></div>
                <div class="order-amount">Rp <?php echo number_format($order['total_amount'], 0, ',', '.'); ?></div>
            </div>
            
            <div class="status-pending">
                <i class="fas fa-hourglass-half"></i> Status: <?php echo $order['payment_status']; ?>
            </div>
            
            <h3 style="color: #006994; margin: 30px 0 20px;">Pilih Metode Pembayaran</h3>
            
            <div class="payment-methods">
                <?php if ($order['payment_method'] === 'bank_transfer' || $order['payment_method'] === 'e_wallet'): ?>
                    <button onclick="processPayment('online', <?php echo $order['id']; ?>)" class="payment-btn">
                        <i class="fas fa-credit-card"></i>
                        <strong>Pembayaran Online</strong>
                        <small>Midtrans/Xendit</small>
                    </button>
                <?php endif; ?>
                
                <?php if ($order['payment_method'] === 'cod'): ?>
                    <button onclick="confirmCOD(<?php echo $order['id']; ?>)" class="payment-btn">
                        <i class="fas fa-truck"></i>
                        <strong>Bayar di Tempat</strong>
                        <small>COD</small>
                    </button>
                <?php else: ?>
                    <button onclick="processPayment('transfer', <?php echo $order['id']; ?>)" class="payment-btn">
                        <i class="fas fa-landmark"></i>
                        <strong>Transfer Manual</strong>
                        <small>Lihat No. Rekening</small>
                    </button>
                <?php endif; ?>
            </div>
            
            <div class="payment-info">
                <strong><i class="fas fa-info-circle"></i> Informasi Penting:</strong>
                <p>Pesanan Anda akan diproses setelah pembayaran dikonfirmasi. Anda akan menerima notifikasi melalui email.</p>
            </div>
            
            <a href="my-orders.php" style="display: inline-block; padding: 12px 30px; background: #6c757d; color: white; text-decoration: none; border-radius: 8px; margin-top: 20px;">
                <i class="fas fa-arrow-left"></i> Kembali ke Pesanan
            </a>
        </div>
    </div>
    
    <script>
        function processPayment(method, orderId) {
            alert('Fitur pembayaran ' + method + ' sedang dikembangkan.\n\nUntuk saat ini, silakan transfer ke rekening yang tersedia.');
            // TODO: Integrate with Midtrans or Xendit
        }
        
        function confirmCOD(orderId) {
            if (confirm('Konfirmasi pesanan dengan metode COD? Anda akan membayar saat barang tiba.')) {
                alert('Pesanan dikonfirmasi! Seller akan segera memproses pesanan Anda.');
                window.location.href = 'my-orders.php';
            }
        }
    </script>
</body>
</html>
