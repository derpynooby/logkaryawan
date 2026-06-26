<?php
/**
 * error.php — Halaman error terpusat
 * FIX: HTTP_REFERER dihapus (open redirect risk) — gunakan javascript:history.back()
 */
if (session_status() === PHP_SESSION_NONE) session_start();

$code = (int)(
    $_GET['code']
    ?? $_SERVER['REDIRECT_STATUS']
    ?? http_response_code()
    ?: 500
);

if (!in_array($code, [400,401,403,404,405,419,500,502,503,504])) $code = 404;

http_response_code($code);

$defaults = [
    400 => ['📋', 'Permintaan Tidak Valid',    'Data yang dikirim tidak dapat diproses oleh server.'],
    401 => ['🔑', 'Autentikasi Diperlukan',    'Anda harus login untuk mengakses halaman ini.'],
    403 => ['🔒', 'Akses Ditolak',             'Anda tidak memiliki izin untuk membuka halaman ini.'],
    404 => ['🔍', 'Halaman Tidak Ditemukan',   'Halaman yang Anda cari tidak tersedia atau sudah dipindahkan.'],
    405 => ['⛔', 'Metode Tidak Diizinkan',    'Metode request yang digunakan tidak didukung halaman ini.'],
    419 => ['⏱️', 'Sesi Kedaluwarsa',          'Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.'],
    500 => ['⚙️', 'Kesalahan Server',          'Terjadi kesalahan tak terduga. Silakan coba beberapa saat lagi.'],
    502 => ['🔌', 'Gateway Error',             'Server tidak dapat memproses permintaan saat ini.'],
    503 => ['🛠️', 'Layanan Tidak Tersedia',   'Server sedang dalam pemeliharaan. Coba lagi nanti.'],
    504 => ['⌛', 'Gateway Timeout',           'Server membutuhkan waktu terlalu lama untuk merespons.'],
];

[$icon, $title, $desc] = $defaults[$code] ?? ['⚠️', 'Terjadi Kesalahan', 'Silakan kembali dan coba lagi.'];

if (!empty($_GET['msg'])) $desc = htmlspecialchars($_GET['msg']);

// FIX SEC-06: Tidak lagi menggunakan HTTP_REFERER — cukup history.back()
$home = '../index.php';
if (!empty($_SESSION['user'])) {
    $home = match($_SESSION['user']['role']) {
        'karyawan' => '../karyawan/dashboard.php',
        'pic'      => '../pic/dashboard.php',
        'direktur' => '../direktur/dashboard.php',
        'admin'    => '../admin/dashboard.php',
        default    => '../index.php',
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Error <?= $code ?> — Log Karyawan</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{
  font-family:system-ui,sans-serif;min-height:100vh;
  background:linear-gradient(160deg,#d97a0f 0%,#f29221 45%,#f5ae66 100%);
  display:flex;align-items:center;justify-content:center;padding:1.5rem;
  position:relative;overflow:hidden;
}
body::before{content:'';position:fixed;width:500px;height:500px;border-radius:50%;
  background:radial-gradient(circle,rgba(255,255,255,.18) 0%,transparent 65%);
  top:-200px;right:-180px;pointer-events:none}
body::after{content:'';position:fixed;width:350px;height:350px;border-radius:50%;
  background:radial-gradient(circle,rgba(255,255,255,.13) 0%,transparent 65%);
  bottom:-120px;left:-100px;pointer-events:none}
.blob{position:fixed;width:200px;height:200px;border-radius:50%;
  background:radial-gradient(circle,rgba(255,255,255,.12) 0%,transparent 65%);
  top:50%;left:-60px;pointer-events:none}
.card{
  background:#fff;border-radius:1.25rem;border:1px solid rgba(240,223,200,.6);
  padding:2.5rem 2rem;width:100%;max-width:460px;
  box-shadow:0 32px 80px rgba(28,19,9,.18);
  text-align:center;position:relative;z-index:1;
}
.brand{font-size:.8rem;font-weight:700;color:#d97a0f;letter-spacing:.08em;
  text-transform:uppercase;margin-bottom:1.75rem;
  display:flex;align-items:center;justify-content:center;gap:.4rem}
.err-icon{font-size:3.2rem;display:block;margin-bottom:.75rem;line-height:1}
.err-code{font-size:.72rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase;
  color:#f29221;margin-bottom:.4rem}
h1{font-size:1.35rem;font-weight:800;color:#1c1309;margin-bottom:.6rem}
p{font-size:.9rem;color:#7a6248;line-height:1.7;margin-bottom:1.75rem}
.actions{display:flex;gap:.65rem;justify-content:center;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;gap:.4rem;padding:.55rem 1.25rem;
  border-radius:.55rem;border:none;cursor:pointer;font-size:.875rem;font-weight:700;
  text-decoration:none;transition:opacity .15s}
.btn-primary{background:linear-gradient(135deg,#d97a0f 0%,#f29221 100%);color:#fff}
.btn-primary:hover{opacity:.85;color:#fff}
.btn-ghost{background:#f5ece0;color:#1c1309}
.btn-ghost:hover{background:#ecd9c4;color:#1c1309}
.tip{margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid #f0dfc8;
  font-size:.78rem;color:#a0917e;line-height:1.8}
</style>
</head>
<body>
<div class="blob"></div>
<div class="card">
  <div class="brand">📋 Log Karyawan</div>
  <span class="err-icon"><?= $icon ?></span>
  <div class="err-code">Error <?= $code ?></div>
  <h1><?= htmlspecialchars($title) ?></h1>
  <p><?= $desc ?></p>
  <div class="actions">
    <a href="javascript:history.back()" class="btn btn-primary">← Kembali</a>
    <a href="<?= htmlspecialchars($home) ?>" class="btn btn-ghost">🏠 Dashboard</a>
  </div>
  <?php if ($code === 419): ?>
  <div class="tip">Token sesi habis karena halaman dibuka terlalu lama atau tab browser ditutup lalu dibuka kembali. Klik <strong>Kembali</strong> untuk me-refresh token secara otomatis.</div>
  <?php elseif ($code === 404): ?>
  <div class="tip">Pastikan URL yang Anda ketik sudah benar. Jika masalah berlanjut, hubungi administrator sistem.</div>
  <?php endif ?>
</div>
</body>
</html>
