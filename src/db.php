<?php

// Here the mysql connection object is generated.
// expects: db_host, db_user, db_pwd and db_name in config.ini.php must be correctly set.
require_once "read_config.php";

$host=$config["db_host"];
$user=$config["db_user"];
$pwd=$config["db_pwd"];
$dbname=$config["db_name"];
// $con=mysql_connect($host,$user,$pwd);
$con=mysqli_connect($host,$user,$pwd);

if(!$con) die(mysqli_error());
if(!mysqli_select_db($con,$dbname)) die(mysqli_error());
//

?>
