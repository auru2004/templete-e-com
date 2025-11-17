<?php
// DIAGNOSTIC SCRIPT - Check why products are not showing
// Access: http://localhost/lumbungdigital/api/diagnose-products.php

session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance();
    $connection = $db->connect();
    
    $results = [
        'timestamp' => date('Y-m-d H:i:s'),
        'diagnostics' => []
    ];
    
    // 1. Check if seller_products table exists
    $checkTable = $connection->query("SHOW TABLES LIKE 'seller_products'");
    $results['diagnostics']['table_exists'] = $checkTable->num_rows > 0 ? 'YES' : 'NO - TABLE NOT FOUND!';
    
    // 2. Count products in seller_products
    if ($checkTable->num_rows > 0) {
        $countResult = $connection->query("SELECT COUNT(*) as total FROM seller_products");
        $count = $countResult->fetch_assoc()['total'];
        $results['diagnostics']['total_products'] = $count;
        
        // 3. Check active products
        $activeResult = $connection->query("SELECT COUNT(*) as total FROM seller_products WHERE status = 'active'");
        $active = $activeResult->fetch_assoc()['total'];
        $results['diagnostics']['active_products'] = $active;
        
        // 4. List all products
        $allProducts = $connection->query("SELECT id, seller_id, name, price, status, created_at FROM seller_products ORDER BY created_at DESC");
        $results['products'] = [];
        while ($row = $allProducts->fetch_assoc()) {
            $results['products'][] = $row;
        }
        
        // 5. Check for sellers
        $sellerResult = $connection->query("SELECT id, name, role FROM users WHERE role = 'seller'");
        $results['sellers'] = [];
        while ($row = $sellerResult->fetch_assoc()) {
            $results['sellers'][] = $row;
        }
    }
    
    // 6. Test the query used in products.php
    $testQuery = $connection->query("SELECT * FROM seller_products WHERE status = 'active' ORDER BY created_at DESC LIMIT 50");
    $results['diagnostics']['test_query_count'] = $testQuery ? $testQuery->num_rows : 'ERROR';
    
    echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
