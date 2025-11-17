<?php
// Authentication and Session Management

class Auth {
    private $db;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    public function register($name, $email, $password, $phone = '', $role = 'buyer') {
        // Validasi email sudah terdaftar
        $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            return ['success' => false, 'message' => 'Email sudah terdaftar'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        
        // Insert user
        $stmt = $this->db->prepare("INSERT INTO users (name, email, password, phone, role) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashedPassword, $phone, $role);
        
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Registrasi berhasil', 'user_id' => $stmt->insert_id];
        } else {
            return ['success' => false, 'message' => 'Gagal registrasi: ' . $stmt->error];
        }
    }
    
    public function login($email, $password) {
        $stmt = $this->db->prepare("SELECT id, name, email, password, role, is_active FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'Email tidak ditemukan'];
        }
        
        $user = $result->fetch_assoc();
        
        if (!$user['is_active']) {
            return ['success' => false, 'message' => 'Akun Anda telah dinonaktifkan'];
        }
        
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Password salah'];
        }
        
        // Update last login
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->bind_param("i", $user['id']);
        $stmt->execute();
        
        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        
        return ['success' => true, 'message' => 'Login berhasil', 'user' => $user];
    }
    
    public function logout() {
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function isAdmin() {
        return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
    
    public function isSeller() {
        return isset($_SESSION['user_role']) && ($_SESSION['user_role'] === 'seller' || $_SESSION['user_role'] === 'admin');
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>
