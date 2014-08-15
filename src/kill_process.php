<?

// This file is called from run_progress.php via ajax, if the user wants to kill a sipp process for some reason.
// expects (GET-variables): either pid (if user wants to kill a specific process), or pid1 and pid2 (if user wants to kill all processes)
// returns (output to browser): message about success
 
require_once "db.php";
require_once "dbHelper.php";
require_once "stop_sipp.php";

$pid = $_GET["pid"];
$pid1 = $_GET["pid1"];
$pid2 = $_GET["pid2"];
	
if($pid == "" && $pid1 == "" && $pid2 == "") die("Cannot kill process, because pid is missing!");			

if($pid != "") {
	$success = stop_sipp($pid);
	if($success == 0) echo "Process stopped!";
	else if($success == 1) echo "Wasn't able to stop process, but process was killed!";
	else echo "Unable to kill process with pid $pid";
} else {
	$success1 = 0;
	$success2 = 0;
	if($pid1 != "") $success1 = stop_sipp($pid1);
	if($pid2 != "") $success2 = stop_sipp($pid2);
	
	if($success1 >= 0 && $success2 >= 0) echo "All processes killed!";
	else echo "Cannot kill all processes. At least one process is still running!";
}

?>
