<?php
$currentUser = getCurrentUser();
// Only owner and supervisor can access settings
if (!in_array($currentUser['role'], ['owner', 'supervisor'])) {
    redirect('dashboard');
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_settings') {
        $ngThreshold = (int)($_POST['ng_threshold'] ?? 100);
        $companyName = trim($_POST['company_name'] ?? 'TEKPOD');
        $companyTagline = trim($_POST['company_tagline'] ?? 'Tracking Produksi Percetakan');

        $success = true;
        $success = $success && updateSetting('ng_threshold', $ngThreshold);
        $success = $success && updateSetting('company_name', $companyName);
        $success = $success && updateSetting('company_tagline', $companyTagline);
        
        if ($success) {
            $_SESSION['flash_message'] = 'Pengaturan berhasil diperbarui!';
            $_SESSION['flash_type'] = 'success';
        } else {
            $_SESSION['flash_message'] = 'Beberapa pengaturan mungkin gagal diperbarui.';
            $_SESSION['flash_type'] = 'warning';
        }
        redirect('settings');
    }
}

$ngThreshold = getSetting('ng_threshold', 100);
$companyName = getSetting('company_name', 'TEKPOD');
$companyTagline = getSetting('company_tagline', 'Tracking Produksi Percetakan');
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
        <h1 class="page-title">Pengaturan Aplikasi</h1>
        <p class="page-subtitle">Konfigurasi parameter, identitas, dan ambang batas sistem</p>
    </div>
</div>

<form method="POST" action="index.php?page=settings">
    <input type="hidden" name="action" value="update_settings">

    <div style="display: grid; gap: var(--space-6); max-width: 800px;">
        
        <!-- Identitas Perusahaan -->
        <div class="card animate-fade-in-up" style="animation-delay: 0.1s;">
            <div class="card-header">
                <h3 class="card-title">Identitas Perusahaan</h3>
            </div>
            <div class="card-body">
                <div style="display: grid; gap: var(--space-4);">
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="company_name">Nama Perusahaan/Aplikasi</label>
                        <input type="text" class="form-input" id="company_name" name="company_name" value="<?= htmlspecialchars($companyName) ?>" required>
                    </div>
                    <div class="form-group" style="margin-bottom: 0;">
                        <label class="form-label" for="company_tagline">Slogan (Tagline)</label>
                        <input type="text" class="form-input" id="company_tagline" name="company_tagline" value="<?= htmlspecialchars($companyTagline) ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Ambang Batas Produksi -->
        <div class="card animate-fade-in-up" style="animation-delay: 0.2s;">
            <div class="card-header">
                <h3 class="card-title">Ambang Batas Produksi</h3>
            </div>
            <div class="card-body">
                <div class="form-group" style="margin-bottom: 0;">
                    <label class="form-label" for="ng_threshold">Trigger Alert Reject (NG)</label>
                    <div style="display: flex; align-items: center; gap: var(--space-3);">
                        <input type="number" class="form-input" id="ng_threshold" name="ng_threshold" value="<?= $ngThreshold ?>" min="0" required style="width: 120px;">
                        <span style="color: var(--text-muted); font-size: var(--font-size-sm);">pcs</span>
                    </div>
                    <p style="margin-top: var(--space-2); font-size: var(--font-size-xs); color: var(--text-muted);">
                        Tentukan jumlah total produk reject (NG) yang akan memicu status <strong>"Perlu Perhatian"</strong> di dashboard.
                    </p>
                </div>
            </div>
        </div>

        <!-- Simpan Button Area -->
        <div class="card animate-fade-in-up" style="animation-delay: 0.3s;">
            <div class="card-body" style="display: flex; justify-content: flex-start; align-items: center; gap: var(--space-4);">
                <span style="color: var(--text-muted); font-size: var(--font-size-sm);">Pastikan data sudah benar sebelum menyimpan.</span>
                <button type="submit" class="btn btn-primary" id="btn-save-settings">
                    💾 Simpan Semua Pengaturan
                </button>
            </div>
        </div>

        <!-- Informasi -->
        <div class="card animate-fade-in-up" style="animation-delay: 0.4s; background: rgba(59, 130, 246, 0.05); border-color: rgba(59, 130, 246, 0.2);">
            <div style="display: flex; gap: var(--space-4); align-items: flex-start; padding: var(--space-4);">
                <div style="font-size: 1.5rem;">ℹ️</div>
                <div>
                    <h4 style="margin: 0; font-size: var(--font-size-sm); font-weight: 600; color: var(--accent-primary);">Informasi</h4>
                    <p style="margin: var(--space-1) 0 0 0; font-size: var(--font-size-xs); color: var(--text-muted); line-height: 1.5;">
                        Pengaturan ini berlaku secara global. Nama perusahaan akan tampil di menu samping (sidebar). Ambang batas digunakan untuk pantauan order pada dashboard pusat.
                    </p>
                </div>
            </div>
        </div>

    </div>
</form>
