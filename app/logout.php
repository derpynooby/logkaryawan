<?php
// FIX: Session hardening sebelum session_start()
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => true,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// FIX: Logout CSRF — hanya boleh via POST dengan token yang valid
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$session_token = $_SESSION['csrf'] ?? '';
$post_token    = $_POST['_csrf']   ?? '';

if ($session_token === '' || $post_token === '' || !hash_equals($session_token, $post_token)) {
    header('Location: index.php');
    exit;
}

// Hancurkan sesi sepenuhnya
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: index.php');
exit;
