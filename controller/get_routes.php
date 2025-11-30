<?php
error_reporting(0); 
ini_set('display_errors', 0);
header('Content-Type: application/json');

// Cek apakah file DB ada
if (!file_exists('../routes/db.php')) {
    echo json_encode(['error' => 'File db.php tidak ditemukan di folder routes.']);
    exit;
}

require '../routes/db.php'; 

if (!function_exists('curl_init')) {
    echo json_encode(['error' => 'Modul PHP cURL belum diinstall.']);
    exit;
}
    
$startLat = $_GET['start_lat'] ?? '';
$startLng = $_GET['start_lng'] ?? '';
$endLat   = $_GET['end_lat'] ?? '';
$endLng   = $_GET['end_lng'] ?? '';
$mode     = $_GET['mode'] ?? 'safe';

if (!$startLat || !$endLat) {
    echo json_encode(['error' => 'Koordinat tidak lengkap.']);
    exit;
}

// 2. Panggil API OSRM (Minta alternatives=true)
$osrmUrl = "http://router.project-osrm.org/route/v1/driving/$startLng,$startLat;$endLng,$endLat?overview=full&geometries=geojson&alternatives=true";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $osrmUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'SaferWayApp/1.0'); 
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Gagal menghubungi OSRM: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$data = json_decode($response, true);

if (!isset($data['routes']) || count($data['routes']) == 0) {
    echo json_encode(['error' => 'OSRM tidak menemukan rute.']);
    exit;
}

// 3. LOGIKA FILTERING SAFERWAY
// Siapkan array untuk menampung semua kandidat rute
$processedRoutes = []; 
$bestRouteIndex = -1;
$bestScore = -1;

try {
    $sql = "SELECT COALESCE(SUM(s.routing_weight), 0) as danger_score
            FROM danger_zones d
            JOIN safety_levels s ON d.level_id = s.id
            WHERE ST_Intersects(ST_GeomFromGeoJSON(:routeGeom), d.area_polygon)";
    $stmt = $pdo->prepare($sql);
} catch (Exception $e) {
    echo json_encode(['error' => 'Database Error: ' . $e->getMessage()]);
    exit;
}

// Loop semua rute dari OSRM
foreach ($data['routes'] as $index => $route) {
    $geometryJson = json_encode($route['geometry']);
    $duration = $route['duration'];
    
    $dangerScore = 0;
    try {
        $stmt->execute(['routeGeom' => $geometryJson]);
        $result = $stmt->fetch();
        $dangerScore = (float) $result['danger_score'];
    } catch (Exception $e) {
        // Abaikan error SQL per rute
    }

    // Hitung Skor Penentuan
    if ($mode == 'safe') {
        $currentScore = ($dangerScore * 1000) + $duration; 
    } else {
        $currentScore = $duration;
    }

    // Cek apakah ini rute terbaik sejauh ini?
    if ($bestScore == -1 || $currentScore < $bestScore) {
        $bestScore = $currentScore;
        $bestRouteIndex = $index;
    }

    // Simpan data sementara
    $processedRoutes[] = [
        'geometry' => $route['geometry'],
        'properties' => [
            'danger_score' => $dangerScore,
            'duration_mnt' => round($duration / 60) . ' menit',
            'distance_km'  => round($route['distance'] / 1000, 1) . ' km',
            'mode_used'    => $mode,
            'score_raw'    => $currentScore // Debugging purpose
        ]
    ];
}

// 4. SUSUN OUTPUT FINAL (FeatureCollection)
$features = [];

foreach ($processedRoutes as $index => $routeData) {
    $isBest = ($index === $bestRouteIndex);
    
    // Tentukan Warna
    if ($isBest) {
        // Jika terpilih: Hijau (Aman) atau Abu-Gelap (Cepat)
        $color = ($mode == 'safe') ? '#198754' : '#212529'; 
        $opacity = 0.9;
        $weight = 6;
        $zIndex = 100; // Supaya muncul paling atas
    } else {
        // Jika alternatif: Abu-abu Muda
        $color = '#696b6dff'; 
        $opacity = 0.6;
        $weight = 4;
        $zIndex = 1;
    }

    $features[] = [
        'type' => 'Feature',
        'properties' => array_merge($routeData['properties'], [
            'is_selected' => $isBest,
            'color'       => $color,
            'opacity'     => $opacity,
            'weight'      => $weight,
            'z_index'     => $zIndex
        ]),
        'geometry' => $routeData['geometry']
    ];
}

// Urutkan features supaya yang "Best" ada di urutan terakhir array
// (Di Leaflet/Map, yang terakhir dirender akan muncul paling atas/depan)
usort($features, function($a, $b) {
    return $a['properties']['z_index'] <=> $b['properties']['z_index'];
});

echo json_encode([
    'type' => 'FeatureCollection',
    'features' => $features
]);
?>