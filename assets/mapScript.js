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

function calculateRoute() {
    if (!startPoint || !endPoint) return alert("Pilih titik dulu!");
    if (routingControl) map.removeControl(routingControl);

    document.getElementById('route-info').classList.add('hidden');
    document.getElementById('safety-alert').classList.add('hidden');

    routingControl = L.Routing.control({
        waypoints: [L.latLng(startPoint), L.latLng(endPoint)],
        routeWhileDragging: false, show: false, createMarker: () => null,
        lineOptions: { styles: [{ color: '#3b82f6', weight: 6, opacity: 0.8 }] }
    }).addTo(map);

    routingControl.on('routesfound', (e) => {
        const summary = e.routes[0].summary;
        document.getElementById('route-info').classList.remove('hidden');
        document.getElementById('info-dist').innerText = (summary.totalDistance / 1000).toFixed(1) + " km";
        document.getElementById('info-time').innerText = Math.round(summary.totalTime / 60) + " mnt";

        if (currentMode === 'safer') analyzeSafety(e.routes[0].coordinates);
    });
}

function analyzeSafety(coords) {
    const filters = Array.from(document.querySelectorAll('.risk-filter:checked')).map(c => c.value);
    let hits = 0;

    map.eachLayer(l => { if (l.options.className === 'danger-dot') map.removeLayer(l); });

    for (let i = 0; i < coords.length; i += 10) {
        const pt = L.latLng(coords[i]);
        for (let c of crimeData) {
            // Cek setiap laporan di dalam cluster
            let isDanger = false;
            for (let r of c.properties.reports) {
                // Bandingkan Level Laporan (DB) dengan Filter User
                if (filters.includes(r.level)) { isDanger = true; break; }
            }

            if (isDanger) {
                const loc = L.latLng(c.geometry.coordinates[1], c.geometry.coordinates[0]);
                if (pt.distanceTo(loc) < 120) {
                    hits++;
                    L.circleMarker(pt, { radius: 4, color: '#dc2626', fillOpacity: 1, className: 'danger-dot' }).addTo(map);
                }
            }
        }
    }

    if (hits > 0) {
        document.getElementById('safety-alert').classList.remove('hidden');
        document.getElementById('conflict-count').innerText = hits;
        if (routingControl._line) routingControl._line.setStyle({ color: '#ef4444' });
    }
}

// --- FUNGSI FILTER DATA KEJAHATAN ---
function filterCrimeData(level) {
    console.log('Menerapkan filter:', level);
    loadCrimeData(level);
}

// Load data pertama kali dengan filter 'all'
loadCrimeData('all');