<?php
header('Content-Type: application/json');
require 'config.php';
require 'includes/functions.php';

$stats = getDashboardStats();
$threshold = getSetting('ng_threshold', 100);

echo json_encode([
    'total_ng' => $stats['total_ng'],
    'threshold' => $threshold,
    'diff' => $threshold - $stats['total_ng'],
    'status' => ($stats['total_ng'] >= $threshold ? 'Perlu perhatian' : 'Normal')
]);
