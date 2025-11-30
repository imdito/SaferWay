<?php
// controller/api.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Panggil file koneksi (Naik satu folder '..', lalu masuk 'routes')
require_once '../routes/db.php';

try {
    // Query Sakti PostGIS
    $sql = "
      WITH clustered_crimes AS (
        SELECT 
          cd.*,
          ct.type_name,
          ST_ClusterDBSCAN(ST_Transform(cd.coordinates::geometry, 3857), eps := 100, minpoints := 1) 
          OVER () AS cluster_id
        FROM crime_data cd
        JOIN crime_types ct ON cd.crime_type_id = ct.id
      )
      SELECT 
        cluster_id,
        COUNT(*) as crime_count,
        ST_AsGeoJSON(ST_Centroid(ST_Collect(coordinates::geometry))) as center_geometry,
        json_agg(json_build_object(
          'location', location_name,
          'desc', description,
          'date', crime_date,
          'type', type_name
        )) as reports
      FROM clustered_crimes
      GROUP BY cluster_id;
    ";

    // Gunakan variabel $pdo dari file db.php
    $stmt = $pdo->query($sql);
    $features = [];

    while ($row = $stmt->fetch()) {
        $count = (int) $row['crime_count'];

        // Logika Warna & Status
        $status = "Aman";
        $color = "#00FF00";

        if ($count >= 9) {
            $status = "Bahaya";
            $color = "#FF0000";
        } elseif ($count >= 6) {
            $status = "Rawan";
            $color = "#FFA500";
        } elseif ($count >= 3) {
            $status = "Siaga";
            $color = "#FFFF00";
        }

        $features[] = [
            "type" => "Feature",
            "geometry" => json_decode($row['center_geometry']),
            "properties" => [
                "count" => $count,
                "status" => $status,
                "color" => $color,
                "reports" => json_decode($row['reports'])
            ]
        ];
    }

    echo json_encode([
        "type" => "FeatureCollection",
        "features" => $features
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["error" => "Server Error: " . $e->getMessage()]);
}
?>