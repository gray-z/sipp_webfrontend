<?

 
// During execution of a call this file is requested periodically via ajax. 
// Here the actual monitor-screen is extracted from the screenfile, and returned to status_screen.php (if monitoring is turned on).
// Further this file checks if the sipp process is still running, and if not, it returns exit-code and standard-error to status_screen.php.
// expects (GET-variables): screen_number and pid 
// returns (output to browser): actual monitor-screen, or exit-code and standard-error
require_once "Call_Data.php";
session_start();


// in the screen file following lines seperate the different screens
$screenDiv = array();
$screenDiv[] = "------------------------------ Scenario Screen -------- [1-9]: Change Screen --";
$screenDiv[] = "----------------------------- Statistics Screen ------- [1-9]: Change Screen --";
$screenDiv[] = "---------------------------- Repartition Screen ------- [1-9]: Change Screen --";
$screenDiv[] = "----------------------------- Variables Screen -------- [1-9]: Change Screen --";
$screenDiv[] = "--------------------------- Repartition 1 Screen ------ [1-9]: Change Screen --";
$screenDiv[] = "--------------------------- Repartition 2 Screen ------ [1-9]: Change Screen --";
$screenDiv[] = "--------------------------- Repartition 3 Screen ------ [1-9]: Change Screen --";
$screenDiv[] = "--------------------------- Repartition 4 Screen ------ [1-9]: Change Screen --";
$screenDiv[] = "--------------------------- Repartition 5 Screen ------ [1-9]: Change Screen --";

// get the requested screen number
$screen_number = $_GET["screen_number"];

// get the pid to be monitored
$pid = $_GET["pid"];
if($pid == "") die("Process ID missing!");

// call Object with call specific data
$cObj = $_SESSION["s_call_".$pid];

// find out if process with pid is (still) running
$running = shell_exec("ps -p $pid | grep $pid");
if(empty($running)) $finished = true;
else $finished = false;

// should progress be monitored on screen?
if($cObj->monitor) {

	if(!$finished) {
		// send USR2 signal to sipp process so that the actual screen is written into the screen file.
		exec("kill -USR2 $pid");
		// do it twice, because of strange behaviour of sipp. the first time only a part of the screen is posted,
		// the second time the rest of the screen ist posted plus one more fragment.
		exec("kill -USR2 $pid");

		// it can take a while until screen file is initially created, so wait for 5 seconds maximum
		$i = 0;
		while(filesize($cObj->screen_file)==0 && $i < 5) {
			$i++;
			sleep(1);
			// output status 
			echo "wait for ".$cObj->screen_file." - ".filesize($cObj->screen_file)."\n";
			clearstatcache();
		}
		
		// determine the number of lines the first screen dump has. this is used to tail the file during the whole execution.
		if(!isset($_SESSION["s_call_".$pid]->screen_line_count) || $_SESSION["s_call_".$pid]->screen_line_count == "") {
			$lines = file($cObj->screen_file);
			// store the number of lines in the session-object
			// +50 because maybe there will be larger screen dumps
			$_SESSION["s_call_".$pid]->screen_line_count = count($lines) + 50;
		}
	}
	
	if(isset($_SESSION["s_call_".$pid]->screen_line_count)) $screen_line_count = $_SESSION["s_call_".$pid]->screen_line_count;
	else $screen_line_count = 300;
	
	// to save memory, just process the tail of the file in php
	exec("tail -n $screen_line_count $cObj->screen_file > $cObj->screen_file_tail");

	// load each line in an array
	$screenfile = file($cObj->screen_file_tail);

	if(!$finished) {
		// find position of the first occurence of the requested screen
		$pos = array_search($screenDiv[$screen_number-1]."\r\n", $screenfile);
		
		// and if found, find the end of the requested screen
		if($pos !== false) {
			$return = "";
			$found = false;
			$c = count($screenfile);
			do {
				$return .= $screenfile[$pos];
				$pos++;
				if($screenfile[$pos]{0} == "-") {
					if(strpos($screenfile[$pos], "[1-9]: Change Screen") !== false) $found = true;
				}
			} while(!$found && $pos < $c-1);
		} else $return = $screenDiv[$screen_number-1];
	}
} else {
	$return = "monitoring turned off...";
}



// if sipp has finished...
if($finished) {
	require_once "db.php";
	require_once "dbHelper.php";
	// get its exitcode ...
	$exit_code = $cObj->getExitCode();
	// store all logfiles in the database ...
	$cObj->updateDatabase();
	// read the standard error ...
	if($cObj->std_error_file != "" && file_exists($cObj->std_error_file)) $std_error = file_get_contents($cObj->std_error_file);
	else $std_error = "";
	// remove all temporary files ...
	$cObj->cleanUp();
	// and return exitcode and standard error to status_screen.php.
	echo "exit=".$exit_code."&std_error=".htmlentities($std_error);
} else {
	echo $return;
}

?>
