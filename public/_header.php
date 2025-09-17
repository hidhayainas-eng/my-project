<?php
require_once __DIR__ . '/../config/app.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= e(APP_NAME) ?></title>
  <link rel="stylesheet" href="<?= url('assets/style.css') ?>">
  <script defer src="<?= url('assets/confirm.js') ?>"></script>
</head>
<body>
<header class="header">
  <div><strong><?= e(APP_NAME) ?></strong></div>
  <nav>
    <?php if (!empty($_SESSION['user'])): ?>
      <a href="<?= url('index.php') ?>">Dashboard</a>
      <a href="<?= url('brands.php') ?>">Brands</a>
      <a href="<?= url('categories.php') ?>">Categories</a>
      <a href="<?= url('items.php') ?>">Items</a>
      <a href="<?= url('logout.php') ?>">Logout</a>
    <?php else: ?>
      <a href="<?= url('login.php') ?>">Login</a>
      <a href="<?= url('register.php') ?>">Register</a>
    <?php endif; ?>
  </nav>
</header>
<div class="container">
<?php foreach (get_flashes() as $f): ?>
  <div class="flash <?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
<div id="confirm-overlay" class="confirm-overlay">
  <div class="confirm-box">
    <p id="confirm-text">Are you sure?</p>
    <div style="display:flex; gap:10px; margin-top:10px;">
      <button id="confirm-yes" class="button">Yes, delete</button>
      <button id="confirm-no" class="button secondary">Cancel</button>
    </div>
  </div>
</div>
