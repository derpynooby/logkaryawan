<?php
if (session_status() === PHP_SESSION_NONE) session_start();

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
        // Redirect to dedicated error page (419 = session expired / token mismatch)
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
