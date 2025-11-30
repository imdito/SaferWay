<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lapor Kejahatan - SaferWay</title>
    <link rel="stylesheet" href="/public/css/form.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Lapor Kejahatan</h1>
            <p>Bantu masyarakat lebih aman dengan melaporkan kejadian kriminal</p>
        </div>

        <div class="form-container">
            <form id="crimeReportForm" action="submit_report.php" method="POST">
                <!-- Informasi Kejahatan -->
                <div class="form-section">
                    <h2>📝 Informasi Kejahatan</h2>
                    
                    <div class="form-group">
                        <label for="crime_type" class="required">Jenis Kejahatan</label>
                        <select id="crime_type" name="crime_type" required>
                            <option value="">Pilih Jenis Kejahatan</option>
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
                    </div>

                    <div class="form-group">
                        <label for="crime_level" class="required">Tingkat Kriminalitas</label>
                        <div class="level-indicator">
                            <div class="level-option level-aman" data-level="1">
                                <div class="level-dot" style="background-color: #00FF00"></div>
                                <span>Aman</span>
                            </div>
                            <div class="level-option level-siaga" data-level="2">
                                <div class="level-dot" style="background-color: #FFFF00"></div>
                                <span>Siaga</span>
                            </div>
                            <div class="level-option level-rawan" data-level="3">
                                <div class="level-dot" style="background-color: #FFA500"></div>
                                <span>Rawan</span>
                            </div>
                            <div class="level-option level-bahaya" data-level="4">
                                <div class="level-dot" style="background-color: #FF0000"></div>
                                <span>Bahaya</span>
                            </div>
                        </div>
                        <input type="hidden" id="crime_level" name="crime_level" required>
                    </div>

                    <div class="form-group">
                        <label for="crime_date" class="required">Tanggal Kejadian</label>
                        <input type="datetime-local" id="crime_date" name="crime_date" required>
                    </div>

                    <div class="form-group">
                        <label for="description" class="required">Deskripsi Kejadian</label>
                        <textarea id="description" name="description" placeholder="Jelaskan secara detail kejadian yang dialami..." required></textarea>
                    </div>
                </div>

                <!-- Lokasi Kejadian -->
                <div class="form-section">
                    <h2>Lokasi Kejadian</h2>

                    <div class="form-group">
                        <label for="location_name" class="required">Nama Lokasi</label>
                        <input type="text" id="location_name" name="location_name" placeholder="Contoh: Jl. Malioboro, Depan Mall Ambarukmo, dll" required>
                    </div>

                    <div class="location-group">
                        <div class="form-group">
                            <label for="latitude" class="required">Latitude</label>
                            <input type="number" id="latitude" name="latitude" step="any" placeholder="-7.795580" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="longitude" class="required">Longitude</label>
                            <input type="number" id="longitude" name="longitude" step="any" placeholder="110.369490" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn" onclick="getCurrentLocation()">
                            Gunakan Lokasi Saat Ini
                        </button>
                    </div>

                    <div class="form-group">
                        <label>Peta Lokasi</label>
                        <div class="map-preview" id="mapPreview">
                            <div class="map-placeholder">
                                <i>🗺️</i>
                                <p>Koordinat akan ditampilkan di sini</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi Pelapor -->
                <div class="form-section">
                    <h2>Informasi Pelapor</h2>
                    
                    <div class="form-group">
                        <label for="reporter_name">Nama Pelapor</label>
                        <input type="text" id="reporter_name" name="reporter_name" placeholder="Nama Anda" disabled>
                    </div>

                    <div class="form-group">
                        <label for="reporter_contact">Email</label>
                        <input type="text" id="reporter_contact" name="reporter_contact" placeholder="Email Anda" disabled>
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="btn-group">
                    <button type="button" class="btn btn-reset" onclick="resetForm()">Reset Form</button>
                    <button type="submit" class="btn btn-submit">Kirim Laporan</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Simulasi data user dari session/database
        // Dalam implementasi sebenarnya, data ini akan diambil dari PHP session
        // yang sudah diset saat user login
        function getUserDataFromSession() {
            // GANTI BAGIAN INI dengan kode PHP untuk mengambil data dari session
            // Contoh PHP yang perlu ditambahkan:
            /*
            <?php
            session_start();
            $userData = [
                'name' => $_SESSION['user_name'] ?? '',
                'email' => $_SESSION['user_email'] ?? ''
            ];
            echo "const userData = " . json_encode($userData) . ";";
            ?>
            */
            
            // Untuk demo, menggunakan data dummy
            return {
                name: 'John Doe',
                email: 'john.doe@example.com'
            };
        }

        // Auto-fill user information
        function fillUserInfo() {
            const userData = getUserDataFromSession();
            document.getElementById('reporter_name').value = userData.name;
            document.getElementById('reporter_contact').value = userData.email;
        }

        // Level kriminalitas selection
        document.querySelectorAll('.level-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.level-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                this.classList.add('selected');
                document.getElementById('crime_level').value = this.getAttribute('data-level');
            });
        });

        // Update map preview when coordinates change
        function updateMapPreview() {
            const lat = document.getElementById('latitude').value;
            const lng = document.getElementById('longitude').value;
            const mapPreview = document.getElementById('mapPreview');
            
            if (lat && lng) {
                mapPreview.innerHTML = `
                    <div class="map-coordinates">
                        <strong>Lokasi Terpilih:</strong><br>
                        Latitude: ${lat}<br>
                        Longitude: ${lng}
                    </div>
                `;
            } else {
                mapPreview.innerHTML = `
                    <div class="map-placeholder">
                        <i>🗺️</i>
                        <p>Koordinat akan ditampilkan di sini</p>
                    </div>
                `;
            }
        }

        document.getElementById('latitude').addEventListener('input', updateMapPreview);
        document.getElementById('longitude').addEventListener('input', updateMapPreview);

        // Get current location
        function getCurrentLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(position) {
                        document.getElementById('latitude').value = position.coords.latitude.toFixed(6);
                        document.getElementById('longitude').value = position.coords.longitude.toFixed(6);
                        updateMapPreview();
                    },
                    function(error) {
                        alert('Tidak dapat mendapatkan lokasi saat ini. Silakan masukkan manual.');
                    }
                );
            } else {
                alert('Browser tidak mendukung geolocation. Silakan masukkan manual.');
            }
        }

        // Form validation
        document.getElementById('crimeReportForm').addEventListener('submit', function(e) {
            const crimeLevel = document.getElementById('crime_level').value;
            if (!crimeLevel) {
                e.preventDefault();
                alert('Silakan pilih tingkat kriminalitas!');
                return;
            }

            const latitude = parseFloat(document.getElementById('latitude').value);
            const longitude = parseFloat(document.getElementById('longitude').value);
            
            if (latitude < -8.5 || latitude > -7.0 || longitude < 109.0 || longitude > 111.5) {
                if (!confirm('Koordinat berada di luar area Yogyakarta. Yakin ingin melanjutkan?')) {
                    e.preventDefault();
                    return;
                }
            }

            // Show success message
            alert('Laporan berhasil dikirim! Terima kasih telah berkontribusi membuat Yogyakarta lebih aman.');
        });

        // Reset form
        function resetForm() {
            if (confirm('Apakah Anda yakin ingin mengosongkan semua form?')) {
                document.getElementById('crimeReportForm').reset();
                document.querySelectorAll('.level-option').forEach(opt => {
                    opt.classList.remove('selected');
                });
                updateMapPreview();
                // Re-fill user info after reset
                fillUserInfo();
            }
        }

        // Initialize form
        window.addEventListener('DOMContentLoaded', function() {
            fillUserInfo();
            updateMapPreview();
        });
    </script>
</body>
</html>