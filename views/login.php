<?php
// login.php
session_start();

require_once '../routes/db.php';

// Redirect jika sudah login
if (isset($_SESSION['user_id'])) {
    header('Location: form.php');
    exit();
}

$error_message = '';
$success_message = '';

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error_message = "Email dan password harus diisi!";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT id, username, email, password FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                header('Location: form.php');
                exit();
            } else {
                $error_message = "Email atau password salah!";
            }
        } catch(PDOException $e) {
            $error_message = "Terjadi kesalahan sistem. Silakan coba lagi.";
        }
    }
}

// Proses Register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['reg_username'] ?? '');
    $email = trim($_POST['reg_email'] ?? '');
    $password = $_POST['reg_password'] ?? '';
    $confirm_password = $_POST['reg_confirm_password'] ?? '';
    
    $errors = [];
    
    if (empty($username)) $errors[] = "Username harus diisi";
    if (empty($email)) $errors[] = "Email harus diisi";
    if (empty($password)) $errors[] = "Password harus diisi";
    if ($password !== $confirm_password) $errors[] = "Password tidak cocok";
    if (strlen($password) < 6) $errors[] = "Password minimal 6 karakter";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid";
    
    if (empty($errors)) {
        try {
            // Cek email sudah terdaftar atau belum
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            
            if ($stmt->fetch()) {
                $error_message = "Email atau username sudah terdaftar!";
            } else {
                // Insert user baru
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$username, $email, $hashed_password]);
                
                $success_message = "Registrasi berhasil! Silakan login.";
            }
        } catch(PDOException $e) {
            $error_message = "Gagal mendaftar: " . $e->getMessage();
        }
    } else {
        $error_message = implode(', ', $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SaferWay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .animate-fade-in { animation: fadeIn 0.5s ease-out; }
        
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .notification { animation: slideDown 0.5s ease-out; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-indigo-50 min-h-screen flex items-center justify-center p-4">

    <div class="w-full max-w-md animate-fade-in">
        
        <!-- Logo & Header -->
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl shadow-lg mb-4">
                <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-slate-800">SaferWay</h1>
            <p class="text-slate-600 mt-2">Sistem Pelaporan Kriminalitas</p>
        </div>

        <?php if ($error_message): ?>
        <div class="notification mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-md">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-600 flex-shrink-0"></i>
                <p class="text-red-700 text-sm"><?= htmlspecialchars($error_message) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
        <div class="notification mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-md">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle" class="w-5 h-5 text-green-600 flex-shrink-0"></i>
                <p class="text-green-700 text-sm"><?= htmlspecialchars($success_message) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tab Switcher -->
        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="flex border-b border-gray-200">
                <button onclick="switchTab('login')" id="loginTab" class="flex-1 py-4 px-6 text-center font-semibold text-blue-600 border-b-2 border-blue-600 transition">
                    Login
                </button>
                <button onclick="switchTab('register')" id="registerTab" class="flex-1 py-4 px-6 text-center font-semibold text-gray-400 border-b-2 border-transparent hover:text-gray-600 transition">
                    Daftar
                </button>
            </div>

            <!-- Login Form -->
            <div id="loginForm" class="p-8">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <div class="relative">
                            <input type="email" name="email" required 
                                   class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                   placeholder="nama@email.com">
                            <i data-lucide="mail" class="w-5 h-5 text-gray-400 absolute left-3 top-3.5"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <input type="password" name="password" required 
                                   class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                   placeholder="Masukkan password">
                            <i data-lucide="lock" class="w-5 h-5 text-gray-400 absolute left-3 top-3.5"></i>
                        </div>
                    </div>

                    <button type="submit" name="login" 
                            class="w-full py-3 bg-blue-600 text-white font-bold rounded-lg shadow-lg hover:bg-blue-700 hover:shadow-xl transition transform active:scale-95">
                        Masuk
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        Belum punya akun? 
                        <button onclick="switchTab('register')" class="text-blue-600 font-semibold hover:underline">Daftar sekarang</button>
                    </p>
                </div>
            </div>

            <!-- Register Form -->
            <div id="registerForm" class="p-8 hidden">
                <form method="POST" class="space-y-5">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Username</label>
                        <div class="relative">
                            <input type="text" name="reg_username" required 
                                   class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                   placeholder="username">
                            <i data-lucide="user" class="w-5 h-5 text-gray-400 absolute left-3 top-3.5"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Email</label>
                        <div class="relative">
                            <input type="email" name="reg_email" required 
                                   class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                   placeholder="nama@email.com">
                            <i data-lucide="mail" class="w-5 h-5 text-gray-400 absolute left-3 top-3.5"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                        <div class="relative">
                            <input type="password" name="reg_password" required 
                                   class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                   placeholder="Minimal 6 karakter">
                            <i data-lucide="lock" class="w-5 h-5 text-gray-400 absolute left-3 top-3.5"></i>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Konfirmasi Password</label>
                        <div class="relative">
                            <input type="password" name="reg_confirm_password" required 
                                   class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                   placeholder="Ulangi password">
                            <i data-lucide="lock" class="w-5 h-5 text-gray-400 absolute left-3 top-3.5"></i>
                        </div>
                    </div>

                    <button type="submit" name="register" 
                            class="w-full py-3 bg-blue-600 text-white font-bold rounded-lg shadow-lg hover:bg-blue-700 hover:shadow-xl transition transform active:scale-95">
                        Daftar
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-500">
                        Sudah punya akun? 
                        <button onclick="switchTab('login')" class="text-blue-600 font-semibold hover:underline">Login di sini</button>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

        function switchTab(tab) {
            const loginTab = document.getElementById('loginTab');
            const registerTab = document.getElementById('registerTab');
            const loginForm = document.getElementById('loginForm');
            const registerForm = document.getElementById('registerForm');

            if (tab === 'login') {
                loginTab.classList.add('text-blue-600', 'border-blue-600');
                loginTab.classList.remove('text-gray-400', 'border-transparent');
                registerTab.classList.add('text-gray-400', 'border-transparent');
                registerTab.classList.remove('text-blue-600', 'border-blue-600');
                loginForm.classList.remove('hidden');
                registerForm.classList.add('hidden');
            } else {
                registerTab.classList.add('text-blue-600', 'border-blue-600');
                registerTab.classList.remove('text-gray-400', 'border-transparent');
                loginTab.classList.add('text-gray-400', 'border-transparent');
                loginTab.classList.remove('text-blue-600', 'border-blue-600');
                registerForm.classList.remove('hidden');
                loginForm.classList.add('hidden');
            }
        }

        // Auto hide notification after 5 seconds
        setTimeout(() => {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notif => {
                notif.style.transition = 'opacity 0.5s';
                notif.style.opacity = '0';
                setTimeout(() => notif.remove(), 500);
            });
        }, 5000);
    </script>
</body>
</html>