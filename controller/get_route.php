<?php
// Tampilkan error jika ada (tapi sembunyikan dari output JSON)
error_reporting(E_ALL);
ini_set('display_errors', 0); 

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../routes/db.php'; 

try {
    // 1. INPUT
    $startLat = $_GET['start_lat'] ?? null;
    $startLng = $_GET['start_lng'] ?? null;
    $endLat   = $_GET['end_lat'] ?? null;
    $endLng   = $_GET['end_lng'] ?? null;
    $mode     = $_GET['mode'] ?? 'safe';

    if (!$startLat || !$endLat) throw new Exception("Koordinat tidak lengkap.");

    // 2. CEK EXTENSION
    $chk = $pdo->query("SELECT count(*) FROM pg_extension WHERE extname = 'pgrouting'");
    if ($chk->fetchColumn() == 0) throw new Exception("Extension pgRouting belum aktif.");

    // 3. FUNGSI SNAPPING (Cari titik jalan terdekat)
    function getNearestNode($pdo, $lat, $lng) {
        // Radius pencarian diperkecil biar cepat (0.01 derajat ~ 1km)
        $sql = "SELECT id FROM ways_vertices_pgr 
                WHERE ST_DWithin(geom, ST_SetSRID(ST_MakePoint(:lng, :lat), 4326), 0.01)
                ORDER BY geom <-> ST_SetSRID(ST_MakePoint(:lng, :lat), 4326) 
                LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['lng' => $lng, 'lat' => $lat]);
        return $stmt->fetchColumn();
    }

    $sourceNode = getNearestNode($pdo, $startLat, $startLng);
    $targetNode = getNearestNode($pdo, $endLat, $endLng);

    if (!$sourceNode || !$targetNode) throw new Exception("Jalan tidak ditemukan di dekat titik tersebut.");

    // 4. BOUNDING BOX (Area Pencarian)
    $buffer = 0.02; // Buffer area kecil saja
    $minLat = min($startLat, $endLat) - $buffer;
    $maxLat = max($startLat, $endLat) + $buffer;
    $minLng = min($startLng, $endLng) - $buffer;
    $maxLng = max($startLng, $endLng) + $buffer;

    // 5. QUERY SUPER OPTIMIZED
    // Menggunakan ST_DWithin tanpa casting geography (pakai derajat)
    // Dan menggunakan COALESCE untuk menangani nilai cost yang NULL
    
    if ($mode == 'safe') {
        $sqlInner = "
            SELECT 
                id, source, target, 
                
                -- COST MAJU
                (COALESCE(cost_s, 0) * CASE 
                    -- Radius 0.00135 ~ 150m (Bahaya)
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 4 AND ST_DWithin(c.coordinates, w.geom, 0.00135)) THEN 1000.0 
                    -- Radius 0.00090 ~ 100m (Rawan)
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 3 AND ST_DWithin(c.coordinates, w.geom, 0.00090)) THEN 50.0 
                    -- Radius 0.00045 ~ 50m (Siaga)
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 2 AND ST_DWithin(c.coordinates, w.geom, 0.00045)) THEN 5.0
                    ELSE 1.0 
                END) as cost, 

                -- COST MUNDUR
                (COALESCE(reverse_cost_s, 0) * CASE 
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 4 AND ST_DWithin(c.coordinates, w.geom, 0.00135)) THEN 1000.0 
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 3 AND ST_DWithin(c.coordinates, w.geom, 0.00090)) THEN 50.0 
                    WHEN EXISTS (SELECT 1 FROM crime_data c WHERE c.level_id = 2 AND ST_DWithin(c.coordinates, w.geom, 0.00045)) THEN 5.0
                    ELSE 1.0 
                END) as reverse_cost

            FROM ways w
            -- Filter Area: HANYA jalan yang ada di kotak rute
            WHERE w.geom && ST_MakeEnvelope($minLng, $minLat, $maxLng, $maxLat, 4326)
        ";
    } else {
        // MODE CEPAT
        $sqlInner = "
            SELECT id, source, target, 
                   COALESCE(cost_s, 0) as cost, 
                   COALESCE(reverse_cost_s, 0) as reverse_cost 
            FROM ways w 
            WHERE w.geom && ST_MakeEnvelope($minLng, $minLat, $maxLng, $maxLat, 4326)
        ";
    }

    // 6. EXECUTE DIJKSTRA
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

    if (empty($rows)) {
        throw new Exception("Rute tidak ditemukan. Coba geser titik sedikit.");
    }

    // 7. FORMAT OUTPUT
    $coordinates = [];
    $totalCost = 0;

    foreach ($rows as $row) {
        $geom = json_decode($row['geometry']);
        if ($geom && isset($geom->coordinates)) {
            foreach ($geom->coordinates as $coord) {
                $coordinates[] = $coord;
            }
        }
        $totalCost += $row['cost'];
    }

    echo json_encode([
        'type' => 'Feature',
        'properties' => [
            'mode' => $mode,
            'status' => 'success',
            'debug_nodes' => [$sourceNode, $targetNode]
        ],
        'geometry' => [
            'type' => 'LineString',
            'coordinates' => $coordinates
        ]
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>