<?php
session_start();
session_unset();
session_destroy();

// Redirect back to homepage or login page after logout
header("Location: homepage.php");
exit();
?>