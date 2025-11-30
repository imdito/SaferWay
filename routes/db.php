<?php
    $host = 'localhost';
    $db   = 'criminality';
    $user = 'postgres';
    $pass = 'root';
    $port = '5432';

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";

    try {
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    } catch (\PDOException $e) {
        die("Koneksi Gagal: " . $e->getMessage());
    }

?>