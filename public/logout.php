<?php
require_once __DIR__ . '/../config/app.php';
session_destroy();
session_start();
flash('success', 'You have been logged out.');
redirect('login.php');
