<?php
require '../shared/auth.php'; require '../shared/config.php';
$user = require_role('karyawan'); $uid = $user['id'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: logbook.php'); exit; }

csrf_verify(); // SEC-04 — after session guaranteed active


function back(string $to, string $type, string $msg): never {
    set_flash($type, $msg); header("Location: $to"); exit;
}

switch ($_POST['action'] ?? '') {

case 'add':
    $jam = (float)($_POST['jam'] ?? 0);
    $pic = (int)($_POST['pic_id'] ?? 0);
    $akt = trim($_POST['aktivitas'] ?? '');
    if ($jam <= 0 || $jam > 24 || !$pic || !$akt)  // BUG-02: tambah max 24
        back('logbook.php', 'error', 'Semua field wajib diisi dan jam harus antara 0.25–24.');
    $s = $db->prepare("SELECT id FROM users WHERE id=? AND role='pic'"); $s->execute([$pic]);
    if (!$s->fetch()) back('logbook.php', 'error', 'PIC tidak valid.');
    $db->prepare('INSERT INTO logbooks (user_id,pic_id,tanggal,jam,aktivitas) VALUES (?,?,CURDATE(),?,?)')
       ->execute([$uid, $pic, $jam, $akt]);
    back('logbook.php', 'success', 'Aktivitas berhasil disimpan.');

case 'edit':
    $id  = (int)($_POST['id'] ?? 0);
    $jam = (float)($_POST['jam'] ?? 0);
    $pic = (int)($_POST['pic_id'] ?? 0);
    $akt = trim($_POST['aktivitas'] ?? '');
    if ($jam <= 0 || $jam > 24 || !$pic || !$akt)  // BUG-02
        back("edit.php?id=$id", 'error', 'Semua field wajib diisi dan jam harus antara 0.25–24.');
    $s = $db->prepare('UPDATE logbooks SET jam=?,pic_id=?,aktivitas=?
                       WHERE id=? AND user_id=? AND tanggal=CURDATE() AND status="pending"');
    $s->execute([$jam, $pic, $akt, $id, $uid]);
    back('logbook.php', $s->rowCount() ? 'success' : 'error',
         $s->rowCount() ? 'Aktivitas diperbarui.' : 'Entri tidak dapat diubah (hanya pending hari ini).');

case 'delete':
    $id = (int)($_POST['id'] ?? 0);
    $s  = $db->prepare('DELETE FROM logbooks WHERE id=? AND user_id=? AND tanggal=CURDATE() AND status="pending"');
    $s->execute([$id, $uid]);
    back('logbook.php', $s->rowCount() ? 'success' : 'error',
         $s->rowCount() ? 'Entri dihapus.' : 'Entri tidak dapat dihapus.');

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

default: header('Location: logbook.php');
}
