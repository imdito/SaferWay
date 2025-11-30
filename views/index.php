<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Sistem Pencarian Rute Aman - SaferWay</title>
    <link rel="stylesheet" href="/public/css/globals.css" />
    <link rel="stylesheet" href="/public/css/index.css" />
    <link rel="stylesheet" href="/public/css/styles.css" />
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body>
    <div class="map-container">
        <!-- Header -->
        <div class="header">
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

        <!-- Sidebar Navigation -->
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

        <!-- Search Panel -->
        <div class="search-panel">
            <div class="input-group">
                <div class="input-wrapper">
                    <i data-lucide="circle" class="input-icon red"></i>
                    <input type="text" id="origin" placeholder="Pilih titik awal" />
                </div>
            </div>
            
            <div class="input-group">
                <div class="input-wrapper">
                    <i data-lucide="map-pin" class="input-icon red"></i>
                    <input type="text" id="destination" placeholder="Pilih tujuan" />
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
        </div>

        <!-- Legend -->
        <div class="legend">
            <div class="legend-title">Indikator Tingkat Kriminalitas</div>
            <div class="legend-item">
                <div class="legend-indicator">
                    <div class="legend-circle safe">
                        <div class="legend-circle-inner"></div>
                    </div>
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
                    <i data-lucide="triangle-alert" style="width: 16px; height: 16px; color: #FF9800;"></i>
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
                    <i data-lucide="alert-circle" style="width: 16px; height: 16px; color: #F44336;"></i>
                </div>
                <div class="legend-text">
                    <div class="name">Bahaya</div>
                    <div class="desc">Hindari jika memungkinkan</div>
                </div>
            </div>
        </div>
        
        <!-- Map Controls -->
        <div class="map-controls">
            <button class="map-control-btn">
                <i data-lucide="plus" style="width: 20px; height: 20px;"></i>
            </button>
            <button class="map-control-btn">
                <i data-lucide="minus" style="width: 20px; height: 20px;"></i>
            </button>
            <button class="map-control-btn">
                <i data-lucide="maximize" style="width: 20px; height: 20px;"></i>
            </button>
            <button class="map-control-btn">
                <i data-lucide="navigation" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        
        <!-- Crime Zones -->
        <div class="crime-zone zone-alert" style="width: 300px; height: 300px; top: 15%; left: 25%;">
            <div class="zone-marker marker-alert">
                <i data-lucide="triangle-alert" style="width: 20px; height: 20px;"></i>
            </div>
        </div>
        
        <div class="crime-zone zone-safe" style="width: 250px; height: 250px; top: 28%; left: 58%;">
            <div class="zone-marker marker-safe">
                <i data-lucide="shield-check" style="width: 20px; height: 20px;"></i>
            </div>
        </div>
        
        <div class="crime-zone zone-prone" style="width: 280px; height: 280px; top: 20%; left: 10%;">
            <div class="zone-marker marker-prone">
                <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
            </div>
        </div>
        
        <div class="crime-zone zone-alert" style="width: 260px; height: 260px; top: 45%; left: 35%;">
            <div class="zone-marker marker-alert">
                <i data-lucide="triangle-alert" style="width: 20px; height: 20px;"></i>
            </div>
        </div>
        
        <div class="crime-zone zone-prone" style="width: 240px; height: 240px; bottom: 20%; right: 25%;">
            <div class="zone-marker marker-prone">
                <i data-lucide="alert-triangle" style="width: 20px; height: 20px;"></i>
            </div>
        </div>
        
        <div class="crime-zone zone-safe" style="width: 200px; height: 200px; top: 22%; right: 15%;">
            <div class="zone-marker marker-safe">
                <i data-lucide="shield-check" style="width: 20px; height: 20px;"></i>
            </div>
        </div>
        
        <div class="crime-zone zone-dangerous" style="width: 320px; height: 320px; bottom: 15%; right: 35%;">
            <div class="zone-marker marker-dangerous">
                <i data-lucide="alert-circle" style="width: 20px; height: 20px;"></i>
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide icons
        lucide.createIcons();

        // Sidebar toggle
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

        // Route type selection
        let selectedRoute = 'safest';
        const routeOptions = document.querySelectorAll('.route-option');
        
        routeOptions.forEach(option => {
            option.addEventListener('click', () => {
                routeOptions.forEach(opt => opt.classList.remove('active'));
                option.classList.add('active');
                selectedRoute = option.getAttribute('data-route');
                lucide.createIcons();
            });
        });

        // Search functionality
        const searchBtn = document.getElementById('search-btn');
        
        searchBtn.addEventListener('click', () => {
            const origin = document.getElementById('origin').value;
            const destination = document.getElementById('destination').value;

            if (origin && destination) {
                const routeText = selectedRoute === 'safest' ? 'teraman' : 'terpendek';
                alert('Mencari rute ' + routeText + ' dari ' + origin + ' ke ' + destination);
            } else {
                alert('Silakan masukkan titik awal dan tujuan');
            }
        });
    </script>
</body>
</html>
