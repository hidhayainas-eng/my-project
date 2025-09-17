<?php
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

if (is_post()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $is_admin = isset($_POST['is_admin']) ? 1 : 0;

    if ($name === '' || $email === '' || $password === '') {
        flash('error', 'All fields are required.');
    } elseif (strlen($name) > 100 || strlen($email) > 150) {
        flash('error', 'Name or email too long.');
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            flash('error', 'Email already registered.');
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, is_admin) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $hash, $is_admin]);
            flash('success', 'Registration successful. You can log in now.');
            redirect('login.php');
        }
    }
}

include __DIR__ . '/_header.php';
?>
<h1>Register</h1>
<form method="post" class="form-grid-1" style="max-width:480px;">
  <label>Name<input type="text" name="name" required maxlength="100"></label>
  <label>Email<input type="email" name="email" required maxlength="150"></label>
  <label>Password<input type="password" name="password" required></label>
  <label><input type="checkbox" name="is_admin"> Register as Admin (demo)</label>
  <button class="button" type="submit">Create Account</button>
</form>
<p class="meta">Already have an account? <a href="<?= url('login.php') ?>">Login</a></p>
<?php include __DIR__ . '/_footer.php'; ?>
