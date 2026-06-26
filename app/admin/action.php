<?php
require '../shared/auth.php';
require '../shared/config.php';

$user = require_role('admin');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: dashboard.php'); exit; }

csrf_verify();

function back(string $to, string $type, string $msg): never {
    set_flash($type, $msg); header("Location: $to"); exit;
}

// Role yang boleh dikelola admin (semua kecuali dirinya sendiri)
// FIX: Admin kini bisa CRUD semua role, bukan hanya karyawan
$MANAGEABLE_ROLES = ['karyawan', 'pic', 'direktur', 'admin'];

$action = $_POST['action'] ?? '';

// ── User CRUD ─────────────────────────────────────────────────────
if ($action === 'create_user') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if (!$name || !$email || !$password || !$role)
        back('karyawan.php', 'error', 'Semua field wajib diisi.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        back('karyawan.php', 'error', 'Format email tidak valid.');
    if (strlen($password) < 8)
        back('karyawan.php', 'error', 'Password minimal 8 karakter.');
    if (!in_array($role, $MANAGEABLE_ROLES, true))
        back('karyawan.php', 'error', 'Role tidak valid.');

    $s = $db->prepare('SELECT id FROM users WHERE email=?'); $s->execute([$email]);
    if ($s->fetch()) back('karyawan.php', 'error', 'Email sudah digunakan.');

    $db->prepare("INSERT INTO users (name,email,password,role) VALUES (?,?,?,?)")
       ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role]);
    back('karyawan.php', 'success', 'User berhasil ditambahkan.');
}

if ($action === 'update_user') {
    $id       = (int)($_POST['id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role     = $_POST['role'] ?? '';

    if ($id <= 0 || !$name || !$email || !$role)
        back('karyawan.php', 'error', 'Data tidak valid.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))
        back('karyawan.php', 'error', 'Format email tidak valid.');
    if (!in_array($role, $MANAGEABLE_ROLES, true))
        back('karyawan.php', 'error', 'Role tidak valid.');
    // Cegah admin hapus role dirinya sendiri
    if ($id === (int)$user['id'] && $role !== 'admin')
        back('karyawan.php', 'error', 'Anda tidak bisa mengubah role akun sendiri.');

    $s = $db->prepare("SELECT id FROM users WHERE id=?"); $s->execute([$id]);
    if (!$s->fetch()) back('karyawan.php', 'error', 'User tidak ditemukan.');

    $s = $db->prepare('SELECT id FROM users WHERE email=? AND id!=?'); $s->execute([$email, $id]);
    if ($s->fetch()) back("karyawan.php?edit=$id", 'error', 'Email sudah digunakan.');

    if ($password !== '') {
        if (strlen($password) < 8)
            back("karyawan.php?edit=$id", 'error', 'Password minimal 8 karakter.');
        $db->prepare('UPDATE users SET name=?,email=?,password=?,role=? WHERE id=?')
           ->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $id]);
    } else {
        $db->prepare('UPDATE users SET name=?,email=?,role=? WHERE id=?')
           ->execute([$name, $email, $role, $id]);
    }
    back('karyawan.php', 'success', 'Data user berhasil diperbarui.');
}

if ($action === 'delete_user') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) back('karyawan.php', 'error', 'Data tidak valid.');
    // Cegah admin hapus dirinya sendiri
    if ($id === (int)$user['id'])
        back('karyawan.php', 'error', 'Anda tidak bisa menghapus akun sendiri.');

    $s = $db->prepare("SELECT id, role FROM users WHERE id=?"); $s->execute([$id]);
    $target = $s->fetch();
    if (!$target) back('karyawan.php', 'error', 'User tidak ditemukan.');

    // Cegah hapus jika punya logbook (untuk karyawan & pic)
    if (in_array($target['role'], ['karyawan', 'pic'])) {
        $col = $target['role'] === 'karyawan' ? 'user_id' : 'pic_id';
        $s = $db->prepare("SELECT COUNT(*) FROM logbooks WHERE $col=?"); $s->execute([$id]);
        if ((int)$s->fetchColumn() > 0)
            back('karyawan.php', 'error', 'User tidak bisa dihapus karena memiliki data logbook.');
    }

    $db->prepare("DELETE FROM users WHERE id=?")->execute([$id]);
    back('karyawan.php', 'success', 'User berhasil dihapus.');
}

// Backward compat aliases (tetap support action lama)
if ($action === 'create_karyawan') { $_POST['action'] = 'create_user'; $_POST['role'] = 'karyawan'; require __FILE__; exit; }
if ($action === 'update_karyawan') { $_POST['action'] = 'update_user'; $_POST['role'] = 'karyawan'; require __FILE__; exit; }
if ($action === 'delete_karyawan') { $_POST['action'] = 'delete_user'; require __FILE__; exit; }

// ── Logbook CRUD ──────────────────────────────────────────────────
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
    if ($status === 'declined' && $catatan_pic === '')
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
    if ($status === 'declined' && $catatan_pic === '')
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
