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

if ($auth->isAdmin() || $auth->isSeller()) {
    header('Location: index.php');
    exit;
}

$buyer_id = $_SESSION['user_id'];

// Get cart items grouped by seller
$stmt = $connection->prepare("
    SELECT 
        bc.id as cart_id,
        bc.quantity,
        sp.*,
        u.name as seller_name,
        u.id as seller_id,
        u.whatsapp
    FROM buyer_cart bc
    JOIN seller_products sp ON bc.seller_product_id = sp.id
    JOIN users u ON sp.seller_id = u.id
    WHERE bc.buyer_id = ?
    ORDER BY u.id, sp.name
");
$stmt->bind_param("i", $buyer_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group by seller
$grouped_items = [];
foreach ($cart_items as $item) {
    $seller_id = $item['seller_id'];
    if (!isset($grouped_items[$seller_id])) {
        $grouped_items[$seller_id] = [
            'seller_name' => $item['seller_name'],
            'seller_id' => $seller_id,
            'whatsapp' => $item['whatsapp'],
            'items' => []
        ];
    }
    $grouped_items[$seller_id]['items'][] = $item;
}

// Handle remove from cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $cart_id = intval($_POST['cart_id']);
    $stmt = $connection->prepare("DELETE FROM buyer_cart WHERE id = ? AND buyer_id = ?");
    $stmt->bind_param("ii", $cart_id, $buyer_id);
    $stmt->execute();
    header('Location: cart.php');
    exit;
}

// Handle update quantity
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $cart_id = intval($_POST['cart_id']);
    $quantity = intval($_POST['quantity']);
    
    if ($quantity > 0) {
        $stmt = $connection->prepare("UPDATE buyer_cart SET quantity = ? WHERE id = ? AND buyer_id = ?");
        $stmt->bind_param("iii", $quantity, $cart_id, $buyer_id);
        $stmt->execute();
    }
    header('Location: cart.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Keranjang Belanja - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 20px;
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
        }
        
        .cart-items {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .seller-section {
            margin-bottom: 30px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            padding: 20px;
        }
        
        .seller-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #00a8d8;
        }
        
        .seller-header h3 {
            color: #006994;
            font-size: 16px;
        }
        
        .item-list {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        
        .cart-item {
            display: flex;
            gap: 15px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 10px;
            align-items: center;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .item-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .item-details {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            color: #006994;
            margin-bottom: 5px;
        }
        
        .item-price {
            color: #00a8d8;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .item-controls {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .qty-control {
            display: flex;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            overflow: hidden;
        }
        
        .qty-control button {
            background: white;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
            font-size: 12px;
            color: #006994;
        }
        
        .qty-control input {
            width: 40px;
            border: none;
            text-align: center;
            padding: 5px;
            font-weight: 600;
        }
        
        .subtotal {
            font-weight: 600;
            color: #006994;
            min-width: 80px;
            text-align: right;
        }
        
        .btn-remove {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            transition: all 0.3s;
        }
        
        .btn-remove:hover {
            background: #c92a2a;
        }
        
        .cart-summary {
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
        
        .btn-checkout {
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
        
        .btn-checkout:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 105, 148, 0.3);
        }
        
        .btn-checkout:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
            background: white;
            border-radius: 15px;
        }
        
        @media (max-width: 768px) {
            .cart-container {
                grid-template-columns: 1fr;
            }
            .cart-summary {
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
                <a href="index.php" class="nav-btn"><i class="fas fa-home"></i> Beranda</a>
                <a href="profile.php" class="nav-btn"><i class="fas fa-user"></i> Profil</a>
                <a href="api/logout.php" class="nav-btn nav-btn-login"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="cart-container">
        <div class="cart-items">
            <h1 style="color: #006994; margin-bottom: 30px;">Keranjang Belanja</h1>
            
            <?php if (count($cart_items) > 0): ?>
                <?php foreach ($grouped_items as $group): ?>
                    <div class="seller-section">
                        <div class="seller-header">
                            <h3>
                                <i class="fas fa-store" style="margin-right: 8px;"></i>
                                <?php echo htmlspecialchars($group['seller_name']); ?>
                            </h3>
                            <?php if ($group['whatsapp']): ?>
                                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $group['whatsapp']); ?>" target="_blank" style="color: #25d366; text-decoration: none; font-size: 14px;">
                                    <i class="fab fa-whatsapp"></i> Chat Penjual
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="item-list">
                            <?php foreach ($group['items'] as $item): 
                                $subtotal = $item['quantity'] * $item['price'];
                            ?>
                                <div class="cart-item">
                                    <div class="item-image">
                                        <?php if ($item['image']): ?>
                                            <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>">
                                        <?php else: ?>
                                            <i class="fas fa-image" style="color: #999; font-size: 24px;"></i>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="item-details">
                                        <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="item-price">Rp <?php echo number_format($item['price'], 0, ',', '.'); ?></div>
                                    </div>
                                    
                                    <form method="POST" style="display: flex; align-items: center; gap: 10px;">
                                        <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                        <div class="item-controls">
                                            <div class="qty-control">
                                                <button type="button" onclick="decreaseQty(this)">-</button>
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" min="1" onchange="this.form.submit()">
                                                <button type="button" onclick="increaseQty(this)">+</button>
                                            </div>
                                            <div class="subtotal">Rp <?php echo number_format($subtotal, 0, ',', '.'); ?></div>
                                            <button type="submit" name="remove_item" class="btn-remove" onclick="return confirm('Hapus dari keranjang?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-cart">
                    <i class="fas fa-shopping-cart" style="font-size: 64px; color: #ddd; margin-bottom: 20px;"></i>
                    <h2 style="color: #999; margin-bottom: 20px;">Keranjang Kosong</h2>
                    <p style="color: #ccc; margin-bottom: 30px;">Mulai berbelanja sekarang!</p>
                    <a href="index.php" style="display: inline-block; background: #00a8d8; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: 600;">Lihat Produk</a>
                </div>
            <?php endif; ?>
        </div>
        
        <?php if (count($cart_items) > 0): ?>
            <div class="cart-summary">
                <div class="summary-title">Ringkasan</div>
                
                <div class="summary-row">
                    <span>Subtotal:</span>
                    <span>Rp <?php 
                        $total = 0;
                        foreach ($cart_items as $item) {
                            $total += $item['quantity'] * $item['price'];
                        }
                        echo number_format($total, 0, ',', '.');
                    ?></span>
                </div>
                
                <div class="summary-row">
                    <span>Ongkos Kirim:</span>
                    <span>Gratis</span>
                </div>
                
                <div class="summary-row total">
                    <span>Total:</span>
                    <span>Rp <?php echo number_format($total, 0, ',', '.'); ?></span>
                </div>
                
                <a href="checkout.php" class="btn-checkout">
                    <i class="fas fa-arrow-right"></i> Lanjut ke Checkout
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function decreaseQty(btn) {
            const input = btn.parentElement.querySelector('input');
            if (input.value > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }
        
        function increaseQty(btn) {
            const input = btn.parentElement.querySelector('input');
            input.value = parseInt(input.value) + 1;
        }
    </script>
</body>
</html>
