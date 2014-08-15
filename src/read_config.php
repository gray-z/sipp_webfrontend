<?

// Here the config file is read in the 3 arrays $executables, $avp, and $config.
// Default values are set if necessary, and there is a function getConfigParameters that assembles 
// commandline parameters from the avp section.
// expects: well formatted config.ini.php

$ini_array = parse_ini_file("config.ini.php", true);

$executables = $ini_array["EXECUTABLES"];

$avp = $ini_array["AVP"];

$config = $ini_array["CONFIG"];

// array of not allowed parameters in the avp section of config.ini.php file
$not_allowed = array("i", "m", "nd", "nr", "t", "p", "r", "timeout", "pause_msg_ign", "v", "bind_local", "inf", "sd", "sf", "sn", "stf", "trace_msg", "trace_shortmsg", "trace_screen", "trace_err", "trace_timeout", "trace_stat", "trace_rtt", "trace_logs", "cp");


// default values
if(!isset($config["remove_garbage_after"]) || $config["remove_garbage_after"] == "") $config["remove_garbage_after"] = 3600;
if(!isset($config["csv_separator"]) || $config["csv_separator"] == "") $config["csv_separator"] = ";";
if(!isset($config["admin_pwd"])) $config["admin_pwd"] = "";

if(!isset($avp["f"]) || $avp["f"] == "") $avp["f"] = 1;


function getConfigParameters() {
	global $avp, $not_allowed;
	
	$config_parameters = "";
	
	foreach($avp as $a => $v) {
		$param = true;
		foreach($not_allowed as $p) {
			if($p == $a) $param = false;
		}
		if($param) {
			if(strtoupper($v) == "TRUE") $config_parameters .= "-".$a." ";
			else if(strtoupper($v) != "FALSE") $config_parameters .= "-".$a." ".$v." ";
		}
	}
	return $config_parameters;
}

?>
