<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistem Pencarian Rute Aman - SaferWay</title>
    
    <link rel="stylesheet" href="/public/css/globals.css" />
    <link rel="stylesheet" href="../public/css/index.css" />
    <link rel="stylesheet" href="/public/css/styles.css" />
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <script src="https://unpkg.com/lucide@latest"></script>

    <style>
        /* CSS Fix biar peta muncul full */
        #map { height: 100vh; width: 100%; z-index: 0; }
        .sidebar, .search-panel, .legend, .map-controls { z-index: 1000; }
    </style>
</head>
<body>
    <div class="map-container">
        <div class="header" style="z-index: 1001;">
            <div class="hamburger-menu" id="hamburger-menu">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <div class="logo-container">
                <div class="logo">S</div>
                <div class="logo-text">
                    <div class="title">SaferWay</div>
                    <div class="subtitle">Yogyakarta</div>
                </div>
            </div>
        </div>

        <div class="sidebar-overlay" id="sidebar-overlay"></div>
        <div class="sidebar" id="sidebar">
            <div class="sidebar-menu">
                <div class="sidebar-section-title">Menu</div>
                <a href="index.php" class="sidebar-menu-item active">
                    <i data-lucide="map"></i>
                    <span>Peta Rute</span>
                </a>
                <a href="form.php" class="sidebar-menu-item">
                    <i data-lucide="file-text"></i>
                    <span>Lapor Kejahatan</span>
                </a>
                <div class="sidebar-divider"></div>
                <div class="sidebar-section-title">Informasi</div>
                <button class="sidebar-menu-item">
                    <i data-lucide="info"></i>
                    <span>Tentang SaferWay</span>
                </button>
                <button class="sidebar-menu-item">
                    <i data-lucide="help-circle"></i>
                    <span>Bantuan</span>
                </button>
                <div class="sidebar-divider"></div>
                <button class="sidebar-menu-item">
                    <i data-lucide="settings"></i>
                    <span>Pengaturan</span>
                </button>
            </div>
        </div>
        
        <div id="map"></div>

        <div class="search-panel">
            <div class="input-group">
                <div class="input-wrapper">
                    <i data-lucide="circle" class="input-icon red"></i>
                    <input type="text" id="origin" placeholder="Pilih titik awal" value="Tugu Yogyakarta" />
                </div>
            </div>
            
            <div class="input-group">
                <div class="input-wrapper">
                    <i data-lucide="map-pin" class="input-icon red"></i>
                    <input type="text" id="destination" placeholder="Pilih tujuan" value="Ambarrukmo Plaza" />
                </div>
            </div>
            
            <div class="input-group">
                <label style="font-size: 12px; color: #666; margin-bottom: 8px; display: block;">Jenis Rute:</label>
                <div class="route-options">
                    <button class="route-option active" id="safest-route" data-route="safest">
                        <i data-lucide="shield" style="width: 16px; height: 16px;"></i>
                        <span>Rute teraman</span>
                    </button>
                    <button class="route-option" id="shortest-route" data-route="shortest">
                        <i data-lucide="zap" style="width: 16px; height: 16px;"></i>
                        <span>Rute terpendek</span>
                    </button>
                </div>
            </div>
            
            <button class="search-button" id="search-btn">
                <i data-lucide="search" style="width: 18px; height: 18px;"></i>
                <span>Cari rute</span>
            </button>
            
            <div id="result-info" style="margin-top:10px; font-size: 12px; display:none;" class="alert alert-info p-2">
                Menghitung rute...
            </div>
        </div>

        <div class="legend">
            <div class="legend-title">Indikator Tingkat Kriminalitas</div>
            <div class="legend-item">
                <div class="legend-indicator">
                    <div class="legend-circle safe">
                        <div class="legend-circle-inner"></div>
                    </div>
                </div>
                <div class="legend-text">
                    <div class="name">Aman</div>
                    <div class="desc">Tingkat kriminalitas rendah</div>
                </div>
            </div>
            
            <div class="legend-item">
                <div class="legend-indicator">
                    <div class="legend-circle alert">
                        <div class="legend-circle-inner"></div>
                    </div>
                </div>
                <div class="legend-text">
                    <div class="name">Siaga</div>
                    <div class="desc">Perlu kewaspadaan sedang</div>
                </div>
            </div>
            
            <div class="legend-item">
                <div class="legend-indicator">
                    <div class="legend-circle prone">
                        <div class="legend-circle-inner"></div>
                    </div>
                </div>
                <div class="legend-text">
                    <div class="name">Rawan</div>
                    <div class="desc">Aktivitas kriminal tinggi</div>
                </div>
            </div>
            
            <div class="legend-item">
                <div class="legend-indicator">
                    <div class="legend-circle dangerous">
                        <div class="legend-circle-inner"></div>
                    </div>
                </div>
                <div class="legend-text">
                    <div class="name">Bahaya</div>
                    <div class="desc">Hindari jika memungkinkan</div>
                </div>
            </div>
        </div>
        
        <div class="map-controls">
            <button class="map-control-btn" id="zoom-in-btn">
                <i data-lucide="plus" style="width: 20px; height: 20px;"></i>
            </button>
            <button class="map-control-btn" id="zoom-out-btn">
                <i data-lucide="minus" style="width: 20px; height: 20px;"></i>
            </button>
            <button class="map-control-btn" onclick="map.fitBounds(currentRouteLine.getBounds())">
                <i data-lucide="maximize" style="width: 20px; height: 20px;"></i>
            </button>
            <button class="map-control-btn">
                <i data-lucide="navigation" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
    </div>
    

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    
    <script>
        // 1. Initialize Icons
        lucide.createIcons();

        // 2. Sidebar Logic
        const hamburgerMenu = document.getElementById('hamburger-menu');
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        hamburgerMenu.addEventListener('click', () => {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });

        sidebarOverlay.addEventListener('click', () => {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        // 3. Route Type Selection Logic
        let selectedRoute = 'safest'; // Default 'safest' -> mapped to 'safe' or 'safest' di backend
        const routeOptions = document.querySelectorAll('.route-option');
        
        routeOptions.forEach(option => {
            option.addEventListener('click', () => {
                routeOptions.forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
                selectedRoute = option.getAttribute('data-route'); // 'safest' or 'shortest'
                lucide.createIcons();
            });
        });

        // 4. MAP INITIALIZATION (LEAFLET)
        var map = L.map('map', { zoomControl: false }).setView([-7.7828, 110.3800], 14);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap'
        }).addTo(map);

        var currentRouteLine = null;

        // 5. LOAD ZONES FROM DB (POSTGIS)
        fetch('api/get_zones.php')
            .then(res => res.json())
            .then(data => {
                L.geoJSON(data, {
                    style: function(feature) {
                        return { 
                            color: feature.properties.color, 
                            fillColor: feature.properties.color, 
                            fillOpacity: 0.4, 
                            weight: 2 
                        };
                    },
                    onEachFeature: function(feature, layer) {
                        layer.bindPopup(`<b>${feature.properties.name}</b><br>${feature.properties.level}`);
                    }
                }).addTo(map);
            })
            .catch(err => console.log("Gagal load zona, pastikan API get_zones.php ada.", err));

        // 6. CONNECT ZOOM BUTTONS
        document.getElementById('zoom-in-btn').onclick = function() { map.zoomIn(); };
        document.getElementById('zoom-out-btn').onclick = function() { map.zoomOut(); };

        // 7. SEARCH FUNCTIONALITY (THE CORE)
        const searchBtn = document.getElementById('search-btn');
        const resultInfo = document.getElementById('result-info');
        
        searchBtn.addEventListener('click', () => {
            const origin = document.getElementById('origin').value;
            const destination = document.getElementById('destination').value;

            if (!origin || !destination) {
                alert('Silakan masukkan titik awal dan tujuan');
                return;
            }

            // UI Feedback
            resultInfo.style.display = 'block';
            resultInfo.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghubungi Server...';
            resultInfo.className = "alert alert-info p-2 mt-2";

            // HARDCODED COORDINATES (SIMULASI)
            // Karena inputnya teks, kita perlu geocoding. 
            // Untuk sementara kita pakai koordinat Tugu & Amplaz fix biar jalan dulu logicnya.
            var startCoords = [-7.7828, 110.3670]; // Tugu
            var endCoords = [-7.7830, 110.4010];   // Amplaz

            // Mapping mode untuk backend: 'safest' -> 'safe', 'shortest' -> 'fast'
            var backendMode = (selectedRoute === 'safest') ? 'safe' : 'fast';

            // Hapus rute lama
            if (currentRouteLine) map.removeLayer(currentRouteLine);

            // PANGGIL API BACKEND (POSTGIS)
            // Pastikan file api/get_route_pgrouting.php ada!
            var url = `api/get_route_pgrouting.php?start_lat=${startCoords[0]}&start_lng=${startCoords[1]}&end_lat=${endCoords[0]}&end_lng=${endCoords[1]}&mode=${backendMode}`;

            fetch(url)
                .then(res => res.json())
                .then(data => {
                    if (data.error) {
                        resultInfo.className = "alert alert-danger p-2 mt-2";
                        resultInfo.innerText = "Error: " + data.error;
                        return;
                    }

                    // GAMBAR GARIS RUTE
                    currentRouteLine = L.geoJSON(data, {
                        style: {
                            color: backendMode === 'safe' ? '#198754' : '#6c757d', // Hijau (Aman) atau Abu (Cepat)
                            weight: 6,
                            opacity: 0.8
                        }
                    }).addTo(map);
                    
                    // Zoom ke rute
                    map.fitBounds(currentRouteLine.getBounds(), {padding: [50, 50]});

                    // Update Info
                    resultInfo.className = "alert alert-success p-2 mt-2";
                    resultInfo.innerHTML = `<b>Rute Ditemukan!</b><br>Mode: ${backendMode === 'safe' ? 'Teraman' : 'Terpendek'}`;
                })
                .catch(err => {
                    console.error(err);
                    resultInfo.className = "alert alert-warning p-2 mt-2";
                    resultInfo.innerText = "Gagal koneksi ke API Routing.";
                });
        });
    </script>
</body>
</html>