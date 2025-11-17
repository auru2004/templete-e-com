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

// Get all warnings
$stmt = $connection->prepare("
    SELECT aw.*, u.name as user_name, a.name as admin_name
    FROM account_warnings aw
    JOIN users u ON aw.user_id = u.id
    LEFT JOIN users a ON aw.admin_id = a.id
    ORDER BY aw.created_at DESC
");
$stmt->execute();
$warnings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Peringatan - Admin</title>
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
        .warnings-grid {
            display: grid;
            gap: 15px;
        }
        .warning-card {
            border-left: 4px solid;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        .warning-card.warning {
            border-left-color: #ffc107;
            background: #fff8e1;
        }
        .warning-card.suspended {
            border-left-color: #ff9800;
            background: #ffe0b2;
        }
        .warning-card.banned {
            border-left-color: #f44336;
            background: #ffebee;
        }
        .warning-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }
        .warning-header h3 {
            color: #333;
            font-size: 15px;
        }
        .severity-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .severity-warning {
            background: #ffc107;
            color: #333;
        }
        .severity-suspended {
            background: #ff9800;
            color: white;
        }
        .severity-banned {
            background: #f44336;
            color: white;
        }
        .warning-info {
            font-size: 13px;
            color: #666;
            margin: 8px 0;
        }
        .warning-reason {
            background: white;
            padding: 10px;
            border-radius: 5px;
            margin: 10px 0;
            font-size: 13px;
            font-weight: 600;
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
                <li><a href="admin-warnings.php" class="active"><i class="fas fa-exclamation-circle"></i> Peringatan</a></li>
                <li><a href="admin-logs.php"><i class="fas fa-history"></i> Log Aktivitas</a></li>
                <li><a href="api/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>
        
        <!-- MAIN CONTENT -->
        <main class="admin-main">
            <div class="section">
                <h1>Manajemen Peringatan Akun</h1>
                
                <?php if (count($warnings) > 0): ?>
                    <div class="warnings-grid">
                        <?php foreach ($warnings as $warning): ?>
                            <div class="warning-card <?php echo $warning['severity']; ?>">
                                <div class="warning-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($warning['user_name']); ?></h3>
                                        <div class="warning-info">
                                            Oleh: <?php echo htmlspecialchars($warning['admin_name'] ?? 'System'); ?> | 
                                            <?php echo date('d M Y H:i', strtotime($warning['created_at'])); ?>
                                        </div>
                                    </div>
                                    <span class="severity-badge severity-<?php echo $warning['severity']; ?>">
                                        <?php echo ucfirst($warning['severity']); ?>
                                    </span>
                                </div>
                                <div class="warning-reason">
                                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($warning['reason']); ?>
                                </div>
                                <?php if ($warning['details']): ?>
                                    <div class="warning-info">
                                        <strong>Detail:</strong> <?php echo htmlspecialchars($warning['details']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #999; padding: 40px;">Tidak ada peringatan</p>
                <?php endif; ?>
            </div>
        </main>
    </div>
</body>
</html>
