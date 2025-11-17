<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Jika sudah login, redirect ke home
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi - Lumbung Digital</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/animations.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #006994 0%, #00a8d8 100%);
            padding: 20px;
        }
        
        .auth-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
            padding: 40px;
            animation: slideIn 0.5s ease;
        }
        
        .auth-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .auth-header h2 {
            color: #006994;
            margin: 20px 0 10px 0;
            font-size: 28px;
        }
        
        .auth-header p {
            color: #666;
            font-size: 14px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #00a8d8;
            box-shadow: 0 0 0 3px rgba(0, 168, 216, 0.1);
        }
        
        .role-selector {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .role-option {
            position: relative;
        }
        
        .role-option input {
            display: none;
        }
        
        .role-option label {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 0;
        }
        
        .role-option input:checked + label {
            background: #00a8d8;
            color: white;
            border-color: #00a8d8;
        }
        
        .btn-register {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #006994, #00a8d8);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s, box-shadow 0.3s;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 105, 148, 0.3);
        }
        
        .btn-register:active {
            transform: translateY(0);
        }
        
        .btn-register.loading {
            opacity: 0.7;
            cursor: not-allowed;
        }
        
        .auth-footer {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        .auth-footer a {
            color: #00a8d8;
            text-decoration: none;
            font-weight: 600;
        }
        
        .alert {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-danger {
            background: #fee;
            color: #c33;
            border: 1px solid #fcc;
        }
        
        .alert-success {
            background: #efe;
            color: #3c3;
            border: 1px solid #cfc;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <i class="fas fa-water" style="font-size: 40px; color: #00a8d8;"></i>
                <h2>Daftar Akun</h2>
                <p>Bergabunglah dengan Lumbung Digital</p>
            </div>
            
            <div id="alert-container"></div>
            
            <form id="register-form">
                <div class="form-group">
                    <label for="name">Nama Lengkap</label>
                    <input type="text" id="name" name="name" placeholder="Masukkan nama lengkap" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Nomor Telepon</label>
                    <input type="tel" id="phone" name="phone" placeholder="Masukkan nomor telepon (opsional)">
                </div>
                
                <div class="form-group">
                    <label>Pilih Tipe Akun</label>
                    <div class="role-selector">
                        <div class="role-option">
                            <input type="radio" id="role-buyer" name="role" value="buyer" checked>
                            <label for="role-buyer">
                                <i class="fas fa-shopping-bag" style="margin-right: 8px;"></i> Pembeli
                            </label>
                        </div>
                        <div class="role-option">
                            <input type="radio" id="role-seller" name="role" value="seller">
                            <label for="role-seller">
                                <i class="fas fa-store" style="margin-right: 8px;"></i> Penjual
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password (minimal 6 karakter)" required>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Konfirmasi Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password" required>
                </div>
                
                <button type="submit" class="btn-register">
                    <i class="fas fa-user-plus"></i> Daftar Sekarang
                </button>
            </form>
            
            <div class="auth-footer">
                Sudah punya akun? <a href="login.php">Login di sini</a>
            </div>
        </div>
    </div>
    
    <script>
        const form = document.getElementById('register-form');
        const alertContainer = document.getElementById('alert-container');
        
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const btn = form.querySelector('button');
            btn.classList.add('loading');
            btn.disabled = true;
            
            const formData = {
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value,
                password: document.getElementById('password').value,
                confirm_password: document.getElementById('confirm_password').value,
                role: document.querySelector('input[name="role"]:checked').value
            };
            
            try {
                const response = await fetch('api/register.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showAlert('Registrasi berhasil! Redirecting...', 'success');
                    setTimeout(() => {
                        window.location.href = 'login.php';
                    }, 1500);
                } else {
                    showAlert(data.message, 'danger');
                }
            } catch (error) {
                showAlert('Terjadi kesalahan: ' + error.message, 'danger');
            } finally {
                btn.classList.remove('loading');
                btn.disabled = false;
            }
        });
        
        function showAlert(message, type) {
            alertContainer.innerHTML = `<div class="alert alert-${type}">${message}</div>`;
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }
    </script>
</body>
</html>
