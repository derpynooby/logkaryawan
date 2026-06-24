<?php
define('BASE', '..');
require '../shared/auth.php';
require '../shared/config.php';
require '../shared/layout.php';
require '../shared/pagination.php';
require '../shared/modal.php';

$user = require_role('admin');
$page = paginate_page();

$q_user = trim($_GET['q_user'] ?? '');
$q_pic  = trim($_GET['q_pic'] ?? '');
$q_date = trim($_GET['q_date'] ?? '');
$q_status = isset($_GET['status']) && in_array($_GET['status'], ['pending', 'approved', 'declined']) ? $_GET['status'] : '';

$editId = (int)($_GET['edit'] ?? 0);
$editData = null;
if ($editId > 0) {
    $s = $db->prepare("SELECT * FROM logbooks WHERE id=?");
    $s->execute([$editId]);
    $editData = $s->fetch();
}

// Build filter
$where = "1";
$params = [];
if ($q_user !== '') {
    $where .= " AND lb.user_id IN (SELECT id FROM users WHERE name LIKE ?)";
    $params[] = "%$q_user%";
}
if ($q_pic !== '') {
    $where .= " AND lb.pic_id IN (SELECT id FROM users WHERE name LIKE ?)";
    $params[] = "%$q_pic%";
}
if ($q_date !== '') {
    $where .= " AND lb.tanggal = ?";
    $params[] = $q_date;
}
if ($q_status !== '') {
    $where .= " AND lb.status = ?";
    $params[] = $q_status;
}

// Counting total rows (pagination)
$countStmt = $db->prepare("SELECT COUNT(*) FROM logbooks lb WHERE $where");
$countStmt->execute($params);
$pg = paginate_meta((int)$countStmt->fetchColumn(), $page);
$page = $pg['page'];
$offset = $pg['offset'];

// Query main list
$listSql = "SELECT lb.*, 
           u1.name AS user_name,
           u2.name AS pic_name
    FROM logbooks lb
    JOIN users u1 ON lb.user_id = u1.id
    JOIN users u2 ON lb.pic_id = u2.id
    WHERE $where
    ORDER BY lb.id DESC
    LIMIT {$pg['perPage']} OFFSET $offset";
$listStmt = $db->prepare($listSql);
$listStmt->execute($params);
$rows = $listStmt->fetchAll();

$baseQs = array_filter([
    'q_user' => $q_user ?: null,
    'q_pic' => $q_pic ?: null,
    'q_date' => $q_date ?: null,
    'status' => $q_status ?: null,
    'edit' => $editId > 0 ? $editId : null
]);
$qs = fn(array $extra = []) => page_qs($baseQs, array_merge($page > 1 ? ['page' => $page] : [], $extra));

// Ambil daftar user/pic untuk dropdown
$users = $db->query("SELECT id, name FROM users WHERE role='karyawan' ORDER BY name")->fetchAll();
$pics  = $db->query("SELECT id, name FROM users WHERE role='pic' ORDER BY name")->fetchAll();

html_head('Data Logbook');
modal_styles();
nav($user, 'Data Logbook');
flash();
?>

<div class="card">
    <h2><?= $editData ? 'Edit Logbook' : 'Tambah Logbook' ?></h2>
    <form method="post" action="action.php">
        <input type="hidden" name="action" value="<?= $editData ? 'update_logbook' : 'create_logbook' ?>">
        <?= csrf_field() ?>
        <?php if ($editData): ?>
            <input type="hidden" name="id" value="<?= (int)$editData['id'] ?>">
        <?php endif ?>
        <div class="grid grid-2">
            <div class="card">
                <div class="field">
                    <label>Karyawan</label>
                    <select name="user_id" required>
                        <option value="">--Pilih Karyawan--</option>
                        <?php foreach ($users as $u): ?>
                            <option value="<?= $u['id'] ?>"
                                <?= (isset($editData['user_id']) && $editData['user_id'] == $u['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($u['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="field">
                    <label>PIC</label>
                    <select name="pic_id" required>
                        <option value="">--Pilih PIC--</option>
                        <?php foreach ($pics as $p): ?>
                            <option value="<?= $p['id'] ?>"
                                <?= (isset($editData['pic_id']) && $editData['pic_id'] == $p['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($p['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="field">
                    <label>Tanggal</label>
                    <input type="date" name="tanggal" required value="<?= htmlspecialchars($editData['tanggal'] ?? '') ?>">
                </div>

                <div class="field">
                    <label>Jam</label>
                    <input type="number" name="jam" step="0.01" min="0" max="99.99" required value="<?= htmlspecialchars($editData['jam'] ?? '') ?>">
                </div>
            </div>
            <div class="card">
                <div class="field">
                    <label>Aktivitas</label>
                    <textarea name="aktivitas" required><?= htmlspecialchars($editData['aktivitas'] ?? '') ?></textarea>
                </div>

                <div class="field">
                    <label>Status</label>
                    <select name="status" required>
                        <?php
                        $statusList = ['pending' => 'Pending', 'approved' => 'Approved', 'declined' => 'Declined'];
                        $val = $editData['status'] ?? 'pending';
                        foreach ($statusList as $v => $lbl): ?>
                            <option value="<?= $v ?>" <?= $val === $v ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="field">
                    <label>Catatan PIC (optional, diisi jika declined)</label>
                    <textarea name="catatan_pic"><?= htmlspecialchars($editData['catatan_pic'] ?? '') ?></textarea>
                </div>
            </div>
        </div>
        <div class="btn-group">
            <button class="btn btn-primary" type="submit"><?= $editData ? 'Simpan Perubahan' : 'Tambah Logbook' ?></button>
            <?php if ($editData): ?>
                <a class="btn btn-ghost" href="logbook.php<?= $qs(['edit' => null, 'page' => $page > 1 ? $page : null]) ?>">Batal</a>
            <?php endif ?>
        </div>
    </form>
</div>

<div class="card">
    <h2>Pencarian & Filter</h2>
    <form method="get" class="search-bar">
        <?php if ($editId > 0): ?>
            <input type="hidden" name="edit" value="<?= $editId ?>">
        <?php endif ?>
        <input type="text" name="q_user" placeholder="Cari nama karyawan..." value="<?= htmlspecialchars($q_user) ?>">
        <input type="text" name="q_pic" placeholder="Cari nama PIC..." value="<?= htmlspecialchars($q_pic) ?>">
        <input type="date" name="q_date" value="<?= htmlspecialchars($q_date) ?>">
        <select name="status">
            <option value="">Semua Status</option>
            <option value="pending" <?= $q_status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="approved" <?= $q_status === 'approved' ? 'selected' : '' ?>>Approved</option>
            <option value="declined" <?= $q_status === 'declined' ? 'selected' : '' ?>>Declined</option>
        </select>
        <button class="btn btn-primary btn-sm" type="submit">Cari</button>
        <?php if ($q_user !== '' || $q_pic !== '' || $q_date !== '' || $q_status !== ''): ?>
            <a class="btn btn-ghost btn-sm" href="logbook.php<?= $editId > 0 ? ('?edit=' . $editId) : '' ?>">Reset</a>
        <?php endif ?>
    </form>
</div>
<div class="card">
    <h2>Daftar Logbook</h2>
    <?php if ($pg['total']): ?>
        <?php render_pagination_info($pg['from'], $pg['to'], $pg['total'], 'logbook'); ?>
        <?php if ($q_user || $q_pic || $q_date || $q_status): ?>
            <p class="pagination-info" style="margin-top:-.5rem">
                Filter:
                <?php if ($q_user): ?>Karyawan: <strong><?= htmlspecialchars($q_user) ?></strong> <?php endif ?>
            <?php if ($q_pic): ?>PIC: <strong><?= htmlspecialchars($q_pic) ?></strong> <?php endif ?>
        <?php if ($q_date): ?>Tanggal: <strong><?= htmlspecialchars($q_date) ?></strong> <?php endif ?>
    <?php if ($q_status): ?>Status: <strong><?= htmlspecialchars($q_status) ?></strong> <?php endif ?>
            </p>
        <?php endif ?>
    <?php endif ?>

    <?php if (!$rows): ?>
        <p style="color:var(--muted);font-size:.9rem">
            Tidak ada logbook yang ditemukan.
        </p>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Karyawan</th>
                        <th>PIC</th>
                        <th>Tanggal</th>
                        <th>Jam</th>
                        <th>Aktivitas</th>
                        <th>Status</th>
                        <th>Catatan PIC</th>
                        <th>Dibuat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= (int)$r['id'] ?></td>
                            <td><?= htmlspecialchars($r['user_name']) ?></td>
                            <td><?= htmlspecialchars($r['pic_name']) ?></td>
                            <td><?= htmlspecialchars($r['tanggal']) ?></td>
                            <td><?= htmlspecialchars($r['jam']) ?></td>
                            <td style="max-width:200px;white-space:pre-wrap"><?= htmlspecialchars($r['aktivitas']) ?></td>
                            <td>
                                <span class="badge badge-<?= $r['status'] === 'approved' ? 'approved' : ($r['status'] === 'declined' ? 'declined' : 'pending') ?>">
                                    <?= ucfirst($r['status']) ?>
                                </span>
                            </td>
                            <td><?= $r['catatan_pic'] ? htmlspecialchars($r['catatan_pic']) : '—' ?></td>
                            <td><?= htmlspecialchars($r['created_at']) ?></td>
                            <td>
                                <div class="btn-group">
                                    <a class="btn btn-primary btn-sm" href="<?= $qs(['edit' => $r['id'], 'page' => $page > 1 ? $page : null]) ?>">Edit</a>
                                    <button type="button" class="btn btn-danger btn-sm"
                                      data-confirm-delete
                                      data-id="<?= (int)$r['id'] ?>"
                                      data-name="Logbook #<?= (int)$r['id'] ?> (<?= htmlspecialchars($r['user_name']) ?>)"
                                      data-label="logbook"
                                      data-form="formDelLogbook<?= (int)$r['id'] ?>">Hapus</button>
                                    <form id="formDelLogbook<?= (int)$r['id'] ?>" method="post" action="action.php" hidden>
                                      <input type="hidden" name="action" value="delete_logbook">
                                      <?= csrf_field() ?>
                                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php render_pagination($page, $pg['totalPages'], $qs, 'Paginasi logbook'); ?>
    <?php endif ?>
</div>
<?php
modal_html();
modal_script();
html_foot();
?>