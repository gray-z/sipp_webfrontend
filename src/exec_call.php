<?


// This file is called from run_progress.php via ajax.
// The basic function of this file is to build up the commandline, create the xml-scenario file
// and the csv-injection file in the system's temporary folder, execute the call and return its process id (pid).
// Further the names of the log files are created, the port for remote controlling sipp is determined, and then
// these informations are stored in a session object for later processing.
// expects (GET-variables): call_id, party (a or b), run_id, test_id, test_version
// returns (output to browser): eitehr call_id;process_id;party, or call_id;exit_code;party if an error occurs

	require_once "Call_Data.php";
	session_start();

	require_once "read_config.php";
	require_once "db.php";
	require_once "dbHelper.php";
	require_once "sys_get_temp_dir.php";


	$call_id = $_GET["id"];
	$test_id = $_GET["test_id"];
	$test_version = $_GET["test_version"];
	$party = $_GET["party"];
	$run_id = $_GET["run_id"];
	
	
	header("text/xml");
	
	$cObj = new SIPpCall($call_id);
	$cRow = $cObj->get();
	
	// store actiual timestamp in database
	$rcObj = new Run_Call($run_id, $test_id, $test_version, $call_id);
	$rcObj->setTime();
	
	// sould information be logged in files and monitored on screen?
	$log = $cRow->log == "t";
	$monitor = $cRow->monitor == "t";
	
	// does this call uses a default scenario or a specific xml scenario?
	$default = $cRow->def == "t";
	
	/* bild up options for sipp call. options are assembled as follows:
	 * first the extended_parameters defined in the call,
	 * then the regular parameters defined in the call,
	 * followed by the avp parameters defined in the config file,
	 * then all parameters that are needed to log data (-trace_screen...),
	 * and finally the extended_parameters again to ensure that they overwrite ambiguous parameters from the config file.
	*/
	$logParameters = $log ? " -trace_err -trace_stat -trace_rtt -trace_logs" : "";
	
	$logParameters .= $monitor ? " -trace_screen" : "";
	$options = $cObj->getOptions($cRow, getConfigParameters().$logParameters);
	
	// look for sipp executeable in config file
	$sipp_path = $executables[$cRow->executable];
	
	// create a folder for this session in the systems temp directory
	$working_dir = get_working_dir($test_id, $test_version, $run_id);
	if(!file_exists($working_dir)) mkdir($working_dir, 0777);
	
	if($default) { 
		// for default scenario, dump xml data in a temporary file
		// this is necessary to have the path for the logfiles in the temporary directory
		$temporary_xml_file = tempnam($working_dir, "xml");
		shell_exec($sipp_path." -sd ".$cRow->xml." > ".$temporary_xml_file);
	} else { 
		//store xml data in temporary files
		$temporary_xml_file = tempnam($working_dir, "xml");
		$handle = fopen($temporary_xml_file, "w");
		fwrite($handle, $cRow->xml);
		fclose($handle);
	
		//store csv data in temporary files
		$temporary_csv_file = "";
		if($cRow->csv != "") { 
			$temporary_csv_file = tempnam($working_dir, "csv");
			$handle = fopen($temporary_csv_file, "w");
			fwrite($handle, $cRow->csv);
			fclose($handle);
			$inf = "-inf ".$temporary_csv_file;
		} else $inf = "";
	}
	
	// build up the command line
	$command = $sipp_path." -sf ".$temporary_xml_file." ".$inf." ".$options." ".$cRow->ip_address;
	
	// define an exitcode file
	$exit_code_file = $temporary_xml_file."_".$call_id."_exitcode.log";
	
	$std_error_file = $temporary_xml_file."_".$call_id."_std_error.log";

	$pid = execute_background($command, $exit_code_file, $std_error_file, "/dev/null");

	$control_port = "";
	if($pid!="" && is_numeric($pid)) {
	
		if($monitor) {
			// in order to determine the control port for the new sipp instance, we need to know if there is a -cp parameter
			// in either the extendet_parameters or in the config.ini.php
			// so, first we search the extended_parameters...
			$found = array();
			$success =  preg_match("/\-cp\s+([0-9]+)/", $cRow->extended_parameters, $found);

			if($success == 1) {
				$startingPort = $found[1]; 		// ... and if we find a pattern like "-cp port" we take the port ...
			} else {
				// ... otherwise we take either an existing avp paramter in the config file matching "cp", or standard port 8888.
				$startingPort = $avp["cp"] == "" ? 8888 : $avp["cp"];			
			}
		
			// now we try to find out the control port of the new sipp instance
			$control_port = getNewControlPort($pid, $startingPort);
		}
		
		// generate log filenames
		$error_file = $temporary_xml_file."_".$pid."_errors.log";
		$rtt_file = $temporary_xml_file."_".$pid."_rtt.csv";
		$logs_file = $temporary_xml_file."_".$pid."_logs.log";
		$shortmessages_file = $temporary_xml_file."_".$pid."_shortmessages.log";
		$stat_file = $temporary_xml_file."_".$pid."_.csv";
		$screen_file = $temporary_xml_file."_".$pid."_screen.log";
		$messages_file = $temporary_xml_file."_".$pid."_messages.log";
		// store call specific data in session object
		$_SESSION["s_call_".$pid] =  new Call_Data($pid, $call_id, $test_id, $test_version, $run_id, $log, $monitor, $control_port, $temporary_xml_file, $temporary_csv_file, $std_error_file, $exit_code_file, $error_file, $rtt_file, $logs_file, $messages_file, $shortmessages_file, $stat_file, $screen_file);
		
		echo "$call_id;$party;$pid";
	} else {
		$cdObj = new Call_Data("", $call_id, $test_id, $test_version, $run_id, $log, $monitor, "", $temporary_xml_file, $temporary_csv_file, $std_error_file, $exit_code_file);
		$exit_code = $cdObj->getExitCode();
		$cdObj->updateDatabase();
		if($std_error_file != "" && file_exists($std_error_file)) $std_error = file_get_contents($std_error_file);
	else $std_error = "";
		$cdObj->cleanUp();

		echo "$call_id;$party;exit=$exit_code&std_error&".htmlentities($std_error);
	}
	
	function execute_background($command, $exit_code_file, $error_file, $output_file) {
		// execute as background process, store exit code in a file and prompt pid of command (parent pid of sipp)
		$ppid = shell_exec("export TERM=vt100; (nohup $command ; echo $? > $exit_code_file) 2> $error_file > $output_file & echo $!");

		$ppid = substr($ppid, 0, -1);
		// because $ppid is the parent pid of the sipp call, determine the pid of childprocess
		$pid = shell_exec("ps -o pid,ppid -e | grep \"^[[:space:]]*[0-9]\+[[:space:]]\+".$ppid."\" | awk '{print $1}'");
		$pid = substr($pid, 0, -1);
		return $pid;
	}
	
	// this function tries to determine the control port registered from a specified sipp process 
	function getNewControlPort($pid, $startingPort="8888") {
		// assuming that the portnumber has at least 3 digits
		$highest2digits = substr($startingPort, 0, 2);
		// using netsat to find out if the sipp instance with $pid has a registerd udp port (starting with the first 2 digits of $startingPort, because sipp registers other udp ports too)
		$command = "netstat -p --numeric-ports -l --udp | grep \"".$pid."/sipp\" | grep -o \"\:".$highest2digits."[0-9]\+\" | grep -o \"[0-9]\+\"";
		
		$port = shell_exec($command);
		
		// maybe it takes a while for sipp to open the control port, so wait 0.5 seconds maximum
		$c = 0;
		while($port == "" && $c < 5) {
			// wait 0.1  seconds
			usleep(100000);
			$port = shell_exec($command);
			$c++;
		}
		
		return $port;
	}
	
?>
