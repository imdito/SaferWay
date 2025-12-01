<?php
// Hourly Crime Statistics API
require_once '../routes/db.php';

header('Content-Type: application/json');

try {
    // Query untuk mendapatkan statistik kejahatan per jam berdasarkan waktu laporan dibuat
    // Menggunakan created_at karena crime_date hanya menyimpan tanggal tanpa waktu
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
    
    // Query untuk mendapatkan detail kejahatan per jam
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
    
    // Kelompokkan detail berdasarkan jam
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
    
    // Inisialisasi array untuk semua jam (0-23) dengan semua level
    $hourlyStats = [];
    for ($hour = 0; $hour < 24; $hour++) {
        $hourlyStats[$hour] = [
            'hour' => $hour,
            'total' => 0,
            'level_1' => 0, // Aman
            'level_2' => 0, // Siaga
            'level_3' => 0, // Rawan
            'level_4' => 0, // Bahaya
            'dominant_level' => 1, // Default: Aman
            'display_level' => 'Aman',
            'color' => '#22c55e' // green-500
        ];
    }
    
    // Isi data dari database
    foreach ($results as $row) {
        $hour = (int)$row['hour'];
        $level = (int)$row['level_id'];
        $count = (int)$row['crime_count'];
        
        $hourlyStats[$hour]['level_' . $level] = $count;
        $hourlyStats[$hour]['total'] += $count;
    }
    
    // Tentukan level dominan untuk setiap jam berdasarkan weighted scoring
    foreach ($hourlyStats as $hour => &$stats) {
        // Weighted scoring: level lebih tinggi = bahaya lebih besar
        $weighted_score = 
            ($stats['level_1'] * 1) +   // Aman: bobot 1
            ($stats['level_2'] * 3) +   // Siaga: bobot 3
            ($stats['level_3'] * 7) +   // Rawan: bobot 7
            ($stats['level_4'] * 15);   // Bahaya: bobot 15
        
        // Tentukan level berdasarkan total dan weighted score
        if ($stats['total'] == 0) {
            $stats['dominant_level'] = 1;
            $stats['display_level'] = 'Aman';
            $stats['color'] = '#22c55e'; // green-500
        } else {
            $avg_weight = $weighted_score / $stats['total'];
            
            if ($avg_weight >= 10) {
                // Sangat berbahaya
                $stats['dominant_level'] = 4;
                $stats['display_level'] = 'Bahaya';
                $stats['color'] = '#ef4444'; // red-500
            } elseif ($avg_weight >= 5) {
                // Rawan
                $stats['dominant_level'] = 3;
                $stats['display_level'] = 'Rawan';
                $stats['color'] = '#f97316'; // orange-500
            } elseif ($avg_weight >= 2) {
                // Siaga
                $stats['dominant_level'] = 2;
                $stats['display_level'] = 'Siaga';
                $stats['color'] = '#eab308'; // yellow-500
            } else {
                // Aman
                $stats['dominant_level'] = 1;
                $stats['display_level'] = 'Aman';
                $stats['color'] = '#22c55e'; // green-500
            }
        }
        
        // Tambahkan detail kejahatan untuk jam ini
        $stats['crimes'] = isset($detailsByHour[$hour]) ? $detailsByHour[$hour] : [];
    }
    
    // Konversi ke array biasa (0-indexed)
    $hourlyStats = array_values($hourlyStats);
    
    echo json_encode([
        'success' => true,
        'data' => $hourlyStats
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
