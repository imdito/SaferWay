<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SaferWay - OpenStreetMap</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        body { overflow: hidden; } 
        /* Peta Full Screen */
        #map { height: calc(100vh - 56px); width: 100%; z-index: 1; }

        /* Panel Kontrol Melayang */
        .floating-panel {
            position: absolute; top: 20px; left: 20px; z-index: 1000;
            width: 350px; max-width: 90%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(5px);
            border-radius: 8px;
        }

        /* Legend / Keterangan Warna */
        .legend-panel {
            position: absolute; bottom: 30px; right: 20px; z-index: 1000;
            background: white; padding: 10px; border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.2); font-size: 0.85rem;
        }

        .dot { height: 10px; width: 10px; display: inline-block; border-radius: 50%; margin-right: 5px; }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold" href="#">
                <i class="fas fa-shield-alt text-warning"></i> SaferWay
            </a>
            <span class="navbar-text text-white-50 small ms-auto">
                OpenStreetMap Mode
            </span>
        </div>
    </nav>

    <div class="position-relative">
        
        <div id="map"></div>

        <div class="card floating-panel shadow border-0">
            <div class="card-body">
                <h5 class="card-title fw-bold mb-3">Cari Rute Aman</h5>
                
                <div class="input-group mb-2">
                    <span class="input-group-text bg-light"><i class="fas fa-map-marker-alt text-primary"></i></span>
                    <input type="text" class="form-control" value="Tugu Yogyakarta" readonly>
                </div>
                <div class="input-group mb-3">
                    <span class="input-group-text bg-light"><i class="fas fa-flag-checkered text-danger"></i></span>
                    <input type="text" class="form-control" value="Ambarrukmo Plaza" readonly>
                </div>

                <div class="d-grid gap-2 mb-3">
                    <input type="radio" class="btn-check" name="routeMode" id="safeMode" checked>
                    <label class="btn btn-outline-success text-start" for="safeMode">
                        <i class="fas fa-shield-alt me-2"></i> <strong>Prioritas Aman</strong>
                    </label>

                    <input type="radio" class="btn-check" name="routeMode" id="fastMode">
                    <label class="btn btn-outline-secondary text-start" for="fastMode">
                        <i class="fas fa-bolt me-2"></i> <strong>Tercepat</strong>
                    </label>
                </div>

                <button class="btn btn-primary w-100 fw-bold py-2" onclick="cariRute()">
                    <i class="fas fa-search-location me-1"></i> Cari Jalan
                </button>
                
                <div id="resultInfo" class="alert alert-info mt-3 d-none">
                    <small id="resultText">Menghitung rute...</small>
                </div>
            </div>
        </div>

        <div class="legend-panel">
            <div class="fw-bold mb-2">Zona Kriminalitas</div>
            <div class="d-flex align-items-center mb-1"><span class="dot bg-success"></span> Aman (Level 1)</div>
            <div class="d-flex align-items-center mb-1"><span class="dot bg-warning"></span> Siaga (Level 2)</div>
            <div class="d-flex align-items-center mb-1"><span class="dot" style="background: orange;"></span> Rawan (Level 3)</div>
            <div class="d-flex align-items-center"><span class="dot bg-danger"></span> Bahaya (Level 4)</div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        // --- 1. INISIALISASI PETA ---
        var map = L.map('map').setView([-7.7828, 110.3800], 14);

        // --- 2. SET LAYER OPENSTREETMAP ---
        // Ini adalah URL server resmi OSM
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        // --- 3. AMBIL DATA ZONA DARI PHP (POSTGIS) ---
        fetch('api/get_zones.php')
            .then(response => {
                if (!response.ok) throw new Error("Database belum connect");
                return response.json();
            })
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
                        layer.bindPopup(`
                            <b>${feature.properties.name}</b><br>
                            Status: ${feature.properties.level}<br>
                            <small>${feature.properties.desc || ''}</small>
                        `);
                    }
                }).addTo(map);
            })
            .catch(error => {
                console.log("Menggunakan Dummy Data (Database Error/Offline)");
                loadDummyZones();
            });

        // --- 4. DUMMY DATA (Fallback) ---
        function loadDummyZones() {
            var zones = [
                { coords: [[-7.785, 110.390], [-7.785, 110.398], [-7.790, 110.398], [-7.790, 110.390]], color: '#dc3545', name: 'Zona Merah Dummy' },
                { coords: [[-7.792, 110.380], [-7.792, 110.405], [-7.800, 110.405], [-7.800, 110.380]], color: '#198754', name: 'Zona Hijau Dummy' }
            ];
            zones.forEach(z => {
                L.polygon(z.coords, { color: z.color, fillOpacity: 0.4, weight: 2 })
                 .bindPopup(z.name).addTo(map);
            });
        }

        // --- 5. LOGIKA SIMULASI RUTE ---
        var startPoint = [-7.7860682, 110.4110175]; 
        var endPoint = [-7.8279,110.4049];   
        L.marker(startPoint).addTo(map).bindPopup("Start: Tugu");
        L.marker(endPoint).addTo(map).bindPopup("Finish: Amplaz");
        var currentRouteLine = null;

        function cariRute() {
    if (currentRouteLine) map.removeLayer(currentRouteLine);
    
    var resultBox = document.getElementById('resultInfo');
    resultBox.classList.remove('d-none');
    resultBox.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mencari rute alternatif...';
    resultBox.className = "alert alert-info mt-3";

    var start = [-7.7860682, 110.4110175]; 
    var end   = [-7.755584, 110.318374];
    var mode = document.getElementById('safeMode').checked ? 'safe' : 'fast';

    var url = `../controller/get_routes.php?start_lat=${start[0]}&start_lng=${start[1]}&end_lat=${end[0]}&end_lng=${end[1]}&mode=${mode}`;

    fetch(url)
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                resultBox.innerHTML = "Error: " + data.error;
                return;
            }

            // --- BAGIAN PENTING: Render FeatureCollection ---
            currentRouteLine = L.geoJSON(data, {
                style: function(feature) {
                    return {
                        color: feature.properties.color,   // Warna dari PHP (Hijau/Abu)
                        weight: feature.properties.weight, // Ketebalan
                        opacity: feature.properties.opacity
                    };
                },
                onEachFeature: function(feature, layer) {
                    // Tambahkan tooltip saat hover garis
                    var status = feature.properties.is_selected ? "Rute Utama" : "Alternatif";
                    var bahaya = feature.properties.danger_score > 0 
                        ? `<br><span class="text-danger">⚠️ ${feature.properties.danger_score} Zona Rawan</span>` 
                        : `<br><span class="text-success">✅ Aman</span>`;
                        
                    layer.bindPopup(`
                        <b>${status}</b><br>
                        Waktu: ${feature.properties.duration_mnt}<br>
                        Jarak: ${feature.properties.distance_km}
                        ${bahaya}
                    `);
                    
                    // Kalau rute utama, langsung buka popupnya (opsional)
                    // if (feature.properties.is_selected) layer.openPopup();
                }
            }).addTo(map);
            
            map.fitBounds(currentRouteLine.getBounds(), {padding: [50, 50]});

            // Cari properti rute utama untuk ditampilkan di Info Box
            // Kita cari feature yang is_selected = true
            var mainRoute = data.features.find(f => f.properties.is_selected);
            var props = mainRoute.properties;

            var infoHtml = `<b>Rute Terpilih (${mode === 'safe' ? 'Aman' : 'Cepat'})</b><br>`;
            infoHtml += `Waktu: ${props.duration_mnt} (${props.distance_km})<br>`;
            
            if (props.danger_score > 0) {
                 infoHtml += `⚠️ Risiko: Melewati ${props.danger_score} titik rawan.<br>`;
                 infoHtml += `<small>Terdapat ${data.features.length - 1} rute alternatif (garis abu-abu).</small>`;
                 resultBox.className = "alert alert-warning mt-3";
            } else {
                 infoHtml += `✅ Aman: Tidak melewati zona bahaya.<br>`;
                 infoHtml += `<small>Ditampilkan juga ${data.features.length - 1} alternatif lain.</small>`;
                 resultBox.className = "alert alert-success mt-3";
            }
            resultBox.innerHTML = infoHtml;
        })
        .catch(err => {
            resultBox.innerHTML = "Gagal: " + err;
            console.error(err);
        });
}
    </script>
</body>
</html>