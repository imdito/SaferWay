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

    if (endPoint) calculateRoute();
});

map.on('locationerror', () => { 
    alert("Gagal ambil lokasi GPS."); 
    document.getElementById('start-input').value = ""; 
});

// --- KLIK KANAN ---
map.on('contextmenu', (e) => {
    if (!startPoint) setStart(e.latlng);
    else if (!endPoint) setEnd(e.latlng);
    else { startPoint = null; endPoint = null; setStart(e.latlng); }
});

function setStart(latlng) {
    startPoint = latlng;
    document.getElementById('start-input').value = `${latlng.lat.toFixed(4)}, ${latlng.lng.toFixed(4)}`;
    L.popup().setLatLng(latlng).setContent("📍 Start").openOn(map);
    if (routingControl) { map.removeControl(routingControl); routingControl = null; }
}

function setEnd(latlng) {
    endPoint = latlng;
    document.getElementById('end-input').value = `${latlng.lat.toFixed(4)}, ${latlng.lng.toFixed(4)}`;
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

        // 7. Update UI Info Panel
        document.getElementById('route-info').classList.remove('hidden');
        document.getElementById('info-dist').innerText = distanceKm + " km";
        document.getElementById('info-time').innerText = timeMinutes + " mnt";

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

// Load data pertama kali dengan filter 'all'
loadCrimeData('all');