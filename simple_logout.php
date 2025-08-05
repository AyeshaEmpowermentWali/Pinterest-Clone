<?php
// Ultra-simple logout that never fails
session_start();
session_destroy();
header('Location: index.php');
exit();
?>
