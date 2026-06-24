<?php
define('BASE','..');
require '../shared/auth.php'; require '../shared/config.php'; require '../shared/layout.php';
$user = require_role('direktur'); $uid = $user['id'];

$s=$db->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$uid]); $u=$s->fetch();
html_head('Profil'); nav($user,'Profil Saya'); flash();
?>
<div class="grid-2 profile-grid">
<div class="card">
  <h2>Informasi Akun</h2>
  <form method="post" action="action.php">
    <input type="hidden" name="action" value="profile">
    <?= csrf_field() ?>
    <div class="field"><label>Nama Lengkap</label><input type="text" name="name" required value="<?=htmlspecialchars($u['name'])?>"></div>
    <div class="field"><label>Email</label><input type="email" name="email" required value="<?=htmlspecialchars($u['email'])?>"></div>
    <div class="field"><label>Role</label><input type="text" value="Direktur" readonly></div>
    <button class="btn btn-primary" type="submit">Simpan</button>
  </form>
</div>
<div class="card">
  <h2>Ganti Kata Sandi</h2>
  <form method="post" action="action.php">
    <input type="hidden" name="action" value="password">
    <?= csrf_field() ?>
    <div class="field"><label>Sandi Saat Ini</label><input type="password" name="old" required></div>
    <div class="field"><label>Sandi Baru</label><input type="password" name="new" required minlength="6"></div>
    <div class="field"><label>Konfirmasi Sandi Baru</label><input type="password" name="confirm" required minlength="6"></div>
    <button class="btn btn-primary" type="submit">Ganti Sandi</button>
  </form>
</div>
</div>
<?php html_foot() ?>
