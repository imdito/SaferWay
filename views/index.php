<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaferWay - Yogyakarta</title>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .leaflet-routing-container {
            display: none !important;
        }

        .custom-scroll::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scroll::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }
        
        .animate-fade-in {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .filter-backdrop {
            background: rgba(0, 0, 0, 0.5);
        }
        
        .suggestion-item {
            transition: background-color 0.15s ease;
        }
        
        .suggestion-item:hover {
            background-color: #f3f4f6;
        }
    </style>
</head>

<body class="bg-gray-100 h-screen flex flex-col overflow-hidden">

    <?php include 'sidebar.php'; ?>

    <div class="flex flex-1 relative overflow-hidden">

        <!-- TOMBOL FILTER & JAM RAWAN KANAN ATAS -->
        <div class="absolute top-4 right-4 z-[1000] flex gap-3">
            <button onclick="showHourlyStats()" class="bg-white rounded-xl shadow-2xl border border-gray-200 px-4 py-3 flex items-center gap-2 hover:bg-purple-50 hover:border-purple-200 transition-all duration-200">
                <i data-lucide="clock" class="w-5 h-5 text-purple-600"></i>
                <span class="text-sm font-semibold text-gray-700">Jam Rawan</span>
            </button>
            <button id="filter-toggle" class="bg-white rounded-xl shadow-2xl border border-gray-200 px-4 py-3 flex items-center gap-2 hover:bg-gray-50 transition-all duration-200">
                <i data-lucide="filter" class="w-5 h-5 text-gray-700"></i>
                <span class="text-sm font-semibold text-gray-700">Filter Data</span>
            </button>
        </div>

        <!-- MODAL FILTER -->
        <div id="filter-modal" class="hidden fixed inset-0 z-[2000] filter-backdrop transition-opacity duration-300">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md transform transition-all duration-300 scale-95 opacity-0"
                     id="filter-modal-content">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                                <i data-lucide="sliders-horizontal" class="w-5 h-5 text-blue-600"></i>
                                Filter Data Kejahatan
                            </h3>
                            <button id="filter-close" class="text-gray-400 hover:text-gray-600 transition">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="text-sm font-semibold text-gray-700 mb-3 block">Pilih Level Keamanan:</label>
                                <div class="space-y-2">
                                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border-2 border-gray-200 hover:border-blue-300 transition-all duration-200">
                                        <input type="radio" name="crime-level" value="all" class="w-4 h-4 text-blue-600" checked>
                                        <div class="flex items-center gap-2 flex-1">
                                            <div class="w-3 h-3 rounded-full bg-gray-400"></div>
                                            <span class="text-sm font-medium text-gray-700">Tampilkan Semua</span>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border-2 border-gray-200 hover:border-red-300 transition-all duration-200">
                                        <input type="radio" name="crime-level" value="Bahaya" class="w-4 h-4 text-red-600">
                                        <div class="flex items-center gap-2 flex-1">
                                            <div class="w-3 h-3 rounded-full bg-red-600"></div>
                                            <span class="text-sm font-medium text-gray-700">Area Bahaya</span>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border-2 border-gray-200 hover:border-orange-300 transition-all duration-200">
                                        <input type="radio" name="crime-level" value="Rawan" class="w-4 h-4 text-orange-500">
                                        <div class="flex items-center gap-2 flex-1">
                                            <div class="w-3 h-3 rounded-full bg-orange-500"></div>
                                            <span class="text-sm font-medium text-gray-700">Area Rawan</span>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border-2 border-gray-200 hover:border-yellow-300 transition-all duration-200">
                                        <input type="radio" name="crime-level" value="Siaga" class="w-4 h-4 text-yellow-500">
                                        <div class="flex items-center gap-2 flex-1">
                                            <div class="w-3 h-3 rounded-full bg-yellow-500"></div>
                                            <span class="text-sm font-medium text-gray-700">Area Siaga</span>
                                        </div>
                                    </label>
                                    
                                    <label class="flex items-center gap-3 cursor-pointer p-3 rounded-xl border-2 border-gray-200 hover:border-green-300 transition-all duration-200">
                                        <input type="radio" name="crime-level" value="Aman" class="w-4 h-4 text-green-600">
                                        <div class="flex items-center gap-2 flex-1">
                                            <div class="w-3 h-3 rounded-full bg-green-600"></div>
                                            <span class="text-sm font-medium text-gray-700">Area Aman</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div id="filter-preview" class="hidden bg-blue-50 border border-blue-200 rounded-xl p-4 animate-fade-in">
                                <div class="flex items-center gap-3">
                                    <i data-lucide="info" class="w-5 h-5 text-blue-600 flex-shrink-0"></i>
                                    <div>
                                        <p class="text-sm font-semibold text-blue-800">Filter Akan Diaplikasikan:</p>
                                        <p class="text-xs text-blue-600 mt-1" id="preview-text">Menampilkan semua data kejahatan</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex gap-3 mt-6 pt-4 border-t border-gray-200">
                            <button id="filter-cancel" 
                                    class="flex-1 py-3 px-4 border border-gray-300 text-gray-700 font-semibold rounded-xl hover:bg-gray-50 transition-all duration-200">
                                Batal
                            </button>
                            <button id="filter-apply" 
                                    class="flex-1 py-3 px-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition-all duration-200 flex items-center justify-center gap-2">
                                <i data-lucide="check" class="w-4 h-4"></i>
                                Terapkan Filter
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PANEL SIDEBAR KIRI (Tetap seperti sebelumnya) -->
        <div class="absolute top-4 left-4 z-[1000] w-[380px] bg-white rounded-xl shadow-2xl border border-gray-200 flex flex-col max-h-[calc(100vh-100px)]"
            id="sidebar-panel">
            <div class="p-5">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i data-lucide="navigation" class="w-5 h-5 text-blue-600"></i> Cari Rute Aman
                </h2>

                <div class="space-y-3 relative">
                    <div class="absolute left-[18px] top-8 bottom-8 w-0.5 bg-gray-300 z-0"></div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-2">
                            <i data-lucide="circle-dot" class="w-5 h-5 text-green-600 bg-white"></i>
                            <div class="flex-1 flex items-center relative">
                                <input type="text" id="start-input" placeholder="Cari atau klik peta untuk titik awal"
                                    class="w-full pl-3 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <button onclick="useCurrentLocation()"
                                    class="absolute right-2 p-1 text-gray-400 hover:text-blue-600 transition"
                                    title="Gunakan Lokasi Saya"><i data-lucide="crosshair" class="w-4 h-4"></i></button>
                                <div id="start-suggestions" class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto z-50"></div>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10">
                        <div class="flex items-center gap-2">
                            <i data-lucide="map-pin" class="w-5 h-5 text-red-600 bg-white"></i>
                            <div class="flex-1 relative">
                                <input type="text" id="end-input" placeholder="Cari atau klik kanan peta untuk tujuan"
                                    class="w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 outline-none">
                                <div id="end-suggestions" class="hidden absolute top-full left-0 right-0 mt-1 bg-white border border-gray-200 rounded-lg shadow-xl max-h-60 overflow-y-auto z-50"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- HAPUS FILTER LAMA DARI SINI -->

                <div class="mt-5">
                    <label class="text-xs font-bold text-gray-500 uppercase mb-2 block">Preferensi Rute</label>
                    <div class="grid grid-cols-2 gap-2">
                        <button onclick="setMode('fastest')" id="btn-fastest"
                            class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg border border-gray-200 text-sm font-bold text-blue-600 bg-blue-50 border-blue-200 transition"><i
                                data-lucide="zap" class="w-4 h-4"></i> Tercepat</button>
                        <button onclick="setMode('safer')" id="btn-safer"
                            class="flex items-center justify-center gap-2 py-2 px-3 rounded-lg border border-gray-200 text-sm font-medium text-gray-600 hover:bg-gray-50 transition"><i
                                data-lucide="shield-check" class="w-4 h-4"></i> Teraman</button>
                    </div>
                </div>

                <div id="safer-filters"
                    class="hidden mt-3 bg-orange-50 p-3 rounded-lg border border-orange-100 animate-fade-in">
                    <p class="text-xs font-bold text-orange-800 mb-2">Hindari Area:</p>
                    <div class="space-y-2">
                        <label
                            class="flex items-center gap-2 cursor-pointer p-1 rounded transition hover:bg-white"><input
                                type="checkbox" class="risk-filter accent-red-600 w-4 h-4" value="Bahaya" checked><span
                                class="w-2 h-2 rounded-full bg-red-600"></span><span
                                class="text-xs font-medium text-gray-700">Bahaya (Merah)</span></label>
                        <label
                            class="flex items-center gap-2 cursor-pointer p-1 rounded transition hover:bg-white"><input
                                type="checkbox" class="risk-filter accent-orange-500 w-4 h-4" value="Rawan"
                                checked><span class="w-2 h-2 rounded-full bg-orange-500"></span><span
                                class="text-xs font-medium text-gray-700">Rawan (Oranye)</span></label>
                        <label
                            class="flex items-center gap-2 cursor-pointer p-1 rounded transition hover:bg-white"><input
                                type="checkbox" class="risk-filter accent-yellow-500 w-4 h-4" value="Siaga"><span
                                class="w-2 h-2 rounded-full bg-yellow-500"></span><span
                                class="text-xs font-medium text-gray-700">Siaga (Kuning)</span></label>
                    </div>
                </div>

                <button onclick="calculateRoute()"
                    class="mt-5 w-full bg-slate-900 hover:bg-slate-800 text-white font-bold py-3 rounded-xl shadow-lg transition transform active:scale-95 flex items-center justify-center gap-2"><i
                        data-lucide="search" class="w-4 h-4"></i> Cari Rute Sekarang</button>

                <div id="route-info" class="hidden mt-4 pt-4 border-t border-gray-100">
                    <button onclick="startNavigation()" id="start-navigation-btn" style="display: none;"
                        class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3 rounded-xl shadow-lg transition transform active:scale-95 flex items-center justify-center gap-2 mb-3">
                        <i data-lucide="navigation" class="w-4 h-4"></i> Mulai Navigasi
                    </button>
                    <div class="flex justify-between items-center bg-gray-50 p-3 rounded-lg border border-gray-200">
                        <div class="text-center">
                            <div class="text-xs text-gray-500">Jarak</div>
                            <div class="font-bold text-gray-800 text-lg" id="info-dist">-</div>
                        </div>
                        <div class="h-8 w-px bg-gray-300"></div>
                        <div class="text-center">
                            <div class="text-xs text-gray-500">Waktu</div>
                            <div class="font-bold text-gray-800 text-lg" id="info-time">-</div>
                        </div>
                    </div>
                    <div id="safety-alert"
                        class="hidden mt-3 bg-red-50 border border-red-100 p-3 rounded-lg flex items-start gap-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5"></i>
                        <div>
                            <h4 class="text-xs font-bold text-red-700">Peringatan Keamanan</h4>
                            <p class="text-[11px] text-red-600 leading-tight mt-1">Rute ini melewati <span
                                    id="conflict-count" class="font-bold underline">0</span> titik rawan.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="map" class="flex-1 h-full z-0 bg-slate-200"></div>
    </div>

    <!-- Modal Statistik Jam Rawan -->
    <div id="hourly-stats-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[3500] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-purple-700 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center gap-2">
                            <i data-lucide="clock" class="w-7 h-7"></i>
                            Jam Rawan Kejahatan
                        </h2>
                        <p class="text-purple-100 text-sm mt-1">Statistik berdasarkan laporan kejahatan per jam</p>
                    </div>
                    <button onclick="closeHourlyStats()" class="hover:bg-purple-600 p-2 rounded-lg transition">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <!-- Legend -->
                <div class="mb-6 p-4 bg-gray-50 rounded-lg">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">Tingkat Kerawanan:</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-green-500"></span>
                            <span class="text-sm font-semibold text-gray-700">Aman</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-yellow-500"></span>
                            <span class="text-sm font-semibold text-gray-700">Siaga</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-orange-500"></span>
                            <span class="text-sm font-semibold text-gray-700">Rawan</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="w-4 h-4 rounded-full bg-red-500"></span>
                            <span class="text-sm font-semibold text-gray-700">Bahaya</span>
                        </div>
                    </div>
                </div>

                <!-- Loading State -->
                <div id="stats-loading" class="text-center py-8">
                    <i data-lucide="loader-2" class="w-8 h-8 text-purple-600 mx-auto animate-spin"></i>
                    <p class="text-gray-600 mt-2">Memuat data...</p>
                </div>

                <!-- Stats Grid -->
                <div id="stats-container" class="hidden grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <!-- Will be populated by JavaScript -->
                </div>

                <!-- Error State -->
                <div id="stats-error" class="hidden text-center py-8">
                    <i data-lucide="alert-circle" class="w-8 h-8 text-red-600 mx-auto"></i>
                    <p class="text-gray-600 mt-2">Gagal memuat data statistik</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Detail Kejahatan Per Jam -->
    <div id="hour-detail-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[4000] hidden flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 p-6 text-white">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="w-7 h-7"></i>
                            Detail Kejahatan - <span id="detail-hour">00:00</span>
                        </h2>
                        <p class="text-indigo-100 text-sm mt-1" id="detail-subtitle">Daftar kejahatan yang terjadi pada jam ini</p>
                    </div>
                    <button onclick="closeHourDetail()" class="hover:bg-indigo-600 p-2 rounded-lg transition">
                        <i data-lucide="x" class="w-6 h-6"></i>
                    </button>
                </div>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]" id="hour-detail-content">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>

    <!-- NAVIGATION MODAL -->
    <div id="navigation-modal" class="hidden fixed inset-0 z-[3000] bg-black bg-opacity-70">
        <div class="flex flex-col h-full">
            <!-- Top Navigation Bar -->
            <div class="bg-white shadow-lg p-4">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                            <i data-lucide="navigation" class="w-6 h-6 text-white"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Navigasi Aktif</h3>
                            <p class="text-xs text-gray-500" id="nav-mode-text">Mode: Teraman</p>
                        </div>
                    </div>
                    <button onclick="stopNavigation()" class="p-2 hover:bg-gray-100 rounded-lg transition">
                        <i data-lucide="x" class="w-6 h-6 text-gray-600"></i>
                    </button>
                </div>
                
                <!-- Current Instruction -->
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                    <div class="flex items-start gap-3">
                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center flex-shrink-0">
                            <i data-lucide="arrow-up" id="nav-arrow-icon" class="w-6 h-6 text-white"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-bold text-blue-900" id="nav-instruction">Memuat instruksi...</p>
                            <p class="text-xs text-blue-600 mt-1" id="nav-distance">0 m</p>
                        </div>
                    </div>
                </div>

                <!-- Progress Info -->
                <div class="grid grid-cols-3 gap-2 mt-3">
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <div class="text-xs text-gray-500">Jarak Tersisa</div>
                        <div class="text-sm font-bold text-gray-800" id="nav-remaining-distance">-</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <div class="text-xs text-gray-500">Waktu Tersisa</div>
                        <div class="text-sm font-bold text-gray-800" id="nav-remaining-time">-</div>
                    </div>
                    <div class="bg-gray-50 rounded-lg p-2 text-center">
                        <div class="text-xs text-gray-500">Kecepatan</div>
                        <div class="text-sm font-bold text-gray-800" id="nav-speed">0 km/h</div>
                    </div>
                </div>
            </div>

            <!-- Map View (Centered on User) -->
            <div id="navigation-map" class="flex-1 relative">
                <!-- Map will be recentered here -->
            </div>

            <!-- Bottom Controls -->
            <div class="bg-white shadow-lg p-4 flex gap-3">
                <button onclick="recenterNavigation()" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
                    <i data-lucide="crosshair" class="w-5 h-5"></i> Pusatkan Lokasi
                </button>
                <button onclick="stopNavigation()" class="flex-1 bg-red-500 hover:bg-red-600 text-white font-semibold py-3 rounded-xl transition flex items-center justify-center gap-2">
                    <i data-lucide="square" class="w-5 h-5"></i> Berhenti
                </button>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script>lucide.createIcons();</script>

    <script src="../assets/mapScript.js"></script>
    
    <script>
        // VARIABEL FILTER
        let currentFilter = 'all';
        
        // ELEMENTS
        const filterToggle = document.getElementById('filter-toggle');
        const filterModal = document.getElementById('filter-modal');
        const filterModalContent = document.getElementById('filter-modal-content');
        const filterClose = document.getElementById('filter-close');
        const filterCancel = document.getElementById('filter-cancel');
        const filterApply = document.getElementById('filter-apply');
        const filterPreview = document.getElementById('filter-preview');
        const previewText = document.getElementById('preview-text');
        const crimeLevels = document.querySelectorAll('input[name="crime-level"]');

        // FUNGSI BUKA MODAL
        filterToggle.addEventListener('click', () => {
            filterModal.classList.remove('hidden');
            setTimeout(() => {
                filterModalContent.classList.remove('scale-95', 'opacity-0');
                filterModalContent.classList.add('scale-100', 'opacity-100');
            }, 10);
        });

        // FUNGSI TUTUP MODAL
        function closeModal() {
            filterModalContent.classList.remove('scale-100', 'opacity-100');
            filterModalContent.classList.add('scale-95', 'opacity-0');
            setTimeout(() => {
                filterModal.classList.add('hidden');
            }, 300);
        }

        filterClose.addEventListener('click', closeModal);
        filterCancel.addEventListener('click', closeModal);

        // UPDATE PREVIEW SAAT PILIH FILTER
        crimeLevels.forEach(radio => {
            radio.addEventListener('change', function() {
                const selectedValue = this.value;
                const selectedText = this.parentElement.querySelector('span').textContent;
                
                filterPreview.classList.remove('hidden');
                
                if (selectedValue === 'all') {
                    previewText.textContent = 'Menampilkan semua data kejahatan';
                    filterPreview.className = 'bg-blue-50 border border-blue-200 rounded-xl p-4 animate-fade-in';
                } else {
                    previewText.textContent = `Hanya menampilkan data dengan level: ${selectedText}`;
                    // Sesuaikan warna preview dengan level yang dipilih
                    const colorClass = {
                        'Bahaya': 'bg-red-50 border-red-200',
                        'Rawan': 'bg-orange-50 border-orange-200',
                        'Siaga': 'bg-yellow-50 border-yellow-200',
                        'Aman': 'bg-green-50 border-green-200'
                    }[selectedValue];
                    filterPreview.className = `${colorClass} rounded-xl p-4 animate-fade-in`;
                }
            });
        });

        // APLIKASI FILTER
        filterApply.addEventListener('click', function() {
            const selectedLevel = document.querySelector('input[name="crime-level"]:checked').value;
            
            // Tampilkan loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Menerapkan...';
            this.disabled = true;
            
            // Simpan filter yang dipilih
            currentFilter = selectedLevel;
            
            // Update tombol filter
            updateFilterButton(selectedLevel);
            
            // Tutup modal setelah delay
            setTimeout(() => {
                closeModal();
                
                // Terapkan filter ke peta
                if (typeof filterCrimeData === 'function') {
                    filterCrimeData(selectedLevel);
                }
                
                // Reset button state
                this.innerHTML = originalText;
                this.disabled = false;
                
                // Tampilkan notifikasi sukses
                showFilterNotification(selectedLevel);
            }, 1000);
        });

        // UPDATE TAMPILAN TOMBOL FILTER
        function updateFilterButton(level) {
            const filterIcon = filterToggle.querySelector('i');
            const filterText = filterToggle.querySelector('span');
            
            if (level === 'all') {
                filterToggle.className = 'bg-white rounded-xl shadow-2xl border border-gray-200 px-4 py-3 flex items-center gap-2 hover:bg-gray-50 transition-all duration-200';
                filterText.textContent = 'Filter Data';
            } else {
                const colorClass = {
                    'Bahaya': 'bg-red-50 border-red-200 text-red-700',
                    'Rawan': 'bg-orange-50 border-orange-200 text-orange-700',
                    'Siaga': 'bg-yellow-50 border-yellow-200 text-yellow-700',
                    'Aman': 'bg-green-50 border-green-200 text-green-700'
                }[level];
                
                filterToggle.className = `${colorClass} rounded-xl shadow-2xl border px-4 py-3 flex items-center gap-2 hover:opacity-90 transition-all duration-200`;
                filterText.textContent = `Filter: ${level}`;
            }
        }

        // NOTIFIKASI FILTER BERHASIL
        function showFilterNotification(level) {
            // Buat elemen notifikasi
            const notification = document.createElement('div');
            notification.className = 'fixed top-20 right-4 z-[2000] bg-white rounded-xl shadow-2xl border border-gray-200 px-4 py-3 flex items-center gap-3 animate-fade-in';
            
            const levelNames = {
                'all': 'Semua Data',
                'Bahaya': 'Area Bahaya',
                'Rawan': 'Area Rawan', 
                'Siaga': 'Area Siaga',
                'Aman': 'Area Aman'
            };
            
            notification.innerHTML = `
                <i data-lucide="check-circle" class="w-5 h-5 text-green-600"></i>
                <div>
                    <p class="text-sm font-semibold text-gray-800">Filter Berhasil Diterapkan</p>
                    <p class="text-xs text-gray-600">Menampilkan: ${levelNames[level]}</p>
                </div>
            `;
            
            document.body.appendChild(notification);
            lucide.createIcons();
            
            // Hapus notifikasi setelah 3 detik
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // TUTUP MODAL SAAT KLIK DI LUAR
        filterModal.addEventListener('click', (e) => {
            if (e.target === filterModal) {
                closeModal();
            }
        });

        // ========== HOURLY STATS FUNCTIONS ==========
        function showHourlyStats() {
            const modal = document.getElementById('hourly-stats-modal');
            const loading = document.getElementById('stats-loading');
            const container = document.getElementById('stats-container');
            const error = document.getElementById('stats-error');
            
            // Show modal
            modal.classList.remove('hidden');
            
            // Reset states
            loading.classList.remove('hidden');
            container.classList.add('hidden');
            error.classList.add('hidden');
            
            // Fetch data
            fetch('../controller/hourly_stats.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayHourlyStats(data.data);
                        loading.classList.add('hidden');
                        container.classList.remove('hidden');
                    } else {
                        throw new Error(data.error || 'Unknown error');
                    }
                })
                .catch(err => {
                    console.error('Error fetching hourly stats:', err);
                    loading.classList.add('hidden');
                    error.classList.remove('hidden');
                })
                .finally(() => {
                    lucide.createIcons();
                });
        }

        function closeHourlyStats() {
            document.getElementById('hourly-stats-modal').classList.add('hidden');
        }

        // Store hourly stats globally for detail view
        let hourlyStatsData = [];

        function displayHourlyStats(stats) {
            const container = document.getElementById('stats-container');
            container.innerHTML = '';
            hourlyStatsData = stats; // Store for later use
            
            console.log('Total hours with data:', stats.length);
            console.log('Sample stat:', stats[0]);
            
            stats.forEach(stat => {
                const hour = stat.hour;
                const hourStr = hour.toString().padStart(2, '0') + ':00';
                const total = stat.total;
                const level = stat.display_level;
                const color = stat.color;
                
                let bgColor, borderColor, textColor;
                if (stat.dominant_level === 4) {
                    bgColor = 'bg-red-50';
                    borderColor = 'border-red-500';
                    textColor = 'text-red-700';
                } else if (stat.dominant_level === 3) {
                    bgColor = 'bg-orange-50';
                    borderColor = 'border-orange-500';
                    textColor = 'text-orange-700';
                } else if (stat.dominant_level === 2) {
                    bgColor = 'bg-yellow-50';
                    borderColor = 'border-yellow-500';
                    textColor = 'text-yellow-700';
                } else {
                    bgColor = 'bg-green-50';
                    borderColor = 'border-green-500';
                    textColor = 'text-green-700';
                }
                
                const card = document.createElement('div');
                card.className = `${bgColor} border-2 ${borderColor} rounded-lg p-4 transition hover:shadow-xl hover:scale-105 cursor-pointer transform`;
                card.innerHTML = `
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-2xl font-bold text-gray-800">${hourStr}</span>
                        <span class="w-3 h-3 rounded-full" style="background-color: ${color}"></span>
                    </div>
                    <div class="text-sm font-bold ${textColor} mb-1">${level}</div>
                    <div class="text-xs text-gray-600 flex items-center gap-1">
                        <i data-lucide="file-text" class="w-3 h-3"></i>
                        ${total} laporan
                    </div>
                    <div class="mt-2 pt-2 border-t border-gray-200">
                        <div class="grid grid-cols-2 gap-1 text-xs">
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                <span class="text-gray-600">${stat.level_1}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                                <span class="text-gray-600">${stat.level_2}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-orange-500"></span>
                                <span class="text-gray-600">${stat.level_3}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                <span class="text-gray-600">${stat.level_4}</span>
                            </div>
                        </div>
                    </div>
                    ${(stat.crimes && stat.crimes.length > 0) ? '<div class="mt-2 text-center"><span class="text-xs text-gray-500 italic">Klik untuk detail</span></div>' : ''}
                `;
                
                // Add click event to show details only if there are crimes
                const hasCrimes = stat.crimes && stat.crimes.length > 0;
                console.log(`Hour ${hour}: total=${total}, crimes=${stat.crimes ? stat.crimes.length : 0}, hasCrimes=${hasCrimes}`);
                
                if (hasCrimes) {
                    card.addEventListener('click', () => {
                        console.log('Clicked hour:', hour);
                        showHourDetail(hour);
                    });
                } else {
                    card.style.cursor = 'default';
                    card.classList.remove('hover:scale-105', 'hover:shadow-xl');
                }
                
                container.appendChild(card);
            });
            
            // Re-create icons after adding cards
            lucide.createIcons();
        }

        // Close modal when clicking outside
        document.getElementById('hourly-stats-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeHourlyStats();
            }
        });

        // ========== HOUR DETAIL FUNCTIONS ==========
        function showHourDetail(hour) {
            console.log('showHourDetail called with hour:', hour);
            console.log('hourlyStatsData:', hourlyStatsData);
            
            const stat = hourlyStatsData.find(s => s.hour === hour);
            console.log('Found stat:', stat);
            
            if (!stat) {
                console.error('No stat found for hour:', hour);
                return;
            }
            
            const modal = document.getElementById('hour-detail-modal');
            const hourStr = hour.toString().padStart(2, '0') + ':00';
            const crimes = stat.crimes || [];
            
            console.log('Crimes for hour', hour, ':', crimes.length);
            
            // Update modal title
            document.getElementById('detail-hour').textContent = hourStr;
            document.getElementById('detail-subtitle').textContent = 
                `${crimes.length} kejahatan tercatat pada jam ini`;
            
            // Build content
            const content = document.getElementById('hour-detail-content');
            
            if (crimes.length === 0) {
                content.innerHTML = `
                    <div class="text-center py-12">
                        <i data-lucide="check-circle" class="w-16 h-16 text-green-500 mx-auto mb-4"></i>
                        <h3 class="text-lg font-bold text-gray-800">Tidak Ada Laporan</h3>
                        <p class="text-gray-600 text-sm mt-2">Belum ada kejahatan yang dilaporkan pada jam ini</p>
                    </div>
                `;
                lucide.createIcons();
            } else {
                let html = '<div class="space-y-4">';
                
                crimes.forEach((crime, index) => {
                    // Escape HTML in strings
                    const escapedLocation = String(crime.location_name).replace(/'/g, "\\'").replace(/"/g, '&quot;');
                    const escapedDesc = String(crime.description).replace(/</g, '&lt;').replace(/>/g, '&gt;');
                    // Tentukan warna berdasarkan level
                    let levelColor, levelBg, levelBorder;
                    if (crime.level_id === 4) {
                        levelColor = 'text-red-700';
                        levelBg = 'bg-red-50';
                        levelBorder = 'border-red-200';
                    } else if (crime.level_id === 3) {
                        levelColor = 'text-orange-700';
                        levelBg = 'bg-orange-50';
                        levelBorder = 'border-orange-200';
                    } else if (crime.level_id === 2) {
                        levelColor = 'text-yellow-700';
                        levelBg = 'bg-yellow-50';
                        levelBorder = 'border-yellow-200';
                    } else {
                        levelColor = 'text-green-700';
                        levelBg = 'bg-green-50';
                        levelBorder = 'border-green-200';
                    }
                    
                    html += `
                        <div class="${levelBg} border ${levelBorder} rounded-xl p-4 hover:shadow-md transition">
                            <div class="flex items-start justify-between mb-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <span class="px-2 py-1 ${levelBg} ${levelColor} text-xs font-bold rounded-full border ${levelBorder}">
                                            ${crime.level_name}
                                        </span>
                                        <span class="px-2 py-1 bg-gray-100 text-gray-700 text-xs font-semibold rounded-full">
                                            ${crime.crime_type}
                                        </span>
                                    </div>
                                    <h4 class="font-bold text-gray-800 flex items-center gap-2">
                                        <i data-lucide="map-pin" class="w-4 h-4 text-gray-600"></i>
                                        ${crime.location_name}
                                    </h4>
                                    <p class="text-xs text-gray-600 mt-1 flex items-center gap-1">
                                        <i data-lucide="map" class="w-3 h-3"></i>
                                        ${crime.area || 'N/A'}
                                    </p>
                                </div>
                                <button onclick="showCrimeOnMap(${crime.latitude}, ${crime.longitude}, '${escapedLocation}')" 
                                        class="ml-3 p-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition flex-shrink-0"
                                        title="Lihat di Peta">
                                    <i data-lucide="navigation" class="w-4 h-4"></i>
                                </button>
                            </div>
                            
                            <div class="bg-white rounded-lg p-3 mb-3">
                                <p class="text-sm text-gray-700 leading-relaxed">${escapedDesc}</p>
                            </div>
                            
                            <div class="flex items-center justify-between text-xs text-gray-500">
                                <span class="flex items-center gap-1">
                                    <i data-lucide="calendar" class="w-3 h-3"></i>
                                    ${crime.crime_date}
                                </span>
                                <span class="flex items-center gap-1">
                                    <i data-lucide="map-pin" class="w-3 h-3"></i>
                                    ${Number(crime.latitude).toFixed(6)}, ${Number(crime.longitude).toFixed(6)}
                                </span>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                content.innerHTML = html;
                
                // Create icons after HTML is inserted
                setTimeout(() => {
                    lucide.createIcons();
                }, 10);
            }
            
            // Show modal
            modal.classList.remove('hidden');
        }

        function closeHourDetail() {
            document.getElementById('hour-detail-modal').classList.add('hidden');
        }

        function showCrimeOnMap(lat, lng, locationName) {
            // Close both modals
            closeHourDetail();
            closeHourlyStats();
            
            // Pan map to location
            if (typeof map !== 'undefined') {
                map.setView([lat, lng], 16);
                
                // Add temporary marker
                const marker = L.marker([lat, lng], {
                    icon: L.divIcon({
                        className: 'custom-marker',
                        html: '<div style="background: #ef4444; width: 30px; height: 30px; border-radius: 50%; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"></div>',
                        iconSize: [30, 30]
                    })
                }).addTo(map);
                
                marker.bindPopup(`
                    <div class="text-center">
                        <strong class="text-red-600">⚠️ Lokasi Kejahatan</strong><br>
                        <span class="text-sm">${locationName}</span>
                    </div>
                `).openPopup();
                
                // Remove marker after 5 seconds
                setTimeout(() => {
                    map.removeLayer(marker);
                }, 5000);
            }
        }

        // Close detail modal when clicking outside
        document.getElementById('hour-detail-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeHourDetail();
            }
        });
    </script>
</body>

</html>