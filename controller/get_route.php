<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); 
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../routes/db.php'; 

try {
    $startLat = $_GET['start_lat'] ?? null;
    $startLng = $_GET['start_lng'] ?? null;
    $endLat   = $_GET['end_lat'] ?? null;
    $endLng   = $_GET['end_lng'] ?? null;
    $mode     = $_GET['mode'] ?? 'safe'; 

    if (!$startLat || !$endLat) throw new Exception("Koordinat tidak lengkap.");
    if ($mode == 'safe') {
        
        // Cek Extension
        $chk = $pdo->query("SELECT count(*) FROM pg_extension WHERE extname = 'pgrouting'");
        if ($chk->fetchColumn() == 0) throw new Exception("Extension pgRouting belum aktif.");

        // Fungsi Snapping (Cari Node Terdekat) - Radius diperkecil (0.005) biar cepat
        function getNearestNode($pdo, $lat, $lng) {
            $sql = "SELECT id FROM ways_vertices_pgr 
                    ORDER BY geom <-> ST_SetSRID(ST_MakePoint(:lng, :lat), 4326) 
                    LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['lng' => $lng, 'lat' => $lat]);
            return $stmt->fetchColumn();
        }

        $sourceNode = getNearestNode($pdo, $startLat, $startLng);
        $targetNode = getNearestNode($pdo, $endLat, $endLng);

        if (!$sourceNode || !$targetNode) throw new Exception("Jalan tidak ditemukan.");
        $buffer = 0.02; 
        $minLat = min($startLat, $endLat) - $buffer;
        $maxLat = max($startLat, $endLat) + $buffer;
        $minLng = min($startLng, $endLng) - $buffer;
        $maxLng = max($startLng, $endLng) + $buffer;

        $sqlInner = "
            SELECT id, source, target, 
                (cost_s * CASE 
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 4 AND ST_DWithin(c.coordinates, w.geom, 0.00135)) THEN 1000.0 
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 3 AND ST_DWithin(c.coordinates, w.geom, 0.00090)) THEN 50.0 
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 2 AND ST_DWithin(c.coordinates, w.geom, 0.00045)) THEN 5.0
                    ELSE 1.0 
                END) as cost, 
                (reverse_cost_s * CASE 
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 4 AND ST_DWithin(c.coordinates, w.geom, 0.00135)) THEN 1000.0 
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 3 AND ST_DWithin(c.coordinates, w.geom, 0.00090)) THEN 50.0 
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 2 AND ST_DWithin(c.coordinates, w.geom, 0.00045)) THEN 5.0
                    ELSE 1.0 
                END) as reverse_cost
            FROM ways w
            WHERE w.geom && ST_MakeEnvelope($minLng, $minLat, $maxLng, $maxLat, 4326)
        ";

        $sqlFinal = "
            SELECT r.seq, r.cost, ST_AsGeoJSON(w.geom) as geometry
            FROM pgr_dijkstra('$sqlInner', $sourceNode, $targetNode, false) as r
            JOIN ways w ON r.edge = w.id
            ORDER BY r.seq
        ";

        $stmt = $pdo->query($sqlFinal);
        
        if (!$stmt) {
            $err = $pdo->errorInfo();
            throw new Exception("SQL Error: " . $err[2]);
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) throw new Exception("Rute aman tidak ditemukan di area ini.");

        // Format GeoJSON
        $coords = [];
        $totalCost = 0;
        foreach ($rows as $row) {
            $geom = json_decode($row['geometry']);
            if ($geom && isset($geom->coordinates)) {
                foreach ($geom->coordinates as $c) $coords[] = $c;
            }
            $totalCost += $row['cost'];
        }

        $features[] = [
            'type' => 'Feature',
            'properties' => [
                'mode' => 'safe',
                'is_main' => true,
                'total_penalty_cost' => $totalCost
            ],
            'geometry' => ['type' => 'LineString', 'coordinates' => $coords]
        ];

    } 

    else {
        if (!function_exists('curl_init')) throw new Exception("PHP cURL belum aktif.");

        $url = "http://router.project-osrm.org/route/v1/driving/$startLng,$startLat;$endLng,$endLat?overview=full&geometries=geojson&alternatives=true";

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'SaferWayApp/1.0');
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);

        if (!isset($data['routes']) || empty($data['routes'])) {
            throw new Exception("OSRM tidak menemukan rute.");
        }

        foreach ($data['routes'] as $index => $route) {
            $features[] = [
                'type' => 'Feature',
                'properties' => [
                    'mode' => 'fast',
                    'is_main' => ($index === 0),
                    'distance_m' => $route['distance'],
                    'duration_s' => $route['duration']
                ],
                'geometry' => $route['geometry']
            ];
        }

        // Urutkan biar yang Utama muncul terakhir (paling atas di map)
        usort($features, function($a, $b) {
            return $a['properties']['is_main'] <=> $b['properties']['is_main'];
        });
    }

    echo json_encode([
        'type' => 'FeatureCollection',
        'features' => $features
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>