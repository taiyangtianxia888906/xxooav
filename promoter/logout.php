<?php
session_start();
unset($_SESSION['promoter_id'], $_SESSION['promoter_username']);
header('Location: login.php');
exit;