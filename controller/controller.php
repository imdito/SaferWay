<?php
// controller/controller.php

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../routes/db.php';

try {
  // QUERY (Masih sama kayak punya lu, karena datanya udah bener)
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
      GROUP BY cluster_id;
    ";

  $stmt = $pdo->query($sql);
  $features = [];

  while ($row = $stmt->fetch()) {
    $count = (int) $row['crime_count'];
    $reports = json_decode($row['reports'], true);

    // --- LOGIKA WARNA PRIORITAS ---
    // Kita cari status paling parah dalam cluster ini.
    // Urutan: Bahaya (#FF0000) > Rawan (#FFA500) > Siaga (#FFFF00) > Aman (#00FF00)

    $finalColor = '#00FF00'; // Default Aman (Hijau)
    $finalStatus = 'Aman';

    foreach ($reports as $rpt) {
      $c = strtoupper($rpt['color']);

      // 1. Kalau ketemu MERAH (Bahaya), langsung kunci! Ini level tertinggi.
      if ($c == '#FF0000') {
        $finalColor = '#FF0000';
        $finalStatus = 'Area Bahaya';
        break; // Gak perlu cek yang lain, udah pasti merah.
      }

      // 2. Kalau ketemu ORANYE (Rawan), simpan, tapi jangan break (siapa tau ada merah nanti)
      if ($c == '#FFA500' && $finalColor != '#FF0000') {
        $finalColor = '#FFA500';
        $finalStatus = 'Area Rawan';
      }

      // 3. Kalau ketemu KUNING (Siaga)
      if ($c == '#FFFF00' && $finalColor == '#00FF00') {
        $finalColor = '#FFFF00';
        $finalStatus = 'Area Siaga';
      }
    }

    // Kalau single report, pake nama level aslinya aja biar rapi
    if ($count == 1) {
      $finalStatus = $reports[0]['level'];
    } else {
      // Kalau cluster, tambahin info jumlah
      $finalStatus .= " (" . $count . " Kasus)";
    }

    $features[] = [
      "type" => "Feature",
      "geometry" => json_decode($row['center_geometry']),
      "properties" => [
        "count" => $count,
        "status" => $finalStatus, // Status terburuk
        "color" => $finalColor,   // Warna terburuk
        "reports" => $reports     // Data lengkap buat slider
      ]
    ];
  }

  echo json_encode(["type" => "FeatureCollection", "features" => $features]);

} catch (Exception $e) {
  http_response_code(500);
  echo json_encode(["error" => "Error: " . $e->getMessage()]);
}
?>