<?php
require_once 'routes/db.php';

// Check if there's any data in crime_data table
$sql = "SELECT 
    COUNT(*) as total_crimes,
    MIN(crime_date) as earliest_crime,
    MAX(crime_date) as latest_crime
FROM crime_data
WHERE crime_date IS NOT NULL";

$stmt = $pdo->query($sql);
$result = $stmt->fetch();

echo "Total crimes with dates: " . $result['total_crimes'] . "\n";
echo "Earliest: " . $result['earliest_crime'] . "\n";
echo "Latest: " . $result['latest_crime'] . "\n\n";

// Get sample of hourly data
$sql2 = "SELECT 
    EXTRACT(HOUR FROM crime_date) as hour,
    level_id,
    COUNT(*) as count
FROM crime_data
WHERE crime_date IS NOT NULL
GROUP BY EXTRACT(HOUR FROM crime_date), level_id
ORDER BY hour, level_id
LIMIT 20";

$stmt2 = $pdo->query($sql2);
$results2 = $stmt2->fetchAll();

echo "Sample hourly data:\n";
foreach ($results2 as $row) {
    echo "Hour: " . $row['hour'] . ", Level: " . $row['level_id'] . ", Count: " . $row['count'] . "\n";
}
?>
