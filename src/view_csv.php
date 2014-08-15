<?
 
// This file is for downloading CSV files (logfiles etc) that are stored in the database.
 
require_once "db.php";
require_once "dbHelper.php";
require_once "read_config.php";

$id = $_GET["id"];

$callid = $_GET["callid"];

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
	$csv = $rcRow[0];
} else if($id != "") {
	$sObj = new Scenario($id);
	$row = $sObj->getOnly("name, csv");
	$name = $row->name;
	$csv = $row->csv;
} else if($callid != "") {
	$cObj = new SIPpCall($callid);
	$row = $cObj->getOnly("csv");
	$name = "scenario";
	$csv = $row->csv;
}



header('Content-type: text/csv');
header('Content-Disposition: attachment; filename="'.$name.'.csv"'); 

if($config["csv_separator"] != ";") echo str_replace(";", $config["csv_separator"], $csv);
else echo $csv;
?>
