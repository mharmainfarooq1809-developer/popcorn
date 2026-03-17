<?php
require_once 'db_connect.php';
$res = $conn->query("SELECT setting_value FROM settings WHERE setting_key='site_logo'");
$row = $res->fetch_assoc();
echo $row['setting_value'];

