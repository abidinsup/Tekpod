<?php
/**
 * TEKPOD - Database Setup Script
 * Menjalankan script ini akan membuat database dan tabel otomatis (jika belum ada)
 */

if (isset($_GET['run'])) {
    // Kita panggil DB_HOST dsb manual tanpa mencoba connect ke dbname dulu
    require_once 'config.php';
    
    try {
        $setupPdo = new PDO("mysql:host=" . DB_HOST . ";charset=utf8mb4", DB_USER, DB_PASS);
        $setupPdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $sql = file_get_contents(__DIR__ . '/database/schema.sql');
        
        if (!$sql) {
            die("Gagal membaca file database/schema.sql");
        }
        
        $setupPdo->exec($sql);
        echo "<div style='background:#10b981;color:white;padding:15px;border-radius:8px;margin-bottom:20px;font-family:sans-serif;'>";
        echo "✅ Database <strong>tekpod_db</strong> dan tabel-tabel berhasil dibuat/diimpor! <br>";
        echo "<a href='index.php' style='color:white;text-decoration:underline;margin-top:10px;display:inline-block;'>Kembali ke Aplikasi</a>";
        echo "</div>";
    } catch (PDOException $e) {
        echo "<div style='background:#ef4444;color:white;padding:15px;border-radius:8px;font-family:sans-serif;'>";
        echo "❌ Gagal membuat database: " . $e->getMessage();
        echo "</div>";
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Setup Database TEKPOD</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; color: #1e293b; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.1); max-width: 500px; width: 100%; text-align: center; }
        .btn { background: #3b82f6; color: white; padding: 10px 20px; border: none; border-radius: 6px; font-size: 16px; cursor: pointer; text-decoration: none; display: inline-block; margin-top: 20px; }
        .btn:hover { background: #2563eb; }
    </style>
</head>
<body>
    <div class="card">
        <h2 style="margin-top:0;">Instalasi Database TEKPOD</h2>
        <p>Klik tombol di bawah ini untuk membuat database <code>tekpod_db</code> secara otomatis beserta isian data awalnya.</p>
        <p style="font-size: 14px; color: #64748b;">(Pastikan MySQL server seperti XAMPP / Laragon sudah Running)</p>
        <a href="setup.php?run=1" class="btn">🚀 Generate Database Sekarang</a>
    </div>
</body>
</html>
