<?
 
// This file is for downloading XML files (scenario files) that are stored in the database.
require_once "db.php";
require_once "dbHelper.php";

$id = $_GET["id"];
$callid = $_GET["callid"];

if($id != "") {
	$sObj = new Scenario($id);
	$row = $sObj->getOnly("name, xml");
	$name = $row->name;
} else if($callid != "") {
	$cObj = new SIPpCall($callid);
	$row = $cObj->getOnly("xml");
	$name = "scenario";
}


header('Content-Type: text/xml');
header('Content-Disposition: inline; filename='.$name.'.xml');

echo $row->xml; 
?> 
