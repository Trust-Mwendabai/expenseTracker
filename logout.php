<?php
require_once 'src/php/auth.php';

$auth = new Auth();
$auth->logout();

header("Location: login.php");
exit();
?>
