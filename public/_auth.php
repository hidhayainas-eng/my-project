<?php

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/db.php';

function current_user() { return $_SESSION['user'] ?? null; }
function require_auth() { if (!current_user()) redirect('login.php'); }
function is_admin() { return !empty($_SESSION['user']) && intval($_SESSION['user']['is_admin']) === 1; }

function load_user($id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, name, email, is_admin FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
