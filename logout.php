<?php
session_start();
session_unset();
session_destroy();
header("Location: admin_login.php?logged_out=1");
exit();
?>
