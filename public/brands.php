<?php
require_once __DIR__ . '/_auth.php';
require_auth();
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/_header.php';

$user = current_user();
$uid = $user['id'];


if (is_post()) {
  $id = intval($_POST['id'] ?? 0);
  $code = trim($_POST['code'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $status = in_array($_POST['status'] ?? 'Active', ['Active','Inactive']) ? $_POST['status'] : 'Active';

  if ($code === '' || $name === '') {
    flash('error','Code and name are required.');
  } elseif (strlen($code) > 50 || strlen($name) > 150) {
    flash('error','Character limit exceeded.');
  } else {
    if ($id > 0) {
      
      $ownerCheck = $pdo->prepare("SELECT user_id FROM master_brand WHERE id = ?");
      $ownerCheck->execute([$id]);
      $row = $ownerCheck->fetch();
      if (!$row) { flash('error','Brand not found.'); }
      elseif (!is_admin() && intval($row['user_id']) !== $uid) { flash('error','Not authorized.'); }
      else {
        $stmt = $pdo->prepare("UPDATE master_brand SET code=?, name=?, status=? WHERE id=?");
        $stmt->execute([$code, $name, $status, $id]);
        flash('success','Brand updated.');
      }
    } else {
      $stmt = $pdo->prepare("INSERT INTO master_brand (user_id, code, name, status) VALUES (?, ?, ?, ?)");
      try {
        $stmt->execute([$uid, $code, $name, $status]);
        flash('success','Brand created.');
      } catch (PDOException $e) {
        flash('error','Create failed. Maybe duplicate code?');
      }
    }
  }
  redirect('brands.php');
}


if (($_GET['action'] ?? '') === 'delete') {
  $id = intval($_GET['id'] ?? 0);
  if ($id > 0) {
    $ownerCheck = $pdo->prepare("SELECT user_id FROM master_brand WHERE id = ?");
    $ownerCheck->execute([$id]);
    $row = $ownerCheck->fetch();
    if ($row && (is_admin() || intval($row['user_id']) === $uid)) {
      $pdo->prepare("DELETE FROM master_brand WHERE id = ?")->execute([$id]);
      flash('success','Brand deleted.');
    } else {
      flash('error','Not authorized or not found.');
    }
  }
  redirect('brands.php');
}


$page = max(1, intval($_GET['page'] ?? 1));
$per = ITEMS_PER_PAGE;
$offset = ($page - 1) * $per;

$where = is_admin() ? '1=1' : 'user_id = :uid';
$countSql = "SELECT COUNT(*) c FROM master_brand WHERE $where";
$listSql = "SELECT * FROM master_brand WHERE $where ORDER BY id DESC LIMIT :per OFFSET :off";

$stmt = $pdo->prepare($countSql);
if (!is_admin()) $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->execute();
$total = (int)$stmt->fetch()['c'];

$stmt = $pdo->prepare($listSql);
if (!is_admin()) $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
$stmt->bindValue(':per', $per, PDO::PARAM_INT);
$stmt->bindValue(':off', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
  $eid = intval($_GET['id'] ?? 0);
  $stmt = $pdo->prepare("SELECT * FROM master_brand WHERE id = ?");
  $stmt->execute([$eid]);
  $tmp = $stmt->fetch();
  if ($tmp && (is_admin() || intval($tmp['user_id']) === $uid)) $edit = $tmp;
}
?>
<h1>Brands</h1>
<form method="post" class="form-grid" style="margin-bottom:16px;">
  <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
  <label>Code<input type="text" name="code" required maxlength="50" value="<?= e($edit['code'] ?? '') ?>"></label>
  <label>Name<input type="text" name="name" required maxlength="150" value="<?= e($edit['name'] ?? '') ?>"></label>
  <label>Status
    <select name="status">
      <?php $s = $edit['status'] ?? 'Active'; ?>
      <option value="Active"<?= $s==='Active'?' selected':'' ?>>Active</option>
      <option value="Inactive"<?= $s==='Inactive'?' selected':'' ?>>Inactive</option>
    </select>
  </label>
  <div style="display:flex; align-items:end;"><button class="button" type="submit"><?= $edit ? 'Update' : 'Create' ?></button></div>
</form>

<table class="table">
  <tr><th>ID</th><th>Code</th><th>Name</th><th>Status</th><th>Actions</th></tr>
  <?php foreach ($rows as $r): ?>
    <tr>
      <td><?= e($r['id']) ?></td>
      <td><?= e($r['code']) ?></td>
      <td><?= e($r['name']) ?></td>
      <td><span class="badge <?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td>
      <td class="actions">
        <a class="button secondary" href="<?= url('brands.php?action=edit&id=' . $r['id']) ?>">Edit</a>
        <form id="delb-<?= $r['id'] ?>" method="post" action="<?= url('brands.php?action=delete&id=' . $r['id']) ?>" style="display:inline;">
          <input type="hidden" name="_method" value="DELETE">
          <button type="button" class="button" onclick="confirmDelete('delb-<?= $r['id'] ?>','Delete this brand?')">Delete</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
</table>

<div class="pagination">
  <?php for ($i=1; $i<=ceil($total/$per); $i++): ?>
    <a href="<?= url('brands.php?page='.$i) ?>" <?= $i==$page ? 'style="font-weight:bold;"' : '' ?>><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
