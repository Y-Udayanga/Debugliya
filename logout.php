<?php
require_once __DIR__ . '/session_bootstrap.php';
app_session_start();
app_clear_login();
header('Location: login.php');
exit;
?>
