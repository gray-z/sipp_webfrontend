<?
// This file removes all sipp directories in the system's temp folder that have not been modified for remove_garbage_after seconds.
// Further all sipp processes older than remove_garbage_after seconds get killed.
// You can adjust the remove_garbage_after value in the config.ini.php.

require_once "read_config.php";
require_once "sys_get_temp_dir.php";
require_once "stop_sipp.php";

$temp_dir = sys_get_temp_dir();
if(substr($temp_dir, -1) != "/") $temp_dir .= "/";

$files = glob($temp_dir."sipp_*");

foreach($files as $file) {
	if(is_dir($file)) {
		if((time()-filectime($file)) > $config["remove_garbage_after"]) deltree($file);
	}
}



// get all sipp currently running processes
$instances = shell_exec("ps -e -o pid,etime,command | grep \"sipp\" | grep -v \"\([[:space:]]grep[[:space:]]\)\|\([[:space:]]ps[[:space:]]\)\|\(nohup[[:space:]]\)\"");
$instances_array = split("\n", $instances);

// kill each process older than remove_garbage_after seconds
foreach($instances_array as $inst) {
	$found = array();

	ereg("^([0-9]+)[ \n\r\t]+([0-9]+(\-[0-9]+)?(:[0-9]+)+)", $inst, $found);

	if(count($found) > 2) {
		$pid = $found[1];
		$uts = string2time($found[2]);
		if($uts > $config["remove_garbage_after"]) stop_sipp($pid);
	}
}


function string2time($str) {
	$t = split("-", $str);

	if(count($t) > 1) $day = $t[0];
	else $day = 0;

	$t = split(":", $str);

	if(count($t) == 3) {
		$hour = $t[0];
		$minute = $t[1];
		$second = $t[2];
	} else {
		$hour = 0;
		$minute = $t[0];
		$second = $t[1];	
	}

	return $day*86400+$hour*3600+$minute*60+$second;
}

function deltree($f) {
  if (is_dir($f)) {
    foreach(glob($f.'/*') as $sf) {
      if (is_dir($sf) && !is_link($sf)) {
        deltree($sf);
      } else {
        unlink($sf);
      } 
    } 
  }
  rmdir($f);
}

?>
