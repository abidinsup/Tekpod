<?php
$currentUser = getCurrentUser();
$orders = getAllOrders();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'input_qc') {
        $orderId    = (int)($_POST['order_id'] ?? 0);
        $totalOk    = (int)($_POST['total_ok'] ?? 0);
        $totalReject = (int)($_POST['total_reject'] ?? 0);
        $keterangan = $_POST['keterangan'] ?? '';

        if ($orderId && ($totalOk + $totalReject) > 0) {
            global $pdo;
            $stmt = $pdo->prepare("INSERT INTO production_logs (order_id, process_id, shift, tipe_proses, hasil_bgs, hasil_nc, hasil_ng, operator_id, operator_name, keterangan) VALUES (?, 7, NULL, NULL, ?, 0, ?, ?, ?, ?)");
            $stmt->execute([$orderId, $totalOk, $totalReject, $currentUser['id'], $currentUser['nama'], $keterangan]);

            // Auto-update order status to 'selesai' after QC
            $stmtUpdate = $pdo->prepare("UPDATE orders SET status = 'selesai' WHERE id = ?");
            $stmtUpdate->execute([$orderId]);

            $_SESSION['flash_message'] = 'Data QC berhasil disimpan! Order ditandai Selesai.';
            if (isAjax()) sendJsonResponse(true, 'Data QC berhasil disimpan! Order ditandai Selesai.');
        } else {
            $_SESSION['flash_message'] = 'Gagal menyimpan. Pastikan Order dipilih dan hasil QC terisi.';
            $_SESSION['flash_type']    = 'error';
            if (isAjax()) sendJsonResponse(false, 'Gagal menyimpan. Pastikan Order dipilih dan hasil QC terisi.');
        }
        redirect('input-qc');
    }
}
?>

<?php if (isset($_SESSION['flash_message'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof showToast === 'function') {
            showToast('<?= addslashes($_SESSION['flash_message']) ?>', '<?= $_SESSION['flash_type'] ?? 'success' ?>');
        }
    });
</script>
<?php unset($_SESSION['flash_message'], $_SESSION['flash_type']); endif; ?>

<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Input QC / Sortir</h1>
        <p class="page-subtitle">Input hasil quality control dan sortir akhir order</p>
    </div>
</div>

<div style="display:grid; grid-template-columns: 1fr 380px; gap:var(--space-6); align-items:start;">

    <!-- Main Form -->
    <div class="card animate-fade-in-up">
        <div class="card-header">
            <h2 class="card-title">✅ Form QC / Sortir Akhir</h2>
        </div>

        <form id="qcForm" method="POST" action="index.php?page=input-qc">
            <input type="hidden" name="action" value="input_qc">

            <!-- Pilih Order -->
            <div class="form-group">
                <label class="form-label" for="order_id">Pilih Order <span class="required">*</span></label>
                <select class="form-select" id="order_id" name="order_id" required onchange="updateQCPreview()">
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

            <!-- Hasil QC -->
            <div style="margin-bottom:var(--space-6);">
                <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-secondary);margin-bottom:var(--space-4);">Hasil Sortir</div>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label" for="total_ok" style="color:var(--status-success);">✅ Total OK <span class="required">*</span></label>
                        <div class="number-input-group">
                            <button type="button" onclick="adjustNumber('total_ok', -100)">−</button>
                            <input type="number" class="form-input" id="total_ok" name="total_ok" min="0" value="0" required oninput="updateQCPreview()">
                            <button type="button" onclick="adjustNumber('total_ok', 100)">＋</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="total_reject" style="color:var(--status-danger);">❌ Total Reject <span class="required">*</span></label>
                        <div class="number-input-group">
                            <button type="button" onclick="adjustNumber('total_reject', -10)">−</button>
                            <input type="number" class="form-input" id="total_reject" name="total_reject" min="0" value="0" required oninput="updateQCPreview()">
                            <button type="button" onclick="adjustNumber('total_reject', 10)">＋</button>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="keterangan">Keterangan</label>
                    <textarea class="form-textarea" id="keterangan" name="keterangan" placeholder="Catatan tambahan dari QC..." rows="3" oninput="updateQCPreview()"></textarea>
                </div>
            </div>

            <!-- Submit -->
            <div style="display:flex; gap:var(--space-3); justify-content:flex-end; padding-top:var(--space-4); border-top:1px solid var(--border-primary);">
                <button type="reset" class="btn btn-secondary" id="btn-reset-qc" onclick="setTimeout(updateQCPreview,50)">Reset</button>
                <button type="submit" class="btn btn-primary btn-lg" id="btn-submit-qc">
                    ✅ Simpan & Tutup Order
                </button>
            </div>
        </form>
    </div>

    <!-- Side Panel - Live Preview -->
    <div class="card animate-fade-in-up" style="position:sticky;top:var(--space-8);">
        <div class="card-header">
            <h3 class="card-title">Preview QC</h3>
        </div>

        <div id="qcPreview" style="display:flex;flex-direction:column;gap:var(--space-4);">
            <div class="empty-state" style="padding:var(--space-6) 0;">
                <div class="empty-state-icon">✅</div>
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
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);">QC · <?= date('d/m/Y H:i') ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function updateQCPreview() {
    const preview = document.getElementById('qcPreview');
    if (!preview) return;

    const orderSelect = document.getElementById('order_id');
    const ok          = parseInt(document.getElementById('total_ok')?.value) || 0;
    const reject      = parseInt(document.getElementById('total_reject')?.value) || 0;
    const keterangan  = document.getElementById('keterangan')?.value || '';
    const total       = ok + reject;

    const orderText = orderSelect && orderSelect.selectedIndex > 0
        ? orderSelect.options[orderSelect.selectedIndex].text
        : null;

    if (!orderText) {
        preview.innerHTML = `
            <div class="empty-state" style="padding:var(--space-6) 0;">
                <div class="empty-state-icon">✅</div>
                <p class="empty-state-desc">Isi form untuk melihat preview</p>
            </div>`;
        return;
    }

    const okPct     = total > 0 ? ((ok / total) * 100).toFixed(1) : 0;
    const rejectPct = total > 0 ? ((reject / total) * 100).toFixed(1) : 0;

    preview.innerHTML = `
        <div style="padding:var(--space-2) 0;">
            <div style="font-size:var(--font-size-xs);color:var(--text-muted);text-transform:uppercase;letter-spacing:0.05em;margin-bottom:var(--space-1);">Order</div>
            <div style="font-size:var(--font-size-sm);font-weight:600;color:var(--text-primary);margin-bottom:var(--space-4);">${orderText}</div>

            <div style="display:flex;gap:var(--space-4);margin-bottom:var(--space-4);">
                <div style="flex:1;background:rgba(34,197,94,0.08);border-radius:var(--radius-lg);padding:var(--space-3);text-align:center;">
                    <div style="font-size:var(--font-size-2xl);font-weight:800;color:var(--status-success);">${ok.toLocaleString('id-ID')}</div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);">Total OK</div>
                </div>
                <div style="flex:1;background:rgba(239,68,68,0.08);border-radius:var(--radius-lg);padding:var(--space-3);text-align:center;">
                    <div style="font-size:var(--font-size-2xl);font-weight:800;color:var(--status-danger);">${reject.toLocaleString('id-ID')}</div>
                    <div style="font-size:var(--font-size-xs);color:var(--text-muted);">Total Reject</div>
                </div>
            </div>

            ${total > 0 ? `
            <div style="font-size:var(--font-size-xs);color:var(--text-muted);margin-bottom:var(--space-2);">Total semua barang</div>
            <div style="font-size:var(--font-size-lg);font-weight:800;color:var(--accent-primary);margin-bottom:var(--space-3);">${total.toLocaleString('id-ID')}</div>
            <div style="display:flex;flex-direction:column;gap:var(--space-2);">
                <div style="display:flex;align-items:center;gap:var(--space-2);">
                    <span style="font-size:var(--font-size-xs);width:36px;color:var(--text-muted);">OK</span>
                    <div style="flex:1;height:6px;background:rgba(148,163,184,0.08);border-radius:99px;overflow:hidden;">
                        <div style="width:${okPct}%;height:100%;background:linear-gradient(90deg,#22c55e,#4ade80);border-radius:99px;transition:width 0.4s ease;"></div>
                    </div>
                    <span style="font-size:var(--font-size-xs);color:var(--status-success);width:40px;text-align:right;">${okPct}%</span>
                </div>
                <div style="display:flex;align-items:center;gap:var(--space-2);">
                    <span style="font-size:var(--font-size-xs);width:36px;color:var(--text-muted);">Reject</span>
                    <div style="flex:1;height:6px;background:rgba(148,163,184,0.08);border-radius:99px;overflow:hidden;">
                        <div style="width:${rejectPct}%;height:100%;background:linear-gradient(90deg,#ef4444,#f87171);border-radius:99px;transition:width 0.4s ease;"></div>
                    </div>
                    <span style="font-size:var(--font-size-xs);color:var(--status-danger);width:40px;text-align:right;">${rejectPct}%</span>
                </div>
            </div>
            ` : ''}

            ${keterangan ? `
            <div style="margin-top:var(--space-4);padding:var(--space-3);background:rgba(148,163,184,0.05);border-radius:var(--radius-md);border-left:3px solid var(--accent-primary);">
                <div style="font-size:var(--font-size-xs);color:var(--text-muted);margin-bottom:var(--space-1);">Keterangan</div>
                <div style="font-size:var(--font-size-sm);color:var(--text-secondary);">${keterangan}</div>
            </div>
            ` : ''}
        </div>`;
}
</script>
