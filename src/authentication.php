<?
 
// This file is included everywhere, where admin should have special rights. $admin=true indicates that admin is logged in.

// load config file to get the admin password
require_once "read_config.php";

// if there is no password specified, everybody is admin
if($config["admin_pwd"] == "") $admin = true;
else {
	if($_GET["action"]=="login" && isset($_POST["pwd"])) {
		if($_POST["pwd"] == $config["admin_pwd"]) {
			$_SESSION["s_authenticated"] = true;
		} else {
			$_SESSION["s_authenticated"] = false;
		}
	} else if($_GET["action"]=="logout") $_SESSION["s_authenticated"] = false;

	$admin = isset($_SESSION["s_authenticated"]) ? $_SESSION["s_authenticated"] : false;
}

?>
