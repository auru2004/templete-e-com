<?php
session_start();
header('Content-Type: application/json');

require_once '../includes/config.php';
require_once '../includes/database.php';

try {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['count' => 0]);
        exit;
    }

    $db = Database::getInstance();
    $result = $db->query(
        "SELECT COUNT(*) as count FROM buyer_cart WHERE buyer_id = ?",
        [$_SESSION['user_id']]
    );
    
    $row = $db->fetch($result);
    echo json_encode(['count' => $row['count'] ?? 0]);
    
} catch (Exception $e) {
    echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
}
?>
