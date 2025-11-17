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

// Get statistics
$stmt = $connection->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch_assoc()['total'];

$stmt = $connection->query("SELECT COUNT(*) as total FROM seller_products");
$total_products = $stmt->fetch_assoc()['total'];

$stmt = $connection->query("SELECT COUNT(*) as total FROM account_warnings");
$total_warnings = $stmt->fetch_assoc()['total'];

// Get all users
$stmt = $connection->prepare("SELECT * FROM users WHERE role != 'admin' ORDER BY created_at DESC LIMIT 100");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent warnings
$stmt = $connection->prepare("
    SELECT aw.*, u.name as user_name, a.name as admin_name
    FROM account_warnings aw
    JOIN users u ON aw.user_id = u.id
    LEFT JOIN users a ON aw.admin_id = a.id
    ORDER BY aw.created_at DESC
    LIMIT 20
");
$stmt->execute();
$warnings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Handle warning action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_warning'])) {
    $user_id = intval($_POST['user_id']);
    $reason = trim($_POST['reason']);
    $severity = trim($_POST['severity']);
    $details = trim($_POST['details'] ?? '');
    
    if ($severity === 'banned' || $severity === 'suspended') {
        // Deactivate user
        $stmt = $connection->prepare("UPDATE users SET is_active = 0 WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
    }
    
    // Add warning
    $stmt = $connection->prepare("INSERT INTO account_warnings (user_id, admin_id, reason, severity, details) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisss", $user_id, $admin_id, $reason, $severity, $details);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Peringatan berhasil ditambahkan';
    }
}

// Handle delete product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    $reason = trim($_POST['reason'] ?? 'Melanggar kebijakan');
    
    // Add warning to seller
    $stmt = $connection->prepare("SELECT seller_id FROM seller_products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result) {
        $seller_id = $result['seller_id'];
        
        // Add warning
        $stmt = $connection->prepare("INSERT INTO account_warnings (user_id, admin_id, reason, severity, details) VALUES (?, ?, ?, 'warning', ?)");
        $stmt->bind_param("iiss", $seller_id, $admin_id, $reason, $reason);
        $stmt->execute();
    }
    
    // Delete product
    $stmt = $connection->prepare("DELETE FROM seller_products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    
    // Log action
    $stmt = $connection->prepare("INSERT INTO admin_logs (admin_id, action, target_product_id, details) VALUES (?, 'delete_product', ?, ?)");
    $stmt->bind_param("iis", $admin_id, $product_id, $reason);
    $stmt->execute();
    
    $_SESSION['message'] = 'Produk berhasil dihapus';
}

// Handle delete user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = intval($_POST['user_id']);
    $reason = trim($_POST['reason'] ?? 'Melanggar kebijakan');
    
    // Log action
    $stmt = $connection->prepare("INSERT INTO admin_logs (admin_id, action, target_user_id, details) VALUES (?, 'delete_user', ?, ?)");
    $stmt->bind_param("iis", $admin_id, $user_id, $reason);
    $stmt->execute();
    
    // Delete user
    $stmt = $connection->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
    $stmt->bind_param("i", $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['message'] = 'Akun berhasil dihapus';
    }
}

// Get admin logs
$stmt = $connection->prepare("
    SELECT al.*, u.name as admin_name, users.name as target_user_name
    FROM admin_logs al
    JOIN users u ON al.admin_id = u.id
    LEFT JOIN users ON al.target_user_id = users.id
    ORDER BY al.created_at DESC
    LIMIT 20
");
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
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
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            border-left: 4px solid #c92a2a;
        }
        
        .stat-card h4 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .stat-card .number {
            font-size: 28px;
            font-weight: bold;
            color: #c92a2a;
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
        
        .users-table,
        .warnings-table,
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        
        .users-table thead th,
        .warnings-table thead th,
        .logs-table thead th {
            background: #f5f7fa;
            padding: 12px;
            text-align: left;
            color: #c92a2a;
            font-weight: 600;
            border-bottom: 2px solid #ff6b35;
        }
        
        .users-table tbody td,
        .warnings-table tbody td,
        .logs-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .users-table tbody tr:hover {
            background: #f9f9f9;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        .status-active {
            background: #efe;
            color: #3c3;
        }
        
        .status-inactive {
            background: #fee;
            color: #c33;
        }
        
        .severity-warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .severity-suspended {
            background: #f8d7da;
            color: #721c24;
        }
        
        .severity-banned {
            background: #f5c6cb;
            color: #721c24;
            font-weight: bold;
        }
        
        .btn-small {
            padding: 6px 12px;
            margin: 0 3px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 11px;
            transition: all 0.3s;
        }
        
        .btn-warn {
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
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h3 {
            color: #c92a2a;
        }
        
        .close {
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            color: #666;
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
            transition: border-color 0.3s;
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
            border: 1px solid #cfc;
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
            .stats-grid {
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
                <li><a href="admin-dashboard.php" class="active"><i class="fas fa-th-large"></i> Dashboard</a></li>
                <!-- Added link to banner management -->
                <li><a href="admin-banners.php"><i class="fas fa-images"></i> Banner & Artikel</a></li>
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
                <div>
                    <h1>Admin Dashboard</h1>
                    <p style="color: #666;">Selamat datang, Admin</p>
                </div>
            </div>
            
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $_SESSION['message']; ?>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>
            
            <!-- STATISTICS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <h4>Total Pengguna</h4>
                    <div class="number"><?php echo $total_users; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Total Produk</h4>
                    <div class="number"><?php echo $total_products; ?></div>
                </div>
                <div class="stat-card">
                    <h4>Total Peringatan</h4>
                    <div class="number"><?php echo $total_warnings; ?></div>
                </div>
            </div>
            
            <!-- PERINGATAN TERBARU -->
            <div class="section">
                <h2>Peringatan Terbaru</h2>
                <?php if (count($warnings) > 0): ?>
                    <table class="warnings-table">
                        <thead>
                            <tr>
                                <th>Pengguna</th>
                                <th>Alasan</th>
                                <th>Tingkat</th>
                                <th>Tanggal</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($warnings, 0, 10) as $warning): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($warning['user_name']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($warning['reason'], 0, 30)); ?></td>
                                    <td>
                                        <span class="status-badge severity-<?php echo $warning['severity']; ?>">
                                            <?php echo ucfirst($warning['severity']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y H:i', strtotime($warning['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-small btn-warn" onclick="viewWarning(<?php echo $warning['id']; ?>)">
                                            <i class="fas fa-eye"></i> Lihat
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">Tidak ada peringatan</p>
                <?php endif; ?>
            </div>
            
            <!-- PENGGUNA TERBARU -->
            <div class="section">
                <h2>Pengguna Terbaru</h2>
                <?php if (count($users) > 0): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Tipe</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($users, 0, 10) as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['name']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo ucfirst($user['role']); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $user['is_active'] ? 'active' : 'inactive'; ?>">
                                            <?php echo $user['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <button class="btn-small btn-warn" onclick="openWarningModal(<?php echo $user['id']; ?>)">
                                            <i class="fas fa-exclamation-triangle"></i> Peringatan
                                        </button>
                                        <form style="display: inline;" method="POST" onsubmit="return confirm('Apakah Anda yakin menghapus akun ini?');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="btn-small btn-delete" onclick="return confirm('Hapus akun ini secara permanen?')">
                                                <i class="fas fa-trash"></i> Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">Tidak ada pengguna</p>
                <?php endif; ?>
            </div>
            
            <!-- LOG AKTIVITAS ADMIN -->
            <div class="section">
                <h2>Log Aktivitas Admin</h2>
                <?php if (count($logs) > 0): ?>
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Admin</th>
                                <th>Aksi</th>
                                <th>Target</th>
                                <th>Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($log['admin_name'] ?? '-'); ?></td>
                                    <td><strong><?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?></strong></td>
                                    <td><?php echo htmlspecialchars($log['target_user_name'] ?? 'Product #' . $log['target_product_id']); ?></td>
                                    <td><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">Tidak ada log aktivitas</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
    
    <!-- MODAL TAMBAH PERINGATAN -->
    <div id="warningModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Tambah Peringatan Akun</h3>
                <span class="close" onclick="closeWarningModal()">&times;</span>
            </div>
            <form method="POST">
                <input type="hidden" id="warning_user_id" name="user_id">
                
                <div class="form-group">
                    <label for="reason">Alasan Peringatan</label>
                    <input type="text" id="reason" name="reason" required>
                </div>
                
                <div class="form-group">
                    <label for="severity">Tingkat Keparahan</label>
                    <select id="severity" name="severity" required>
                        <option value="warning">Peringatan</option>
                        <option value="suspended">Suspend (Sementara)</option>
                        <option value="banned">Ban (Permanen)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="details">Detail Peringatan</label>
                    <textarea id="details" name="details" placeholder="Jelaskan detail pelanggaran..."></textarea>
                </div>
                
                <button type="submit" name="add_warning" class="btn-submit">Tambah Peringatan</button>
            </form>
        </div>
    </div>
    
    <script>
        function openWarningModal(userId) {
            document.getElementById('warning_user_id').value = userId;
            document.getElementById('warningModal').classList.add('show');
        }
        
        function closeWarningModal() {
            document.getElementById('warningModal').classList.remove('show');
        }
        
        function viewWarning(warningId) {
            alert('Detail warning #' + warningId);
        }
        
        window.onclick = function(event) {
            const modal = document.getElementById('warningModal');
            if (event.target === modal) {
                closeWarningModal();
            }
        }
    </script>
</body>
</html>
