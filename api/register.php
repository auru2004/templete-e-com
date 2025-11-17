<?php
session_start();
header('Content-Type: application/json');

try {
    $basePath = dirname(dirname(__FILE__));
    require_once $basePath . '/includes/config.php';
    require_once $basePath . '/includes/database.php';
    require_once $basePath . '/includes/auth.php';
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Data JSON tidak valid']);
            exit;
        }
        
        $name = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        $confirm_password = trim($data['confirm_password'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $role = trim($data['role'] ?? 'buyer');
        
        // Validasi
        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Semua field harus diisi']);
            exit;
        }
        
        if ($password !== $confirm_password) {
            echo json_encode(['success' => false, 'message' => 'Password tidak cocok']);
            exit;
        }
        
        if (strlen($password) < 6) {
            echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter']);
            exit;
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Email tidak valid']);
            exit;
        }
        
        if (!in_array($role, ['buyer', 'seller'])) {
            $role = 'buyer';
        }
        
        $connection = Database::getInstance()->connect();
        $auth = new Auth($connection);
        
        $result = $auth->register($name, $email, $password, $phone, $role);
        http_response_code($result['success'] ? 201 : 400);
        echo json_encode($result);
    } else {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method tidak allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}
?>
