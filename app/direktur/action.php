<?php
require '../shared/auth.php'; require '../shared/config.php';
$user = require_role('direktur'); $uid = $user['id'];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }

csrf_verify(); // SEC-04 — after session guaranteed active


function back(string $to, string $type, string $msg): never {
    set_flash($type, $msg); header("Location: $to"); exit;
}

switch ($_POST['action'] ?? '') {
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
