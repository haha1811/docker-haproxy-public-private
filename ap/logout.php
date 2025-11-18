<?php
session_start();

// 清空 session
session_unset();
session_destroy();

// 回到登入頁
header('Location: login.php');
exit;
