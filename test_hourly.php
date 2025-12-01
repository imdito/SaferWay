<?php
require_once 'routes/db.php';

// Test hourly stats query
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

echo "Total rows: " . count($results) . "\n\n";

// Show sample data
foreach (array_slice($results, 0, 10) as $row) {
    echo "Hour: {$row['hour']}, Level: {$row['level_id']}, Count: {$row['crime_count']}\n";
}

// Test the full logic
$hourlyStats = [];
for ($hour = 0; $hour < 24; $hour++) {
    $hourlyStats[$hour] = [
        'hour' => $hour,
        'total' => 0,
        'level_1' => 0,
        'level_2' => 0,
        'level_3' => 0,
        'level_4' => 0,
    ];
}

foreach ($results as $row) {
    $hour = (int)$row['hour'];
    $level = (int)$row['level_id'];
    $count = (int)$row['crime_count'];
    
    $hourlyStats[$hour]['level_' . $level] = $count;
    $hourlyStats[$hour]['total'] += $count;
}

echo "\nHourly totals:\n";
foreach ($hourlyStats as $stat) {
    if ($stat['total'] > 0) {
        echo "Hour {$stat['hour']}: {$stat['total']} crimes (L1:{$stat['level_1']}, L2:{$stat['level_2']}, L3:{$stat['level_3']}, L4:{$stat['level_4']})\n";
    }
}
?>
