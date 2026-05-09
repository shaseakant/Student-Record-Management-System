<?php
session_start();
session_destroy();

// Clear cookies
setcookie("remember_user", "", time() - 3600, "/");
setcookie("remember_pass", "", time() - 3600, "/");

header("Location: index.html");
exit();
