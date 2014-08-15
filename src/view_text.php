<?
// This file is for downloading text files (logfiles etc) that are stored in the database.
require_once "db.php";
require_once "dbHelper.php";

$run_id = $_GET["run_id"];
$test_id = $_GET["test_id"];
$call_id = $_GET["call_id"];
$test_version= $_GET["test_version"];
$field = $_GET["field"];

if($run_id != "" && $test_id != "" && $test_version != "" && $call_id != "" && $field != "") {
	// load run detail data
	$rcObj = new Run_Call($run_id, $test_id, $test_version, $call_id);
	$rcRow = $rcObj->getOnly($field);

	$rObj = new Run($run_id, $test_id, $test_version);
	$rRes = $rObj->getAll("timestamp", "id=".$run_id);
	$rRow = mysqli_fetch_array($rRes, MYSQL_NUM);

	$tObj = new Test($test_id);
	$tRow = $tObj->get();
	
	$name = $field."_".$tRow->name."_v_".$test_version."_".$rRow[0];
	$txt = $rcRow[0];
} else die("File not found!");



header('Content-type: text/plain');
header('Content-Disposition: attachment; filename="'.$name.'.txt"'); 

echo $txt;
?>
