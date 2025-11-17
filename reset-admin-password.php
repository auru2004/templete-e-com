<?php
/**
 * ADMIN PASSWORD RESET TOOL
 * Script ini untuk me-reset password admin ke default password
 * Setelah jalankan script ini, hapus file ini untuk keamanan
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = Database::getInstance();
    $conn = $db->connect();
    
    if (!$conn) {
        die("Connection failed");
    }
    
    // Password baru
    $newPassword = 'admin123456';
    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
    
    // Update password admin
    $sql = "UPDATE users SET password = ? WHERE email = 'admin@lumbungdigital.com' AND role = 'admin'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $hashedPassword);
    
    if ($stmt->execute()) {
        echo "<h2 style='color: green; font-family: Arial;'>✓ Password Admin Berhasil Di-Reset</h2>";
        echo "<p><strong>Email:</strong> admin@lumbungdigital.com</p>";
        echo "<p><strong>Password Baru:</strong> admin123456</p>";
        echo "<p style='color: red;'><strong>PENTING:</strong> Hapus file ini setelah login untuk alasan keamanan!</p>";
        echo "<p><a href='login.php'>Klik di sini untuk login</a></p>";
    } else {
        echo "<h2 style='color: red;'>✗ Gagal me-reset password</h2>";
        echo "<p>Error: " . $stmt->error . "</p>";
    }
    
    $stmt->close();
    
} catch (Exception $e) {
    echo "<h2 style='color: red;'>✗ Error Koneksi Database</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "<p>Pastikan database sudah di-setup dengan menjalankan schema.sql, schema_v2.sql, dan schema_v3.sql</p>";
}
?>
