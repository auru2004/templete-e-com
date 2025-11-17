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
$user = $auth->getCurrentUser();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $whatsapp = $_POST['whatsapp'] ?? '';
    $bio = $_POST['bio'] ?? '';
    $city = $_POST['city'] ?? '';
    $address = $_POST['address'] ?? '';
    
    $stmt = $connection->prepare("UPDATE users SET whatsapp = ?, bio = ?, city = ?, address = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $whatsapp, $bio, $city, $address, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        $_SESSION['update_success'] = true;
        header('Location: profile.php');
        exit;
    }
}

// Handle profile image upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['profile_image'])) {
    $file = $_FILES['profile_image'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif'];
    
    if (in_array($file['type'], $allowed) && $file['size'] <= 2000000) {
        $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $upload_path = 'uploads/' . $filename;
        
        if (!is_dir('uploads')) {
            mkdir('uploads', 0777, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            $stmt = $connection->prepare("UPDATE users SET profile_image = ? WHERE id = ?");
            $stmt->bind_param("si", $upload_path, $_SESSION['user_id']);
            $stmt->execute();
            
            $_SESSION['update_success'] = true;
            header('Location: profile.php');
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #006994, #00a8d8);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            border: 4px solid white;
        }
        
        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .profile-info h2 {
            font-size: 28px;
            margin-bottom: 5px;
        }
        
        .profile-info p {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .profile-card {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        
        .profile-card h3 {
            color: #006994;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #00a8d8;
            padding-bottom: 10px;
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
        .form-group textarea:focus {
            outline: none;
            border-color: #00a8d8;
            box-shadow: 0 0 0 3px rgba(0, 168, 216, 0.1);
        }
        
        .btn-save {
            background: linear-gradient(135deg, #006994, #00a8d8);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
        }
        
        .btn-logout {
            background: #ff6b35;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
        }
        
        .whatsapp-link {
            display: inline-block;
            background: #25d366;
            color: white;
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            margin-top: 10px;
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
                <a href="api/logout.php" class="nav-btn nav-btn-login"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </nav>
    
    <div class="profile-container">
        <?php if (isset($_SESSION['update_success'])): ?>
            <div class="alert-success">
                <i class="fas fa-check-circle"></i> Profil berhasil diperbarui!
            </div>
            <?php unset($_SESSION['update_success']); ?>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-avatar">
                <?php if ($user['profile_image']): ?>
                    <img src="<?php echo $user['profile_image']; ?>" alt="<?php echo $user['name']; ?>">
                <?php else: ?>
                    <i class="fas fa-user" style="font-size: 60px;"></i>
                <?php endif; ?>
            </div>
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['name']); ?></h2>
                <p><?php echo htmlspecialchars($user['email']); ?></p>
                <p>Tipe Akun: <strong><?php echo ucfirst($user['role']); ?></strong></p>
                <?php if ($user['whatsapp']): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $user['whatsapp']); ?>" target="_blank" class="whatsapp-link">
                        <i class="fab fa-whatsapp"></i> Chat WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="profile-card">
            <h3>Upload Foto Profil</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label for="profile_image">Pilih Foto (Max 2MB)</label>
                    <input type="file" id="profile_image" name="profile_image" accept="image/*" required>
                </div>
                <button type="submit" class="btn-save">
                    <i class="fas fa-upload"></i> Upload Foto
                </button>
            </form>
        </div>
        
        <div class="profile-card">
            <h3>Edit Profil</h3>
            <form method="POST">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                </div>
                
                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="whatsapp">Nomor WhatsApp</label>
                    <input type="tel" id="whatsapp" name="whatsapp" placeholder="Contoh: +62-812-3456-7890" value="<?php echo htmlspecialchars($user['whatsapp'] ?? ''); ?>">
                    <small style="color: #666;">Nomor ini akan ditampilkan agar pembeli bisa menghubungi Anda</small>
                </div>
                
                <div class="form-group">
                    <label for="city">Kota</label>
                    <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($user['city'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Alamat</label>
                    <textarea id="address" name="address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="bio">Bio / Deskripsi Singkat</label>
                    <textarea id="bio" name="bio" placeholder="Ceritakan tentang Anda atau toko Anda..."><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                </div>
                
                <button type="submit" name="update_profile" class="btn-save">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
        
        <div class="profile-card">
            <h3>Akun</h3>
            <a href="api/logout.php" class="btn-logout">
                <i class="fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </div>
</body>
</html>
