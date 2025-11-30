<?php
// controller/controller.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../routes/db.php';

try {
  // Ambil parameter filter dari request
  $filter_level = isset($_GET['level']) ? $_GET['level'] : null;
  
  // Query dasar
  $sql = "
      WITH clustered_crimes AS (
        SELECT 
          cd.*,
          ct.type_name,
          cl.level_name, 
          cl.color_code,
          -- Clustering Radius 100m
          ST_ClusterDBSCAN(ST_Transform(cd.coordinates::geometry, 3857), eps := 100, minpoints := 1) 
          OVER () AS cluster_id
        FROM crime_data cd
        JOIN crime_types ct ON cd.crime_type_id = ct.id
        JOIN criminality_levels cl ON cd.level_id = cl.id
        WHERE 1=1
  ";

  // Tambahkan filter berdasarkan level jika ada
  if ($filter_level && $filter_level !== 'all') {
    $sql .= " AND cl.level_name = :level_name";
  }

  $sql .= "
      )
      SELECT 
        cluster_id,
        COUNT(*) as crime_count,
        ST_AsGeoJSON(ST_Centroid(ST_Collect(coordinates::geometry))) as center_geometry,
        json_agg(json_build_object(
          'location', location_name,
          'desc', description,
          'date', crime_date,
          'type', type_name,
          'level', level_name,
          'color', color_code
        )) as reports
      FROM clustered_crimes
      GROUP BY cluster_id
      ORDER BY cluster_id;
    ";

  $stmt = $pdo->prepare($sql);
  
  // Bind parameter jika ada filter
  if ($filter_level && $filter_level !== 'all') {
    $stmt->bindValue(':level_name', $filter_level);
  }
  
  $stmt->execute();
  $features = [];

  while ($row = $stmt->fetch()) {
    $count = (int) $row['crime_count'];
    $reports = json_decode($row['reports'], true);

    // --- LOGIKA WARNA PRIORITAS ---
    // Untuk filter, jika ada filter level tertentu, langsung gunakan warna level tersebut
    if ($filter_level && $filter_level !== 'all') {
      $finalColor = $reports[0]['color'];
      $finalStatus = $reports[0]['level'];
    } else {
      // Jika tidak ada filter, gunakan logika prioritas seperti sebelumnya
      $finalColor = '#00FF00'; // Default Aman (Hijau)
      $finalStatus = 'Aman';

      foreach ($reports as $rpt) {
        $c = strtoupper($rpt['color']);

        if ($c == '#FF0000') {
          $finalColor = '#FF0000';
          $finalStatus = 'Area Bahaya';
          break;
        }

        if ($c == '#FFA500' && $finalColor != '#FF0000') {
          $finalColor = '#FFA500';
          $finalStatus = 'Area Rawan';
        }

        if ($c == '#FFFF00' && $finalColor == '#00FF00') {
          $finalColor = '#FFFF00';
          $finalStatus = 'Area Siaga';
        }
      }
    }

    if ($count == 1) {
      $finalStatus = $reports[0]['level'];
    } else {
      $finalStatus .= " (" . $count . " Kasus)";
    }

    $features[] = [
      "type" => "Feature",
      "geometry" => json_decode($row['center_geometry']),
      "properties" => [
        "count" => $count,
        "status" => $finalStatus,
        "color" => $finalColor,
        "reports" => $reports
      ]
    ];
  }

  echo json_encode(["type" => "FeatureCollection", "features" => $features]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["error" => "Error: " . $e->getMessage()]);
}
?>