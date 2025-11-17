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
        
        $email = trim($data['email'] ?? '');
        $password = trim($data['password'] ?? '');
        
        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'message' => 'Email dan password harus diisi']);
            exit;
        }
        
        $connection = Database::getInstance()->connect();
        $auth = new Auth($connection);
        
        $result = $auth->login($email, $password);
        http_response_code($result['success'] ? 200 : 401);
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
