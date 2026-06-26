<?php
function html_head(string $title): void { ?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?> — Log Karyawan</title>
<style>
/* color tokens — #f29221 primary,rgb(179, 75, 0) secondary */
:root{
  --or1:#d97a0f; --or2:#f29221; --or3:rgb(179, 75, 0); --or4:#f8c98e; --or5:#fff4e6;
  --primary:#f29221; --primary-dark:#d97a0f;
  --danger:#b94040; --success:#3a7d52; --warn:#b87a1a;
  --bg:#fdf8f2; --card:#fff; --text:#1c1309; --muted:#7a6248; --border:#f0dfc8;
  --sidebar-w:240px;
}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex}

/* ── SIDEBAR ── */
.sidebar{
  width:var(--sidebar-w);min-height:100vh;flex-shrink:0;
  background:linear-gradient(180deg,var(--or1) 0%,var(--or2) 55%,var(--or3) 100%);
  position:relative;overflow:hidden;
  display:flex;flex-direction:column;
}

/* SIDEBAR BUBBLES — FULL COLOR, NO GRADIENT */
.sidebar::before{content:'';position:absolute;border-radius:50%;pointer-events:none;
  width:300px;height:300px;
  background:#fff4e6;
  opacity:.22;
  top:-90px;right:-110px;}
.sidebar::after{content:'';position:absolute;border-radius:50%;pointer-events:none;
  width:220px;height:220px;
  background:#f8c98e;
  opacity:.30;
  bottom:50px;left:-70px;}
.sb-blob{
  position:absolute;border-radius:50%;pointer-events:none;
  width:170px;height:170px;
  background:#fff;
  opacity:.34;
  top:44%;right:-55px;
}
.sb-blob2{
  position:absolute;border-radius:50%;pointer-events:none;
  width:100px;height:100px;
  background:#f8c98e;
  opacity:.22;
  top:62%;left:10px;
}
.sb-blob3{
  position:absolute;border-radius:50%;pointer-events:none;
  width:130px;height:130px;
  background:#fff4e6;
  opacity:.41;
  top:18%;left:-30px;
}

.sb-brand{padding:1.75rem 1.25rem 1rem;position:relative;z-index:1}
.sb-brand .logo{font-size:1.1rem;font-weight:800;color:#fff;display:flex;align-items:center;gap:.5rem}
.sb-brand .tagline{font-size:.68rem;color:rgba(255,255,255,.6);margin-top:.2rem}
.sb-section{font-size:.62rem;font-weight:700;letter-spacing:.11em;text-transform:uppercase;
  color:rgba(255,255,255,.45);padding:.9rem 1.25rem .3rem;position:relative;z-index:1}
.sidebar nav{flex:1;padding:.2rem 0;position:relative;z-index:1}
.sidebar nav a{
  display:flex;align-items:center;gap:.6rem;
  padding:.6rem 1.25rem;font-size:.875rem;font-weight:500;
  color:rgba(255,255,255,.82);text-decoration:none;
  margin:.1rem .5rem;border-radius:.5rem;
  transition:background .15s,color .15s;
}
.sidebar nav a:hover{background:rgba(255,255,255,.18);color:#fff;text-decoration:none}
.sidebar nav a.active{background:rgba(255,255,255,.92);color:var(--or1);font-weight:700}
.sidebar nav a .ico{width:1.1rem;text-align:center;flex-shrink:0;font-style:normal;font-size:.9rem}
.sb-footer{padding:1rem 1.25rem;position:relative;z-index:1;border-top:1px solid rgba(255,255,255,.15)}
.sb-user{display:flex;align-items:center;gap:.5rem;margin-bottom:.6rem}
.sb-avatar{width:30px;height:30px;border-radius:50%;background:rgba(255,255,255,.25);color:#fff;
  display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:800;flex-shrink:0;
  border:2px solid rgba(255,255,255,.35);}
.sb-uname{font-weight:600;color:#fff;font-size:.8rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.sb-urole{font-size:.67rem;color:rgba(255,255,255,.52);text-transform:capitalize}
.sb-logout{display:block;text-align:center;padding:.4rem;
  background:rgba(255,255,255,.12);color:rgba(255,255,255,.8);
  border-radius:.4rem;font-size:.8rem;text-decoration:none;transition:background .15s;
  border:none;width:100%;cursor:pointer;font-family:inherit}
.sb-logout:hover{background:rgba(255,255,255,.22);color:#fff;text-decoration:none}

/* ── MAIN ── */
.main{flex:1;min-width:0;display:flex;flex-direction:column}
.topbar{background:#fff;border-bottom:1px solid var(--border);
  padding:.75rem 1.5rem;display:flex;align-items:center;gap:1rem}
.topbar-title{font-size:1rem;font-weight:700;color:var(--text);flex:1}
.topbar-date{font-size:.8rem;color:var(--muted)}
.wrap{padding:1.5rem;flex:1}

/* ── COMPONENTS ── */
a{color:var(--primary);text-decoration:none}a:hover{text-decoration:underline}
.card{background:var(--card);border-radius:.75rem;border:1px solid var(--border);padding:1.5rem;margin-bottom:1rem}
.card h2{margin-bottom:1rem;font-size:1rem;font-weight:700;color:var(--text)}
.grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:1rem;margin-bottom:1rem}
.stat{background:var(--card);border-radius:.75rem;border:1px solid var(--border);padding:1.25rem;text-align:center}
.stat .val{font-size:1.9rem;font-weight:800;color:var(--primary)}
.stat .lbl{font-size:.75rem;color:var(--muted);margin-top:.25rem}
table{width:100%;border-collapse:collapse;font-size:.875rem}
th,td{padding:.6rem .75rem;border-bottom:1px solid var(--border);text-align:left}
th{background:#fdf5eb;font-weight:700;font-size:.75rem;text-transform:uppercase;letter-spacing:.05em;color:var(--muted)}
tr:last-child td{border-bottom:none}
.badge{display:inline-block;padding:.15rem .55rem;border-radius:9999px;font-size:.72rem;font-weight:700}
.badge-pending{background:#fef3c7;color:#92400e}
.badge-approved{background:#d1fae5;color:#065f46}
.badge-declined{background:#fee2e2;color:#991b1b}
.alert{padding:.75rem 1rem;border-radius:.5rem;margin-bottom:1rem;font-size:.875rem}
.alert-warn{background:#fffbeb;border:1px solid #fde68a;color:#92400e}
.alert-success{background:#ecfdf5;border:1px solid #a7f3d0;color:#065f46}
.alert-error{background:#fef2f2;border:1px solid #fecaca;color:#991b1b}
.btn{display:inline-block;padding:.5rem 1rem;border-radius:.5rem;border:none;cursor:pointer;
  font-size:.875rem;font-weight:600;transition:opacity .15s,background .15s;line-height:1.4}
.btn-primary{background:linear-gradient(135deg,var(--or1) 0%,var(--primary) 100%);color:#fff}
.btn-primary:hover{background:linear-gradient(135deg,#c16e0a 0%,var(--or1) 100%);text-decoration:none;color:#fff}
.btn-success{background:var(--success);color:#fff}.btn-success:hover{opacity:.85;text-decoration:none;color:#fff}
.btn-danger{background:var(--danger);color:#fff}.btn-danger:hover{opacity:.85;text-decoration:none;color:#fff}
.btn-ghost{background:#f5ece0;color:var(--text)}.btn-ghost:hover{background:#ecd9c4;text-decoration:none;color:var(--text)}
.btn-sm{padding:.3rem .65rem;font-size:.8rem}
form .field{margin-bottom:1rem}
form label{display:block;font-size:.78rem;font-weight:700;margin-bottom:.35rem;
  color:var(--muted);text-transform:uppercase;letter-spacing:.05em}
form input,form select,form textarea{
  width:100%;padding:.5rem .75rem;border:1px solid var(--border);
  border-radius:.5rem;font-size:.9rem;font-family:inherit;background:#fff;color:var(--text)}
form textarea{resize:vertical;min-height:80px}
form input:focus,form select:focus,form textarea:focus{outline:2px solid var(--primary);border-color:transparent}
form input[readonly]{background:#fdf8f2;color:var(--muted)}
.filter-bar{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:center}
.filter-bar input,.filter-bar select{width:auto;padding:.4rem .6rem;border:1px solid var(--border);
  border-radius:.5rem;font-size:.85rem;font-family:inherit}
.grid-2{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
.profile-grid{max-width:780px}
.table-wrap{width:100%;overflow-x:auto;-webkit-overflow-scrolling:touch}
.table-wrap table{min-width:520px}
.btn-group{display:flex;flex-wrap:wrap;gap:.35rem;align-items:center}
.pagination{display:flex;flex-wrap:wrap;gap:.35rem;align-items:center;justify-content:center;margin-top:1rem}
.pagination a,.pagination span{padding:.35rem .7rem;border-radius:.4rem;font-size:.82rem;font-weight:600;text-decoration:none;border:1px solid var(--border);line-height:1.3}
.pagination a{color:var(--text);background:#fff}
.pagination a:hover{background:var(--or5);text-decoration:none;color:var(--text)}
.pagination .active{background:var(--primary);color:#fff;border-color:var(--primary)}
.pagination .disabled{opacity:.45;pointer-events:none;color:var(--muted);background:#fdf8f2}
.pagination-info{font-size:.8rem;color:var(--muted);margin:.75rem 0;text-align:center}
.search-bar{display:flex;gap:.5rem;flex-wrap:wrap;margin-bottom:1rem;align-items:center}
.search-bar input{flex:1;min-width:180px;padding:.45rem .65rem;border:1px solid var(--border);border-radius:.5rem;font-size:.85rem;font-family:inherit}

/* ── HAMBURGER & OVERLAY ── */
.menu-toggle{
  display:none;flex-direction:column;justify-content:center;gap:5px;
  width:2.25rem;height:2.25rem;padding:.4rem;
  background:var(--or5);border:1px solid var(--border);border-radius:.5rem;
  cursor:pointer;flex-shrink:0;transition:background .15s;
}
.menu-toggle span{display:block;height:2px;width:100%;background:var(--or1);border-radius:2px;transition:transform .2s,opacity .2s}
.menu-toggle:hover{background:#f5e6d0}
.sidebar-overlay{
  display:none;position:fixed;inset:0;background:rgba(28,19,9,.45);
  z-index:199;opacity:0;transition:opacity .25s;
}
body.sidebar-open .sidebar-overlay{display:block;opacity:1}
body.sidebar-open{overflow:hidden}

/* ── RESPONSIVE ── */
@media(max-width:991px){
  .menu-toggle{display:flex}
  .sidebar{
    position:fixed;top:0;left:0;right:auto;bottom:0;
    width:min(var(--sidebar-w),85vw);max-width:300px;
    z-index:200;transform:translateX(-100%);
    transition:transform .28s ease;box-shadow:4px 0 24px rgba(0,0,0,.18);
  }
  body.sidebar-open .sidebar{transform:translateX(0)}
  body.sidebar-open .menu-toggle span:nth-child(1){transform:translateY(7px) rotate(45deg)}
  body.sidebar-open .menu-toggle span:nth-child(2){opacity:0}
  body.sidebar-open .menu-toggle span:nth-child(3){transform:translateY(-7px) rotate(-45deg)}
  .main{width:100%}
}
@media(max-width:767px){
  .topbar{padding:.65rem 1rem;gap:.65rem}
  .topbar-title{font-size:.92rem;line-height:1.3}
  .topbar-date{font-size:.72rem;text-align:right;line-height:1.3}
  .wrap{padding:1rem}
  .card{padding:1rem;margin-bottom:.85rem}
  .card h2{font-size:.95rem}
  .grid{grid-template-columns:repeat(2,1fr);gap:.7rem}
  .grid-2{grid-template-columns:1fr}
  .stat{padding:1rem}
  .stat .val{font-size:1.5rem}
  .stat .lbl{font-size:.7rem}
  .filter-bar{flex-direction:column;align-items:stretch}
  .filter-bar input,.filter-bar select{width:100%}
  .filter-bar .btn{width:100%;text-align:center}
  .search-bar{flex-direction:column;align-items:stretch}
  .search-bar input{width:100%;min-width:0}
  .search-bar .btn{width:100%;text-align:center}
  .table-wrap table{min-width:480px;font-size:.82rem}
  th,td{padding:.5rem .45rem}
  .btn-sm{min-height:2rem}
  .card .btn:not(.btn-sm){width:100%;text-align:center}
  .btn-group .btn{width:auto;flex:1 1 auto}
}
@media(max-width:480px){
  .grid{grid-template-columns:1fr}
  .topbar-date{display:none}
  .table-wrap table{min-width:420px;font-size:.78rem}
}
</style>
</head>
<body>
<?php }

function nav(array $user, string $page_title): void {
    $role  = $user['role'];
    $base  = defined('BASE') ? BASE : '..';
    $cur   = basename($_SERVER['PHP_SELF']);
    $a     = fn(string $f) => basename($f)===$cur ? 'active' : '';
    $init  = strtoupper(mb_substr($user['name'],0,1));

    $links = match($role) {
        'karyawan' => [
            ["$base/karyawan/dashboard.php",    '◉', 'Dashboard'],
            ["$base/karyawan/logbook.php",      '✎', 'Input Logbook'],
            ["$base/karyawan/laporan.php",      '≡', 'Laporan'],
            ["$base/karyawan/profile.php",      '◎', 'Profil'],
        ],
        'admin' => [
            ["$base/admin/dashboard.php", '◉', 'Dashboard Admin'],
            ["$base/admin/logbook.php",   '≡', 'Logbook Karyawan'],
            ["$base/admin/karyawan.php",  '👥', 'Data Karyawan'],
        ],
        'pic' => [
            ["$base/pic/dashboard.php",   '◉', 'Dashboard'],
            ["$base/pic/logbook.php",     '≡', 'Detail Logbook'],
            ["$base/pic/profile.php",     '◎', 'Profil'],
        ],
        default => [
            ["$base/direktur/dashboard.php", '◉', 'Dashboard Eksekutif'],
            ["$base/direktur/logbook.php",   '≡', 'Detail Logbook'],
            ["$base/direktur/profile.php",   '◎', 'Profil'],
        ],
    };

    $role_label = match($role) {
        'karyawan' => 'Karyawan',
        'pic' => 'PIC / Supervisor',
        'admin' => 'Admin',
        default => 'Direktur'
    };
    ?>
<div class="sidebar-overlay" id="sidebarOverlay" aria-hidden="true"></div>
<aside class="sidebar" id="sidebar">
  <div class="sb-blob"></div><div class="sb-blob2"></div><div class="sb-blob3"></div>
  <div class="sb-brand">
    <div class="logo">📋 Log Karyawan</div>
    <div class="tagline">Manajemen Aktivitas Harian</div>
  </div>
  <div class="sb-section">Menu Utama</div>
  <nav>
    <?php foreach ($links as [$href, $ico, $lbl]): ?>
    <a href="<?= $href ?>" class="<?= $a($href) ?>">
      <i class="ico"><?= $ico ?></i><?= htmlspecialchars($lbl) ?>
    </a>
    <?php endforeach ?>
  </nav>
  <div class="sb-footer">
    <div class="sb-user">
      <div class="sb-avatar"><?= htmlspecialchars($init) ?></div>
      <div>
        <div class="sb-uname"><?= htmlspecialchars($user['name']) ?></div>
        <div class="sb-urole"><?= $role_label ?></div>
      </div>
    </div>
    <form method="post" action="<?= $base ?>/logout.php" style="margin:0">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <button type="submit" class="sb-logout">⎋ Keluar</button>
    </form>
  </div>
</aside>
<div class="main">
  <div class="topbar">
    <button type="button" class="menu-toggle" id="menuToggle" aria-label="Buka menu" aria-expanded="false" aria-controls="sidebar">
      <span></span><span></span><span></span>
    </button>
    <span class="topbar-title"><?= htmlspecialchars($page_title) ?></span>
    <span class="topbar-date"><?= _tgl(time()) ?></span>
  </div>
  <div class="wrap">
<?php }

function _tgl(int $ts): string {
    static $b=['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    static $h=['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    return $h[date('w',$ts)].', '.date('j',$ts).' '.$b[(int)date('n',$ts)].' '.date('Y',$ts);
}

function html_foot(): void { ?>
</div></div>
<script>
(function(){
  var btn=document.getElementById('menuToggle');
  var overlay=document.getElementById('sidebarOverlay');
  var sidebar=document.getElementById('sidebar');
  if(!btn||!overlay||!sidebar)return;
  function open(){document.body.classList.add('sidebar-open');btn.setAttribute('aria-expanded','true');btn.setAttribute('aria-label','Tutup menu')}
  function close(){document.body.classList.remove('sidebar-open');btn.setAttribute('aria-expanded','false');btn.setAttribute('aria-label','Buka menu')}
  function toggle(){document.body.classList.contains('sidebar-open')?close():open()}
  btn.addEventListener('click',toggle);
  overlay.addEventListener('click',close);
  sidebar.querySelectorAll('nav a,.sb-logout').forEach(function(a){a.addEventListener('click',close)});
  document.addEventListener('keydown',function(e){if(e.key==='Escape')close()});
  window.addEventListener('resize',function(){if(window.innerWidth>991)close()});
})();
</script>
</body></html>
<?php }

function flash(): void {
    if (empty($_SESSION['flash'])) return;
    ['type'=>$t,'msg'=>$m] = $_SESSION['flash']; unset($_SESSION['flash']);
    echo '<div class="alert alert-'.htmlspecialchars($t).'">'.htmlspecialchars($m).'</div>';
}

function badge(string $s): string {
    $l = match($s){ 'approved'=>'Disetujui','declined'=>'Ditolak',default=>'Menunggu' };
    return "<span class=\"badge badge-$s\">$l</span>";
}
