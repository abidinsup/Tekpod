<?php
/**
 * TEKPOD - Database Helper Functions
 * Menangani semua query ke database MySQL
 */

require_once __DIR__ . '/../config.php';

/**
 * Helper: Get all users
 */
function getAllUsers() {
    global $pdo;
    return $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

/**
 * Helper: Get all active processes
 */
function getAllProcesses() {
    global $pdo;
    return $pdo->query("SELECT * FROM processes WHERE is_active = 1 ORDER BY urutan ASC")->fetchAll();
}

/**
 * Helper: Get all orders with total_processes count
 */
function getAllOrders() {
    global $pdo;
    return $pdo->query("
        SELECT o.*, 
        (SELECT COUNT(*) FROM order_processes op WHERE op.order_id = o.id) as total_processes 
        FROM orders o 
        ORDER BY o.id DESC
    ")->fetchAll();
}

/**
 * Helper: Get order by ID
 */
function getOrderById($id) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT o.*, 
        (SELECT COUNT(*) FROM order_processes op WHERE op.order_id = o.id) as total_processes 
        FROM orders o 
        WHERE o.id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Helper: Get processes assigned to an order
 */
function getOrderProcesses($orderId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT p.* 
        FROM processes p
        JOIN order_processes op ON p.id = op.process_id
        WHERE op.order_id = ?
        ORDER BY op.urutan ASC
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

/**
 * Helper: Get production logs for an order
 */
function getLogsByOrderId($orderId) {
    global $pdo;
    $stmt = $pdo->prepare("
        SELECT l.*, p.nama_proses, p.icon, u.nama as operator
        FROM production_logs l
        JOIN processes p ON l.process_id = p.id
        LEFT JOIN users u ON l.operator_id = u.id
        WHERE l.order_id = ?
        ORDER BY l.created_at DESC
    ");
    $stmt->execute([$orderId]);
    return $stmt->fetchAll();
}

/**
 * Helper: Get recent production logs for dashboard
 */
function getRecentLogs($limit = 10) {
    global $pdo;
    return $pdo->query("
        SELECT l.*, p.nama_proses, p.icon, u.nama as operator, o.nama_job
        FROM production_logs l
        JOIN processes p ON l.process_id = p.id
        JOIN orders o ON l.order_id = o.id
        LEFT JOIN users u ON l.operator_id = u.id
        ORDER BY l.created_at DESC
        LIMIT $limit
    ")->fetchAll();
}

/**
 * Helper: Get process by ID
 */
function getProcessById($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM processes WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

/**
 * Helper: Count completed (unique) processes for an order
 */
function getCompletedProcesses($orderId) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT COUNT(DISTINCT process_id) FROM production_logs WHERE order_id = ?");
    $stmt->execute([$orderId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Helper: Calculate dashboard stats from DB
 */
function getDashboardStats() {
    global $pdo;
    
    $totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $progressOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'progress'")->fetchColumn();
    $selesaiOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'selesai'")->fetchColumn();
    $draftOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'draft'")->fetchColumn();
    
    $logs = $pdo->query("SELECT SUM(hasil_ng) as total_ng, SUM(hasil_bgs) as total_bgs FROM production_logs")->fetch();
    
    return [
        'total_orders' => (int)$totalOrders,
        'on_progress' => (int)$progressOrders,
        'selesai' => (int)$selesaiOrders,
        'draft' => (int)$draftOrders,
        'total_ng' => (int)($logs['total_ng'] ?? 0),
        'total_bgs' => (int)($logs['total_bgs'] ?? 0),
    ];
}

/**
 * Helper: Get progress percentage for an order
 */
function getOrderProgress($order) {
    global $pdo;
    $tp = (int)($order['total_processes'] ?? 0);
    if ($tp == 0) return 0;
    
    // Check if QC (process_id 7) is completed
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM production_logs WHERE order_id = ? AND process_id = 7");
    $stmt->execute([$order['id']]);
    if ($stmt->fetchColumn() > 0) return 100;

    $completed = getCompletedProcesses($order['id']);
    return round(($completed / $tp) * 100);
}

/**
 * Helper: Get setting value by key
 */
function getSetting($key, $default = null) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $val = $stmt->fetchColumn();
        return ($val !== false) ? $val : $default;
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Helper: Update setting value
 */
function updateSetting($key, $value) {
    global $pdo;
    try {
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                                ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        return $stmt->execute([$key, $value]);
    } catch (Exception $e) {
        return false;
    }
}
