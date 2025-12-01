<?php
require_once 'routes/db.php';

// Check column type
$sql = "SELECT column_name, data_type, character_maximum_length
FROM information_schema.columns
WHERE table_name = 'crime_data' AND column_name = 'crime_date'";

$stmt = $pdo->query($sql);
$result = $stmt->fetch();

echo "Column info:\n";
print_r($result);

// Check some sample data
$sql2 = "SELECT id, crime_date, created_at FROM crime_data LIMIT 5";
$stmt2 = $pdo->query($sql2);
$results = $stmt2->fetchAll();

echo "\nSample data:\n";
foreach ($results as $row) {
    echo "ID: {$row['id']}, crime_date: {$row['crime_date']}, created_at: {$row['created_at']}\n";
}
?>
