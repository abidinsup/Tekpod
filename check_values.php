<?php
require 'config.php';
require 'includes/functions.php';
$stats = getDashboardStats();
$threshold = getSetting('ng_threshold', 100);
echo "Total NG: " . $stats['total_ng'] . "\n";
echo "Threshold: " . $threshold . "\n";
