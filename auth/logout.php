<?php
require_once '../includes/functions.php';

init_session();

// Destroy session
session_unset();
session_destroy();

// Redirect to login
header('Location: /auth/login.php');
exit();
?>
