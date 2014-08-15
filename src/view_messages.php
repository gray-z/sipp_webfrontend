<?

// This file is for downloading the file created by the commanline option -trace_messages
// The trace_messages file is not stored in the database because it may be very large, but it is left
// for a short time in the systems temp folder until the garbage collector removes it.
require_once "sys_get_temp_dir.php";

$run_id = $_GET["run_id"];
$call_id = $_GET["call_id"];
$test_id = $_GET["test_id"];
$test_version = $_GET["test_version"];

$working_dir = get_working_dir($test_id, $test_version, $run_id);
$file = $working_dir."messages_".$call_id;
if(!file_exists($file)) die("File not found! ");

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename='.basename($file).'.txt');

echo file_get_contents($file); 
?> 
