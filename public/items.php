<?php
require_once __DIR__ . '/_auth.php';
require_auth();
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/_header.php';

$user = current_user();
$uid = $user['id'];


$brandSql = is_admin() ? "SELECT * FROM master_brand ORDER BY name" : "SELECT * FROM master_brand WHERE user_id = {$uid} ORDER BY name";
$brands = $pdo->query($brandSql)->fetchAll();

$catSql = is_admin() ? "SELECT * FROM master_category ORDER BY name" : "SELECT * FROM master_category WHERE user_id = {$uid} ORDER BY name";
$categories = $pdo->query($catSql)->fetchAll();

$uploadDir = __DIR__ . '/uploads/';
if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }


if (is_post() && ($_POST['form'] ?? '') === 'item') {
    $id = intval($_POST['id'] ?? 0);
    $code = trim($_POST['code'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $brand_id = intval($_POST['brand_id'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $status = in_array($_POST['status'] ?? 'Active', ['Active','Inactive']) ? $_POST['status'] : 'Active';
    $attachmentPath = null;

    if ($code === '' || $name === '' || $brand_id<=0 || $category_id<=0) {
        flash('error','All fields are required.');
    } elseif (strlen($code) > 50 || strlen($name) > 150) {
        flash('error','Character limit exceeded.');
    } else {
        if (!empty($_FILES['attachment']['name'])) {
            $fname = basename($_FILES['attachment']['name']);
            $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf','jpg','jpeg','png','gif'])) {
                flash('error', 'Invalid file type.');
                redirect('items.php');
            }
            $newName = 'att_' . time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/','_', $fname);
            $dest = $uploadDir . $newName;
            if (move_uploaded_file($_FILES['attachment']['tmp_name'], $dest)) {
                $attachmentPath = 'uploads/' . $newName;
            } else {
                flash('error','File upload failed.');
                redirect('items.php');
            }
        }

        if ($id > 0) {
            $ownerCheck = $pdo->prepare("SELECT user_id, attachment FROM master_item WHERE id = ?");
            $ownerCheck->execute([$id]);
            $row = $ownerCheck->fetch();
            if (!$row) { flash('error','Item not found.'); }
            elseif (!is_admin() && intval($row['user_id']) !== $uid) { flash('error','Not authorized.'); }
            else {
                $sql = "UPDATE master_item SET code=?, name=?, brand_id=?, category_id=?, status=?";
                $params = [$code, $name, $brand_id, $category_id, $status];
                if ($attachmentPath) { $sql .= ", attachment=?"; $params[] = $attachmentPath; }
                $sql .= " WHERE id=?";
                $params[] = $id;
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                flash('success','Item updated.');
            }
        } else {
            $stmt = $pdo->prepare("INSERT INTO master_item (user_id, brand_id, category_id, code, name, attachment, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            try {
                $stmt->execute([$uid, $brand_id, $category_id, $code, $name, $attachmentPath, $status]);
                flash('success','Item created.');
            } catch (PDOException $e) {
                flash('error','Create failed. Maybe duplicate code?');
            }
        }
    }
    redirect('items.php');
}


if (($_GET['action'] ?? '') === 'delete') {
    $id = intval($_GET['id'] ?? 0);
    if ($id > 0) {
        $ownerCheck = $pdo->prepare("SELECT user_id, attachment FROM master_item WHERE id = ?");
        $ownerCheck->execute([$id]);
        $row = $ownerCheck->fetch();
        if ($row && (is_admin() || intval($row['user_id']) === $uid)) {
            if (!empty($row['attachment'])) {
                $f = __DIR__ . '/' . $row['attachment'];
                if (is_file($f)) @unlink($f);
            }
            $pdo->prepare("DELETE FROM master_item WHERE id = ?")->execute([$id]);
            flash('success','Item deleted.');
        } else { flash('error','Not authorized or not found.'); }
    }
    redirect('items.php');
}


if (($_GET['action'] ?? '') === 'export_csv') {
    $where = is_admin() ? '1=1' : 'mi.user_id = :uid';
    $sql = "SELECT mi.id, mi.code, mi.name, mi.status, mi.attachment, b.name AS brand, c.name AS category
            FROM master_item mi
            JOIN master_brand b ON b.id = mi.brand_id
            JOIN master_category c ON c.id = mi.category_id
            WHERE $where
            ORDER BY mi.id DESC";
    $stmt = $pdo->prepare($sql);
    if (!is_admin()) $stmt->bindValue(':uid', $uid, PDO::PARAM_INT);
    $stmt->execute();
    $data = $stmt->fetchAll();

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="items.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Code','Name','Status','Attachment','Brand','Category']);
    foreach ($data as $d) {
        fputcsv($out, [$d['id'], $d['code'], $d['name'], $d['status'], $d['attachment'], $d['brand'], $d['category']]);
    }
    fclose($out);
    exit;
}


$page = max(1, intval($_GET['page'] ?? 1));
$per = ITEMS_PER_PAGE;
$offset = ($page - 1) * $per;

$q = trim($_GET['q'] ?? '');
$statusF = $_GET['status'] ?? '';

$whereParts = [];
$params = [];
if (!is_admin()) { $whereParts[] = 'mi.user_id = :uid'; $params[':uid'] = $uid; }
if ($q !== '') { $whereParts[] = '(mi.code LIKE :q OR mi.name LIKE :q)'; $params[':q'] = "%{$q}%"; }
if (in_array($statusF, ['Active','Inactive'])) { $whereParts[] = 'mi.status = :st'; $params[':st'] = $statusF; }
$where = $whereParts ? implode(' AND ', $whereParts) : '1=1';


$countSql = "SELECT COUNT(*) c FROM master_item mi 
             JOIN master_brand b ON b.id = mi.brand_id 
             JOIN master_category c ON c.id = mi.category_id 
             WHERE $where";
$stmt = $pdo->prepare($countSql);
foreach ($params as $k=>$v) {
    $type = ($k === ':uid') ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($k, $v, $type);
}
$stmt->execute();
$total = (int)$stmt->fetch()['c'];


$listSql = "SELECT mi.*, b.name AS brand_name, c.name AS category_name 
            FROM master_item mi 
            JOIN master_brand b ON b.id = mi.brand_id 
            JOIN master_category c ON c.id = mi.category_id 
            WHERE $where 
            ORDER BY mi.id DESC 
            LIMIT :per OFFSET :off";
$stmt = $pdo->prepare($listSql);
foreach ($params as $k=>$v) {
    $type = ($k === ':uid') ? PDO::PARAM_INT : PDO::PARAM_STR;
    $stmt->bindValue($k, $v, $type);
}
$stmt->bindValue(':per', (int)$per, PDO::PARAM_INT);
$stmt->bindValue(':off', (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();


$edit = null;
if (($_GET['action'] ?? '') === 'edit') {
    $eid = intval($_GET['id'] ?? 0);
    $stmt = $pdo->prepare("SELECT * FROM master_item WHERE id = ?");
    $stmt->execute([$eid]);
    $tmp = $stmt->fetch();
    if ($tmp && (is_admin() || intval($tmp['user_id']) === $uid)) $edit = $tmp;
}

?>
<h1>Items</h1>


<form method="get" class="searchbar" style="margin-bottom:12px;">
    <input type="text" name="q" placeholder="Search code or name..." value="<?= e($q) ?>">
    <select name="status">
        <option value="">All</option>
        <option value="Active" <?= $statusF==='Active'?'selected':'' ?>>Active</option>
        <option value="Inactive" <?= $statusF==='Inactive'?'selected':'' ?>>Inactive</option>
    </select>
    <button class="button" type="submit">Search</button>
    <a class="button secondary" href="<?= url('items.php') ?>">Reset</a>
    <a class="button" href="<?= url('items.php?action=export_csv') ?>">Export CSV</a>
</form>


<form method="post" enctype="multipart/form-data" class="form-grid" style="margin-bottom:16px;">
    <input type="hidden" name="form" value="item">
    <input type="hidden" name="id" value="<?= e($edit['id'] ?? 0) ?>">
    <label>Brand
        <select name="brand_id" required>
            <option value="">-- Select Brand --</option>
            <?php foreach ($brands as $b): ?>
                <option value="<?= e($b['id']) ?>" <?= isset($edit['brand_id']) && $edit['brand_id']==$b['id']?'selected':'' ?>><?= e($b['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Category
        <select name="category_id" required>
            <option value="">-- Select Category --</option>
            <?php foreach ($categories as $c): ?>
                <option value="<?= e($c['id']) ?>" <?= isset($edit['category_id']) && $edit['category_id']==$c['id']?'selected':'' ?>><?= e($c['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>Code<input type="text" name="code" required maxlength="50" value="<?= e($edit['code'] ?? '') ?>"></label>
    <label>Name<input type="text" name="name" required maxlength="150" value="<?= e($edit['name'] ?? '') ?>"></label>
    <label>Status
        <select name="status">
            <?php $s = $edit['status'] ?? 'Active'; ?>
            <option value="Active"<?= $s==='Active'?' selected':'' ?>>Active</option>
            <option value="Inactive"<?= $s==='Inactive'?' selected':'' ?>>Inactive</option>
        </select>
    </label>
    <label>Attachment (optional)<input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.gif"></label>
    <div style="display:flex; align-items:end;"><button class="button" type="submit"><?= $edit ? 'Update' : 'Create' ?></button></div>
</form>


<table class="table">
<tr><th>ID</th><th>Code</th><th>Name</th><th>Brand</th><th>Category</th><th>Status</th><th>Attachment</th><th>Actions</th></tr>
<?php foreach ($rows as $r): ?>
<tr>
<td><?= e($r['id']) ?></td>
<td><?= e($r['code']) ?></td>
<td><?= e($r['name']) ?></td>
<td><?= e($r['brand_name']) ?></td>
<td><?= e($r['category_name']) ?></td>
<td><span class="badge <?= strtolower($r['status']) ?>"><?= e($r['status']) ?></span></td>
<td>
    <?php if ($r['attachment']): ?>
        <a href="<?= url($r['attachment']) ?>" target="_blank">View</a>
    <?php else: ?>—<?php endif; ?>
</td>
<td class="actions">
    <a class="button secondary" href="<?= url('items.php?action=edit&id=' . $r['id']) ?>">Edit</a>
    <form id="deli-<?= $r['id'] ?>" method="post" action="<?= url('items.php?action=delete&id=' . $r['id']) ?>" style="display:inline;">
        <input type="hidden" name="_method" value="DELETE">
        <button type="button" class="button" onclick="confirmDelete('deli-<?= $r['id'] ?>','Delete this item?')">Delete</button>
    </form>
</td>
</tr>
<?php endforeach; ?>
</table>

<div class="pagination">
<?php for ($i=1; $i<=ceil($total/$per); $i++): ?>
    <a href="<?= url('items.php?page=' . $i . '&q=' . urlencode($q) . '&status=' . urlencode($statusF)) ?>"
       <?= $i==$page ? 'style="font-weight:bold;"' : '' ?>><?= $i ?></a>
<?php endfor; ?>
</div>

<?php include __DIR__ . '/_footer.php'; ?>

