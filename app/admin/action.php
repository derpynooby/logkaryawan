<?php
require '../shared/auth.php';
require '../shared/config.php';

$user = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }

csrf_verify(); // SEC-04 — after session guaranteed active


function back(string $to, string $type, string $msg): never {
    set_flash($type, $msg); header("Location: $to"); exit;
}

$action = $_POST['action'] ?? '';

// ── Karyawan CRUD ────────────────────────────────────────────────
if ($action === 'create_karyawan') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$name || !$email || !$password) back('karyawan.php', 'error', 'Nama, email, dan password wajib diisi.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back('karyawan.php', 'error', 'Format email tidak valid.');
    if (strlen($password) < 8) back('karyawan.php', 'error', 'Password minimal 8 karakter.'); // SEC-07
    $s = $db->prepare('SELECT id FROM users WHERE email=?'); $s->execute([$email]);
    if ($s->fetch()) back('karyawan.php', 'error', 'Email sudah digunakan.');
    $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,'karyawan')")
       ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
    back('karyawan.php', 'success', 'Karyawan berhasil ditambahkan.');
}

if ($action === 'update_karyawan') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($id <= 0 || !$name || !$email) back('karyawan.php', 'error', 'Data tidak valid.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) back('karyawan.php', 'error', 'Format email tidak valid.');
    $s = $db->prepare("SELECT id FROM users WHERE id=? AND role='karyawan'"); $s->execute([$id]);
    if (!$s->fetch()) back('karyawan.php', 'error', 'Karyawan tidak ditemukan.');
    $s = $db->prepare('SELECT id FROM users WHERE email=? AND id!=?'); $s->execute([$email, $id]);
    if ($s->fetch()) back("karyawan.php?edit=$id", 'error', 'Email sudah digunakan.');
    if ($password !== '') {
        if (strlen($password) < 8) back("karyawan.php?edit=$id", 'error', 'Password minimal 8 karakter.'); // SEC-07
        $db->prepare('UPDATE users SET name=?,email=?,password=? WHERE id=? AND role="karyawan"')
           ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $id]);
    } else {
        $db->prepare('UPDATE users SET name=?,email=? WHERE id=? AND role="karyawan"')
           ->execute([$name, $email, $id]);
    }
    back('karyawan.php', 'success', 'Data karyawan berhasil diperbarui.');
}

if ($action === 'delete_karyawan') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) back('karyawan.php', 'error', 'Data tidak valid.');
    $s = $db->prepare("SELECT id FROM users WHERE id=? AND role='karyawan'"); $s->execute([$id]);
    if (!$s->fetch()) back('karyawan.php', 'error', 'Karyawan tidak ditemukan.');
    $s = $db->prepare('SELECT COUNT(*) FROM logbooks WHERE user_id=?'); $s->execute([$id]);
    if ((int)$s->fetchColumn() > 0) back('karyawan.php', 'error', 'Karyawan tidak bisa dihapus karena memiliki data logbook.');
    $db->prepare("DELETE FROM users WHERE id=? AND role='karyawan'")->execute([$id]);
    back('karyawan.php', 'success', 'Karyawan berhasil dihapus.');
}

// ── Logbook CRUD ─────────────────────────────────────────────────
if ($action === 'create_logbook') {
    $user_id     = (int)($_POST['user_id'] ?? 0);
    $pic_id      = (int)($_POST['pic_id'] ?? 0);
    $tanggal     = trim($_POST['tanggal'] ?? '');
    $jam         = trim($_POST['jam'] ?? '');
    $aktivitas   = trim($_POST['aktivitas'] ?? '');
    $status      = $_POST['status'] ?? 'pending';
    $catatan_pic = trim($_POST['catatan_pic'] ?? '');

    if (!$user_id || !$pic_id || !$tanggal || $jam === '' || !$aktivitas || !$status)
        back('logbook.php', 'error', 'Semua field wajib diisi.');
    if (!is_numeric($jam) || (float)$jam < 0 || (float)$jam > 99.99)
        back('logbook.php', 'error', 'Jam harus antara 0–99.99.');
    if (!in_array($status, ['pending', 'approved', 'declined']))
        back('logbook.php', 'error', 'Status tidak valid.');
    if ($status === 'declined' && $catatan_pic === '') // BUG-07
        back('logbook.php', 'error', 'Catatan PIC wajib diisi jika status Declined.');

    $s = $db->prepare("SELECT id FROM users WHERE id=? AND role='karyawan'"); $s->execute([$user_id]);
    if (!$s->fetch()) back('logbook.php', 'error', 'Karyawan tidak valid.');
    $s = $db->prepare("SELECT id FROM users WHERE id=? AND role='pic'"); $s->execute([$pic_id]);
    if (!$s->fetch()) back('logbook.php', 'error', 'PIC tidak valid.');

    $db->prepare("INSERT INTO logbooks (user_id,pic_id,tanggal,jam,aktivitas,status,catatan_pic,created_at) VALUES (?,?,?,?,?,?,?,NOW())")
       ->execute([$user_id, $pic_id, $tanggal, $jam, $aktivitas, $status, $catatan_pic]);
    back('logbook.php', 'success', 'Logbook berhasil ditambahkan.');
}

if ($action === 'update_logbook') {
    $id          = (int)($_POST['id'] ?? 0);
    $user_id     = (int)($_POST['user_id'] ?? 0);
    $pic_id      = (int)($_POST['pic_id'] ?? 0);
    $tanggal     = trim($_POST['tanggal'] ?? '');
    $jam         = trim($_POST['jam'] ?? '');
    $aktivitas   = trim($_POST['aktivitas'] ?? '');
    $status      = $_POST['status'] ?? 'pending';
    $catatan_pic = trim($_POST['catatan_pic'] ?? '');

    if ($id <= 0 || !$user_id || !$pic_id || !$tanggal || $jam === '' || !$aktivitas || !$status)
        back('logbook.php', 'error', 'Semua field wajib diisi.');
    if (!is_numeric($jam) || (float)$jam < 0 || (float)$jam > 99.99)
        back("logbook.php?edit=$id", 'error', 'Jam harus antara 0–99.99.');
    if (!in_array($status, ['pending', 'approved', 'declined']))
        back("logbook.php?edit=$id", 'error', 'Status tidak valid.');
    if ($status === 'declined' && $catatan_pic === '') // BUG-07
        back("logbook.php?edit=$id", 'error', 'Catatan PIC wajib diisi jika status Declined.');

    $s = $db->prepare("SELECT id FROM users WHERE id=? AND role='karyawan'"); $s->execute([$user_id]);
    if (!$s->fetch()) back("logbook.php?edit=$id", 'error', 'Karyawan tidak valid.');
    $s = $db->prepare("SELECT id FROM users WHERE id=? AND role='pic'"); $s->execute([$pic_id]);
    if (!$s->fetch()) back("logbook.php?edit=$id", 'error', 'PIC tidak valid.');
    $s = $db->prepare("SELECT id FROM logbooks WHERE id=?"); $s->execute([$id]);
    if (!$s->fetch()) back('logbook.php', 'error', 'Logbook tidak ditemukan.');

    $db->prepare("UPDATE logbooks SET user_id=?,pic_id=?,tanggal=?,jam=?,aktivitas=?,status=?,catatan_pic=? WHERE id=?")
       ->execute([$user_id, $pic_id, $tanggal, $jam, $aktivitas, $status, $catatan_pic, $id]);
    back('logbook.php', 'success', 'Logbook berhasil diperbarui.');
}

if ($action === 'delete_logbook') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) back('logbook.php', 'error', 'Data tidak valid.');
    $s = $db->prepare("SELECT id FROM logbooks WHERE id=?"); $s->execute([$id]);
    if (!$s->fetch()) back('logbook.php', 'error', 'Logbook tidak ditemukan.');
    $db->prepare("DELETE FROM logbooks WHERE id=?")->execute([$id]);
    back('logbook.php', 'success', 'Logbook berhasil dihapus.');
}

$url = ($action && str_contains($action, 'logbook')) ? 'logbook.php' : 'karyawan.php';
back($url, 'error', 'Aksi tidak dikenali.');
