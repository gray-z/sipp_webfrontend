<?

// Class Call_Data contains call specific data (process-id, logfilenames...) of a currently running call. 
// It has methods to store logfiles in the database, delete the logfiles and determine the exitcode after execution.
// When a call is about to be executed, a object of this class is generated and stored in a session variable.
class Call_Data {
	var $pid;
	var $call_id;
	var $test_id;
	var $test_version;
	var $run_id;
	var $log;
	var $monitor;
	var $control_port;
	var $xml_file;
	var $csv_file;
	var $std_error_file;
	var $exit_code_file;
	var $error_file;
	var $rtt_file;
	var $logs_file;
	var $messages_file;
	var $shortmessages_file;
	var $screen_file;
	var $stat_file;
	var $exit_code;
	
	var $screen_line_count;
	var $screen_file_tail;
	
	function Call_Data($pid, $call_id, $test_id, $test_version, $run_id, $log, $monitor, $control_port, $xml_file, $csv_file, $std_error_file, $exit_code_file, $error_file="", $rtt_file="", $logs_file="", $messages_file="", $shortmessages_file="", $stat_file="", $screen_file="") {
		$this->pid = $pid;
		$this->call_id = $call_id;
		$this->test_id = $test_id;
		$this->test_version = $test_version;
		$this->run_id = $run_id;		
		$this->log = $log;
		$this->monitor = $monitor;
		$this->control_port = $control_port;
		$this->xml_file = $xml_file;
		$this->csv_file = $csv_file;
		$this->std_error_file = $std_error_file;
		$this->exit_code_file = $exit_code_file;
		$this->error_file = $error_file;
		$this->rtt_file = $rtt_file;
		$this->logs_file = $logs_file;
		$this->messages_file = $messages_file;
		$this->shortmessages_file = $shortmessages_file;
		$this->stat_file = $stat_file;
		$this->screen_file = $screen_file;
		$this->screen_file_tail = $screen_file.".tail";
		$this->exit_code = "-1";
	}
	
	// updateDatabase is called after the execution of a call has finished. here the logfiles are stored in the datbase.
	function updateDatabase() {
		global $con;
		
		// maybe files are quite big, so increase memory limit
		ini_set('memory_limit', '50M');
		
		// load content of std_error file
		if($this->std_error_file != "" && file_exists($this->std_error_file)) {
			$std_error = addslashes(file_get_contents($this->std_error_file));
		} else $std_error = "";
		
		// load content of error file
		if($this->error_file != "" && file_exists($this->error_file)) {
			$errors = addslashes(file_get_contents($this->error_file));
		} else $errors = "";
		
		// load content of rtt file
		if($this->rtt_file != "" && file_exists($this->rtt_file)) {
			$rtt = file_get_contents($this->rtt_file);
		} else $rtt = "";
		
		// load content of logs file
		if($this->logs_file != "" && file_exists($this->logs_file)) {
			$logs = addslashes(file_get_contents($this->logs_file));
		} else $logs = "";
		
		// load content of shortmessages file
		if($this->shortmessages_file != "" && file_exists($this->shortmessages_file)) {
			$shortmessages = addslashes(file_get_contents($this->shortmessages_file));
		} else $shortmessages = "";
		
		// load content of stat file
		if($this->stat_file != "" && file_exists($this->stat_file)) {
			$stat_file = addslashes(file_get_contents($this->stat_file));
		} else $stat_file = "";
		
		$rcObj = new Run_Call($this->run_id, $this->test_id, $this->test_version, $this->call_id, $std_error, $this->exit_code, $errors, $rtt, $logs, $shortmessages, $stat_file);
		$rcObj->update();
	}
	
	// extract and return exit code from exit code file
	function getExitCode() {
		if($this->exit_code_file != "" && file_exists($this->exit_code_file)) {
			$this->exit_code =  preg_replace("(\r\n|\n|\r)", "", file_get_contents($this->exit_code_file));
		}
		return $this->exit_code;
	}
	

	// deletes all log files and the call specific session object, that were created earlier
	function cleanUp() {
		if($this->xml_file != "" && file_exists($this->xml_file)) unlink($this->xml_file);
		if($this->csv_file != "" && file_exists($this->csv_file)) unlink($this->csv_file);
		if($this->std_error_file != "" && file_exists($this->std_error_file)) unlink($this->std_error_file);
		if($this->exit_code_file != "" && file_exists($this->exit_code_file)) unlink($this->exit_code_file);
		if($this->error_file != "" && file_exists($this->error_file)) unlink($this->error_file);
		if($this->rtt_file != "" && file_exists($this->rtt_file)) unlink($this->rtt_file);
		if($this->logs_file != "" && file_exists($this->logs_file)) unlink($this->logs_file);
		if($this->shortmessages_file != "" && file_exists($this->shortmessages_file)) unlink($this->shortmessages_file);
		if($this->stat_file != "" && file_exists($this->stat_file)) unlink($this->stat_file);
		if($this->screen_file != "" && file_exists($this->screen_file)) unlink($this->screen_file);
		if($this->screen_file_tail != "" && file_exists($this->screen_file_tail)) unlink($this->screen_file_tail);
		
		// Because the messages file normally is very big, it isn't stored in the database. instead it stays in the working
		// directory until the garbage collector deletes it, so that the user can view it after test execution. Yet later
		// the Call_Data object is destroyed, and the filename of the messages file is lost, hence it gets renamed to "messages_<callid>".
		if(file_exists($this->messages_file)) rename($this->messages_file, dirname($this->messages_file)."/messages_".$this->call_id);

		// delete session object
		unset($_SESSION["s_call_".$this->pid]);
	}

}
?>
