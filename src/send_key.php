<?

// This file is called via ajax from status_screen.php when the user presses a key on the keyboard.
require_once "Call_Data.php";
session_start();

$key = $_GET["key"];
$pid = $_GET["pid"];
if($pid == "" || $key == "") die("Either pid or key is missing!");

// call Object with call specific data
$cObj = $_SESSION["s_call_".$pid];

// send the key via udp to the controlport of sipp
if($cObj->monitor && $cObj->control_port != "") {
	//exec("echo \"".$key."\" >/dev/udp/127.0.0.1/".$cObj->control_port);
	$fp = fsockopen("udp://127.0.0.1", $cObj->control_port, $errno, $errstr);
	if (!$fp) {
  		echo "Error sending via udp: $errno - $errstr<br />\n";
	} else {
    	fwrite($fp, $key);
    	fclose($fp);
	}
}

if($cObj->control_port == "") echo "Could not determine control port! Key commands are not possible.";

?>
