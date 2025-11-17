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

// Get all users dengan filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT * FROM users WHERE role != 'admin'";
if ($filter === 'active') {
    $query .= " AND is_active = 1";
} else if ($filter === 'inactive') {
    $query .= " AND is_active = 0";
}

if (!empty($search)) {
    $search_term = '%' . $search . '%';
    $query .= " AND (name LIKE ? OR email LIKE ?)";
}

$query .= " ORDER BY created_at DESC";

$stmt = $connection->prepare($query);
if (!empty($search)) {
    $stmt->bind_param("ss", $search_term, $search_term);
}
$stmt->execute();
$all_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Pengguna - Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
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
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .filter-bar input {
            padding: 10px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            flex: 1;
            min-width: 200px;
        }
        .filter-bar a {
            padding: 10px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
        }
        .filter-bar a.active {
            background: #c92a2a;
            color: white;
        }
        .users-grid {
            display: grid;
            gap: 20px;
        }
        .user-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .user-info {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff6b35, #c92a2a);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .user-details h3 {
            color: #006994;
            margin-bottom: 5px;
        }
        .user-details p {
            color: #666;
            font-size: 13px;
            margin: 3px 0;
        }
        .user-actions {
            display: flex;
            gap: 10px;
        }
        .btn-action {
            padding: 8px 15px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-action:hover {
            transform: scale(1.05);
        }
        .btn-warn {
            background: #ffc107;
            color: #333;
        }
        .btn-delete {
            background: #c92a2a;
            color: white;
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
                <li><a href="admin-users.php" class="active"><i class="fas fa-users"></i> Pengguna</a></li>
                <li><a href="admin-products.php"><i class="fas fa-box"></i> Produk</a></li>
                <li><a href="admin-warnings.php"><i class="fas fa-exclamation-circle"></i> Peringatan</a></li>
                <li><a href="admin-logs.php"><i class="fas fa-history"></i> Log Aktivitas</a></li>
                <li><a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="admin-main">
            <h1 style="color: #c92a2a; margin-bottom: 20px;">Manajemen Pengguna</h1>
            
            <!-- FILTER -->
            <div class="filter-bar">
                <form style="display: flex; gap: 15px; flex: 1;">
                    <input type="text" name="search" placeholder="Cari nama atau email..." value="<?php echo $search; ?>">
                    <a href="?filter=all" class="<?php echo $filter === 'all' ? 'active' : ''; ?>">Semua</a>
                    <a href="?filter=active" class="<?php echo $filter === 'active' ? 'active' : ''; ?>">Aktif</a>
                    <a href="?filter=inactive" class="<?php echo $filter === 'inactive' ? 'active' : ''; ?>">Nonaktif</a>
                    <button type="submit" style="background: #c92a2a; color: white; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; font-weight: 600;">Cari</button>
                </form>
            </div>
            
            <!-- USER LIST -->
            <div class="users-grid">
                <?php foreach ($all_users as $user): ?>
                    <div class="user-card">
                        <div class="user-info">
                            <div class="user-avatar">
                                <i class="fas fa-<?php echo $user['role'] === 'seller' ? 'store' : 'user'; ?>"></i>
                            </div>
                            <div class="user-details">
                                <h3><?php echo htmlspecialchars($user['name']); ?></h3>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong>Tipe:</strong> <?php echo ucfirst($user['role']); ?></p>
                                <p><strong>Status:</strong> <?php echo $user['is_active'] ? '✓ Aktif' : '✗ Nonaktif'; ?></p>
                                <p><strong>Terdaftar:</strong> <?php echo date('d M Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="user-actions">
                            <button class="btn-action btn-warn" onclick="alert('Detail pengguna akan ditambahkan')">
                                <i class="fas fa-info-circle"></i> Detail
                            </button>
                            <button class="btn-action btn-warn" onclick="alert('Fitur peringatan akan ditambahkan')">
                                <i class="fas fa-exclamation-triangle"></i> Peringatan
                            </button>
                            <form style="display: inline;" method="POST" action="admin-dashboard.php" onsubmit="return confirm('Hapus akun ini?')">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                <button type="submit" name="delete_user" class="btn-action btn-delete">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>
