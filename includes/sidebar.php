<?php
$currentUser = getCurrentUser();
$initials = '';
if ($currentUser) {
    $parts = explode(' ', $currentUser['nama']);
    $initials = strtoupper(substr($parts[0], 0, 1));
    if (isset($parts[1])) $initials .= strtoupper(substr($parts[1], 0, 1));
}
$companyNameSetting = getSetting('company_name', 'TEKPOD');
?>

<!-- Mobile Menu Button -->
<button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()">☰</button>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo"><?= htmlspecialchars($companyNameSetting) ?></div>
    </div>

    <nav class="sidebar-nav">
        <div class="sidebar-section-label">Menu Utama</div>

        <a href="index.php?page=dashboard" class="nav-item <?= isActivePage('dashboard') ?>" id="nav-dashboard">
            <span class="nav-item-icon">📊</span>
            <span>Dashboard</span>
        </a>

        <a href="index.php?page=input-produksi" class="nav-item <?= isActivePage('input-produksi') ?>" id="nav-input">
            <span class="nav-item-icon">🏭</span>
            <span>Input Produksi Mesin</span>
        </a>

        <a href="index.php?page=input-qc" class="nav-item <?= isActivePage('input-qc') ?>" id="nav-qc">
            <span class="nav-item-icon">✅</span>
            <span>Input QC / Sortir</span>
        </a>

        <?php if ($currentUser && $currentUser['role'] === 'owner'): ?>
        <div class="sidebar-section-label">Manajemen</div>
        <a href="index.php?page=users" class="nav-item <?= isActivePage('users') ?>" id="nav-users">
            <span class="nav-item-icon">👥</span>
            <span>Manajemen User</span>
        </a>
        <?php endif; ?>

        <?php if ($currentUser && in_array($currentUser['role'], ['owner', 'supervisor'])): ?>
        <div class="sidebar-section-label">Sistem</div>
        <a href="index.php?page=settings" class="nav-item <?= isActivePage('settings') ?>" id="nav-settings">
            <span class="nav-item-icon">⚙️</span>
            <span>Pengaturan</span>
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user" style="margin-bottom: var(--space-4);">
            <div class="sidebar-user-avatar"><?= $initials ?></div>
            <div class="sidebar-user-info">
                <div class="sidebar-user-name"><?= $currentUser['nama'] ?? 'Guest' ?></div>
                <div class="sidebar-user-role"><?= ucfirst($currentUser['role'] ?? '') ?></div>
            </div>
        </div>
        
        <a href="index.php?action=logout" class="btn btn-secondary w-full" 
           style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: #ef4444; justify-content: center; gap: 8px; font-weight: 700;" 
           onclick="openModal('logoutModal'); return false;" id="btn-logout-final">
            <span style="font-size: 1.1rem;">🚪</span> Logout
        </a>
    </div>
</aside>

<!-- Logout Confirmation Modal -->
<div class="modal-overlay" id="logoutModal">
    <div class="modal" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">Konfirmasi Logout</h3>
            <button class="modal-close" onclick="closeModal('logoutModal')">&times;</button>
        </div>
        <div class="modal-body text-center" style="padding: var(--space-8) var(--space-6); text-align: center;">
            <div style="font-size: 3rem; margin-bottom: var(--space-4);">🚪</div>
            <p style="color: var(--text-primary); font-size: var(--font-size-lg); font-weight: 600; margin-bottom: var(--space-2);">Yakin ingin keluar?</p>
            <p style="color: var(--text-muted);">Sesi Anda akan berakhir dan Anda harus login kembali untuk mengakses TEKPOD.</p>
        </div>
        <div class="modal-footer" style="justify-content: center; gap: var(--space-4); padding-bottom: var(--space-8);">
            <button class="btn btn-secondary" onclick="closeModal('logoutModal')" style="min-width: 120px;">Batal</button>
            <a href="index.php?action=logout" class="btn btn-primary" style="min-width: 120px; background: var(--status-danger); border-color: var(--status-danger); color: white; display: flex; align-items: center; justify-content: center; text-decoration: none;">Ya, Logout</a>
        </div>
    </div>
</div>

<!-- Sidebar overlay for mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
