<?php
/**
 * TEKPOD - Main Router
 * Entry point for the application
 */

// Enable Output Buffering to prevent "Headers already sent" errors
ob_start();

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';

// Handle logout via GET
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    header("Location: index.php?page=login");
    exit;
}

// Handle login/logout actions via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'login') {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        
        global $pdo;
        if (!$pdo) {
            $_SESSION['login_error'] = 'Koneksi database gagal: ' . ($db_error ?? 'Tidak diketahui');
            redirect('login');
        }
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['logged_in'] = true;
            $_SESSION['user'] = $user;
            redirect('dashboard');
        } else {
            $_SESSION['login_error'] = 'Email atau password salah.';
            redirect('login');
        }
    }
    
    if ($_POST['action'] === 'logout') {
        session_destroy();
        header("Location: index.php?page=login");
        exit;
    }
}

// Get current page
$page = getCurrentPage();

// Auth guard: redirect to login if not authenticated
$publicPages = ['login'];
if (!in_array($page, $publicPages) && !isLoggedIn()) {
    redirect('login');
}

// If logged in and on login page, go to dashboard
if ($page === 'login' && isLoggedIn()) {
    redirect('dashboard');
}

// RBAC: Defined page access per role
$permissions = [
    'owner'      => ['dashboard', 'input-produksi', 'input-qc', 'users', 'detail-order', 'settings'],
    'supervisor' => ['dashboard', 'input-produksi', 'input-qc', 'detail-order', 'settings'],
    'operator'   => ['dashboard', 'input-produksi', 'input-qc', 'detail-order']
];

$role = $_SESSION['user']['role'] ?? 'operator';
$allowedPages = $permissions[$role] ?? ['dashboard'];

if ($page !== 'login' && !in_array($page, $allowedPages)) {
    $_SESSION['flash_message'] = 'Maaf, Anda tidak memiliki akses ke halaman tersebut.';
    $_SESSION['flash_type'] = 'error';
    redirect('dashboard');
}

// Render the appropriate page
if ($page === 'login') {
    require_once __DIR__ . '/pages/login.php';
} else {
    // All other pages use the app layout (sidebar + content)
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/sidebar.php';
    
    echo '<main class="main-content">';
    
    switch ($page) {
        case 'dashboard':
            require_once __DIR__ . '/pages/dashboard.php';
            break;
        case 'detail-order':
            require_once __DIR__ . '/pages/detail-order.php';
            break;
        case 'input-produksi':
            require_once __DIR__ . '/pages/input-produksi.php';
            break;
        case 'input-qc':
            require_once __DIR__ . '/pages/input-qc.php';
            break;
        case 'users':
            require_once __DIR__ . '/pages/users.php';
            break;
        case 'settings':
            require_once __DIR__ . '/pages/settings.php';
            break;
        default:
            require_once __DIR__ . '/pages/dashboard.php';
            break;
    }
    
    echo '</main>';
    require_once __DIR__ . '/includes/footer.php';
}
