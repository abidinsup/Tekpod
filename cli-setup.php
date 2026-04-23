<?php
require_once __DIR__ . '/config.php';

try {
    $setupPdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
    $setupPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $sql = file_get_contents(__DIR__ . '/database/schema.sql');
    
    if (!$sql) {
        die("Gagal membaca file database/schema.sql\n");
    }
    
    $setupPdo->exec($sql);
    echo "Database created successfully via CLI!\n";
} catch (PDOException $e) {
    echo "Gagal membuat database: " . $e->getMessage() . "\n";
}
