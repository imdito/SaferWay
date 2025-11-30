<?php
// controller/search_location.php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../routes/db.php';

try {
    $query = $_GET['q'] ?? '';
    
    if (strlen($query) < 2) {
        echo json_encode(['results' => []]);
        exit;
    }

    $results = [];
    
    // 1. SEARCH LOCAL CRIME DATA LOCATIONS
    $sql = "
        SELECT DISTINCT 
            location_name,
            area,
            ST_Y(coordinates::geometry) as lat,
            ST_X(coordinates::geometry) as lng,
            COUNT(*) OVER (PARTITION BY location_name) as crime_count
        FROM crime_data
        WHERE LOWER(location_name) LIKE LOWER(:query)
           OR LOWER(area) LIKE LOWER(:query)
        ORDER BY crime_count DESC
        LIMIT 10
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['query' => '%' . $query . '%']);
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $results[] = [
            'type' => 'local',
            'display_name' => $row['location_name'],
            'area' => $row['area'],
            'lat' => $row['lat'],
            'lon' => $row['lng'],
            'crime_count' => (int)$row['crime_count'],
            'label' => $row['location_name'] . ', ' . $row['area']
        ];
    }
    
    // 2. SEARCH NOMINATIM (OpenStreetMap) untuk hasil tambahan
    // Fokus pada area Yogyakarta
    $nominatimUrl = 'https://nominatim.openstreetmap.org/search?' . http_build_query([
        'format' => 'json',
        'q' => $query . ', Yogyakarta',
        'countrycodes' => 'id',
        'viewbox' => '110.1,-8.1,110.6,-7.5', // Yogyakarta bounds
        'bounded' => '1',
        'limit' => 5,
        'addressdetails' => 1
    ]);
    
    // Set user agent untuk Nominatim (required)
    $context = stream_context_create([
        'http' => [
            'header' => "User-Agent: SaferWay-Yogyakarta/1.0\r\n"
        ]
    ]);
    
    $nominatimData = @file_get_contents($nominatimUrl, false, $context);
    
    if ($nominatimData) {
        $nominatimResults = json_decode($nominatimData, true);
        
        foreach ($nominatimResults as $item) {
            // Check if this location is not already in our local results
            $isDuplicate = false;
            foreach ($results as $localResult) {
                // Check proximity (within ~50 meters)
                $distance = sqrt(
                    pow($localResult['lat'] - floatval($item['lat']), 2) + 
                    pow($localResult['lon'] - floatval($item['lon']), 2)
                );
                
                if ($distance < 0.0005) { // ~50 meters
                    $isDuplicate = true;
                    break;
                }
            }
            
            if (!$isDuplicate) {
                $results[] = [
                    'type' => 'nominatim',
                    'display_name' => $item['display_name'],
                    'lat' => $item['lat'],
                    'lon' => $item['lon'],
                    'label' => explode(',', $item['display_name'])[0]
                ];
            }
        }
    }
    
    echo json_encode(['results' => array_slice($results, 0, 8)]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
