<?php
// LUMBUNGDIGITAL - Web Penjualan Ikan Profesional
// Main Entry Point - Banner & Artikel Home

session_start();

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
require_once 'includes/auth.php';

$isLoggedIn = isLoggedIn();
$userRole = null;
$userName = null;
$userPhoto = null;

if ($isLoggedIn) {
    $userId = $_SESSION['user_id'];
    $userRole = $_SESSION['user_role'];
    $userName = $_SESSION['user_name'] ?? 'User';
    
    try {
        $db = Database::getInstance();
        $result = $db->query("SELECT photo FROM users WHERE id = ?", [$userId]);
        if ($result && $result->num_rows > 0) {
            $row = $db->fetch($result);
            $userPhoto = $row['photo'] ?? null;
        }
    } catch (Exception $e) {
        // Silent fail
    }
}

// Get banners
$banners = [];
$articles = [];
try {
    $db = Database::getInstance();
    $banner_result = $db->query("SELECT * FROM banners WHERE is_active = 1 ORDER BY display_order ASC LIMIT 5", []);
    if ($banner_result && $banner_result->num_rows > 0) {
        while ($row = $db->fetch($banner_result)) {
            $banners[] = $row;
        }
    }
    
    $article_result = $db->query("SELECT * FROM articles WHERE is_active = 1 ORDER BY created_at DESC LIMIT 6", []);
    if ($article_result && $article_result->num_rows > 0) {
        while ($row = $db->fetch($article_result)) {
            $articles[] = $row;
        }
    }
} catch (Exception $e) {
    // Silent fail
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Lumbung Digital - Penjualan Ikan Berkualitas Premium">
    <title>Lumbung Digital - Penjualan Ikan Segar</title>
    
    <!-- CSS Files -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="assets/css/loading.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
<style>
/* Banner Slider Styles - ENHANCED */
        .banner-slider-container {
            position: relative;
            width: 100%;
            padding: 0;
            background: #f9f9f9;
            margin-top: 0;
            max-width: 100%;
        }
        
        .banner-slider-wrapper {
            max-width: 100%;
            margin: 0 auto;
            border-radius: 0;
            overflow: hidden;
            box-shadow: none;
            background: white;
        }
        
        .banner-slider {
            display: flex;
            min-height: 480px;
            transition: transform 0.5s ease-in-out;
            width: 100%;
        }
        
        .banner-slide {
            min-width: 100%;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            flex-shrink: 0;
        }
        
        .banner-slide img {
            width: 100%;
            height: 100%;
            display: block;
            object-fit: cover;
            background: white;
        }
        
        .banner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, rgba(0, 0, 0, 0.4) 0%, rgba(0, 0, 0, 0.2) 50%, transparent 100%);
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            justify-content: center;
            color: white;
            text-align: left;
            padding: 60px;
        }
        
        .banner-content h2 {
            font-size: 3.2rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.6);
            max-width: 600px;
            line-height: 1.2;
            letter-spacing: -0.5px;
        }
        
        .banner-content p {
            font-size: 1.3rem;
            margin-bottom: 2.5rem;
            text-shadow: 1px 1px 4px rgba(0, 0, 0, 0.5);
            max-width: 500px;
            line-height: 1.5;
            opacity: 0.95;
        }
        
        .banner-btn {
            background: linear-gradient(135deg, #ff6b35, #ff5520);
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.3s ease;
            box-shadow: 0 8px 24px rgba(255, 107, 53, 0.35);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            letter-spacing: 0.3px;
        }
        
        .banner-btn:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(255, 107, 53, 0.45);
            background: linear-gradient(135deg, #ff7a4a, #ff6535);
        }
        
        .slider-controls {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 12px;
            z-index: 10;
        }
        
        .slider-dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid white;
        }
        
        .slider-dot.active {
            background: white;
            transform: scale(1.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .slider-arrow {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(255, 255, 255, 0.15);
            color: white;
            border: none;
            font-size: 1.8rem;
            padding: 12px 18px;
            cursor: pointer;
            transition: all 0.3s;
            z-index: 10;
            border-radius: 8px;
            backdrop-filter: blur(5px);
        }
        
        .slider-arrow:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: translateY(-50%) scale(1.1);
        }
        
        .slider-arrow-left {
            left: 30px;
        }
        
        .slider-arrow-right {
            right: 30px;
        }

        
        /* Articles Section */
        .articles-section {
            padding: 60px 20px;
            background: #f9f9f9;
        }
        
        .articles-container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .section-title {
            text-align: center;
            font-size: 2.2rem;
            color: #c92a2a;
            margin-bottom: 10px;
            font-weight: bold;
        }
        
        .section-subtitle {
            text-align: center;
            color: #666;
            margin-bottom: 40px;
            font-size: 1rem;
        }
        
        .articles-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 25px;
        }
        
        .article-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .article-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        .article-image {
            width: 100%;
            height: 250px;
            background: #e0e0e0;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .article-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .article-body {
            padding: 20px;
        }
        
        .article-title {
            font-size: 1.3rem;
            color: #333;
            margin-bottom: 10px;
            font-weight: 600;
            line-height: 1.4;
        }
        
        .article-excerpt {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 15px;
            line-height: 1.6;
        }
        
        .article-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 15px;
            border-top: 1px solid #f0f0f0;
        }
        
        .article-date {
            color: #999;
            font-size: 0.85rem;
        }
        
        .article-link {
            color: #ff6b35;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .article-link:hover {
            color: #c92a2a;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #ff6b35, #c92a2a);
            color: white;
            padding: 60px 20px;
            text-align: center;
        }
        
        .cta-content h2 {
            font-size: 2rem;
            margin-bottom: 1rem;
        }
        
        .cta-content p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .cta-btn {
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 1rem;
            transition: all 0.3s;
        }
        
        .cta-btn-primary {
            background: white;
            color: #c92a2a;
        }
        
        .cta-btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
        }
        
        .cta-btn-secondary {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            border: 2px solid white;
        }
        
        .cta-btn-secondary:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        @media (max-width: 768px) {
            .banner-slider-container {
                height: auto;
            }
            
            .banner-overlay {
                padding: 40px;
            }
            
            .banner-content h2 {
                font-size: 1.8rem;
            }
            
            .banner-content p {
                font-size: 1rem;
            }
            
            .articles-grid {
                grid-template-columns: 1fr;
            }
            
            .section-title {
                font-size: 1.8rem;
            }
            
            .cta-content h2 {
                font-size: 1.5rem;
            }
        }
    </style>

</head>
<body>
    <!-- LOADING SCREEN -->
    <div id="loading-screen" class="loading-screen">
        <div class="loader-container">
            <div class="loader">
                <div class="fish-bubble"></div>
                <div class="wave wave-1"></div>
                <div class="wave wave-2"></div>
                <div class="wave wave-3"></div>
            </div>
            <h2>Lumbung Digital</h2>
            <p>Mempersiapkan ikan segar untuk Anda...</p>
            <div class="loading-bar">
                <div class="loading-progress"></div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div id="main-content">
        <!-- NAVIGATION HEADER -->
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
                        <i class="fas fa-shopping-cart"></i> Keranjang <span id="cart-count">0</span>
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
                                    <a href="admin-banners.php" class="dropdown-item">
                                        <i class="fas fa-images"></i> Banner & Artikel
                                    </a>
                                    <a href="admin-users.php" class="dropdown-item">
                                        <i class="fas fa-users"></i> Kelola Pengguna
                                    </a>
                                    <a href="admin-products.php" class="dropdown-item">
                                        <i class="fas fa-box"></i> Kelola Produk
                                    </a>
                                    <a href="admin-warnings.php" class="dropdown-item">
                                        <i class="fas fa-exclamation-triangle"></i> Peringatan
                                    </a>
                                </div>
                            </div>
                        <?php elseif ($userRole === 'seller'): ?>
                            <div class="nav-dropdown">
                                <button class="nav-btn dropdown-toggle">
                                    <i class="fas fa-store"></i> Toko Saya
                                </button>
                                <div class="dropdown-menu">
                                    <a href="seller-dashboard.php" class="dropdown-item">
                                        <i class="fas fa-chart-line"></i> Dashboard
                                    </a>
                                    <a href="seller-products.php" class="dropdown-item">
                                        <i class="fas fa-box"></i> Produk Saya
                                    </a>
                                    <a href="seller-transactions.php" class="dropdown-item">
                                        <i class="fas fa-receipt"></i> Transaksi
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <button class="nav-btn" onclick="window.location.href='my-orders.php'">
                                <i class="fas fa-box-open"></i> Pesanan Saya
                            </button>
                        <?php endif; ?>

                        <!-- User Profile Dropdown -->
                        <div class="nav-dropdown user-dropdown">
                            <button class="nav-btn dropdown-toggle user-toggle">
                                <?php if ($userPhoto && file_exists($userPhoto)): ?>
                                    <img src="<?php echo htmlspecialchars($userPhoto); ?>" alt="Profile" class="profile-pic">
                                <?php else: ?>
                                    <i class="fas fa-user-circle"></i>
                                <?php endif; ?>
                                <?php echo htmlspecialchars(substr($userName, 0, 15)); ?>
                            </button>
                            <div class="dropdown-menu">
                                <a href="profile.php" class="dropdown-item">
                                    <i class="fas fa-user"></i> Profil Saya
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
                
                <button class="menu-toggle">
                    <span></span>
                    <span></span>
                    <span></span>
                </button>
            </div>
        </nav>

        <!-- BANNER SLIDER -->
        <?php if (count($banners) > 0): ?>
        <div class="banner-slider-container">
            <div class="banner-slider" id="bannerSlider">
                <?php foreach ($banners as $index => $banner): ?>
                <div class="banner-slide">
                    <?php if ($banner['image'] && file_exists($banner['image'])): ?>
                        <img src="<?php echo htmlspecialchars($banner['image']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                    <?php else: ?>
                        <div style="width: 100%; height: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);"></div>
                    <?php endif; ?>
                    <div class="banner-overlay">
                        <div class="banner-content">
                            <h2><?php echo htmlspecialchars($banner['title']); ?></h2>
                            <p><?php echo htmlspecialchars($banner['description']); ?></p>
                            <?php if ($banner['link']): ?>
                                <a href="<?php echo htmlspecialchars($banner['link']); ?>" class="banner-btn">
                                    Lihat Selengkapnya <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php else: ?>
                                <a href="products.php" class="banner-btn">
                                    Lihat Produk <i class="fas fa-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <button class="slider-arrow slider-arrow-left" onclick="prevSlide()">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="slider-arrow slider-arrow-right" onclick="nextSlide()">
                <i class="fas fa-chevron-right"></i>
            </button>
            
            <div class="slider-controls">
                <?php foreach ($banners as $index => $banner): ?>
                <div class="slider-dot <?php echo $index === 0 ? 'active' : ''; ?>" onclick="goToSlide(<?php echo $index; ?>)"></div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="banner-slider-container" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
            <div class="banner-overlay">
                <div class="banner-content">
                    <h2>Selamat Datang di Lumbung Digital</h2>
                    <p>Ikan Segar Berkualitas Premium untuk Kebutuhan Anda</p>
                    <a href="products.php" class="banner-btn">
                        Lihat Koleksi Produk <i class="fas fa-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ARTICLES SECTION -->
        <?php if (count($articles) > 0): ?>
        <section class="articles-section">
            <div class="articles-container">
                <h2 class="section-title">Artikel & Informasi</h2>
                <p class="section-subtitle">Pelajari tips dan trik seputar ikan segar dan cara merawatnya</p>
                
                <div class="articles-grid">
                    <?php foreach ($articles as $article): ?>
                    <div class="article-card">
                        <div class="article-image">
                            <?php if ($article['image'] && file_exists($article['image'])): ?>
                                <img src="<?php echo htmlspecialchars($article['image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>">
                            <?php else: ?>
                                <i class="fas fa-newspaper" style="font-size: 3rem; color: #ccc;"></i>
                            <?php endif; ?>
                        </div>
                        <div class="article-body">
                            <h3 class="article-title"><?php echo htmlspecialchars($article['title']); ?></h3>
                            <p class="article-excerpt">
                                <?php 
                                    $excerpt = $article['excerpt'] ?? substr(strip_tags($article['content']), 0, 150);
                                    echo htmlspecialchars($excerpt . '...');
                                ?>
                            </p>
                            <div class="article-footer">
                                <span class="article-date">
                                    <i class="fas fa-calendar"></i>
                                    <?php echo date('d M Y', strtotime($article['created_at'])); ?>
                                </span>
                                <a href="#" class="article-link">
                                    Baca Selengkapnya <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
        <?php endif; ?>

        <!-- CTA SECTION -->
        <section class="cta-section">
            <div class="cta-content">
                <h2>Siap Berbelanja Ikan Berkualitas?</h2>
                <p>Temukan berbagai jenis ikan segar pilihan dengan harga terbaik di Lumbung Digital</p>
                <div class="cta-buttons">
                    <button class="cta-btn cta-btn-primary" onclick="window.location.href='products.php'">
                        <i class="fas fa-shopping-bag"></i> Belanja Sekarang
                    </button>
                    <button class="cta-btn cta-btn-secondary" onclick="window.location.href='#contact'">
                        <i class="fas fa-phone"></i> Hubungi Kami
                    </button>
                </div>
            </div>
        </section>

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
                    <p>📍 Kalimantan Selatan, Indonesia</p>
                </div>
                <div class="footer-section">
                    <h4>Ikuti Kami</h4>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-whatsapp"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 Lumbung Digital. Semua Hak Dilindungi.</p>
            </div>
        </footer>
    </div>

    <!-- SCRIPT FILES -->
    <script src="assets/js/loading.js"></script>
    <script src="assets/js/dropdown.js"></script>
    <script src="assets/js/cart.js"></script>
    
    <script>
        let currentSlide = 0;
        const totalSlides = document.querySelectorAll('.banner-slide').length;

        function updateSlider() {
            const slider = document.getElementById('bannerSlider');
            slider.style.transform = `translateX(-${currentSlide * 100}%)`;
            
            document.querySelectorAll('.slider-dot').forEach((dot, index) => {
                dot.classList.toggle('active', index === currentSlide);
            });
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateSlider();
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateSlider();
        }

        function goToSlide(index) {
            currentSlide = index;
            updateSlider();
        }

        // Auto-advance slider every 5 seconds
        if (totalSlides > 1) {
            setInterval(nextSlide, 5000);
        }

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
    </script>
</body>
</html>
