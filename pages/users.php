<?php
$currentUser = getCurrentUser();
if ($currentUser['role'] !== 'owner') {
    redirect('dashboard');
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    global $pdo;
    
    if ($_POST['action'] === 'add_user') {
        $nama = $_POST['nama'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';
        
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (nama, email, role, password) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $email, $role, $hashedPassword]);
        
        $_SESSION['flash_message'] = 'User baru berhasil ditambahkan!';
        redirect('users');
    }
    
    if ($_POST['action'] === 'edit_user') {
        $id = (int)($_POST['id'] ?? 0);
        $nama = $_POST['nama'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? '';
        $password = $_POST['password'] ?? '';
        
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, role=?, password=? WHERE id=?");
            $stmt->execute([$nama, $email, $role, $hashedPassword, $id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, role=? WHERE id=?");
            $stmt->execute([$nama, $email, $role, $id]);
        }
        
        // Update current user if it's the one being edited
        if ($currentUser['id'] === $id) {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id=?");
            $stmt->execute([$id]);
            $_SESSION['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        $_SESSION['flash_message'] = 'Data user berhasil diperbarui!';
        redirect('users');
    }
    
    if ($_POST['action'] === 'delete_user') {
        $id = (int)($_POST['id'] ?? 0);
        if ($id !== $currentUser['id']) {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
            $stmt->execute([$id]);
            $_SESSION['flash_message'] = 'User berhasil dihapus!';
        }
        redirect('users');
    }
}

// Ensure $users is up to date with db
global $pdo;
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
?>

<?php if (isset($_SESSION['flash_message'])): ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        if (typeof showToast === 'function') {
            showToast('<?= addslashes($_SESSION['flash_message']) ?>', 'success');
        }
    });
</script>
<?php unset($_SESSION['flash_message']); endif; ?>


<!-- Page Header -->
<div class="page-header">
    <div>
        <h1 class="page-title">Manajemen User</h1>
        <p class="page-subtitle">Kelola pengguna dan hak akses sistem</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="openModal('addUserModal')" id="btn-add-user">
            <span>＋</span> Tambah User
        </button>
    </div>
</div>

<!-- Users Stats -->
<div class="stats-grid stagger-children" style="grid-template-columns: repeat(3, 1fr);">
    <div class="stat-card">
        <div class="stat-card-icon purple">👨‍💼</div>
        <div class="stat-card-value"><?= count(array_filter($users, fn($u) => $u['role'] === 'owner')) ?></div>
        <div class="stat-card-label">Owner</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon cyan">👷</div>
        <div class="stat-card-value"><?= count(array_filter($users, fn($u) => $u['role'] === 'operator')) ?></div>
        <div class="stat-card-label">Operator</div>
    </div>
    <div class="stat-card">
        <div class="stat-card-icon amber">👁️</div>
        <div class="stat-card-value"><?= count(array_filter($users, fn($u) => $u['role'] === 'supervisor')) ?></div>
        <div class="stat-card-label">Supervisor</div>
    </div>
</div>

<!-- Users Table -->
<div class="card animate-fade-in-up">
    <div class="table-container" style="border:none;background:transparent;">
        <table class="data-table" id="usersTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:var(--space-3);">
                            <div style="width:38px;height:38px;border-radius:var(--radius-full);background:<?= $user['role'] === 'owner' ? 'linear-gradient(135deg, #8b5cf6, #6d28d9)' : ($user['role'] === 'supervisor' ? 'linear-gradient(135deg, #f59e0b, #d97706)' : 'linear-gradient(135deg, #06b6d4, #0891b2)') ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:var(--font-size-sm);color:white;flex-shrink:0;">
                                <?= strtoupper(substr($user['nama'], 0, 1)) ?>
                            </div>
                            <div class="table-cell-name"><?= $user['nama'] ?></div>
                        </div>
                    </td>
                    <td style="color:var(--text-muted);"><?= $user['email'] ?></td>
                    <td>
                        <span class="badge badge-<?= $user['role'] ?>">
                            <?= ucfirst($user['role']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge badge-selesai">Aktif</span>
                    </td>
                    <td>
                        <div style="display:flex;gap:var(--space-2);">
                            <button class="btn btn-secondary btn-sm" onclick="openEditModal(<?= htmlspecialchars(json_encode($user)) ?>)" id="btn-edit-<?= $user['id'] ?>">
                                ✏️ Edit
                            </button>
                            <?php if ($user['id'] !== $currentUser['id']): ?>
                            <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?= $user['id'] ?>, '<?= htmlspecialchars(addslashes($user['nama'])) ?>')" id="btn-delete-<?= $user['id'] ?>">
                                🗑️
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Role Permissions Info -->
<div class="card animate-fade-in-up mt-6">
    <div class="card-header">
        <h3 class="card-title">Hak Akses Per Role</h3>
    </div>
    <div class="table-container" style="border:none;background:transparent;">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Fitur</th>
                    <th style="text-align:center;">Owner</th>
                    <th style="text-align:center;">Operator</th>
                    <th style="text-align:center;">Supervisor</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="table-cell-name">Dashboard</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                </tr>
                <tr>
                    <td class="table-cell-name">Lihat Detail Order</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                </tr>
                <tr>
                    <td class="table-cell-name">Input Produksi</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                    <td style="text-align:center;color:var(--text-muted);">❌</td>
                </tr>
                <tr>
                    <td class="table-cell-name">Validasi Data</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                    <td style="text-align:center;color:var(--text-muted);">❌</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                </tr>
                <tr>
                    <td class="table-cell-name">Manajemen User</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                    <td style="text-align:center;color:var(--text-muted);">❌</td>
                    <td style="text-align:center;color:var(--text-muted);">❌</td>
                </tr>
                <tr>
                    <td class="table-cell-name">Manage Order</td>
                    <td style="text-align:center;color:var(--status-success);">✅</td>
                    <td style="text-align:center;color:var(--text-muted);">❌</td>
                    <td style="text-align:center;color:var(--text-muted);">❌</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Add User Modal -->
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Tambah User Baru</h3>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="addUserForm" method="POST" action="index.php?page=users">
                <input type="hidden" name="action" value="add_user">
                <div class="form-group">
                    <label class="form-label" for="new_nama">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" class="form-input" id="new_nama" name="nama" placeholder="Masukkan nama lengkap" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_email">Email <span class="required">*</span></label>
                    <input type="email" class="form-input" id="new_email" name="email" placeholder="contoh@tekpod.com" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_role">Role <span class="required">*</span></label>
                    <select class="form-select" id="new_role" name="role" required>
                        <option value="">-- Pilih Role --</option>
                        <option value="owner">Owner</option>
                        <option value="operator">Operator</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="new_password">Password <span class="required">*</span></label>
                    <input type="password" class="form-input" id="new_password" name="password" placeholder="Minimal 6 karakter" required minlength="6">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('addUserModal')">Batal</button>
            <button type="submit" form="addUserForm" class="btn btn-primary" id="btn-save-user">💾 Simpan User</button>
        </div>
    </div>
</div>

<!-- Edit User Modal -->
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <h3 class="modal-title">Edit User</h3>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <div class="modal-body">
            <form id="editUserForm" method="POST" action="index.php?page=users">
                <input type="hidden" name="action" value="edit_user">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label class="form-label" for="edit_nama">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" class="form-input" id="edit_nama" name="nama" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_email">Email <span class="required">*</span></label>
                    <input type="email" class="form-input" id="edit_email" name="email" required>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_role">Role <span class="required">*</span></label>
                    <select class="form-select" id="edit_role" name="role" required>
                        <option value="owner">Owner</option>
                        <option value="operator">Operator</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label" for="edit_password">Password (Kosongkan jika tidak ingin mengubah)</label>
                    <input type="password" class="form-input" id="edit_password" name="password" placeholder="Minimal 6 karakter" minlength="6">
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Batal</button>
            <button type="submit" form="editUserForm" class="btn btn-primary" id="btn-update-user">💾 Simpan Perubahan</button>
        </div>
    </div>
</div>

<!-- Delete Form Hidden -->
<form id="deleteUserForm" method="POST" action="index.php?page=users" style="display:none;">
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="id" id="delete_id">
</form>

<script>
function openEditModal(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_nama').value = user.nama;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_role').value = user.role;
    document.getElementById('edit_password').value = ''; // Biarkan kosong
    openModal('editUserModal');
}

function confirmDelete(id, nama) {
    if (confirm('Apakah Anda yakin ingin menghapus user ' + nama + '?')) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteUserForm').submit();
    }
}
</script>
