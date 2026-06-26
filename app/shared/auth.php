<?php
if (session_status() === PHP_SESSION_NONE) {
    // FIX: Hardening session cookie — Secure, HttpOnly, SameSite=Lax
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => true,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function require_login(): array {
    if (empty($_SESSION['user'])) { header('Location: ../index.php'); exit; }
    return $_SESSION['user'];
}

function require_role(string ...$roles): array {
    $user = require_login();
    if (!in_array($user['role'], $roles, true)) { header('Location: ../index.php'); exit; }
    return $user;
}

function today_hours(PDO $db, int $uid): float {
    $s = $db->prepare('SELECT COALESCE(SUM(jam),0) FROM logbooks WHERE user_id=? AND tanggal=CURDATE()');
    $s->execute([$uid]);
    return (float)$s->fetchColumn();
}

function set_flash(string $type, string $msg): void {
    $_SESSION['flash'] = compact('type', 'msg');
}

// ── CSRF helpers ─────────────────────────────────────────────────
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="_csrf" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verify(): void {
    $session_token = $_SESSION['csrf'] ?? '';
    $post_token    = $_POST['_csrf'] ?? '';

    if ($session_token === '' || $post_token === '' || !hash_equals($session_token, $post_token)) {
        $depth = str_repeat('../', substr_count($_SERVER['SCRIPT_NAME'], '/') - 2);
        header('Location: ' . $depth . 'error.php?code=419');
        exit;
    }
}

// ── Safe redirect (SEC-01, SEC-02) ───────────────────────────────
function safe_redirect(string $raw, array $allowed = ['dashboard.php', 'logbook.php', 'profile.php']): string {
    $path = basename(parse_url($raw, PHP_URL_PATH) ?? '');
    return in_array($path, $allowed, true) ? $path : 'dashboard.php';
}

// ── Login rate limiting ───────────────────────────────────────────
// FIX: Brute force protection — max 5 attempts per 5 menit
function login_check_throttle(): bool {
    $now      = time();
    $window   = 300; // 5 menit
    $max      = 5;
    $key      = 'login_attempts';
    $ts_key   = 'login_window_start';

    if (!isset($_SESSION[$ts_key]) || ($now - $_SESSION[$ts_key]) > $window) {
        $_SESSION[$ts_key] = $now;
        $_SESSION[$key]    = 0;
    }

    return $_SESSION[$key] >= $max;
}

function login_record_attempt(): void {
    $_SESSION['login_attempts'] = ($_SESSION['login_attempts'] ?? 0) + 1;
}

function login_reset_attempts(): void {
    unset($_SESSION['login_attempts'], $_SESSION['login_window_start']);
}
