<?php
session_start();
if (!empty($_SESSION['user'])) {
    $r    = $_SESSION['user']['role'];
    $dest = match($r){
        'karyawan' => 'karyawan',
        'pic' => 'pic',
        'admin' => 'admin',
        default => 'direktur'
    };
    header("Location: $dest/dashboard.php"); exit;
}
require __DIR__.'/shared/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $id = trim($_POST['id']??'');
    $pass  = $_POST['password']??'';
    $s = $db->prepare('SELECT * FROM users WHERE id=?');
    $s->execute([$id]); $u = $s->fetch();
    if ($u && password_verify($pass, $u['password'])) {
        session_regenerate_id(true); // SEC-05: prevent session fixation
        $_SESSION['user'] = ['id'=>$u['id'],'name'=>$u['name'],'email'=>$u['email'],'role'=>$u['role']];
        $_SESSION['csrf'] = bin2hex(random_bytes(32)); // generate once, reuse across all forms
        $dest = match($u['role']){
            'karyawan' => 'karyawan',
            'pic' => 'pic',
            'admin' => 'admin',
            default => 'direktur'
        };
        header("Location: $dest/dashboard.php"); exit;
    }
    $error = 'id atau kata sandi salah.';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Masuk — Log Karyawan</title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;min-height:100vh;display:flex;background:#fdf8f2}
.panel-l{
  width:400px;flex-shrink:0;
  background:#f29221;
  position:relative;overflow:hidden;
  display:flex;flex-direction:column;align-items:center; /* center content horizontally */
  padding:3rem 2.5rem;
}
/* bubble circles — gunakan warna penuh, tanpa gradasi, gunakan opacity random */
.panel-l::before{content:'';position:absolute;width:360px;height:360px;border-radius:50%;
  background:#fffbe9;
  opacity:0.22;
  top:-130px;right:-130px;}
.panel-l::after{content:'';position:absolute;width:260px;height:260px;border-radius:50%;
  background:#ffe6be;
  opacity:0.30;
  bottom:-90px;left:-90px;}
.blob{position:absolute;width:190px;height:190px;border-radius:50%;
  background:#f8c98e;
  opacity:0.34;
  top:52%;right:-65px;transform:translateY(-50%);}
.blob2{position:absolute;width:110px;height:110px;border-radius:50%;
  background:#fff4e6;
  opacity:0.22;
  top:30%;left:-30px;}
.blob3{position:absolute;width:80px;height:80px;border-radius:50%;
  background:#ffd699;
  opacity:0.41;
  top:70%;left:40px;}
/* LOGO BESAR sendiri di sidebar, margin-top sama dengan margin-bawah (Ke teks di bawah) */
.panel-l .logo-container {
  width: 100%;
  display: flex;
  flex-direction: column;
  align-items: center;
  margin-top: 2.5rem;
  margin-bottom: 2.5rem;
}
.brand-logo {
  display: block;
  width: 106px;
  height: 106px;
  z-index: 1;
  position: relative;
  background: #fff;
  border-radius: 24px;
  box-shadow: 0 2px 12px 0 rgba(0,0,0,0.04);
  padding: 3px;
  object-fit: contain;
  border: 1.5px solid #f0dfc8;
}
@media (max-width:900px){
  .panel-l{width:100%;min-height:auto;padding:2rem 1.5rem}
  .brand-appname{font-size:2.25rem}
  .brand-logo{width:78px;height:78px;padding:7px;}
  .logo-container{margin-top:1.4rem;margin-bottom:1.4rem;}
  .brand-sub{margin-bottom:1.25rem;font-size:.88rem;}
  .tagline{font-size:.89rem}
}
@media(max-width:480px){
  .panel-l{padding:1.5rem 1.25rem}
  .brand-appname{font-size:1.35rem}
  .brand-logo{width:48px;height:48px;padding:2px;}
  .box h1{font-size:1.25rem}
  .hint{font-size:.72rem}
}
.panel-l .ct{
  position:relative;
  z-index:1;
  display:flex;
  flex-direction:column;
  align-items:center;
}
.brand-appname {
  font-size: 2.2rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: .1rem;
  letter-spacing: 0.01em;
  line-height: 1.08;
  text-align:center;
}
.brand-sub{
  font-size:.96rem;
  color:rgba(255,255,255,.68);
  margin-bottom:2.5rem;
  text-align:center;
}
.tagline{
  color:rgba(255,255,255,.85);
  font-size:.97rem;
  line-height:1.65;
  text-align:center;
}
.panel-r{flex:1;display:flex;align-items:center;justify-content:center;padding:2rem}
.box{width:100%;max-width:360px}
h1{font-size:1.45rem;font-weight:800;color:#1c1309;margin-bottom:.3rem}
.sub{font-size:.875rem;color:#7a6248;margin-bottom:2rem}
.field{margin-bottom:1.1rem}
label{display:block;font-size:.73rem;font-weight:700;text-transform:uppercase;
  letter-spacing:.06em;color:#7a6248;margin-bottom:.4rem}
input{width:100%;padding:.6rem .85rem;border:1.5px solid #f0dfc8;
  border-radius:.5rem;font-size:.9rem;background:#fff;color:#1c1309;transition:border .15s}
input:focus{outline:none;border-color:#f29221}
.btn{width:100%;padding:.7rem;border:none;border-radius:.5rem;
  background:linear-gradient(135deg,#d97a0f 0%,#f29221 100%);
  color:#fff;font-size:.95rem;font-weight:700;
  cursor:pointer;margin-top:.25rem;transition:opacity .15s}
.btn:hover{opacity:.88}
.err{background:#fef2f2;border:1px solid #fecaca;color:#991b1b;
  padding:.65rem .85rem;border-radius:.5rem;font-size:.85rem;margin-bottom:1rem}
.hint{font-size:.77rem;color:#a0917e;margin-top:1.5rem;
  padding-top:1.25rem;border-top:1px solid #f0dfc8;line-height:1.8}
.hint code{background:#fff4e6;padding:.1rem .3rem;border-radius:.25rem;font-size:.73rem}
</style>
</head>
<body>
<div class="panel-l">
  <div class="blob"></div><div class="blob2"></div><div class="blob3"></div>
  <div class="logo-container">
    <img src="assets/logo.webp" alt="Logo Log Karyawan" class="brand-logo">
  </div>
  <div class="ct">
    <div class="brand-appname">Log Karyawan</div>
    <div class="brand-sub">Sistem Manajemen Aktivitas Harian</div>
    <div class="tagline">Pantau produktivitas, validasi logbook, dan kelola tim Anda secara terstruktur.</div>
  </div>
</div>
<div class="panel-r">
  <div class="box">
    <h1>Selamat Datang</h1>
    <p class="sub">Masuk ke akun Anda untuk melanjutkan.</p>
    <?php if ($error): ?><div class="err"><?= htmlspecialchars($error) ?></div><?php endif ?>
    <form method="post">
      <div class="field"><label>Id Anda</label>
        <input type="id" name="id" required autofocus placeholder="nama@perusahaan.com"></div>
      <div class="field"><label>Kata Sandi</label>
        <input type="password" name="password" required placeholder="••••••••"></div>
      <button class="btn" type="submit">Masuk →</button>
    </form>
    <?php if (($_ENV['APP_ENV'] ?? 'production') === 'development'): ?>
    <div class="hint">
      <strong>Akun Demo</strong> — sandi: <code>password123</code><br>
      admin@company.com &nbsp;·&nbsp; Admin<br>
      direktur@company.com &nbsp;·&nbsp; Direktur<br>
      budi@company.com &nbsp;·&nbsp; PIC<br>
      andi@company.com &nbsp;·&nbsp; Karyawan
    </div>
    <?php endif ?>
  </div>
</div>
</body></html>
