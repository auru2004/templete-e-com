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

// Handle add banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_banner'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $link = trim($_POST['link'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $description = str_replace(['PNG (Ukuran: Min 1200x400px)', 'Format: JPG, PNG, GIF, WebP (Maksimal 5MB)', 'Format:', 'Ukuran:', 'Min', 'Max', 'MB'], '', $description);
    $description = trim($description);
    
    $image = '';
    
    // Handle image upload
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $upload_dir = 'uploads/banners/';
        
        if (!is_dir($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                $_SESSION['error'] = 'Gagal membuat folder upload. Hubungi administrator.';
            }
        }
        
        $file_info = $_FILES['banner_image'];
        $file_error = $file_info['error'];
        $file_size = $file_info['size'];
        $max_size = 5 * 1024 * 1024; // 5MB
        
        if ($file_size > $max_size) {
            $_SESSION['error'] = 'Ukuran file terlalu besar. Maksimal 5MB.';
        } else {
            $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
            $allowed_ext = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            
            if (!in_array($file_ext, $allowed_ext)) {
                $_SESSION['error'] = 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.';
            } else {
                $file_name = time() . '_' . uniqid() . '.' . $file_ext;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($file_info['tmp_name'], $file_path)) {
                    $image = $file_path;
                    $_SESSION['debug'] = "File uploaded successfully to: $file_path";
                } else {
                    $_SESSION['error'] = 'Gagal mengunggah file. Periksa permission folder.';
                }
            }
        }
    }
    
    // Only insert if there's no error and we have an image or at least title
    if (!isset($_SESSION['error'])) {
        $stmt = $connection->prepare("INSERT INTO banners (title, description, image, link, display_order, created_by) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            $_SESSION['error'] = 'Database error: ' . $connection->error;
        } else {
            $stmt->bind_param("ssissi", $title, $description, $image, $link, $display_order, $admin_id);
            
            if ($stmt->execute()) {
                $_SESSION['message'] = 'Banner berhasil ditambahkan';
            } else {
                $_SESSION['error'] = 'Gagal menambahkan banner: ' . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Handle update banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_banner'])) {
    $banner_id = intval($_POST['banner_id']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $description = str_replace(['PNG (Ukuran: Min 1200x400px)', 'Format: JPG, PNG, GIF, WebP (Maksimal 5MB)', 'Format:', 'Ukuran:', 'Min', 'Max', 'MB'], '', $description);
    $description = trim($description);
    $link = trim($_POST['link'] ?? '');
    $display_order = intval($_POST['display_order'] ?? 0);
    $is_active = intval($_POST['is_active'] ?? 0);
    
    $image = null;
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $upload_dir = 'uploads/banners/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_info = $_FILES['banner_image'];
        $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        $file_name = time() . '_' . uniqid() . '.' . $file_ext;
        $file_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($file_info['tmp_name'], $file_path)) {
            $image = $file_path;
            
            // Delete old image if exists
            $stmt_old = $connection->prepare("SELECT image FROM banners WHERE id = ?");
            $stmt_old->bind_param("i", $banner_id);
            $stmt_old->execute();
            $result = $stmt_old->get_result()->fetch_assoc();
            if ($result && $result['image'] && file_exists($result['image'])) {
                unlink($result['image']);
            }
            $stmt_old->close();
        }
    }
    
    if ($image) {
        $stmt = $connection->prepare("UPDATE banners SET title = ?, description = ?, image = ?, link = ?, display_order = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("ssssiii", $title, $description, $image, $link, $display_order, $is_active, $banner_id);
    } else {
        $stmt = $connection->prepare("UPDATE banners SET title = ?, description = ?, link = ?, display_order = ?, is_active = ? WHERE id = ?");
        $stmt->bind_param("sssiii", $title, $description, $link, $display_order, $is_active, $banner_id);
    }
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Banner berhasil diperbarui';
    } else {
        $_SESSION['error'] = 'Gagal memperbarui banner: ' . $stmt->error;
    }
    $stmt->close();
}

// Handle delete banner
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_banner'])) {
    $banner_id = intval($_POST['banner_id']);
    
    $stmt = $connection->prepare("SELECT image FROM banners WHERE id = ?");
    $stmt->bind_param("i", $banner_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result && $result['image'] && file_exists($result['image'])) {
        unlink($result['image']);
    }
    $stmt->close();
    
    $stmt = $connection->prepare("DELETE FROM banners WHERE id = ?");
    $stmt->bind_param("i", $banner_id);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['message'] = 'Banner berhasil dihapus';
}

// Get all banners
$stmt = $connection->prepare("SELECT * FROM banners ORDER BY display_order ASC");
$stmt->execute();
$banners = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Banner - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
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
            color: #c92a2a;
            font-size: 24px;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #ff6b35, #c92a2a);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(201, 42, 42, 0.3);
        }
        
        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }
        
        .section h2 {
            color: #c92a2a;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #ff6b35;
            padding-bottom: 10px;
        }
        
        .banner-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .banner-card {
            border: 2px solid #f0f0f0;
            border-radius: 12px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .banner-card:hover {
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            border-color: #ff6b35;
        }
        
        .banner-image {
            width: 100%;
            height: 200px;
            background: #f5f5f5;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }
        
        .banner-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .banner-info {
            padding: 15px;
        }
        
        .banner-info h3 {
            color: #333;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .banner-info p {
            color: #666;
            font-size: 13px;
            margin-bottom: 10px;
        }
        
        .banner-actions {
            display: flex;
            gap: 5px;
        }
        
        .btn-small {
            padding: 6px 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.3s;
            flex: 1;
        }
        
        .btn-edit {
            background: #ffc107;
            color: #333;
        }
        
        .btn-delete {
            background: #c92a2a;
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
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 13px;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-family: inherit;
            font-size: 13px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #c92a2a;
        }
        
        .btn-submit {
            background: linear-gradient(135deg, #ff6b35, #c92a2a);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            font-size: 13px;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
        }
        
        .alert-error {
            background: #fee;
            color: #c33;
        }
        
        .alert-info {
            background: #e7f3ff;
            color: #004085;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .badge-active {
            background: #efe;
            color: #3c3;
        }
        
        .badge-inactive {
            background: #f0f0f0;
            color: #666;
        }
        
        .file-preview {
            margin-top: 10px;
            padding: 10px;
            background: #f5f5f5;
            border-radius: 5px;
            display: none;
        }
        
        .file-preview img {
            max-width: 100%;
            max-height: 150px;
            border-radius: 5px;
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
            .banner-grid {
                grid-template-columns: 1fr;
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
                <li><a href="admin-banners.php" class="active"><i class="fas fa-images"></i> Banner & Artikel</a></li>
                <li><a href="admin-users.php"><i class="fas fa-users"></i> Pengguna</a></li>
                <li><a href="admin-products.php"><i class="fas fa-box"></i> Produk</a></li>
                <li><a href="admin-warnings.php"><i class="fas fa-exclamation-circle"></i> Peringatan</a></li>
                <li><a href="admin-logs.php"><i class="fas fa-history"></i> Log Aktivitas</a></li>
                <li><a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="admin-main">
            <div class="header-top">
                <h1>Kelola Banner & Artikel</h1>
                <button class="btn-add" onclick="openBannerModal()">
                    <i class="fas fa-plus"></i> Tambah Banner
                </button>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['debug'])): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> <?php echo $_SESSION['debug']; unset($_SESSION['debug']); ?>
                </div>
            <?php endif; ?>
            
            <!-- BANNERS SECTION -->
            <div class="section">
                <h2><i class="fas fa-images"></i> Daftar Banner Promosi</h2>
                
                <?php if (count($banners) > 0): ?>
                    <div class="banner-grid">
                        <?php foreach ($banners as $banner): ?>
                            <div class="banner-card">
                                <div class="banner-image">
                                    <?php if ($banner['image'] && file_exists($banner['image'])): ?>
                                        <img src="<?php echo htmlspecialchars($banner['image']); ?>" alt="<?php echo htmlspecialchars($banner['title']); ?>">
                                    <?php else: ?>
                                        <i class="fas fa-image" style="font-size: 3rem; color: #ccc;"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="banner-info">
                                    <h3><?php echo htmlspecialchars($banner['title']); ?></h3>
                                    <p><?php echo substr(htmlspecialchars($banner['description']), 0, 50); ?>...</p>
                                    <p>
                                        <span class="badge <?php echo $banner['is_active'] ? 'badge-active' : 'badge-inactive'; ?>">
                                            <?php echo $banner['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </p>
                                    <div class="banner-actions">
                                        <button class="btn-small btn-edit" onclick="editBanner(<?php echo htmlspecialchars(json_encode($banner)); ?>)">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <form style="display: inline; flex: 1;" method="POST" onsubmit="return confirm('Hapus banner ini?');">
                                            <input type="hidden" name="banner_id" value="<?php echo $banner['id']; ?>">
                                            <button type="submit" name="delete_banner" class="btn-small btn-delete" style="width: 100%;">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">Belum ada banner. Klik tombol "Tambah Banner" untuk membuat yang baru.</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- MODAL TAMBAH/EDIT BANNER -->
    <div id="bannerModal" class="modal">
        <div class="modal-content">
            <h3 style="color: #c92a2a; margin-bottom: 20px;"><i class="fas fa-image"></i> <span id="modal-title">Tambah Banner</span></h3>
            
            <form method="POST" enctype="multipart/form-data" id="bannerForm">
                <input type="hidden" id="banner_id" name="banner_id">
                
                <div class="form-group">
                    <label for="title">Judul Banner</label>
                    <input type="text" id="title" name="title" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Deskripsi</label>
                    <textarea id="description" name="description" required></textarea>
                </div>
                
                <div class="form-group">
                    <label for="banner_image">Gambar Banner</label>
                    <input type="file" id="banner_image" name="banner_image" accept="image/*" onchange="previewImage(this)">
                    <div id="file-preview" class="file-preview"></div>
                </div>
                
                <div class="form-group">
                    <label for="link">Link Target (Opsional)</label>
                    <input type="text" id="link" name="link" placeholder="https://contoh.com">
                </div>
                
                <div class="form-group">
                    <label for="display_order">Urutan Tampilan</label>
                    <input type="number" id="display_order" name="display_order" value="0">
                </div>
                
                <div class="form-group" id="active-group" style="display: none;">
                    <label>
                        <input type="checkbox" id="is_active" name="is_active" value="1">
                        Aktifkan Banner
                    </label>
                </div>
                
                <button type="submit" id="submit-btn" name="add_banner" class="btn-submit">Tambah Banner</button>
                <button type="button" class="btn-submit" style="background: #666; margin-top: 10px;" onclick="closeBannerModal()">Batal</button>
            </form>
        </div>
    </div>
    
    <script>
        function openBannerModal() {
            document.getElementById('bannerModal').classList.add('show');
            document.getElementById('modal-title').textContent = 'Tambah Banner';
            document.getElementById('bannerForm').reset();
            document.getElementById('banner_id').value = '';
            document.getElementById('submit-btn').name = 'add_banner';
            document.getElementById('submit-btn').textContent = 'Tambah Banner';
            document.getElementById('active-group').style.display = 'none';
            document.getElementById('file-preview').style.display = 'none';
        }
        
        function editBanner(bannerData) {
            document.getElementById('bannerModal').classList.add('show');
            document.getElementById('modal-title').textContent = 'Edit Banner';
            document.getElementById('banner_id').value = bannerData.id;
            document.getElementById('title').value = bannerData.title;
            document.getElementById('description').value = bannerData.description;
            document.getElementById('link').value = bannerData.link;
            document.getElementById('display_order').value = bannerData.display_order;
            document.getElementById('is_active').checked = bannerData.is_active == 1;
            document.getElementById('submit-btn').name = 'update_banner';
            document.getElementById('submit-btn').textContent = 'Update Banner';
            document.getElementById('active-group').style.display = 'block';
            document.getElementById('file-preview').style.display = 'none';
        }
        
        function closeBannerModal() {
            document.getElementById('bannerModal').classList.remove('show');
        }
        
        function previewImage(input) {
            const preview = document.getElementById('file-preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = '<img src="' + e.target.result + '" alt="Preview">';
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('bannerModal');
            if (event.target === modal) {
                closeBannerModal();
            }
        }
    </script>
</body>
</html>
