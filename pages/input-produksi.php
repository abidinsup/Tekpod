<?php
$currentUser = getCurrentUser();
$orders      = getAllOrders();

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'create_order') {
        $namaJob  = $_POST['nama_job'] ?? '';
        $pelanggan = $_POST['pelanggan'] ?? '';
        $qtyOrder  = (int)($_POST['qty_order'] ?? 0);

        if ($namaJob && $pelanggan && $qtyOrder > 0) {
            global $pdo;
            try {
                $pdo->beginTransaction();
                $tanggalMulai = date('Y-m-d');
                $stmt = $pdo->prepare("INSERT INTO orders (nama_job, pelanggan, qty_order, tanggal_mulai, created_by) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$namaJob, $pelanggan, $qtyOrder, $tanggalMulai, $currentUser['id']]);
                $newOrderId = $pdo->lastInsertId();

                $activeProcs = getAllProcesses();
                foreach ($activeProcs as $proc) {
                    $stmtProc = $pdo->prepare("INSERT INTO order_processes (order_id, process_id, urutan) VALUES (?, ?, ?)");
                    $stmtProc->execute([$newOrderId, $proc['id'], $proc['urutan']]);
                }

                $pdo->commit();
                $_SESSION['flash_message'] = 'Order baru berhasil ditambahkan!';
                if (isAjax()) sendJsonResponse(true, 'Order baru berhasil ditambahkan!');
            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $_SESSION['flash_message'] = 'Gagal menambahkan order: ' . $e->getMessage();
                $_SESSION['flash_type']    = 'error';
                if (isAjax()) sendJsonResponse(false, 'Gagal menambahkan order: ' . $e->getMessage());
            }
        } else {
            $_SESSION['flash_message'] = 'Gagal menyimpan. Pastikan Nama Order, No PO/Pelanggan, dan Qty terisi.';
            $_SESSION['flash_type']    = 'error';
            if (isAjax()) sendJsonResponse(false, 'Gagal menyimpan. Pastikan semua field terisi.');
        }
        redirect('input-produksi');
    }

    if ($_POST['action'] === 'input_produksi') {
        $orderId    = (int)($_POST['order_id'] ?? 0);
        $processId  = (int)($_POST['process_id'] ?? 0);
        $shift      = !empty($_POST['shift']) ? (int)$_POST['shift'] : null;
        $tipeProses = $_POST['tipe_proses'] ?? null;
        $bgs        = (int)($_POST['hasil_bgs'] ?? 0);
        $nc         = (int)($_POST['hasil_nc'] ?? 0);
        $ng         = (int)($_POST['hasil_ng'] ?? 0);

        if ($orderId && $processId && ($bgs + $nc + $ng) > 0) {
            global $pdo;

            // --- VALIDATION: check process belongs to order ---
            $stmtProc = $pdo->prepare("SELECT urutan FROM order_processes WHERE order_id = ? AND process_id = ?");
            $stmtProc->execute([$orderId, $processId]);
            $currentOrderProc = $stmtProc->fetch();

            if (!$currentOrderProc) {
                $_SESSION['flash_message'] = 'Gagal: Proses tidak terdaftar untuk order ini.';
                $_SESSION['flash_type']    = 'error';
                if (isAjax()) sendJsonResponse(false, 'Gagal: Proses tidak terdaftar untuk order ini.');
                redirect('input-produksi');
            }

            $urutan     = $currentOrderProc['urutan'];
            $maxAllowed = 0;

            if ($urutan == 1) {
                $stmtOrder = $pdo->prepare("SELECT qty_order FROM orders WHERE id = ?");
                $stmtOrder->execute([$orderId]);
                $maxAllowed = $stmtOrder->fetchColumn();
            } else {
                $stmtPrev = $pdo->prepare("
                    SELECT p.id, op.urutan
                    FROM order_processes op
                    JOIN processes p ON op.process_id = p.id
                    WHERE op.order_id = ? AND op.urutan < ?
                    ORDER BY op.urutan DESC LIMIT 1
                ");
                $stmtPrev->execute([$orderId, $urutan]);
                $prevProc = $stmtPrev->fetch();

                if ($prevProc) {
                    $stmtBgs = $pdo->prepare("SELECT SUM(hasil_bgs) FROM production_logs WHERE order_id = ? AND process_id = ?");
                    $stmtBgs->execute([$orderId, $prevProc['id']]);
                    $maxAllowed = (int)$stmtBgs->fetchColumn();
                }
            }

            $totalInput = $bgs + $nc + $ng;
            if ($totalInput > $maxAllowed) {
                $_SESSION['flash_message'] = "Gagal: Total input ($totalInput) melebihi stok sebelumnya ($maxAllowed).";
                $_SESSION['flash_type']    = 'error';
                if (isAjax()) sendJsonResponse(false, "Total input ($totalInput) melebihi stok sebelumnya ($maxAllowed).");
                redirect('input-produksi');
            }
            // --- END VALIDATION ---

            $stmt = $pdo->prepare("INSERT INTO production_logs (order_id, process_id, shift, tipe_proses, hasil_bgs, hasil_nc, hasil_ng, operator_id, operator_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$orderId, $processId, $shift, $tipeProses, $bgs, $nc, $ng, $currentUser['id'], $currentUser['nama']]);

            $stmtUpdate = $pdo->prepare("UPDATE orders SET status = 'progress' WHERE id = ? AND status = 'draft'");
            $stmtUpdate->execute([$orderId]);

            $_SESSION['flash_message'] = 'Data produksi berhasil disimpan!';
            if (isAjax()) sendJsonResponse(true, 'Data produksi berhasil disimpan!');
        } else {
            $_SESSION['flash_message'] = 'Gagal menyimpan. Pastikan semua field terisi.';
            $_SESSION['flash_type']    = 'error';
            if (isAjax()) sendJsonResponse(false, 'Gagal menyimpan. Pastikan semua field terisi.');
        }
        redirect('input-produksi');
    }
}

// Fetch order-processes mapping for JS
global $pdo;
$orderProcMapping = [];
$allOrderProcs    = $pdo->query("
    SELECT op.order_id, op.process_id, p.nama_proses, p.icon
    FROM order_processes op
    JOIN processes p ON op.process_id = p.id
    ORDER BY op.order_id, op.urutan
")->fetchAll();

foreach ($allOrderProcs as $row) {
    if ($row['process_id'] == 7) continue; // Skip QC process here
    $orderProcMapping[$row['order_id']][] = [
        'id'   => $row['process_id'],
        'nama' => $row['icon'] . ' ' . $row['nama_proses']
    ];
}
?>

<script>
    const orderProcesses = <?= json_encode($orderProcMapping) ?>;
</script>

<?php if (isset($_SESSION['flash_message'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '<?= ($_SESSION['flash_type'] ?? 'success') === 'success' ? 'Berhasil!' : 'Peringatan!' ?>',
                text: '<?= addslashes($_SESSION['flash_message']) ?>',
                icon: '<?= $_SESSION['flash_type'] ?? 'success' ?>',
                confirmButtonText: 'Tutup',
                confirmButtonColor: '#06b6d4',
                background: '#0f172a',
                color: '#f1f5f9'
            });
        } else if (typeof showToast === 'function') {
            showToast('<?= addslashes($_SESSION['flash_message']) ?>', '<?= $_SESSION['flash_type'] ?? 'success' ?>');
        }
    });
</script>
<?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Input Produksi Mesin</h1>
        <p class="page-subtitle">Catat hasil proses produksi per mesin</p>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 380px; gap:var(--space-6); align-items:start;">

    <!-- Main Form -->
    <div class="card animate-fade-in-up">
        <div class="card-header">
            <h2 class="card-title">🏭 Form Input Proses</h2>
        </div>

        <form id="productionForm" method="POST" action="index.php?page=input-produksi">
            <input type="hidden" name="action" value="input_produksi">

            <!-- Pilih Order -->
            <div class="form-group">
                <label class="form-label" for="order_id" style="display:flex; justify-content:space-between; align-items:center;">
                    <span>Pilih Order <span class="required">*</span></span>
                    <button type="button" class="btn btn-secondary btn-sm" onclick="openModal('addOrderModal')" style="padding:2px 10px; font-size:var(--font-size-xs);">
                        ➕ Order Baru
                    </button>
                </label>
                <select class="form-select" id="order_id" name="order_id" required onchange="handleOrderChange()">
                    <option value="">-- Pilih Order --</option>
                    <?php foreach ($orders as $order): ?>
                        <?php if ($order['status'] !== 'selesai'): ?>
                        <option value="<?= $order['id'] ?>">
                            #ORD-<?= str_pad($order['id'], 4, '0', STR_PAD_LEFT) ?> - <?= $order['nama_job'] ?> (<?= $order['pelanggan'] ?>)
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Pilih Proses -->
            <div class="form-group">
                <label class="form-label" for="process_id">Pilih Proses <span class="required">*</span></label>
                <select class="form-select" id="process_id" name="process_id" required onchange="handleProcessChange()">
                    <option value="">-- Pilih Proses --</option>
                </select>
            </div>

            <!-- Tipe Proses (khusus Laminating) -->
            <div class="form-group" id="tipeProses-group" style="display:none;">
                <label class="form-label" for="tipe_proses">Tipe Proses</label>
                <select class="form-select" id="tipe_proses" name="tipe_proses">
                    <option value="">-- Pilih Tipe --</option>
                    <option value="Gloss">Gloss</option>
                    <option value="Matte">Matte</option>
                </select>
            </div>

            <!-- Shift -->
            <div class="form-group">
                <label class="form-label" for="shift">Shift (opsional)</label>
                <select class="form-select" id="shift" name="shift">
                    <option value="">-- Tidak Ada --</option>
                    <option value="1">Shift 1 (Pagi)</option>
                    <option value="2">Shift 2 (Siang)</option>
                    <option value="3">Shift 3 (Malam)</option>
                </select>
            </div>

            <!-- Hasil Produksi -->
            <div style="margin-bottom:var(--space-6);">
                <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-secondary);margin-bottom:var(--space-4);">Hasil Produksi</div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="hasil_bgs" style="color:var(--status-success);">✅ BGS (Bagus) <span class="required">*</span></label>
                        <div class="number-input-group">
                            <button type="button" onclick="adjustNumber('hasil_bgs', -100)">−</button>
                            <input type="number" class="form-input" id="hasil_bgs" name="hasil_bgs" min="0" value="0" required>
                            <button type="button" onclick="adjustNumber('hasil_bgs', 100)">＋</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="hasil_nc" style="color:var(--status-warning);">⚠️ NC (Not Clean) <span class="required">*</span></label>
                        <div class="number-input-group">
                            <button type="button" onclick="adjustNumber('hasil_nc', -10)">−</button>
                            <input type="number" class="form-input" id="hasil_nc" name="hasil_nc" min="0" value="0" required>
                            <button type="button" onclick="adjustNumber('hasil_nc', 10)">＋</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="hasil_ng" style="color:var(--status-danger);">❌ NG (Reject) <span class="required">*</span></label>
                        <div class="number-input-group">
                            <button type="button" onclick="adjustNumber('hasil_ng', -10)">−</button>
                            <input type="number" class="form-input" id="hasil_ng" name="hasil_ng" min="0" value="0" required>
                            <button type="button" onclick="adjustNumber('hasil_ng', 10)">＋</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div style="display:flex; gap:var(--space-3); justify-content:flex-end; padding-top:var(--space-4); border-top:1px solid var(--border-primary);">
                <button type="reset" class="btn btn-secondary" id="btn-reset">Reset</button>
                <button type="submit" class="btn btn-primary btn-lg" id="btn-submit">
                    💾 Simpan Data
                </button>
            </div>
        </form>
    </div>

    <!-- Side Panel - Live Preview -->
    <div class="card animate-fade-in-up" style="position:sticky;top:var(--space-8);">
        <div class="card-header">
            <h3 class="card-title">Preview Input</h3>
        </div>

        <div id="livePreview" style="display:flex;flex-direction:column;gap:var(--space-4);">
            <div class="empty-state" style="padding:var(--space-6) 0;">
                <div class="empty-state-icon">📋</div>
                <p class="empty-state-desc">Isi form untuk melihat preview</p>
            </div>
        </div>

        <!-- Operator Info -->
        <div style="margin-top:var(--space-5);padding-top:var(--space-4);border-top:1px solid var(--border-primary);">
            <div style="display:flex;align-items:center;gap:var(--space-3);">
                <div style="width:32px;height:32px;border-radius:var(--radius-full);background:var(--accent-gradient);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:var(--font-size-xs);color:white;">
                    <?= strtoupper(substr($currentUser['nama'], 0, 1)) ?>
                </div>
                <div>
                    <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);"><?= $currentUser['nama'] ?></div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);">Operator · <?= date('d/m/Y H:i') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function handleOrderChange() {
        const orderId   = document.getElementById('order_id').value;
        const procSelect = document.getElementById('process_id');

        procSelect.innerHTML = '<option value="">-- Pilih Proses --</option>';

        if (orderId && orderProcesses[orderId]) {
            orderProcesses[orderId].forEach(proc => {
                const opt      = document.createElement('option');
                opt.value      = proc.id;
                opt.textContent = proc.nama;
                procSelect.appendChild(opt);
            });
        }

        if (typeof updateLivePreview === 'function') updateLivePreview();
    }

    function handleProcessChange() {
        const processSelect = document.getElementById('process_id');
        const tipeGroup     = document.getElementById('tipeProses-group');

        if (processSelect && tipeGroup) {
            tipeGroup.style.display = processSelect.value === '2' ? 'block' : 'none';
            if (processSelect.value === '2') tipeGroup.style.animation = 'fadeInUp 0.3s ease';
        }

        if (typeof updateLivePreview === 'function') updateLivePreview();
    }
</script>

<!-- Add Order Modal -->
<div class="modal-overlay" id="addOrderModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tambah Order Baru</h3>
            <button class="modal-close" onclick="closeModal('addOrderModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addOrderForm" method="POST" action="index.php?page=input-produksi">
                <input type="hidden" name="action" value="create_order">
                <div class="form-group">
                    <label class="form-label" for="new_nama_job">Nama Order <span class="required">*</span></label>
                    <input type="text" class="form-input" id="new_nama_job" name="nama_job" placeholder="Contoh: Brosur A4 Full Color" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_pelanggan">No PO / Nama Pelanggan <span class="required">*</span></label>
                    <input type="text" class="form-input" id="new_pelanggan" name="pelanggan" placeholder="Contoh: PO-2026-001 / PT ABC" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_qty_order">Qty Order <span class="required">*</span></label>
                    <input type="number" class="form-input" id="new_qty_order" name="qty_order" min="1" placeholder="Jumlah cetak (lembar/pcs)" required>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addOrderModal')">Batal</button>
            <button type="submit" form="addOrderForm" class="btn btn-primary">💾 Simpan Order</button>
        </div>
    </div>
</div>
