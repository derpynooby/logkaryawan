<?php
define('BASE','..');
require '../shared/auth.php'; require '../shared/config.php'; require '../shared/layout.php';
$user = require_role('karyawan'); $uid = $user['id'];

$id=(int)($_GET['id']??0);
$s=$db->prepare('SELECT * FROM logbooks WHERE id=? AND user_id=? AND tanggal=CURDATE() AND status="pending"');
$s->execute([$id,$uid]); $e=$s->fetch();
if(!$e){ header('Location: logbook.php'); exit; }

$pics=$db->query("SELECT id,name FROM users WHERE role='pic' ORDER BY name")->fetchAll();
html_head('Edit Logbook'); nav($user,'Edit Logbook'); flash();
?>
<div class="card" style="max-width:600px">
  <h2>Edit Aktivitas</h2>
  <form method="post" action="action.php">
    <input type="hidden" name="action" value="edit"><input type="hidden" name="id" value="<?=$e['id']?>">
    <?= csrf_field() ?>
    <div class="grid grid-2">
      <div class="field"><label>Tanggal</label><input type="text" value="<?=date('d M Y')?>" readonly></div>
      <div class="field"><label>Jumlah Jam</label><input type="number" name="jam" min="0.25" max="24" step="0.25" required value="<?=htmlspecialchars($e['jam'])?>"></div>
    </div>
    <div class="field"><label>PIC / Supervisor</label>
      <select name="pic_id" required>
      <?php foreach($pics as $p): ?>
        <option value="<?=$p['id']?>" <?=$p['id']==$e['pic_id']?'selected':''?>><?=htmlspecialchars($p['name'])?></option>
      <?php endforeach ?></select></div>
    <div class="field"><label>Deskripsi Aktivitas</label>
      <textarea name="aktivitas" required><?=htmlspecialchars($e['aktivitas'])?></textarea></div>
    <div class="btn-group">
    <button class="btn btn-primary" type="submit">Perbarui</button>
    <a href="logbook.php" class="btn btn-ghost">Batal</a>
    </div>
  </form>
</div>
<?php html_foot() ?>
