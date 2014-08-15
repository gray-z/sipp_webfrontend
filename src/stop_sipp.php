<?
// This function tries to stop a running sipp instance. It takes care that the pid belongs to a sipp process.
function stop_sipp($pid) {

	// only instances of sipp may be stopped
	$sipps = shell_exec("ps -e -o pid,comm | grep \"^".$pid."[[:space:]+]sipp$\"");
	if(empty($sipps)) return -1;

	exec("kill -USR1 ".$pid);
	$c=0;
	$running = exec("ps -p $pid | grep $pid");
	while(!empty($running) && $c < 5) {
		// wait 0.2  seconds
		usleep(200000);
		$running = exec("ps -p $pid | grep $pid");
		$c++;
	}
		

	if(!empty($running)) {
		exec("kill -KILL ".$pid);
		$c=0;
		$running = exec("ps -p $pid | grep $pid");
		while(!empty($running) && $c < 5) {
			// wait 0.2  seconds
			usleep(200000);
			$running = exec("ps -p $pid | grep $pid");
			$c++;
		}

		if(!empty($running)) return -1; // Unable to kill process with pid $pid
		else return 1; // Wasn't able to stop process, but process was killed
	} else return 0; // Process stopped
}
?>
