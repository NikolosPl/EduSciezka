<?php
require_once "security.php";

session_destroy();
header("Location: logowanie.php");
exit;
?>