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

        <!-- TOMBOL FILTER KANAN ATAS -->
        <div class="absolute top-4 right-4 z-[1000]">
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
    </script>
</body>

</html>