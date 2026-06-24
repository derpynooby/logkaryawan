<?php
require '../shared/auth.php'; require '../shared/config.php';
$user = require_role('pic'); $uid = $user['id'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }

csrf_verify(); // SEC-04 — after session guaranteed active


function back(string $to, string $type, string $msg): never {
    set_flash($type, $msg); header("Location: $to"); exit;
}

$id = (int)($_POST['id'] ?? 0);
switch ($_POST['action'] ?? '') {

case 'approve':
    $s = $db->prepare('UPDATE logbooks SET status="approved",catatan_pic=NULL
                       WHERE id=? AND pic_id=? AND status="pending"');
    $s->execute([$id, $uid]);
    // SEC-01: whitelist internal redirect
    $redirect = safe_redirect($_POST['redirect'] ?? 'dashboard.php'); // SEC-02
    back($redirect, $s->rowCount() ? 'success' : 'error',
         $s->rowCount() ? 'Logbook disetujui.' : 'Tidak ditemukan.');

case 'approve_all':
    // SEC-09: sanitize IDs — cast to int, drop ≤0
    $raw_ids     = (array)($_POST['logbook_ids'] ?? []);
    $logbook_ids = array_values(array_filter(array_map('intval', $raw_ids), fn($v) => $v > 0));
    $user_id     = (int)($_POST['user_id'] ?? 0);
    $tanggal     = $_POST['tanggal'] ?? '';
    // SEC-02: whitelist redirect
    $redirect    = safe_redirect($_POST['redirect'] ?? 'logbook.php');

    if (!$logbook_ids || !$user_id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
        back($redirect, 'error', 'Data logbook, user, atau tanggal tidak valid.');
    }
    // BUG-08: tanggal tidak boleh masa depan
    if ($tanggal > date('Y-m-d')) back($redirect, 'error', 'Tanggal tidak valid.');

    $placeholders = implode(',', array_fill(0, count($logbook_ids), '?'));
    $params = array_merge($logbook_ids, [$uid, $user_id, $tanggal]);
    $db->prepare("UPDATE logbooks SET status='approved', catatan_pic=NULL
                  WHERE id IN ($placeholders) AND pic_id=? AND user_id=? AND tanggal=? AND status='pending'")
       ->execute($params);
    $jumlah = $db->query('SELECT ROW_COUNT()')->fetchColumn();
    back($redirect,
         $jumlah > 0 ? 'success' : 'error',
         $jumlah > 0 ? "Berhasil menyetujui $jumlah logbook." : 'Tidak ada logbook yang disetujui.');

case 'decline':
    $cat = trim($_POST['catatan'] ?? '');
    if (!$cat) back("logbook.php?review=$id", 'error', 'Alasan penolakan wajib diisi.');
    $s = $db->prepare('UPDATE logbooks SET status="declined",catatan_pic=?
                       WHERE id=? AND pic_id=? AND status="pending"');
    $s->execute([$cat, $id, $uid]);
    back('logbook.php', $s->rowCount() ? 'success' : 'error',
         $s->rowCount() ? 'Logbook ditolak.' : 'Tidak ditemukan.');

case 'profile':
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    if (!$name || !$email) back('profile.php', 'error', 'Nama dan email wajib diisi.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back('profile.php', 'error', 'Format email tidak valid.');
    $s = $db->prepare('SELECT id FROM users WHERE email=? AND id!=?'); $s->execute([$email, $uid]);
    if ($s->fetch()) back('profile.php', 'error', 'Email sudah digunakan.');
    $db->prepare('UPDATE users SET name=?,email=? WHERE id=?')->execute([$name, $email, $uid]);
    $_SESSION['user']['name']  = $name;
    $_SESSION['user']['email'] = $email;
    back('profile.php', 'success', 'Profil diperbarui.');

case 'password':
    $old  = $_POST['old'] ?? '';
    $new  = $_POST['new'] ?? '';
    $conf = $_POST['confirm'] ?? '';
    $s = $db->prepare('SELECT password FROM users WHERE id=?'); $s->execute([$uid]);
    if (!password_verify($old, $s->fetchColumn())) back('profile.php', 'error', 'Sandi lama salah.');
    if ($new !== $conf) back('profile.php', 'error', 'Konfirmasi sandi tidak cocok.');
    if (strlen($new) < 8) back('profile.php', 'error', 'Sandi minimal 8 karakter.'); // SEC-07
    $db->prepare('UPDATE users SET password=? WHERE id=?')
       ->execute([password_hash($new, PASSWORD_DEFAULT), $uid]);
    back('profile.php', 'success', 'Kata sandi berhasil diubah.');

default: header('Location: dashboard.php');
}
