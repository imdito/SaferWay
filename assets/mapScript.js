// assets/js/mapScript.js

const API_URL = '../controller/controller.php';

// Init Map
const map = L.map('map', { zoomControl: false }).setView([-7.7956, 110.3695], 11);
L.control.zoom({ position: 'bottomright' }).addTo(map);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19, attribution: '© OpenStreetMap'
}).addTo(map);

let crimeData = [];
let crimeLayer = null; // Layer untuk crime data
let routingControl = null;
let startPoint = null;
let endPoint = null;
let currentMode = 'fastest';
let searchTimeout = null;
let startMarker = null;
let endMarker = null;

// Navigation variables
let navigationActive = false;
let navigationRoute = null;
let navigationSteps = [];
let currentStepIndex = 0;
let userLocationMarker = null;
let navigationWatchId = null;
let lastUserPosition = null;
let totalRouteDistance = 0; // Store total route distance in meters
let totalRouteTime = 0; // Store total route time in minutes

// --- LOAD DATA dengan Filter ---
async function loadCrimeData(filterLevel = 'all') {
    try {
        const url = filterLevel === 'all' ? API_URL : `${API_URL}?level=${filterLevel}`;
        const response = await fetch(url);
        const data = await response.json();
        crimeData = data.features;

        // Hapus layer crime yang lama jika ada
        if (crimeLayer) {
            map.removeLayer(crimeLayer);
        }

        // Buat layer baru
        crimeLayer = L.geoJSON(data, {
            pointToLayer: function (feature, latlng) {
                const props = feature.properties;
                // Ukuran marker: Single=Kecil, Cluster=Sedikit Besar
                const radiusSize = props.count === 1 ? 12 : (14 + (props.count * 1.2));

                return L.circleMarker(latlng, {
                    radius: Math.min(radiusSize, 35),
                    fillColor: props.color, // Warna Marker (Hasil Prioritas PHP)
                    color: "white",
                    weight: 2,
                    opacity: 1,
                    fillOpacity: 0.9
                });
            },
            onEachFeature: function (feature, layer) {
                const props = feature.properties;
                const reports = props.reports;

                // HEADER POPUP (Menampilkan Status Terburuk)
                let html = `
                    <div class="w-[260px] font-sans">
                        <div class="flex justify-between items-center mb-2 pb-2 border-b border-gray-100">
                            <span class="text-xs font-bold px-2 py-1 rounded text-white shadow-sm" 
                                  style="background-color:${props.color}">
                                ${props.status}
                            </span>
                        </div>
                        
                        <div class="flex overflow-x-auto snap-x snap-mandatory gap-2 pb-2 custom-scroll">
                `;

                // LOOP KARTU LAPORAN
                reports.forEach((rpt, idx) => {
                    const dateStr = new Date(rpt.date).toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });

                    html += `
                        <div class="flex-none w-full snap-center bg-gray-50 border border-gray-200 rounded p-2 shadow-sm">
                            <div class="flex justify-between items-center mb-1">
                                <span class="text-[10px] text-white px-2 py-0.5 rounded font-bold" 
                                      style="background-color: ${rpt.color}">
                                    ${rpt.level}
                                </span>
                                <span class="text-[10px] text-gray-400">${dateStr}</span>
                            </div>
                            <div class="font-bold text-sm text-gray-800 mb-0.5">${rpt.type}</div>
                            <div class="text-[10px] text-gray-500 mb-1 truncate">📍 ${rpt.location}</div>
                            <p class="text-[11px] text-gray-600 italic border-l-2 border-gray-300 pl-2">"${rpt.desc}"</p>
                        </div>
                    `;
                });

                html += `</div>`; // Tutup slider

                if (props.count > 1) {
                    html += `<div class="text-center text-[10px] text-gray-400 mt-1">↔ Geser kanan kiri (${props.count} laporan)</div>`;
                }

                html += `</div>`; // Tutup main container

                layer.bindPopup(html, { maxWidth: 300 });
            }
        }).addTo(map);
    } catch (e) { 
        console.error("Gagal load data:", e); 
    }
}

// --- FITUR GPS ---
function useCurrentLocation() {
    const inputA = document.getElementById('start-input');
    inputA.value = "Mencari...";
    inputA.parentElement.classList.add("animate-pulse");
    map.locate({ setView: true, maxZoom: 16 });
}

map.on('locationfound', (e) => {
    document.getElementById('start-input').parentElement.classList.remove("animate-pulse");
    startPoint = e.latlng;
    document.getElementById('start-input').value = "📍 Lokasi Saya";

    map.eachLayer(l => { if (l.options.className === 'gps-marker') map.removeLayer(l); });
    L.circleMarker(e.latlng, { radius: 8, color: '#2563eb', fillColor: '#3b82f6', fillOpacity: 1, className: 'gps-marker' }).addTo(map).bindPopup("Posisi Anda").openPopup();

    if (endPoint) {
        calculateRoute();
    }
    // Update navigation button visibility
    updateNavigationButtonVisibility();
});

map.on('locationerror', () => { 
    alert("Gagal ambil lokasi GPS."); 
    document.getElementById('start-input').value = ""; 
});

// --- KLIK PETA ---
// Left click for start point
map.on('click', (e) => {
    setStart(e.latlng);
});

// Right click for destination
map.on('contextmenu', (e) => {
    setEnd(e.latlng);
});

function setStart(latlng) {
    startPoint = latlng;
    document.getElementById('start-input').value = `📍 ${latlng.lat.toFixed(5)}, ${latlng.lng.toFixed(5)}`;
    startSuggestions.classList.add('hidden');
    
    // Remove old marker
    if (startMarker) map.removeLayer(startMarker);
    
    // Add new marker
    startMarker = L.circleMarker(latlng, {
        radius: 8,
        color: '#10b981',
        fillColor: '#34d399',
        fillOpacity: 1,
        weight: 2
    }).addTo(map).bindPopup("📍 Titik Awal").openPopup();
    
    if (routingControl) { 
        map.removeControl(routingControl); 
        routingControl = null; 
    }
    
    // Update navigation button visibility
    updateNavigationButtonVisibility();
    
    if (endPoint) calculateRoute();
}

function setEnd(latlng) {
    endPoint = latlng;
    document.getElementById('end-input').value = `🎯 ${latlng.lat.toFixed(5)}, ${latlng.lng.toFixed(5)}`;
    endSuggestions.classList.add('hidden');
    
    // Remove old marker
    if (endMarker) map.removeLayer(endMarker);
    
    // Add new marker
    endMarker = L.circleMarker(latlng, {
        radius: 8,
        color: '#ef4444',
        fillColor: '#f87171',
        fillOpacity: 1,
        weight: 2
    }).addTo(map).bindPopup("🎯 Tujuan").openPopup();
    
    calculateRoute();
}

// --- MODE & ROUTE ---
function setMode(mode) {
    currentMode = mode;
    document.getElementById('safer-filters').classList.toggle('hidden', mode === 'fastest');

    const btnFast = document.getElementById('btn-fastest');
    const btnSafe = document.getElementById('btn-safer');
    const activeClass = "bg-blue-50 border-blue-200 text-blue-600 font-bold shadow-sm";
    const inactiveClass = "text-gray-600 hover:bg-gray-50 font-medium border-transparent";

    if (mode === 'fastest') {
        btnFast.className = `flex items-center justify-center gap-2 py-2 px-3 rounded-lg border text-sm transition ${activeClass}`;
        btnSafe.className = `flex items-center justify-center gap-2 py-2 px-3 rounded-lg border text-sm transition ${inactiveClass}`;
    } else {
        btnSafe.className = `flex items-center justify-center gap-2 py-2 px-3 rounded-lg border text-sm transition ${activeClass.replace('blue', 'orange')}`;
        btnFast.className = `flex items-center justify-center gap-2 py-2 px-3 rounded-lg border text-sm transition ${inactiveClass}`;
    }
    if (startPoint && endPoint) calculateRoute();
}

// Variabel global untuk menyimpan layer rute agar bisa dihapus saat cari ulang
let currentRouteLayer = null;

async function calculateSafeRoute() {
    // 1. Validasi Input
    if (!startPoint || !endPoint) {
        alert("Harap pilih lokasi asal dan tujuan pada peta terlebih dahulu!");
        return;
    }

    // 2. Bersihkan Peta (Hapus rute OSRM lama atau rute GeoJSON lama)
    if (routingControl) {
        map.removeControl(routingControl);
        routingControl = null;
    }
    if (currentRouteLayer) {
        map.removeLayer(currentRouteLayer);
        currentRouteLayer = null;
    }

    // Sembunyikan panel info & alert saat loading
    document.getElementById('route-info').classList.add('hidden');
    document.getElementById('safety-alert').classList.add('hidden');

    // 3. Persiapkan Data untuk API
    // Handle format LatLng object dari Leaflet
    const lat1 = startPoint.lat;
    const lng1 = startPoint.lng;
    const lat2 = endPoint.lat;
    const lng2 = endPoint.lng;

    // Mapping mode: 'safer' -> 'safe' (untuk backend), 'fastest' -> 'fast'
    const modeBackend = (currentMode === 'safer') ? 'safe' : 'fast';

    // 4. Panggil API PostGIS
    // Pastikan path ini sesuai dengan struktur folder Anda
    const url = `../controller/get_route.php?start_lat=${lat1}&start_lng=${lng1}&end_lat=${lat2}&end_lng=${lng2}&mode=${modeBackend}`;

    try {
        console.log(`Mencari rute ${modeBackend} via PostGIS...`);
        
        // Tampilkan loading indicator sederhana (opsional)
        document.getElementById('info-dist').innerText = "Loading...";
        document.getElementById('route-info').classList.remove('hidden');

        const response = await fetch(url);
        const data = await response.json();

        // Cek jika API mengembalikan error
        if (data.error) {
            throw new Error(data.error);
        }

        // 5. Gambar Rute ke Peta
        currentRouteLayer = L.geoJSON(data, {
            style: function(feature) {
                return {
                    // Hijau untuk Aman, Biru/Abu untuk Cepat
                    color: modeBackend === 'safe' ? '#10b981' : '#3b82f6', 
                    weight: 6,
                    opacity: 0.8,
                    lineCap: 'round',
                    lineJoin: 'round'
                };
            }
        }).addTo(map);

        // Zoom peta agar rute terlihat semua
        map.fitBounds(currentRouteLayer.getBounds(), { padding: [50, 50] });

        // 6. Hitung Statistik (Jarak & Waktu) Manual
        // Karena pgRouting mengirim bentuk garis, kita hitung panjangnya pakai Leaflet
        let totalDistanceMeters = 0;
        
        currentRouteLayer.eachLayer(function(layer) {
            if (layer instanceof L.Polyline) {
                // Mengambil semua titik koordinat garis
                const latlngs = layer.getLatLngs();
                
                // Ratakan array jika MultiLineString (array bersarang)
                const flatLatLngs = Array.isArray(latlngs[0]) ? latlngs.flat(Infinity) : latlngs;

                for (let i = 0; i < flatLatLngs.length - 1; i++) {
                    totalDistanceMeters += flatLatLngs[i].distanceTo(flatLatLngs[i + 1]);
                }
            }
        });

        // Konversi ke KM & Menit
        const distanceKm = (totalDistanceMeters / 1000).toFixed(1);
        const speedKmh = 30; // Asumsi kecepatan rata-rata dalam kota
        const timeMinutes = Math.round((distanceKm / speedKmh) * 60);

        // Store globally for navigation
        totalRouteDistance = totalDistanceMeters;
        totalRouteTime = timeMinutes;

        // 7. Update UI Info Panel
        document.getElementById('route-info').classList.remove('hidden');
        document.getElementById('info-dist').innerText = distanceKm + " km";
        document.getElementById('info-time').innerText = timeMinutes + " mnt";
        
        // Always check and update navigation button visibility after route calculation
        updateNavigationButtonVisibility();

        // 8. Update Alert Keamanan
        const alertBox = document.getElementById('safety-alert');
        const conflictCount = document.getElementById('conflict-count'); // Span angka di HTML

        if (modeBackend === 'safe') {
            alertBox.classList.remove('hidden');
            // Kita ubah styling alert jadi hijau karena aman
            alertBox.className = "mt-3 bg-green-50 border border-green-200 rounded-lg p-3 flex items-start gap-3";
            alertBox.innerHTML = `
                <div class="text-green-500 mt-0.5">🛡️</div>
                <div>
                    <div class="text-xs font-bold text-green-700 uppercase tracking-wide">Rute Terproteksi</div>
                    <div class="text-xs text-green-600 mt-0.5">Jalur ini dipilih server karena menghindari titik rawan kriminalitas.</div>
                </div>
            `;
        } else {
            // Mode Cepat (Fastest) - Peringatan Waspada
            alertBox.classList.remove('hidden');
            alertBox.className = "mt-3 bg-red-50 border border-red-200 rounded-lg p-3 flex items-start gap-3";
            alertBox.innerHTML = `
                <div class="text-red-500 mt-0.5">⚠️</div>
                <div>
                    <div class="text-xs font-bold text-red-700 uppercase tracking-wide">Mode Tercepat</div>
                    <div class="text-xs text-red-600 mt-0.5">Waspada! Rute ini mungkin melewati area rawan demi efisiensi waktu.</div>
                </div>
            `;
        }

    } catch (error) {
        console.error("Routing Error:", error);
        alert("Gagal menghitung rute: " + error.message);
        document.getElementById('route-info').classList.add('hidden');
    }
}

// Override fungsi calculateRoute lama agar memanggil fungsi baru ini
function calculateRoute() {
    calculateSafeRoute();
}

// --- FUNGSI FILTER DATA KEJAHATAN ---
function filterCrimeData(level) {
    console.log('Menerapkan filter:', level);
    loadCrimeData(level);
}

// --- AUTOCOMPLETE SEARCH FUNCTIONALITY ---
const startInput = document.getElementById('start-input');
const endInput = document.getElementById('end-input');
const startSuggestions = document.getElementById('start-suggestions');
const endSuggestions = document.getElementById('end-suggestions');

// Debounced search function using integrated API
async function searchLocation(query, suggestionsElement, isStart) {
    if (query.length < 2) {
        suggestionsElement.classList.add('hidden');
        return;
    }
    
    const url = `../controller/search_location.php?q=${encodeURIComponent(query)}`;
    
    try {
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.results && data.results.length > 0) {
            displaySuggestions(data.results, suggestionsElement, isStart);
        } else {
            suggestionsElement.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">Tidak ada hasil ditemukan</div>';
            suggestionsElement.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Search error:', error);
        suggestionsElement.innerHTML = '<div class="p-3 text-sm text-red-500 text-center">Error mencari lokasi</div>';
        suggestionsElement.classList.remove('hidden');
    }
}

// Display search suggestions with enhanced UI for local crime data
function displaySuggestions(results, suggestionsElement, isStart) {
    suggestionsElement.innerHTML = '';
    
    results.forEach(result => {
        const item = document.createElement('div');
        item.className = 'suggestion-item p-3 cursor-pointer border-b border-gray-100 last:border-b-0';
        
        // Different styling for local crime data vs external locations
        if (result.type === 'local') {
            const crimeIcon = result.crime_count >= 5 ? '🔴' : result.crime_count >= 3 ? '🟠' : '🟡';
            
            item.innerHTML = `
                <div class="flex items-start gap-2">
                    <i data-lucide="alert-circle" class="w-4 h-4 text-orange-500 mt-0.5 flex-shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2">
                            <div class="text-sm font-medium text-gray-800 truncate">${result.display_name}</div>
                            <span class="text-xs px-1.5 py-0.5 bg-orange-100 text-orange-700 rounded font-bold">${result.crime_count}</span>
                        </div>
                        <div class="text-xs text-gray-500">${result.area} ${crimeIcon}</div>
                    </div>
                </div>
            `;
        } else {
            const displayName = result.label || result.display_name.split(',')[0];
            const fullName = result.display_name;
            
            item.innerHTML = `
                <div class="flex items-start gap-2">
                    <i data-lucide="map-pin" class="w-4 h-4 text-blue-500 mt-0.5 flex-shrink-0"></i>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-800 truncate">${displayName}</div>
                        <div class="text-xs text-gray-500 truncate">${fullName}</div>
                    </div>
                </div>
            `;
        }
        
        item.addEventListener('click', () => {
            selectLocation(result, isStart);
        });
        
        suggestionsElement.appendChild(item);
    });
    
    lucide.createIcons();
    suggestionsElement.classList.remove('hidden');
}

// Select a location from suggestions
function selectLocation(result, isStart) {
    const latlng = L.latLng(parseFloat(result.lat), parseFloat(result.lon));
    const displayName = result.label || result.display_name.split(',')[0];
    
    if (isStart) {
        startPoint = latlng;
        startInput.value = displayName;
        startSuggestions.classList.add('hidden');
        
        // Remove old marker
        if (startMarker) map.removeLayer(startMarker);
        
        // Add new marker with different color for crime locations
        const markerColor = result.type === 'local' ? '#f59e0b' : '#10b981';
        const markerFill = result.type === 'local' ? '#fbbf24' : '#34d399';
        
        startMarker = L.circleMarker(latlng, {
            radius: result.type === 'local' ? 10 : 8,
            color: markerColor,
            fillColor: markerFill,
            fillOpacity: 1,
            weight: 2
        }).addTo(map);
        
        // Add popup with crime warning if local location
        if (result.type === 'local') {
            startMarker.bindPopup(`
                📍 Titik Awal: ${displayName}<br>
                <span class="text-xs text-orange-600">⚠️ ${result.crime_count} laporan kejahatan</span>
            `).openPopup();
        } else {
            startMarker.bindPopup(`📍 Titik Awal: ${displayName}`).openPopup();
        }
        
        map.setView(latlng, 16);
        
        // Update navigation button visibility
        updateNavigationButtonVisibility();
        
        // Auto calculate if end point exists
        if (endPoint) calculateRoute();
    } else {
        endPoint = latlng;
        endInput.value = displayName;
        endSuggestions.classList.add('hidden');
        
        // Remove old marker
        if (endMarker) map.removeLayer(endMarker);
        
        // Add new marker with different color for crime locations
        const markerColor = result.type === 'local' ? '#f59e0b' : '#ef4444';
        const markerFill = result.type === 'local' ? '#fbbf24' : '#f87171';
        
        endMarker = L.circleMarker(latlng, {
            radius: result.type === 'local' ? 10 : 8,
            color: markerColor,
            fillColor: markerFill,
            fillOpacity: 1,
            weight: 2
        }).addTo(map);
        
        // Add popup with crime warning if local location
        if (result.type === 'local') {
            endMarker.bindPopup(`
                🎯 Tujuan: ${displayName}<br>
                <span class="text-xs text-orange-600">⚠️ ${result.crime_count} laporan kejahatan</span>
            `).openPopup();
        } else {
            endMarker.bindPopup(`🎯 Tujuan: ${displayName}`).openPopup();
        }
        
        map.setView(latlng, 16);
        
        // Auto calculate if start point exists
        if (startPoint) calculateRoute();
    }
}

// Event listeners for search inputs
startInput.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();
    
    if (query.length < 2) {
        startSuggestions.classList.add('hidden');
        return;
    }
    
    // Show loading state
    startSuggestions.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center flex items-center justify-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Mencari...</div>';
    startSuggestions.classList.remove('hidden');
    lucide.createIcons();
    
    searchTimeout = setTimeout(() => {
        searchLocation(query, startSuggestions, true);
    }, 400);
});

endInput.addEventListener('input', (e) => {
    clearTimeout(searchTimeout);
    const query = e.target.value.trim();
    
    if (query.length < 2) {
        endSuggestions.classList.add('hidden');
        return;
    }
    
    // Show loading state
    endSuggestions.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center flex items-center justify-center gap-2"><i data-lucide="loader-2" class="w-4 h-4 animate-spin"></i> Mencari...</div>';
    endSuggestions.classList.remove('hidden');
    lucide.createIcons();
    
    searchTimeout = setTimeout(() => {
        searchLocation(query, endSuggestions, false);
    }, 400);
});

// Clear search when input is focused and empty
startInput.addEventListener('focus', () => {
    if (startInput.value.trim() === '') {
        startPoint = null;
        if (startMarker) {
            map.removeLayer(startMarker);
            startMarker = null;
        }
    }
});

endInput.addEventListener('focus', () => {
    if (endInput.value.trim() === '') {
        endPoint = null;
        if (endMarker) {
            map.removeLayer(endMarker);
            endMarker = null;
        }
    }
});

// Close suggestions when clicking outside
document.addEventListener('click', (e) => {
    if (!startInput.contains(e.target) && !startSuggestions.contains(e.target)) {
        startSuggestions.classList.add('hidden');
    }
    if (!endInput.contains(e.target) && !endSuggestions.contains(e.target)) {
        endSuggestions.classList.add('hidden');
    }
});

// --- NAVIGATION FUNCTIONS ---

function showNavigationButton() {
    const navButton = document.getElementById('start-navigation-btn');
    if (navButton) {
        navButton.classList.remove('hidden');
        navButton.style.display = 'flex';
    }
}

function hideNavigationButton() {
    const navButton = document.getElementById('start-navigation-btn');
    if (navButton) {
        navButton.classList.add('hidden');
        navButton.style.display = 'none';
    }
}

function updateNavigationButtonVisibility() {
    const startInputValue = document.getElementById('start-input').value;
    const routeInfoVisible = !document.getElementById('route-info').classList.contains('hidden');
    
    // Show navigation button only if route exists AND start point is current location
    if (routeInfoVisible && startInputValue === '📍 Lokasi Saya') {
        showNavigationButton();
    } else {
        hideNavigationButton();
    }
}

function startNavigation() {
    if (!currentRouteLayer || !startPoint || !endPoint) {
        alert('Harap cari rute terlebih dahulu sebelum memulai navigasi!');
        return;
    }
    
    // Check if user is using their current location as start point
    const startInputValue = document.getElementById('start-input').value;
    if (startInputValue !== '📍 Lokasi Saya') {
        alert('Navigasi hanya dapat digunakan jika Anda menggunakan lokasi Anda saat ini sebagai titik awal!\n\nKlik tombol GPS (⊕) untuk menggunakan lokasi Anda.');
        return;
    }

    navigationActive = true;
    
    // Show navigation modal
    document.getElementById('navigation-modal').classList.remove('hidden');
    
    // Set mode text
    const modeText = currentMode === 'safer' ? 'Rute Teraman' : 'Rute Tercepat';
    document.getElementById('nav-mode-text').textContent = `Mode: ${modeText}`;
    
    // Initialize with exact route distance and time
    const distKm = (totalRouteDistance / 1000).toFixed(1);
    document.getElementById('nav-remaining-distance').textContent = distKm + ' km';
    document.getElementById('nav-remaining-time').textContent = totalRouteTime + ' menit';
    document.getElementById('nav-speed').textContent = '0 km/h';
    
    // Generate turn-by-turn instructions from route
    generateNavigationSteps();
    
    // Start watching user location
    startLocationTracking();
    
    // Recenter icons
    lucide.createIcons();
}

function generateNavigationSteps() {
    navigationSteps = [];
    
    if (!currentRouteLayer) return;
    
    // Extract coordinates from route
    let allCoords = [];
    currentRouteLayer.eachLayer(layer => {
        if (layer instanceof L.Polyline) {
            const coords = layer.getLatLngs();
            const flatCoords = Array.isArray(coords[0]) ? coords.flat() : coords;
            allCoords = allCoords.concat(flatCoords);
        }
    });
    
    if (allCoords.length === 0) return;
    
    // Generate simple steps (every 500 meters or significant turn)
    let step = {
        position: allCoords[0],
        instruction: 'Mulai perjalanan',
        distance: 0,
        icon: 'play-circle'
    };
    navigationSteps.push(step);
    
    let cumulativeDistance = 0;
    
    for (let i = 1; i < allCoords.length; i++) {
        const dist = allCoords[i-1].distanceTo(allCoords[i]);
        cumulativeDistance += dist;
        
        // Create step every ~500m or at significant points
        if (cumulativeDistance >= 500 || i === allCoords.length - 1) {
            const bearing = calculateBearing(allCoords[i-1], allCoords[i]);
            const direction = getDirectionFromBearing(bearing);
            
            step = {
                position: allCoords[i],
                instruction: i === allCoords.length - 1 ? 'Anda telah tiba di tujuan' : `Lanjutkan ke ${direction}`,
                distance: cumulativeDistance,
                icon: i === allCoords.length - 1 ? 'flag' : getArrowIcon(direction),
                bearing: bearing
            };
            navigationSteps.push(step);
            cumulativeDistance = 0;
        }
    }
    
    currentStepIndex = 0;
    updateNavigationDisplay();
}

function calculateBearing(from, to) {
    const lat1 = from.lat * Math.PI / 180;
    const lat2 = to.lat * Math.PI / 180;
    const dLng = (to.lng - from.lng) * Math.PI / 180;
    
    const y = Math.sin(dLng) * Math.cos(lat2);
    const x = Math.cos(lat1) * Math.sin(lat2) - Math.sin(lat1) * Math.cos(lat2) * Math.cos(dLng);
    const bearing = Math.atan2(y, x) * 180 / Math.PI;
    
    return (bearing + 360) % 360;
}

function getDirectionFromBearing(bearing) {
    const directions = ['Utara', 'Timur Laut', 'Timur', 'Tenggara', 'Selatan', 'Barat Daya', 'Barat', 'Barat Laut'];
    const index = Math.round(bearing / 45) % 8;
    return directions[index];
}

function getArrowIcon(direction) {
    const iconMap = {
        'Utara': 'arrow-up',
        'Timur Laut': 'arrow-up-right',
        'Timur': 'arrow-right',
        'Tenggara': 'arrow-down-right',
        'Selatan': 'arrow-down',
        'Barat Daya': 'arrow-down-left',
        'Barat': 'arrow-left',
        'Barat Laut': 'arrow-up-left'
    };
    return iconMap[direction] || 'arrow-up';
}

function startLocationTracking() {
    if (navigator.geolocation) {
        navigationWatchId = navigator.geolocation.watchPosition(
            updateUserPosition,
            handleLocationError,
            {
                enableHighAccuracy: true,
                timeout: 10000,
                maximumAge: 0
            }
        );
    } else {
        alert('Geolocation tidak didukung oleh browser Anda');
    }
}

function updateUserPosition(position) {
    const userLat = position.coords.latitude;
    const userLng = position.coords.longitude;
    const userPos = L.latLng(userLat, userLng);
    
    lastUserPosition = userPos;
    
    // Update user marker
    if (userLocationMarker) {
        map.removeLayer(userLocationMarker);
    }
    
    userLocationMarker = L.circleMarker(userPos, {
        radius: 10,
        color: '#3b82f6',
        fillColor: '#60a5fa',
        fillOpacity: 1,
        weight: 3
    }).addTo(map);
    
    // Add direction arrow
    const arrow = L.marker(userPos, {
        icon: L.divIcon({
            className: 'user-direction-arrow',
            html: '<div style="width: 30px; height: 30px; background: #3b82f6; border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="white" stroke="white" stroke-width="2"><path d="M12 2L12 22M12 2L6 8M12 2L18 8"/></svg></div>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        })
    }).addTo(map);
    
    // Center map on user
    map.setView(userPos, 17);
    
    // Calculate speed first
    let speedKmh = 0;
    if (position.coords.speed && position.coords.speed > 0) {
        speedKmh = position.coords.speed * 3.6; // m/s to km/h
    }
    document.getElementById('nav-speed').textContent = speedKmh.toFixed(1) + ' km/h';
    
    // Calculate distance to current step and update
    if (navigationSteps.length > 0 && navigationSteps[currentStepIndex]) {
        const stepPos = navigationSteps[currentStepIndex].position;
        const distToStep = userPos.distanceTo(stepPos);
        
        // Update distance to next turn/instruction
        const distText = distToStep < 1000 ? Math.round(distToStep) + ' m' : (distToStep / 1000).toFixed(1) + ' km';
        document.getElementById('nav-distance').textContent = distText;
        
        // If close to current step, move to next
        if (distToStep < 50 && currentStepIndex < navigationSteps.length - 1) {
            currentStepIndex++;
            updateNavigationDisplay();
        }
    }
    
    // Update remaining distance and time to destination
    updateRemainingInfo(userPos, speedKmh);
}

function updateNavigationDisplay() {
    if (!navigationSteps[currentStepIndex]) return;
    
    const step = navigationSteps[currentStepIndex];
    
    // Update instruction
    document.getElementById('nav-instruction').textContent = step.instruction;
    
    // Update distance to next step (will be updated again in updateUserPosition)
    if (lastUserPosition) {
        const dist = lastUserPosition.distanceTo(step.position);
        const distText = dist < 1000 ? Math.round(dist) + ' m' : (dist / 1000).toFixed(1) + ' km';
        document.getElementById('nav-distance').textContent = distText;
    } else {
        document.getElementById('nav-distance').textContent = 'Menghitung...';
    }
    
    // Update arrow icon
    const arrowIcon = document.getElementById('nav-arrow-icon');
    arrowIcon.setAttribute('data-lucide', step.icon);
    lucide.createIcons();
}

function updateRemainingInfo(userPos, currentSpeed) {
    if (!navigationSteps.length || !currentRouteLayer) return;
    
    // Calculate remaining distance from user to destination
    let remainingDist = 0;
    
    // Distance from user to current step
    if (navigationSteps[currentStepIndex]) {
        remainingDist = userPos.distanceTo(navigationSteps[currentStepIndex].position);
    }
    
    // Add distances for all remaining steps
    for (let i = currentStepIndex + 1; i < navigationSteps.length; i++) {
        if (navigationSteps[i-1] && navigationSteps[i]) {
            remainingDist += navigationSteps[i-1].position.distanceTo(navigationSteps[i].position);
        }
    }
    
    // Update remaining distance display
    const distKm = (remainingDist / 1000).toFixed(1);
    document.getElementById('nav-remaining-distance').textContent = distKm + ' km';
    
    // Calculate remaining time based on current speed or default
    let timeMin;
    if (currentSpeed && currentSpeed > 5) {
        // Use current speed if available and reasonable (> 5 km/h)
        timeMin = Math.round((parseFloat(distKm) / currentSpeed) * 60);
    } else {
        // Use default speed of 30 km/h for city driving
        timeMin = Math.round((parseFloat(distKm) / 30) * 60);
    }
    
    // Format time display
    if (timeMin < 60) {
        document.getElementById('nav-remaining-time').textContent = timeMin + ' menit';
    } else {
        const hours = Math.floor(timeMin / 60);
        const mins = timeMin % 60;
        document.getElementById('nav-remaining-time').textContent = hours + ' jam ' + mins + ' menit';
    }
    
    // Check if arrived (within 30 meters)
    if (remainingDist < 30) {
        showArrivalNotification();
    }
}

function showArrivalNotification() {
    // Play sound or show notification
    const notification = document.createElement('div');
    notification.className = 'fixed top-24 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-4 rounded-xl shadow-2xl z-[4000] animate-fade-in';
    notification.innerHTML = `
        <div class="flex items-center gap-3">
            <i data-lucide="check-circle" class="w-8 h-8"></i>
            <div>
                <p class="font-bold text-lg">Anda Telah Tiba!</p>
                <p class="text-sm">Selamat, perjalanan Anda selesai</p>
            </div>
        </div>
    `;
    document.body.appendChild(notification);
    lucide.createIcons();
    
    setTimeout(() => {
        notification.remove();
        stopNavigation();
    }, 3000);
}

function handleLocationError(error) {
    console.error('Location error:', error);
    let errorMsg = 'Error mendapatkan lokasi: ';
    
    switch(error.code) {
        case error.PERMISSION_DENIED:
            errorMsg += 'Izin lokasi ditolak';
            break;
        case error.POSITION_UNAVAILABLE:
            errorMsg += 'Lokasi tidak tersedia';
            break;
        case error.TIMEOUT:
            errorMsg += 'Timeout';
            break;
    }
    
    console.warn(errorMsg);
}

function recenterNavigation() {
    if (lastUserPosition) {
        map.setView(lastUserPosition, 17);
    }
}

function stopNavigation() {
    navigationActive = false;
    
    // Stop watching location
    if (navigationWatchId !== null) {
        navigator.geolocation.clearWatch(navigationWatchId);
        navigationWatchId = null;
    }
    
    // Remove user marker
    if (userLocationMarker) {
        map.removeLayer(userLocationMarker);
        userLocationMarker = null;
    }
    
    // Hide modal
    document.getElementById('navigation-modal').classList.add('hidden');
    
    // Reset map view
    if (currentRouteLayer) {
        map.fitBounds(currentRouteLayer.getBounds(), { padding: [50, 50] });
    }
    
    // Reset variables
    navigationSteps = [];
    currentStepIndex = 0;
    lastUserPosition = null;
}

// Load data pertama kali dengan filter 'all'
loadCrimeData('all');