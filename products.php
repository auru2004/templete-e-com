<?php
session_start();

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';

// Cek status user
$isLoggedIn = isLoggedIn();
$userRole = null;
$userName = null;

if ($isLoggedIn) {
    $userRole = $_SESSION['user_role'];
    $userName = $_SESSION['user_name'] ?? 'User';
}

// Get kategori dari URL parameter
$category = isset($_GET['category']) ? sanitizeInput($_GET['category']) : null;

// Ambil produk dari database
try {
    $db = Database::getInstance();
    
    if ($category) {
        $products = $db->query(
            "SELECT * FROM seller_products WHERE category = ? AND status = 'active' ORDER BY views DESC, created_at DESC",
            [$category]
        );
    } else {
        $products = $db->query(
            "SELECT * FROM seller_products WHERE status = 'active' ORDER BY views DESC, created_at DESC LIMIT 50"
        );
    }
    
    // Convert result to array for compatibility
    if ($products && $products->num_rows > 0) {
        $productsArray = [];
        while ($row = $products->fetch_assoc()) {
            $productsArray[] = $row;
        }
        $products = $productsArray;
    } else {
        $products = [];
    }
} catch (Exception $e) {
    $products = [];
    $errorMessage = "Gagal memuat produk. Pastikan database telah diinisialisasi dengan menjalankan: database/setup-complete.sql";
    // Log error for debugging
    error_log("Products page error: " . $e->getMessage());
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Koleksi Produk Ikan - Lumbung Digital">
    <title>Produk - Lumbung Digital</title>
    
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .products-page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 20px;
        }

        .products-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .products-header h1 {
            font-size: 2.5rem;
            color: var(--dark);
            margin-bottom: 0.5rem;
        }

        .filter-section {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .filter-btn {
            padding: 0.7rem 1.5rem;
            border: 2px solid var(--primary);
            background: var(--white);
            color: var(--primary);
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 600;
        }

        .filter-btn:hover,
        .filter-btn.active {
            background: var(--primary);
            color: var(--white);
        }

        .no-products {
            text-align: center;
            padding: 3rem;
            color: #999;
        }

        .no-products i {
            font-size: 3rem;
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .error-message {
            background: #fee;
            color: #c92a2a;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c92a2a;
        }
    </style>
</head>
<body>
    <!-- NAVIGATION -->
    <nav class="navbar">
        <div class="container-nav">
            <div class="logo-section">
                <i class="fas fa-water"></i>
                <a href="index.php"><h1>Lumbung Digital</h1></a>
            </div>
            
            <div class="search-box">
                <input type="text" id="search-input" placeholder="Cari ikan...">
                <button><i class="fas fa-search"></i></button>
            </div>
            
            <div class="nav-buttons">
                <button class="nav-btn" onclick="window.location.href='index.php'">
                    <i class="fas fa-home"></i> Beranda
                </button>
                <button class="nav-btn" onclick="window.location.href='products.php'">
                    <i class="fas fa-fish"></i> Produk
                </button>
                <button class="nav-btn" onclick="window.location.href='cart.php'">
                    <i class="fas fa-shopping-cart"></i> Keranjang
                </button>
                
                <?php if ($isLoggedIn): ?>
                    <?php if ($userRole === 'admin'): ?>
                        <div class="nav-dropdown">
                            <button class="nav-btn dropdown-toggle">
                                <i class="fas fa-cog"></i> Admin
                            </button>
                            <div class="dropdown-menu">
                                <a href="admin-dashboard.php" class="dropdown-item">
                                    <i class="fas fa-chart-line"></i> Dashboard
                                </a>
                                <a href="admin-users.php" class="dropdown-item">
                                    <i class="fas fa-users"></i> Kelola Pengguna
                                </a>
                            </div>
                        </div>
                    <?php elseif ($userRole === 'seller'): ?>
                        <div class="nav-dropdown">
                            <button class="nav-btn dropdown-toggle">
                                <i class="fas fa-store"></i> Toko
                            </button>
                            <div class="dropdown-menu">
                                <a href="seller-dashboard.php" class="dropdown-item">
                                    <i class="fas fa-chart-line"></i> Dashboard
                                </a>
                                <a href="seller-products.php" class="dropdown-item">
                                    <i class="fas fa-box"></i> Produk Saya
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="nav-dropdown user-dropdown">
                        <button class="nav-btn dropdown-toggle user-toggle">
                            <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars(substr($userName, 0, 10)); ?>
                        </button>
                        <div class="dropdown-menu">
                            <a href="profile.php" class="dropdown-item">
                                <i class="fas fa-user"></i> Profil
                            </a>
                            <hr class="dropdown-divider">
                            <a href="api/logout.php" class="dropdown-item">
                                <i class="fas fa-sign-out-alt"></i> Keluar
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <button class="nav-btn nav-btn-primary" onclick="window.location.href='login.php'">
                        <i class="fas fa-sign-in-alt"></i> Masuk
                    </button>
                    <button class="nav-btn nav-btn-secondary" onclick="window.location.href='register.php'">
                        <i class="fas fa-user-plus"></i> Daftar
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- PRODUCTS PAGE -->
    <div class="products-page-container">
        <div class="products-header">
            <h1>Koleksi Produk Ikan</h1>
            <p>Pilih kategori atau lihat semua produk ikan berkualitas kami</p>
        </div>

        <!-- ERROR MESSAGE -->
        <?php if (isset($errorMessage)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> 
                <?php echo htmlspecialchars($errorMessage); ?>
                <br><small>Hubungi admin jika masalah berlanjut.</small>
            </div>
        <?php endif; ?>

        <!-- FILTER KATEGORI -->
        <div class="filter-section">
            <button class="filter-btn <?php echo !$category ? 'active' : ''; ?>" onclick="window.location.href='products.php'">
                <i class="fas fa-list"></i> Semua Produk
            </button>
            <button class="filter-btn <?php echo $category === 'lele' ? 'active' : ''; ?>" onclick="window.location.href='products.php?category=lele'">
                <i class="fas fa-fish"></i> Ikan Lele
            </button>
            <button class="filter-btn <?php echo $category === 'nila' ? 'active' : ''; ?>" onclick="window.location.href='products.php?category=nila'">
                <i class="fas fa-fish"></i> Ikan Nila
            </button>
            <button class="filter-btn <?php echo $category === 'gurame' ? 'active' : ''; ?>" onclick="window.location.href='products.php?category=gurame'">
                <i class="fas fa-fish"></i> Ikan Gurame
            </button>
            <button class="filter-btn <?php echo $category === 'mas' ? 'active' : ''; ?>" onclick="window.location.href='products.php?category=mas'">
                <i class="fas fa-fish"></i> Ikan Mas
            </button>
        </div>

        <!-- PRODUK GRID -->
        <?php if (is_array($products) && count($products) > 0): ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card" onclick="viewProductDetail(<?php echo $product['id']; ?>)">
                        <div class="product-image">
                            <?php if ($product['image'] && file_exists($product['image'])): ?>
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                <i class="fas fa-fish"></i>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <h3 class="product-name"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-category"><?php echo htmlspecialchars($product['category']); ?></p>
                            <p class="product-price">Rp. <?php echo number_format($product['price'], 0, ',', '.'); ?></p>
                            <p class="product-description"><?php echo substr(htmlspecialchars($product['description']), 0, 100); ?>...</p>
                            <div class="product-buttons">
                                <button class="product-buttons button" onclick="viewProductDetail(<?php echo $product['id']; ?>); event.stopPropagation();" style="background: var(--light); color: var(--primary); flex: 1; padding: 0.7rem; border: none; border-radius: 8px; cursor: pointer;">
                                    <i class="fas fa-info-circle"></i> Detail
                                </button>
                                <button class="product-buttons button" onclick="addToCart(<?php echo $product['id']; ?>); event.stopPropagation();" style="background: var(--secondary); color: var(--white); flex: 1; padding: 0.7rem; border: none; border-radius: 8px; cursor: pointer;">
                                    <i class="fas fa-cart-plus"></i> Beli
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-products">
                <i class="fas fa-inbox"></i>
                <h3>Tidak ada produk</h3>
                <p><?php echo $category ? "Tidak ada produk dalam kategori ini" : "Produk belum tersedia. Seller belum menambahkan produk"; ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <footer class="footer">
        <div class="footer-content">
            <div class="footer-section">
                <h4>Tentang Lumbung Digital</h4>
                <p>Kami menjual ikan berkualitas premium langsung dari perairan terbaik di Indonesia.</p>
            </div>
            <div class="footer-section">
                <h4>Menu Cepat</h4>
                <ul>
                    <li><a href="index.php">Beranda</a></li>
                    <li><a href="products.php">Produk</a></li>
                    <li><a href="cart.php">Keranjang</a></li>
                </ul>
            </div>
            <div class="footer-section">
                <h4>Hubungi Kami</h4>
                <p>📞 +62 812 3456 7890</p>
                <p>📧 info@lumbungdigital.com</p>
            </div>
            <div class="footer-section">
                <h4>Ikuti Kami</h4>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2025 Lumbung Digital. Semua Hak Dilindungi.</p>
        </div>
    </footer>

    <script src="assets/js/dropdown.js"></script>
    <script src="assets/js/cart.js"></script>
    <script>
        function addToCart(productId) {
            // Check if user is logged in
            fetch('api/add-to-cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    product_id: productId,
                    quantity: 1
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    alert('✓ ' + data.message);
                    // Update cart count
                    updateCartCount();
                } else {
                    // Show error message
                    if (data.message.includes('login')) {
                        if (confirm(data.message + '\n\nAkses halaman login?')) {
                            window.location.href = 'login.php';
                        }
                    } else {
                        alert('✗ ' + data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('✗ Terjadi kesalahan saat menambah ke keranjang');
            });
        }

        function viewProductDetail(productId) {
            // TODO: Implementasi untuk menampilkan detail produk
            console.log('View product detail:', productId);
        }

        function updateCartCount() {
            // Implementasi untuk memperbarui jumlah item di keranjang
            console.log('Update cart count');
        }
    </script>
</body>
</html>
