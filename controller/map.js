// --- 1. SETUP PETA OPENSTREETMAP ---
var map = L.map('map').setView([-7.7828, 110.3800], 14);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 19,
    attribution: '© OpenStreetMap contributors'
}).addTo(map);

// --- 2. TAMPILKAN ZONA DARI DATABASE ---
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
    .catch(err => console.log("Gagal load zona:", err));

// --- 3. MARKER START & FINISH ---
var startPoint = [-7.782, 110.367]; 
var endPoint   = [-7.783, 110.401];   
L.marker(startPoint).addTo(map).bindPopup("Start: Tugu");
L.marker(endPoint).addTo(map).bindPopup("Finish: Amplaz");

var currentRouteLine = null;

// --- 4. FUNGSI CARI RUTE (POSTGIS PGROUTING) ---
function cariRute() {
    if (currentRouteLine) map.removeLayer(currentRouteLine);
    
    var resultBox = document.getElementById('resultInfo');
    var resultText = document.getElementById('resultText');
    resultBox.classList.remove('d-none');
    resultText.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Menghubungi PostGIS Server...';
    resultBox.className = "alert alert-info mt-3";

    var mode = document.getElementById('safeMode').checked ? 'safe' : 'fast';

    // Panggil API PHP
    var url = `api/get_route_pgrouting.php?start_lat=${startPoint[0]}&start_lng=${startPoint[1]}&end_lat=${endPoint[0]}&end_lng=${endPoint[1]}&mode=${mode}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                resultBox.className = "alert alert-danger mt-3";
                resultText.innerHTML = "<b>Gagal:</b> " + data.error;
                return;
            }

            // Render Rute Garis
            currentRouteLine = L.geoJSON(data, {
                style: {
                    color: mode === 'safe' ? '#198754' : '#6c757d',
                    weight: 6,
                    opacity: 0.8
                }
            }).addTo(map);
            
            map.fitBounds(currentRouteLine.getBounds(), {padding: [50, 50]});

            // Pesan Sukses
            resultBox.className = "alert alert-success mt-3";
            resultText.innerHTML = `
                <b>Rute Ditemukan!</b><br>
                Mode: ${mode === 'safe' ? 'Aman' : 'Cepat'}<br>
                <small>Dikalkulasi oleh pgRouting Dijkstra.</small>
            `;
        })
        .catch(err => {
            resultBox.className = "alert alert-warning mt-3";
            resultText.innerHTML = "API Error. Cek console browser.";
            console.error(err);
        });
}