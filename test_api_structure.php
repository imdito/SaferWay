<?php
// Simulate what the API returns
require_once 'routes/db.php';

header('Content-Type: application/json');

try {
    $sql = "
        SELECT 
            EXTRACT(HOUR FROM created_at) as hour,
            level_id,
            COUNT(*) as crime_count
        FROM crime_data
        WHERE created_at IS NOT NULL
        GROUP BY EXTRACT(HOUR FROM created_at), level_id
        ORDER BY hour, level_id
    ";
    
    $stmt = $pdo->query($sql);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $detailSql = "
        SELECT 
            cd.id,
            EXTRACT(HOUR FROM cd.created_at) as hour,
            ct.type_name as crime_type,
            cl.level_name,
            cd.level_id,
            cd.location_name,
            cd.description,
            cd.crime_date,
            cd.area,
            ST_Y(cd.coordinates) as latitude,
            ST_X(cd.coordinates) as longitude
        FROM crime_data cd
        JOIN crime_types ct ON cd.crime_type_id = ct.id
        JOIN criminality_levels cl ON cd.level_id = cl.id
        WHERE cd.created_at IS NOT NULL
        ORDER BY cd.created_at DESC
    ";
    
    $detailStmt = $pdo->query($detailSql);
    $crimeDetails = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $detailsByHour = [];
    foreach ($crimeDetails as $detail) {
        $hour = (int)$detail['hour'];
        if (!isset($detailsByHour[$hour])) {
            $detailsByHour[$hour] = [];
        }
        $detailsByHour[$hour][] = [
            'id' => $detail['id'],
            'crime_type' => $detail['crime_type'],
            'level_name' => $detail['level_name'],
            'level_id' => $detail['level_id'],
            'location_name' => $detail['location_name'],
            'description' => $detail['description'],
            'crime_date' => $detail['crime_date'],
            'area' => $detail['area'],
            'latitude' => $detail['latitude'],
            'longitude' => $detail['longitude']
        ];
    }
    
    echo "Details by hour 20:\n";
    echo "Count: " . count($detailsByHour[20] ?? []) . "\n";
    if (isset($detailsByHour[20])) {
        echo "First 3 crimes:\n";
        foreach (array_slice($detailsByHour[20], 0, 3) as $crime) {
            echo "- {$crime['crime_type']}: {$crime['location_name']}\n";
        }
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
