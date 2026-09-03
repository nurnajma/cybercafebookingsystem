<?php
require_once __DIR__ . '/includes/auth.php';
startSess();
session_destroy();
header('Location: index.php');
exit;