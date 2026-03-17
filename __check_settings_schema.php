<?php
require_once 'db_connect.php';
$res = $conn->query("SHOW CREATE TABLE settings");
$row = $res ? $res->fetch_assoc() : null;
if (!$row) { echo "NO_TABLE\n"; exit; }
echo $row['Create Table'], "\n";
$dup = $conn->query("SELECT setting_key, COUNT(*) c FROM settings GROUP BY setting_key HAVING c > 1");
if ($dup && $dup->num_rows > 0) {
  echo "DUPLICATES\n";
  while($r=$dup->fetch_assoc()){ echo $r['setting_key'],":",$r['c'],"\n"; }
} else {
  echo "NO_DUPLICATES\n";
}
$mm = $conn->query("SELECT setting_value FROM settings WHERE setting_key='maintenance_mode'");
if ($mm && $mm->num_rows) { while($r=$mm->fetch_assoc()){ echo "maintenance_mode=",$r['setting_value'],"\n"; } } else { echo "maintenance_mode=MISSING\n"; }
?>

