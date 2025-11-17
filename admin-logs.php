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

// Get admin logs
$stmt = $connection->prepare("
    SELECT al.*, u.name as admin_name, users.name as target_user_name
    FROM admin_logs al
    JOIN users u ON al.admin_id = u.id
    LEFT JOIN users ON al.target_user_id = users.id
    ORDER BY al.created_at DESC
    LIMIT 100
");
$stmt->execute();
$logs = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Aktivitas Admin - Admin</title>
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
        .section {
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }
        .section h1 {
            color: #c92a2a;
            margin-bottom: 20px;
        }
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .logs-table thead th {
            background: #f5f7fa;
            padding: 12px;
            text-align: left;
            color: #c92a2a;
            font-weight: 600;
            border-bottom: 2px solid #ff6b35;
        }
        .logs-table tbody td {
            padding: 12px;
            border-bottom: 1px solid #e0e0e0;
        }
        .logs-table tbody tr:hover {
            background: #f9f9f9;
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
                <li><a href="admin-users.php"><i class="fas fa-users"></i> Pengguna</a></li>
                <li><a href="admin-products.php"><i class="fas fa-box"></i> Produk</a></li>
                <li><a href="admin-warnings.php"><i class="fas fa-exclamation-circle"></i> Peringatan</a></li>
                <li><a href="admin-logs.php" class="active"><i class="fas fa-history"></i> Log Aktivitas</a></li>
                <li><a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="admin-main">
            <div class="section">
                <h1>Log Aktivitas Admin</h1>
                
                <?php if (count($logs) > 0): ?>
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th>Tanggal & Waktu</th>
                                <th>Admin</th>
                                <th>Aksi</th>
                                <th>Target</th>
                                <th>Detail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?php echo date('d M Y H:i:s', strtotime($log['created_at'])); ?></td>
                                    <td><?php echo htmlspecialchars($log['admin_name']); ?></td>
                                    <td><strong><?php echo ucfirst(str_replace('_', ' ', $log['action'])); ?></strong></td>
                                    <td>
                                        <?php 
                                        if ($log['target_user_name']) {
                                            echo htmlspecialchars($log['target_user_name']);
                                        } else if ($log['target_product_id']) {
                                            echo 'Product #' . $log['target_product_id'];
                                        } else {
                                            echo '-';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars(substr($log['details'], 0, 50)); ?></td>
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
</body>
</html>
