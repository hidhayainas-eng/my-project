<?php

define('APP_NAME', 'MASTER DATA MANAGEMENT SYSTEM');
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/')); 
define('ITEMS_PER_PAGE', 5);

function base_path($path = '') {
    return __DIR__ . '/../' . ltrim($path, '/');
}

function url($path = '') {
    $base = BASE_URL === '/' ? '' : BASE_URL;
    return $base . '/' . ltrim($path, '/');
}

function is_post() { return $_SERVER['REQUEST_METHOD'] === 'POST'; }

function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

function e($str) { return htmlspecialchars((string)$str, ENT_QUOTES, 'UTF-8'); }


session_start();
if (!isset($_SESSION['flash'])) $_SESSION['flash'] = [];
function flash($type, $msg) { $_SESSION['flash'][] = ['type'=>$type,'msg'=>$msg]; }
function get_flashes() { $f=$_SESSION['flash']; $_SESSION['flash']=[]; return $f; }
