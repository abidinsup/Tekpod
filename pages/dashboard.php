<?php
// Handle Delete Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_order') {
    $currentUser = getCurrentUser();
    $role = $currentUser['role'] ?? 'operator';
    if (in_array($role, ['owner', 'supervisor'])) {
        $deleteId = (int)($_POST['order_id'] ?? 0);
        if ($deleteId > 0) {
            global $pdo;
            try {
                $pdo->beginTransaction();
                $pdo->prepare("DELETE FROM production_logs WHERE order_id = ?")->execute([$deleteId]);
                $pdo->prepare("DELETE FROM qc_logs WHERE order_id = ?")->execute([$deleteId]);
                $pdo->prepare("DELETE FROM order_processes WHERE order_id = ?")->execute([$deleteId]);
                $pdo->prepare("DELETE FROM orders WHERE id = ?")->execute([$deleteId]);
                $pdo->commit();
                $_SESSION['flash_message'] = 'Order berhasil dihapus.';
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['flash_message'] = 'Gagal menghapus order: ' . $e->getMessage();
                $_SESSION['flash_type'] = 'error';
            }
        }
    } else {
        $_SESSION['flash_message'] = 'Anda tidak memiliki akses untuk menghapus order.';
        $_SESSION['flash_type'] = 'error';
    }
    redirect('dashboard');
}

// Handle form submission for New Order
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_order') {
    $currentUser = getCurrentUser();
    $role = $currentUser['role'] ?? 'operator';
    if (in_array($role, ['owner', 'supervisor', 'operator'])) {
        $namaJob = $_POST['nama_job'] ?? '';
        $pelanggan = $_POST['pelanggan'] ?? '';
        $qtyOrder = (int)($_POST['qty_order'] ?? 0);
        $tanggalMulai = $_POST['tanggal_mulai'] ?? date('Y-m-d');
        
        $selectedProcesses = $_POST['processes'] ?? []; // array of selected process IDs
        $totalProcesses = count($selectedProcesses);
        
        if ($namaJob && $pelanggan && $qtyOrder > 0 && $totalProcesses > 0) {
            global $pdo;
            try {
                $pdo->beginTransaction();
                
                $stmt = $pdo->prepare("INSERT INTO orders (nama_job, pelanggan, qty_order, tanggal_mulai, status, created_by) VALUES (?, ?, ?, ?, 'draft', ?)");
                $stmt->execute([$namaJob, $pelanggan, $qtyOrder, $tanggalMulai, $currentUser['id']]);
                
                $orderId = $pdo->lastInsertId();
                
                $stmtProcess = $pdo->prepare("INSERT INTO order_processes (order_id, process_id, urutan) VALUES (?, ?, ?)");
                foreach ($selectedProcesses as $index => $procId) {
                    $stmtProcess->execute([$orderId, $procId, $index + 1]);
                }
                
                $pdo->commit();
                $_SESSION['flash_message'] = 'Order baru berhasil ditambahkan!';
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['flash_message'] = 'Terjadi kesalahan sistem saat menyimpan order.';
                $_SESSION['flash_type'] = 'error';
            }
        } else {
            $_SESSION['flash_message'] = 'Gagal menambah order. Pastikan semua field dan minimal 1 proses dipilih.';
            $_SESSION['flash_type'] = 'error';
        }
        redirect('dashboard');
    }
}

$stats = getDashboardStats();
$orders = getAllOrders();
$processes = getAllProcesses();
$currentUser = getCurrentUser();
$role = $currentUser['role'] ?? 'operator';
$ngThreshold = (int)getSetting('ng_threshold', 100);
$ngStatus = $stats['total_ng'] >= $ngThreshold ? 'Perlu perhatian' : 'Normal';
$ngTrendClass = $stats['total_ng'] >= $ngThreshold ? 'down' : 'up';
$ngTrendIcon = $stats['total_ng'] >= $ngThreshold ? '↓' : '↑';

$greeting = 'Selamat Datang';
$hour = (int)date('H');
if ($hour < 12) $greeting = 'Selamat Pagi';
elseif ($hour < 15) $greeting = 'Selamat Siang';
elseif ($hour < 18) $greeting = 'Selamat Sore';
else $greeting = 'Selamat Malam';
?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title"><?= $greeting ?>, <?= explode(' ', $currentUser['nama'])[0] ?> 👋</h1>
        <p class="page-subtitle">Pantau progress produksi percetakan secara real-time</p>
    </div>
    <div class="page-actions">
        <a href="index.php?page=input-produksi" class="btn btn-primary" id="btn-new-input">
            <span>＋</span> Input Produksi
        </a>
    </div>
</div>

<!-- Stats Grid -->
<div class="stats-grid stagger-children" id="stats-section">
    <div class="stat-card">
        <div class="stat-card-icon cyan">📦</div>
        <div class="stat-card-value"><?= $stats['total_orders'] ?></div>
        <div class="stat-card-label">Total Order</div>
        <div class="stat-card-trend up">↑ Aktif</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon purple">⚡</div>
        <div class="stat-card-value"><?= $stats['on_progress'] ?></div>
        <div class="stat-card-label">On Progress</div>
        <div class="stat-card-trend up">↑ Berjalan</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon green">✅</div>
        <div class="stat-card-value"><?= $stats['selesai'] ?></div>
        <div class="stat-card-label">Selesai</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon red">🔴</div>
        <div class="stat-card-value"><?= formatNumber($stats['total_ng']) ?></div>
        <div class="stat-card-label">Total Reject (NG)</div>
        <div class="stat-card-trend <?= $ngTrendClass ?>"><?= $ngTrendIcon ?> <?= $ngStatus ?></div>
    </div>
</div>

<!-- Orders Table -->
<div class="card animate-fade-in-up" id="orders-section">
    <div class="card-header">
        <div>
            <h2 class="card-title">Daftar Order Produksi</h2>
            <p class="card-description">Semua order yang sedang berjalan dan selesai</p>
        </div>
        <div class="page-actions" style="display:flex;gap:var(--space-3);align-items:center;">
            <div style="position:relative;">
                <input type="text" class="form-input" id="searchOrders" placeholder="🔍  Cari order..." style="width:220px; padding-left: var(--space-4); font-size: var(--font-size-sm);">
            </div>

        </div>
    </div>

    <div class="table-container" style="border:none; background:transparent;">
        <table class="data-table" id="ordersTable">
            <thead>
                <tr>
                    <th>Order</th>
                    <th>Pelanggan</th>
                    <th>Qty</th>
                    <th>Mulai</th>
                    <th>Status</th>
                    <th>Progress</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr class="order-row" data-search="<?= strtolower($order['nama_job'] . ' ' . $order['pelanggan']) ?>">
                    <td>
                        <div class="table-cell-name"><?= $order['nama_job'] ?></div>
                        <div class="table-cell-secondary">#ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?></div>
                    </td>
                    <td><?= $order['pelanggan'] ?></td>
                    <td><span class="font-semibold"><?= formatNumber($order['qty_order']) ?></span></td>
                    <td><?= formatDate($order['tanggal_mulai']) ?></td>
                    <td>
                        <span class="badge <?= getStatusBadge($order['status']) ?>">
                            <?= getStatusLabel($order['status']) ?>
                        </span>
                    </td>
                    <td style="min-width: 140px;">
                        <?php $progress = getOrderProgress($order); ?>
                        <div class="progress-bar-container">
                            <div class="progress-bar-label">
                                <span><?= getCompletedProcesses($order['id']) ?>/<?= $order['total_processes'] ?> proses</span>
                                <span class="font-semibold"><?= $progress ?>%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-bar-fill <?= $progress >= 100 ? 'green' : '' ?>" style="width: <?= $progress ?>%"></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if (in_array($role, ['owner', 'supervisor', 'operator'])): ?>
                        <div style="display:flex;gap:var(--space-2);align-items:center;">
                            <a href="index.php?page=detail-order&id=<?= $order['id'] ?>" class="btn btn-secondary btn-sm" id="btn-detail-<?= $order['id'] ?>">
                                Detail →
                            </a>
                            <?php if (in_array($role, ['owner', 'supervisor'])): ?>
                            <button
                                class="btn btn-danger btn-sm"
                                id="btn-delete-<?= $order['id'] ?>"
                                onclick="confirmDeleteOrder(<?= $order['id'] ?>, '<?= addslashes($order['nama_job']) ?>')"
                                title="Hapus Order"
                            >
                                🗑️
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span style="color:var(--text-muted); font-size:var(--font-size-xs);">No Access</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Quick Summary Cards -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: var(--space-5); margin-top: var(--space-8);">
    
    <!-- Recent Activity -->
    <div class="card animate-fade-in-up">
        <div class="card-header">
            <h3 class="card-title">Aktivitas Terkini</h3>
        </div>
        <div style="display:flex; flex-direction:column; gap: var(--space-4);">
            <?php 
            $recentLogs = getRecentLogs(5);
            foreach ($recentLogs as $log):
            ?>
            <div style="display:flex; align-items:flex-start; gap:var(--space-3); padding-bottom:var(--space-3); border-bottom:1px solid var(--border-primary);">
                <div style="width:36px;height:36px;border-radius:var(--radius-md);background:rgba(6,182,212,0.1);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:0.9rem;">
                    <?= $log['icon'] ?? '📋' ?>
                </div>
                <div style="flex:1;min-width:0;">
                    <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);">
                        <?= $log['nama_proses'] ?>
                        <?php if ($log['tipe_proses']): ?>
                            <span style="color:var(--text-muted);font-weight:400;">(<?= $log['tipe_proses'] ?>)</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);margin-top:2px;">
                        <?= $log['nama_job'] ?> · <?= $log['operator'] ?>
                    </div>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <div style="font-size:var(--font-size-xs);font-weight:600;color:var(--status-success);">BGS <?= formatNumber($log['hasil_bgs']) ?></div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);">Shift <?= $log['shift'] ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Orders by Progress -->
    <div class="card animate-fade-in-up">
        <div class="card-header">
            <h3 class="card-title">Order Hampir Selesai</h3>
        </div>
        <div style="display:flex; flex-direction:column; gap: var(--space-5);">
            <?php 
            $progressOrders = array_filter($orders, fn($o) => $o['status'] === 'progress');
            usort($progressOrders, fn($a, $b) => getOrderProgress($b) - getOrderProgress($a));
            $progressOrders = array_slice($progressOrders, 0, 4);
            foreach ($progressOrders as $order):
                $progress = getOrderProgress($order);
            ?>
            <div>
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:var(--space-2);">
                    <div>
                        <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);"><?= $order['nama_job'] ?></div>
                        <div style="font-size:var(--font-size-xs);color:var(--text-muted);"><?= $order['pelanggan'] ?></div>
                    </div>
                    <span style="font-size:var(--font-size-sm);font-weight:700;color:var(--accent-primary);"><?= $progress ?>%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-bar-fill" style="width:<?= $progress ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>



<!-- Hidden delete form -->
<form id="deleteOrderForm" method="POST" action="index.php?page=dashboard" style="display:none;">
    <input type="hidden" name="action" value="delete_order">
    <input type="hidden" name="order_id" id="deleteOrderId" value="">
</form>

<!-- Flash message + Delete confirmation script -->
<script>
    function confirmDeleteOrder(orderId, namaJob) {
        Swal.fire({
            title: 'Hapus Order?',
            html: `Yakin ingin menghapus order <strong>${namaJob}</strong>?<br><small style="color:#94a3b8;">Semua data produksi & QC terkait ikut terhapus.</small>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: '🗑️ Ya, Hapus!',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: '#475569',
            background: '#0f172a',
            color: '#f1f5f9',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('deleteOrderId').value = orderId;
                document.getElementById('deleteOrderForm').submit();
            }
        });
    }
</script>

<?php if (isset($_SESSION['flash_message'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '<?= ($_SESSION['flash_type'] ?? 'success') === 'success' ? 'Berhasil!' : 'Gagal!' ?>',
                text: '<?= addslashes($_SESSION['flash_message']) ?>',
                icon: '<?= $_SESSION['flash_type'] ?? 'success' ?>',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#06b6d4',
                background: '#0f172a',
                color: '#f1f5f9',
                timer: 3000,
                timerProgressBar: true
            });
        } else if (typeof showToast === 'function') {
            showToast('<?= addslashes($_SESSION['flash_message']) ?>', '<?= $_SESSION['flash_type'] ?? 'success' ?>');
        }
    });
</script>
<?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); endif; ?>
