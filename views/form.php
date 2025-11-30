<?php
// Konfigurasi Database
session_start();

$host = 'localhost';
$dbname = 'criminality';
$username = 'postgres';
$password = 'taufiq';
$port = '5432';

try {
    // Tambahkan timeout dan options
    $dsn = "pgsql:host=$host;port=$port;dbname=$dbname;connect_timeout=5";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5
    ];
    
    $pdo = new PDO($dsn, $username, $password, $options);
} catch(PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage() . "<br>Pastikan PostgreSQL berjalan di port 5555");
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Ambil data user dari database
$stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Proses submit form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $crime_type = filter_input(INPUT_POST, 'crime_type', FILTER_VALIDATE_INT);
        $crime_date = $_POST['crime_date'] ?? '';
        $crime_level = filter_input(INPUT_POST, 'crime_level', FILTER_VALIDATE_INT);
        $description = trim($_POST['description'] ?? '');
        $location_name = trim($_POST['location_name'] ?? '');
        $latitude = filter_input(INPUT_POST, 'latitude', FILTER_VALIDATE_FLOAT);
        $longitude = filter_input(INPUT_POST, 'longitude', FILTER_VALIDATE_FLOAT);

        $errors = [];
        
        if (!$crime_type || $crime_type < 1 || $crime_type > 9) {
            $errors[] = "Jenis kejahatan tidak valid";
        }
        if (empty($crime_date)) {
            $errors[] = "Waktu kejadian harus diisi";
        }
        if (!$crime_level || $crime_level < 1 || $crime_level > 4) {
            $errors[] = "Tingkat bahaya tidak valid";
        }
        if (empty($description)) {
            $errors[] = "Kronologi kejadian harus diisi";
        }
        if (empty($location_name)) {
            $errors[] = "Nama lokasi harus diisi";
        }
        if ($latitude === false || $longitude === false) {
            $errors[] = "Koordinat lokasi tidak valid";
        }
        
        if (!empty($errors)) {
            $error_message = implode(', ', $errors);
        } else {
            $crime_types = [
                1 => 'Pencurian', 2 => 'Begal', 3 => 'Pencopetan',
                4 => 'Penjambretan', 5 => 'Penipuan', 6 => 'Pengrusakan',
                7 => 'Kekerasan', 8 => 'Narkoba', 9 => 'Lainnya'
            ];
            $crime_type_name = $crime_types[$crime_type];
            
            $crime_types = [
                1 => 'Pencurian', 2 => 'Begal', 3 => 'Pencopetan',
                4 => 'Penjambretan', 5 => 'Penipuan', 6 => 'Pengrusakan',
                7 => 'Kekerasan', 8 => 'Narkoba', 9 => 'Lainnya'
            ];
            $crime_type_name = $crime_types[$crime_type];

            $sql = "INSERT INTO crime_data 
                    (crime_type_id, level_id, location_name, coordinates, crime_date, description, area)
                    VALUES (?, ?, ?, ST_SetSRID(ST_MakePoint(?, ?), 4326), ?, ?, ?)";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $crime_type,        // 1. crime_type_id
                $crime_level,       // 2. level_id
                $location_name,     // 3. location_name
                $longitude,         // 4. longitude (untuk ST_MakePoint parameter 1)
                $latitude,          // 5. latitude (untuk ST_MakePoint parameter 2)
                $crime_date,        // 6. crime_date
                $description,       // 7. description
                $crime_type_name    // 8. area
            ]);

            $success_message = "Laporan berhasil dikirim! Terima kasih atas kontribusi Anda.";
        }
    } catch(PDOException $e) {
        $error_message = "Gagal menyimpan laporan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapor Kejahatan - SaferWay</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        .notification { animation: slideDown 0.5s ease-out; }
    </style>
</head>
<body class="bg-gray-50 text-slate-800">

    <div class="container mx-auto px-4 py-8 max-w-3xl">

        <?php if ($success_message): ?>
        <div class="notification mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg shadow-md">
            <div class="flex items-center gap-3">
                <i data-lucide="check-circle" class="w-6 h-6 text-green-600"></i>
                <div>
                    <h4 class="font-bold text-green-800">Berhasil!</h4>
                    <p class="text-green-700 text-sm"><?= htmlspecialchars($success_message) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="notification mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg shadow-md">
            <div class="flex items-center gap-3">
                <i data-lucide="alert-circle" class="w-6 h-6 text-red-600"></i>
                <div>
                    <h4 class="font-bold text-red-800">Gagal!</h4>
                    <p class="text-red-700 text-sm"><?= htmlspecialchars($error_message) ?></p>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden">
            <div class="bg-blue-600 p-6 text-white text-center relative overflow-hidden">
                <div class="absolute top-0 left-0 w-full h-full bg-[url('https://www.transparenttextures.com/patterns/cubes.png')] opacity-10"></div>
                <h2 class="text-2xl font-bold relative z-10">Formulir Laporan</h2>
                <p class="text-blue-100 text-sm mt-1 relative z-10">Bantu kami menciptakan lingkungan yang lebih aman</p>
            </div>

            <!-- Info User -->
            <div class="bg-blue-50 border-b border-blue-100 p-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold">
                        <?= strtoupper(substr($user['username'], 0, 1)) ?>
                    </div>
                    <div>
                        <p class="font-bold text-slate-800"><?= htmlspecialchars($user['username']) ?></p>
                        <p class="text-sm text-slate-600"><?= htmlspecialchars($user['email']) ?></p>
                    </div>
                </div>
            </div>

            <form method="POST" class="p-6 md:p-8 space-y-8">

                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-700 flex items-center gap-2 border-b pb-2">
                        <span class="bg-blue-100 text-blue-600 p-1.5 rounded"><i data-lucide="alert-triangle" class="w-5 h-5"></i></span>
                        Detail Kejadian
                    </h3>

                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-600 mb-1">Jenis Kejahatan <span class="text-red-500">*</span></label>
                            <div class="relative">
                                <select id="crime_type" name="crime_type" required class="w-full pl-10 pr-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none outline-none transition">
                                    <option value="">Pilih Jenis...</option>
                                    <option value="1">Pencurian</option>
                                    <option value="2">Begal</option>
                                    <option value="3">Pencopetan</option>
                                    <option value="4">Penjambretan</option>
                                    <option value="5">Penipuan</option>
                                    <option value="6">Pengrusakan</option>
                                    <option value="7">Kekerasan</option>
                                    <option value="8">Narkoba</option>
                                    <option value="9">Lainnya</option>
                                </select>
                                <i data-lucide="list" class="w-5 h-5 text-gray-400 absolute left-3 top-3"></i>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-600 mb-1">Waktu Kejadian <span class="text-red-500">*</span></label>
                            <input type="datetime-local" id="crime_date" name="crime_date" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition text-gray-600">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-2">Tingkat Bahaya <span class="text-red-500">*</span></label>
                        <input type="hidden" id="crime_level" name="crime_level" required>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                            <div class="level-option cursor-pointer border rounded-lg p-3 flex flex-col items-center gap-2 hover:bg-green-50 hover:border-green-400 transition" data-level="1">
                                <span class="w-4 h-4 rounded-full bg-green-500 shadow-sm"></span>
                                <span class="text-xs font-bold text-gray-600">Aman</span>
                            </div>
                            <div class="level-option cursor-pointer border rounded-lg p-3 flex flex-col items-center gap-2 hover:bg-yellow-50 hover:border-yellow-400 transition" data-level="2">
                                <span class="w-4 h-4 rounded-full bg-yellow-400 shadow-sm"></span>
                                <span class="text-xs font-bold text-gray-600">Siaga</span>
                            </div>
                            <div class="level-option cursor-pointer border rounded-lg p-3 flex flex-col items-center gap-2 hover:bg-orange-50 hover:border-orange-400 transition" data-level="3">
                                <span class="w-4 h-4 rounded-full bg-orange-500 shadow-sm"></span>
                                <span class="text-xs font-bold text-gray-600">Rawan</span>
                            </div>
                            <div class="level-option cursor-pointer border rounded-lg p-3 flex flex-col items-center gap-2 hover:bg-red-50 hover:border-red-400 transition" data-level="4">
                                <span class="w-4 h-4 rounded-full bg-red-600 shadow-sm"></span>
                                <span class="text-xs font-bold text-gray-600">Bahaya</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Kronologi Singkat <span class="text-red-500">*</span></label>
                        <textarea id="description" name="description" rows="3" placeholder="Ceritakan detail kejadian..." required class="w-full px-4 py-3 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition text-sm"></textarea>
                    </div>
                </div>

                <div class="space-y-4">
                    <h3 class="text-lg font-bold text-slate-700 flex items-center gap-2 border-b pb-2">
                        <span class="bg-blue-100 text-blue-600 p-1.5 rounded"><i data-lucide="map-pin" class="w-5 h-5"></i></span>
                        Lokasi Kejadian
                    </h3>

                    <div>
                        <label class="block text-sm font-semibold text-gray-600 mb-1">Nama Lokasi <span class="text-red-500">*</span></label>
                        <input type="text" id="location_name" name="location_name" placeholder="Contoh: Depan Indomaret Point" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 outline-none transition">
                    </div>

                    <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
                        <div class="flex justify-between items-center mb-3">
                            <span class="text-sm font-bold text-slate-600">Titik Koordinat</span>
                            <button type="button" onclick="getCurrentLocation()" class="text-xs flex items-center gap-1 bg-white border border-gray-300 px-3 py-1.5 rounded-md shadow-sm hover:bg-blue-50 hover:text-blue-600 hover:border-blue-200 transition">
                                <i data-lucide="crosshair" class="w-3 h-3"></i> Ambil Lokasi Saya
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Latitude</label>
                                <input type="number" id="latitude" name="latitude" step="any" placeholder="-7.xxx" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded text-sm focus:ring-1 focus:ring-blue-500 outline-none">
                            </div>
                            <div>
                                <label class="text-xs text-gray-500 block mb-1">Longitude</label>
                                <input type="number" id="longitude" name="longitude" step="any" placeholder="110.xxx" required class="w-full px-3 py-2 bg-white border border-gray-300 rounded text-sm focus:ring-1 focus:ring-blue-500 outline-none">
                            </div>
                        </div>
                        <div id="mapPreview" class="mt-3 h-24 bg-gray-200 rounded border border-gray-300 flex items-center justify-center text-gray-400 text-xs italic">
                            <span class="flex items-center gap-1"><i data-lucide="map"></i> Peta akan muncul di sini</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="button" onclick="resetForm()" class="flex-1 py-3 border border-gray-300 text-gray-600 font-bold rounded-xl hover:bg-gray-100 transition">Reset</button>
                    <button type="submit" class="flex-[2] py-3 bg-blue-600 text-white font-bold rounded-xl shadow-lg hover:bg-blue-700 hover:shadow-blue-500/30 transition transform active:scale-95">Kirim Laporan</button>
                </div>

            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();

        const levelOptions = document.querySelectorAll('.level-option');
        levelOptions.forEach(opt => {
            opt.addEventListener('click', () => {
                levelOptions.forEach(o => {
                    o.classList.remove('ring-2', 'ring-offset-1', 'bg-opacity-20');
                    if (o.dataset.level == '1') o.classList.remove('bg-green-100', 'ring-green-500');
                    if (o.dataset.level == '2') o.classList.remove('bg-yellow-100', 'ring-yellow-500');
                    if (o.dataset.level == '3') o.classList.remove('bg-orange-100', 'ring-orange-500');
                    if (o.dataset.level == '4') o.classList.remove('bg-red-100', 'ring-red-500');
                });

                const lvl = opt.dataset.level;
                opt.classList.add('ring-2', 'ring-offset-1');
                if (lvl == '1') opt.classList.add('bg-green-100', 'ring-green-500');
                if (lvl == '2') opt.classList.add('bg-yellow-100', 'ring-yellow-500');
                if (lvl == '3') opt.classList.add('bg-orange-100', 'ring-orange-500');
                if (lvl == '4') opt.classList.add('bg-red-100', 'ring-red-500');

                document.getElementById('crime_level').value = lvl;
            });
        });

        function getCurrentLocation() {
            if (navigator.geolocation) {
                const btn = event.target;
                const oriText = btn.innerHTML;
                btn.innerHTML = `<i data-lucide="loader-2" class="w-3 h-3 animate-spin"></i> Loading...`;
                lucide.createIcons();

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        document.getElementById('latitude').value = pos.coords.latitude.toFixed(6);
                        document.getElementById('longitude').value = pos.coords.longitude.toFixed(6);
                        updateMapPreview();
                        btn.innerHTML = `<i data-lucide="check" class="w-3 h-3"></i> Sukses`;
                        lucide.createIcons();
                        setTimeout(() => { btn.innerHTML = oriText; lucide.createIcons(); }, 2000);
                    },
                    (err) => {
                        alert('Gagal mengambil lokasi. Pastikan GPS aktif.');
                        btn.innerHTML = oriText;
                        lucide.createIcons();
                    }
                );
            } else {
                alert('Browser tidak mendukung GPS.');
            }
        }

        function updateMapPreview() {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            const box = document.getElementById('mapPreview');
            if (lat && lng) {
                box.innerHTML = `<span class="text-blue-600 font-bold">📍 ${lat}, ${lng}</span>`;
                box.classList.add('bg-blue-50', 'border-blue-200');
            }
        }

        function resetForm() {
            if (confirm("Hapus semua isian?")) {
                document.querySelector('form').reset();
                document.getElementById('mapPreview').innerHTML = '<span class="flex items-center gap-1"><i data-lucide="map"></i> Peta akan muncul di sini</span>';
                document.getElementById('mapPreview').classList.remove('bg-blue-50', 'border-blue-200');
                levelOptions.forEach(o => {
                    o.classList.remove('ring-2', 'ring-offset-1', 'bg-green-100', 'bg-yellow-100', 'bg-orange-100', 'bg-red-100', 'ring-green-500', 'ring-yellow-500', 'ring-orange-500', 'ring-red-500');
                });
                lucide.createIcons();
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