<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::logout();
header('Location: /admin/login.php');
