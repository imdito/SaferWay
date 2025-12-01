<?php
require_once 'routes/db.php';

// Test the actual API response
$sql = "
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
    LIMIT 10
";

$stmt = $pdo->query($sql);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Sample crime details:\n\n";
foreach ($results as $row) {
    echo "Hour: {$row['hour']}, Crime: {$row['crime_type']}, Level: {$row['level_name']}, Location: {$row['location_name']}\n";
}

// Check hour 20 specifically
echo "\n\nCrimes at hour 20:\n";
$sql2 = "
    SELECT 
        cd.id,
        ct.type_name as crime_type,
        cl.level_name,
        cd.location_name
    FROM crime_data cd
    JOIN crime_types ct ON cd.crime_type_id = ct.id
    JOIN criminality_levels cl ON cd.level_id = cl.id
    WHERE EXTRACT(HOUR FROM cd.created_at) = 20
    LIMIT 10
";

$stmt2 = $pdo->query($sql2);
$results2 = $stmt2->fetchAll(PDO::FETCH_ASSOC);

foreach ($results2 as $row) {
    echo "{$row['id']}: {$row['crime_type']} - {$row['level_name']} - {$row['location_name']}\n";
}
?>
