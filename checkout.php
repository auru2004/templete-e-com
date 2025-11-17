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
$buyer = $auth->getCurrentUser();

$buyer_id = $_SESSION['user_id'];

// Get cart items
$stmt = $connection->prepare("
    SELECT 
        bc.id as cart_id,
        bc.quantity,
        sp.*,
        u.id as seller_id,
        u.name as seller_name
    FROM buyer_cart bc
    JOIN seller_products sp ON bc.seller_product_id = sp.id
    JOIN users u ON sp.seller_id = u.id
    WHERE bc.buyer_id = ?
    ORDER BY u.id
");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if (empty($cart_items)) {
    header('Location: cart.php');
    exit;
}

// Group by seller
$grouped_items = [];
$total_amount = 0;

foreach ($cart_items as $item) {
    $seller_id = $item['seller_id'];
    if (!isset($grouped_items[$seller_id])) {
        $grouped_items[$seller_id] = [
            'seller_name' => $item['seller_name'],
            'items' => [],
            'subtotal' => 0
        ];
    }
    $subtotal = $item['quantity'] * $item['price'];
    $grouped_items[$seller_id]['items'][] = $item;
    $grouped_items[$seller_id]['subtotal'] += $subtotal;
    $total_amount += $subtotal;
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $buyer_phone = trim($_POST['phone'] ?? '');
    $buyer_name = trim($_POST['name'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? 'transfer_bank');
    
    if (empty($shipping_address) || empty($buyer_phone) || empty($buyer_name)) {
        $_SESSION['error'] = 'Semua field pengiriman harus diisi';
    } else {
        // Create orders for each seller
        $all_success = true;
        $order_ids = [];
        
        foreach ($grouped_items as $seller_id => $group) {
            $order_number = 'ORD-' . date('YmdHis') . '-' . rand(1000, 9999);
            $stmt = $connection->prepare("
                INSERT INTO buyer_orders 
                (buyer_id, seller_id, order_number, total_amount, shipping_address, buyer_phone, buyer_name, payment_method, payment_status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->bind_param(
                "iisdssss",
                $buyer_id,
                $seller_id,
                $order_number,
                $group['subtotal'],
                $shipping_address,
                $buyer_phone,
                $buyer_name,
                $payment_method
            );
            
            if ($stmt->execute()) {
                $order_id = $stmt->insert_id;
                $order_ids[] = $order_id;
                
                // Add order items
                foreach ($group['items'] as $item) {
                    $stmt = $connection->prepare("
                        INSERT INTO buyer_order_items
                        (order_id, product_id, product_name, quantity, price, subtotal)
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $subtotal = $item['quantity'] * $item['price'];
                    $stmt->bind_param(
                        "iisids",
                        $order_id,
                        $item['id'],
                        $item['name'],
                        $item['quantity'],
                        $item['price'],
                        $subtotal
                    );
                    $stmt->execute();
                }
            } else {
                $all_success = false;
            }
        }
        
        if ($all_success) {
            // Clear cart
            $stmt = $connection->prepare("DELETE FROM buyer_cart WHERE buyer_id = ?");
            $stmt->bind_param("i", $buyer_id);
            $stmt->execute();
            
            $_SESSION['success'] = 'Pesanan berhasil dibuat! Silakan lakukan pembayaran.';
            header('Location: my-orders.php');
            exit;
        } else {
            $_SESSION['error'] = 'Gagal membuat pesanan';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .checkout-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }
        
        .checkout-form {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .section-title {
            color: #006994;
            font-size: 18px;
            font-weight: 600;
            margin: 25px 0 20px 0;
            border-bottom: 2px solid #00a8d8;
            padding-bottom: 10px;
        }
        
        .section-title:first-child {
            margin-top: 0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: border-color 0.3s;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00a8d8;
            box-shadow: 0 0 0 3px rgba(0, 168, 216, 0.1);
        }
        
        .order-summary {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            height: fit-content;
            position: sticky;
            top: 20px;
        }
        
        .summary-title {
            color: #006994;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            border-bottom: 2px solid #00a8d8;
            padding-bottom: 15px;
        }
        
        .order-item {
            font-size: 13px;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 14px;
        }
        
        .summary-row.total {
            font-size: 16px;
            font-weight: 600;
            color: #006994;
            border-top: 2px solid #e0e0e0;
            padding-top: 15px;
            margin-top: 15px;
        }
        
        .btn-place-order {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #006994, #00a8d8);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        .btn-place-order:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 105, 148, 0.3);
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        @media (max-width: 768px) {
            .checkout-container {
                grid-template-columns: 1fr;
            }
            .order-summary {
                position: relative;
                top: 0;
            }
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
                <a href="cart.php" class="nav-btn"><i class="fas fa-arrow-left"></i> Kembali ke Keranjang</a>
            </div>
        </div>
    </nav>
    
    <div class="checkout-container">
        <div class="checkout-form">
            <h1 style="color: #006994; margin-bottom: 30px;">Checkout</h1>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i> <?php echo $_SESSION['error']; ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>
            
            <form method="POST">
                <!-- INFORMASI PENGIRIMAN -->
                <h3 class="section-title">Informasi Pengiriman</h3>
                
                <div class="form-group">
                    <label for="name">Nama Penerima</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($buyer['name']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($buyer['phone'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="shipping_address">Alamat Pengiriman Lengkap</label>
                    <textarea id="shipping_address" name="shipping_address" placeholder="Jalan, No. Rumah, RT/RW, Kelurahan, Kecamatan, Kota, Provinsi, Kode Pos" required><?php echo htmlspecialchars($buyer['address'] ?? ''); ?></textarea>
                </div>
                
                <!-- METODE PEMBAYARAN -->
                <h3 class="section-title">Metode Pembayaran</h3>
                
                <div class="form-group">
                    <label for="payment_method">Pilih Metode Pembayaran</label>
                    <select id="payment_method" name="payment_method" required>
                        <option value="transfer_bank">Transfer Bank</option>
                        <option value="e_wallet">E-Wallet (OVO, DANA, GoPay)</option>
                        <option value="cod">Cash on Delivery (COD)</option>
                    </select>
                </div>
                
                <!-- KONFIRMASI PESANAN -->
                <h3 class="section-title">Konfirmasi Pesanan</h3>
                <p style="color: #666; font-size: 13px; margin-bottom: 20px;">
                    <i class="fas fa-info-circle"></i> 
                    Dengan melanjutkan, Anda setuju bahwa pesanan ini akan segera diproses oleh penjual.
                </p>
                
                <button type="submit" name="place_order" class="btn-place-order">
                    <i class="fas fa-check"></i> Buat Pesanan
                </button>
            </form>
        </div>
        
        <!-- ORDER SUMMARY -->
        <div class="order-summary">
            <div class="summary-title">Ringkasan Pesanan</div>
            
            <?php foreach ($grouped_items as $group): ?>
                <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 2px solid #e0e0e0;">
                    <div style="font-weight: 600; color: #006994; margin-bottom: 10px; font-size: 13px;">
                        Penjual: <?php echo htmlspecialchars($group['seller_name']); ?>
                    </div>
                    <?php foreach ($group['items'] as $item): ?>
                        <div class="order-item">
                            <div style="margin-bottom: 5px;">
                                <strong><?php echo htmlspecialchars(substr($item['name'], 0, 30)); ?></strong>
                            </div>
                            <div style="color: #666;">
                                <?php echo $item['quantity']; ?> × Rp <?php echo number_format($item['price'], 0, ',', '.'); ?> = 
                                <strong style="color: #00a8d8;">Rp <?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="font-weight: 600; color: #006994; font-size: 13px; text-align: right;">
                        Subtotal: Rp <?php echo number_format($group['subtotal'], 0, ',', '.'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <div class="summary-row">
                <span>Ongkos Kirim:</span>
                <span>Gratis</span>
            </div>
            
            <div class="summary-row total">
                <span>Total Pembayaran:</span>
                <span>Rp <?php echo number_format($total_amount, 0, ',', '.'); ?></span>
            </div>
        </div>
    </div>
</body>
</html>
