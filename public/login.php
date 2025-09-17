<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (is_post()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = ['id'=>$user['id'],'name'=>$user['name'],'email'=>$user['email'],'is_admin'=>$user['is_admin']];
        flash('success', 'Welcome back, ' . $user['name'] . '!');
        redirect('index.php');
    } else {
        flash('error', 'Invalid credentials.');
    }
}

include __DIR__ . '/_header.php';
?>
<h1>Login</h1>
<form method="post" class="form-grid-1" style="max-width:420px;">
  <label>Email<input type="email" name="email" required></label>
  <label>Password<input type="password" name="password" required></label>
  <button class="button" type="submit">Login</button>
</form>
<p class="meta">No account? <a href="<?= url('register.php') ?>">Register</a></p>
<?php include __DIR__ . '/_footer.php'; ?>
