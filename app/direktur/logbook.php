<?php
define('BASE','..');
require '../shared/auth.php'; 
require '../shared/config.php';
require '../shared/layout.php';
require '../shared/pagination.php';
$user = require_role('direktur');
$page = paginate_page();

$today = date('Y-m-d');
$ftgl  = preg_match('/^\d{4}-\d{2}-\d{2}$/',$_GET['tanggal']??'') ? $_GET['tanggal'] : $today;
if ($ftgl > $today) $ftgl = $today; // BUG-08
$fuid  = (int)($_GET['user_id']??0);
$fsts  = in_array($_GET['status']??'',['pending','approved','declined']) ? $_GET['status'] : '';
$fpic  = (int)($_GET['pic_id']??0);

$where = 'l.tanggal=?'; 
$p = [$ftgl];
if($fuid){ $where.=' AND l.user_id=?'; $p[]=$fuid; }
if($fsts){ $where.=' AND l.status=?'; $p[]=$fsts; }
if($fpic){ $where.=' AND l.pic_id=?'; $p[]=$fpic; }

$countStmt = $db->prepare("SELECT COUNT(DISTINCT l.user_id) FROM logbooks l WHERE $where");
$countStmt->execute($p);
$pg = paginate_meta((int)$countStmt->fetchColumn(), $page);
$page = $pg['page'];

// Ambil daftar user_id untuk accordion di halaman ini
$s = $db->prepare("
    SELECT u.id, u.name, u.email 
    FROM logbooks l 
    JOIN users u ON u.id = l.user_id 
    WHERE $where 
    GROUP BY u.id
    ORDER BY u.name ASC
    LIMIT {$pg['perPage']} OFFSET {$pg['offset']}
");
$s->execute($p);
$karyawans = $s->fetchAll();

$userIds = array_column($karyawans, 'id');
$logbooksByUser = [];
if ($userIds) {
    $in = implode(',', array_fill(0, count($userIds), '?'));
    $params = array_merge($p, $userIds); // BUG-03 fix: build once, use once

    // fetch logbook di hari itu untuk semua user dalam halaman
    $stmt = $db->prepare("
        SELECT l.*, u.name as user_name, pv.name as pic_name 
        FROM logbooks l
        JOIN users u ON u.id=l.user_id 
        JOIN users pv ON pv.id=l.pic_id 
        WHERE $where AND l.user_id IN ($in)
        ORDER BY l.tanggal DESC, l.id DESC
    ");
    $stmt->execute($params); // was: array_merge($p, $userIds) — caused double-bind
    while($r = $stmt->fetch()){
        $logbooksByUser[$r['user_id']][] = $r;
    }
}

// data filter (opsi select)
$emps = $db->query("SELECT id,name FROM users WHERE role='karyawan' ORDER BY name")->fetchAll();
$pics = $db->query("SELECT id,name FROM users WHERE role='pic' ORDER BY name")->fetchAll();

$baseQs = array_filter([
    'tanggal' => $ftgl,
    'user_id' => $fuid ?: null,
    'pic_id' => $fpic ?: null,
    'status' => $fsts ?: null
]);
$qs = fn(array $extra = []) => page_qs($baseQs, array_merge($page > 1 ? ['page' => $page] : [], $extra));

html_head('Detail Logbook'); nav($user,'Seluruh Logbook Karyawan');
?>
<div class="card">
  <h2>Seluruh Logbook</h2>
  <form method="get" class="filter-bar">
    <label style="font-size:.8rem;color:var(--muted);font-weight:600;margin:0">Tanggal:</label>
    <input type="date" name="tanggal" value="<?=htmlspecialchars($ftgl)?>" max="<?=$today?>">
    <select name="user_id">
      <option value="">Semua Karyawan</option>
      <?php foreach($emps as $e): ?>
      <option value="<?=$e['id']?>" <?=$fuid==$e['id']?'selected':''?>><?=htmlspecialchars($e['name'])?></option>
      <?php endforeach ?>
    </select>
    <select name="pic_id">
      <option value="">Semua PIC</option>
      <?php foreach($pics as $e): ?>
      <option value="<?=$e['id']?>" <?=$fpic==$e['id']?'selected':''?>><?=htmlspecialchars($e['name'])?></option>
      <?php endforeach ?>
    </select>
    <select name="status">
      <option value="">Semua Status</option>
      <?php foreach(['pending'=>'Menunggu','approved'=>'Disetujui','declined'=>'Ditolak'] as $v=>$l): ?>
      <option value="<?=$v?>" <?=$fsts===$v?'selected':''?>><?=$l?></option>
      <?php endforeach ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit">Filter</button>
    <?php if($ftgl!==$today): ?>
    <a href="logbook.php" class="btn btn-ghost btn-sm">Hari Ini</a>
    <?php endif ?>
  </form>
  <p style="font-size:.8rem;color:var(--muted);margin-bottom:.75rem">
    Menampilkan: <strong><?=date('d/m/Y',strtotime($ftgl))?></strong>
    <?=$ftgl===$today?' (Hari Ini)':''?>
  </p>
  <?php if(empty($karyawans)): ?>
    <p style="color:var(--muted);font-size:.9rem">Tidak ada data untuk tanggal ini.</p>
  <?php else: ?>
  <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'karyawan'); ?>
  <div class="accordion-list">
    <?php foreach ($karyawans as $k): 
      $logbooks = $logbooksByUser[$k['id']] ?? [];
      ?>
      <div class="accordion-card">
        <button class="accordion-toggle" type="button" data-acc-target="#acc-<?=$k['id']?>">
          <strong><?=htmlspecialchars($k['name'])?></strong>
          <span style="color:var(--muted);font-weight:normal;font-size:.95em;margin-left:8px">
            (<?=htmlspecialchars($k['email'])?>)
          </span>
          <span class="acc-arrow" style="float:right">▼</span>
        </button>
        <div class="accordion-panel" id="acc-<?=$k['id']?>" style="display:none;padding-left:8px;padding-top:5px">
          <?php if (!$logbooks): ?>
            <p style="color:var(--muted);font-size:.9rem">Belum ada logbook.</p>
          <?php else: ?>
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>PIC</th>
                  <th>Jam</th>
                  <th>Aktivitas</th>
                  <th>Status</th>
                  <th>Catatan PIC</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($logbooks as $log): ?>
                <tr>
                  <td><?=htmlspecialchars($log['pic_name'])?></td>
                  <td><?=htmlspecialchars($log['jam'])?></td>
                  <td><?=htmlspecialchars(mb_strimwidth($log['aktivitas'],0,60,'…'))?></td>
                  <td><?=badge($log['status'])?></td>
                  <td style="font-size:.9em;color:var(--muted)"><?=htmlspecialchars($log['catatan_pic']??'—')?></td>
                </tr>
                <?php endforeach ?>
              </tbody>
            </table>
          </div>
          <?php endif ?>
        </div>
      </div>
    <?php endforeach ?>
  </div>
  <style>
    .accordion-list .accordion-card { border-bottom: 1px solid var(--border); }
    .accordion-toggle {
      background: none; border: none; width: 100%; text-align: left; padding: 1rem .5rem .8rem .1rem;
      cursor: pointer; outline: none; font-size: 1rem; font-family: inherit;
      transition: background .13s;
    }
    .accordion-toggle:hover { background: #faf6f1; }
    .acc-arrow { transition: transform .2s; }
    .accordion-card.open .acc-arrow { transform: rotate(180deg); }
    .accordion-panel { animation: fadeAccordion .13s; }
    @keyframes fadeAccordion { from { opacity: 0; } to { opacity: 1; } }
  </style>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      document.querySelectorAll('.accordion-toggle').forEach(function(btn) {
        btn.addEventListener('click', function() {
          var card = btn.closest('.accordion-card');
          var panel = card.querySelector('.accordion-panel');
          var isOpen = card.classList.contains('open');
          // close all
          document.querySelectorAll('.accordion-card.open').forEach(function(c) {
            c.classList.remove('open');
            c.querySelector('.accordion-panel').style.display = 'none';
          });
          if (!isOpen) {
            card.classList.add('open');
            panel.style.display = '';
          }
        });
      });
    });
  </script>
  <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi logbook'); ?>
  <?php endif ?>
</div>
<?php html_foot() ?>
