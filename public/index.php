<?php
require_once __DIR__ . '/_auth.php';
require_auth();
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';
include __DIR__ . '/_header.php';

$user = current_user();
$uid = $user['id'];


if (is_admin()) {
  $brandCount = $pdo->query("SELECT COUNT(*) c FROM master_brand")->fetch()['c'];
  $catCount = $pdo->query("SELECT COUNT(*) c FROM master_category")->fetch()['c'];
  $itemCount = $pdo->query("SELECT COUNT(*) c FROM master_item")->fetch()['c'];
} else {
  $brandCount = $pdo->query("SELECT COUNT(*) c FROM master_brand WHERE user_id = {$uid}")->fetch()['c'];
  $catCount = $pdo->query("SELECT COUNT(*) c FROM master_category WHERE user_id = {$uid}")->fetch()['c'];
  $itemCount = $pdo->query("SELECT COUNT(*) c FROM master_item WHERE user_id = {$uid}")->fetch()['c'];
}
?>
<h1>Dashboard</h1>
<p class="meta">Welcome, <?= e($user['name']) ?><?php if (is_admin()) echo ' (Admin)'; ?>.</p>
<div class="form-grid">
  <div style="background:#f9fafb; padding:16px; border-radius:12px;">
    <h3>Brands</h3>
    <p><strong><?= e($brandCount) ?></strong></p>
    <a class="button" href="<?= url('brands.php') ?>">Manage Brands</a>
  </div>
  <div style="background:#f9fafb; padding:16px; border-radius:12px;">
    <h3>Categories</h3>
    <p><strong><?= e($catCount) ?></strong></p>
    <a class="button" href="<?= url('categories.php') ?>">Manage Categories</a>
  </div>
  <div style="background:#f9fafb; padding:16px; border-radius:12px;">
    <h3>Items</h3>
    <p><strong><?= e($itemCount) ?></strong></p>
    <a class="button" href="<?= url('items.php') ?>">Manage Items</a>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>
