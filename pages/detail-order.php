<?php
$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    redirect('dashboard');
}

$order = getOrderById($orderId);
if (!$order) {
    redirect('dashboard');
}

$logs = getLogsByOrderId($orderId);
$progress = getOrderProgress($order);

// Build process status map
$completedProcessIds = array_unique(array_column(array_values($logs), 'process_id'));

// Get actual processes assigned to this order
$orderProcesses = getOrderProcesses($orderId);

// Calculate totals
$totalBGS = 0;
$totalNC = 0;
$totalNG = 0;
foreach ($logs as $log) {
    $totalBGS += $log['hasil_bgs'];
    $totalNC += $log['hasil_nc'];
    $totalNG += $log['hasil_ng'];
}
$totalOutput = $totalBGS + $totalNC + $totalNG;
$lossFromOrder = $order['qty_order'] - $totalBGS;
?>

<!-- Page Header -->
<div class="page-header">
    <div style="display:flex; align-items:center; gap:var(--space-4);">
        <a href="index.php?page=dashboard" class="btn btn-secondary btn-icon" id="btn-back" title="Kembali">←</a>
        <div>
            <h1 class="page-title"><?= $order['nama_job'] ?></h1>
            <p class="page-subtitle">#ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?> · <?= $order['pelanggan'] ?></p>
        </div>
    </div>
    <div class="page-actions">
        <span class="badge <?= getStatusBadge($order['status']) ?>" style="font-size: var(--font-size-sm); padding: 6px 16px;">
            <?= getStatusLabel($order['status']) ?>
        </span>
    </div>
</div>

<!-- Order Info -->
<div class="card animate-fade-in-up mb-6">
    <div class="order-info-grid">
        <div class="order-info-item">
            <span class="order-info-label">Pelanggan</span>
            <span class="order-info-value"><?= $order['pelanggan'] ?></span>
        </div>
        <div class="order-info-item">
            <span class="order-info-label">Qty Order</span>
            <span class="order-info-value"><?= formatNumber($order['qty_order']) ?></span>
        </div>
        <div class="order-info-item">
            <span class="order-info-label">Tanggal Mulai</span>
            <span class="order-info-value"><?= formatDate($order['tanggal_mulai']) ?></span>
        </div>
        <div class="order-info-item">
            <span class="order-info-label">Progress</span>
            <span class="order-info-value text-accent"><?= $progress ?>%</span>
        </div>
    </div>
    <div style="margin-top:var(--space-5);">
        <div class="progress-bar" style="height:10px;">
            <div class="progress-bar-fill <?= $progress >= 100 ? 'green' : '' ?>" style="width:<?= $progress ?>%"></div>
        </div>
    </div>
</div>

<!-- Process Timeline -->
<div class="card animate-fade-in-up mb-6">
    <div class="card-header">
        <h2 class="card-title">Timeline Proses</h2>
        <span style="font-size:var(--font-size-sm);color:var(--text-muted);"><?= getCompletedProcesses($order['id']) ?>/<?= $order['total_processes'] ?> proses selesai</span>
    </div>
    <div class="process-timeline">
        <?php foreach ($orderProcesses as $i => $proc): 
            $isCompleted = in_array($proc['id'], $completedProcessIds);
            $isActive = !$isCompleted && ($i === 0 || in_array($orderProcesses[$i-1]['id'], $completedProcessIds));
            $stepClass = $isCompleted ? 'completed' : ($isActive ? 'active' : '');
        ?>
            <?php if ($i > 0): ?>
                <div class="process-step-connector <?= $isCompleted ? 'completed' : '' ?>"></div>
            <?php endif; ?>
            <div class="process-step <?= $stepClass ?>">
                <div class="process-step-dot">
                    <?php if ($isCompleted): ?>✓<?php else: ?><?= $i + 1 ?><?php endif; ?>
                </div>
                <span class="process-step-label"><?= $proc['nama_proses'] ?></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Summary Stats -->
<div class="stats-grid stagger-children mb-6">
    <div class="stat-card">
        <div class="stat-card-icon green">✅</div>
        <div class="stat-card-value"><?= formatNumber($totalBGS) ?></div>
        <div class="stat-card-label">Total BGS (Bagus)</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon amber">⚠️</div>
        <div class="stat-card-value"><?= formatNumber($totalNC) ?></div>
        <div class="stat-card-label">Total NC (Not Clean)</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon red">❌</div>
        <div class="stat-card-value"><?= formatNumber($totalNG) ?></div>
        <div class="stat-card-label">Total NG (Reject)</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon cyan">📊</div>
        <div class="stat-card-value"><?= formatNumber($totalOutput) ?></div>
        <div class="stat-card-label">Total Output</div>
    </div>
</div>

<!-- Process Result Cards -->
<div class="card animate-fade-in-up">
    <div class="card-header">
        <h2 class="card-title">Hasil Per Proses</h2>
    </div>

    <?php if (count($logs) === 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📋</div>
            <h3 class="empty-state-title">Belum Ada Data</h3>
            <p class="empty-state-desc">Belum ada proses yang diinput untuk order ini.</p>
            <a href="index.php?page=input-produksi" class="btn btn-primary">Input Produksi</a>
        </div>
    <?php else: ?>
        <div class="process-results-grid">
            <?php foreach ($logs as $log):
                $proc = getProcessById($log['process_id']);
                $logTotal = $log['hasil_bgs'] + $log['hasil_nc'] + $log['hasil_ng'];
                $bgsPercent = $logTotal > 0 ? ($log['hasil_bgs'] / $logTotal) * 100 : 0;
                $ncPercent = $logTotal > 0 ? ($log['hasil_nc'] / $logTotal) * 100 : 0;
                $ngPercent = $logTotal > 0 ? ($log['hasil_ng'] / $logTotal) * 100 : 0;
            ?>
            <div class="process-result-card">
                <div class="process-result-header">
                    <div>
                        <div class="process-result-name">
                            <?= $proc['icon'] ?> <?= $proc['nama_proses'] ?>
                            <?php if ($log['tipe_proses']): ?>
                                <span style="color:var(--text-muted);font-weight:400;font-size:var(--font-size-sm);">(<?= $log['tipe_proses'] ?>)</span>
                            <?php endif; ?>
                        </div>
                        <div style="font-size:var(--font-size-xs);color:var(--text-muted);margin-top:2px;">
                            <?= $log['operator'] ?> · Shift <?= $log['shift'] ?? '-' ?> · <?= formatDate($log['created_at']) ?>
                        </div>
                    </div>
                    <div style="font-size:var(--font-size-sm);font-weight:700;color:var(--text-primary);">
                        <?= formatNumber($logTotal) ?>
                    </div>
                </div>
                <div class="process-result-bars">
                    <div class="result-bar-item">
                        <span class="result-bar-label">BGS</span>
                        <div class="result-bar-track">
                            <div class="result-bar-value bgs" style="width:<?= $bgsPercent ?>%"></div>
                        </div>
                        <span class="result-bar-count text-success"><?= formatNumber($log['hasil_bgs']) ?></span>
                    </div>
                    <div class="result-bar-item">
                        <span class="result-bar-label">NC</span>
                        <div class="result-bar-track">
                            <div class="result-bar-value nc" style="width:<?= $ncPercent ?>%"></div>
                        </div>
                        <span class="result-bar-count text-warning"><?= formatNumber($log['hasil_nc']) ?></span>
                    </div>
                    <div class="result-bar-item">
                        <span class="result-bar-label">NG</span>
                        <div class="result-bar-track">
                            <div class="result-bar-value ng" style="width:<?= $ngPercent ?>%"></div>
                        </div>
                        <span class="result-bar-count text-danger"><?= formatNumber($log['hasil_ng']) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
