<?php
session_start();
require_once '../config/db.php';
require_once '../includes/functions.php';
session_destroy();
redirect(BASE_URL . '/admin/login.php');
