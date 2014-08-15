<?
session_start();
require_once "authentication.php";
require_once "stop_sipp.php";
require_once "garbagecollector.php";
require_once "read_config.php";

function getSymbolByQuantity($bytes) {
   	$symbols = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
    $exp = floor(log($bytes)/log(1024));
   	return sprintf('%.2f '.$symbols[$exp], ($bytes/pow(1024, floor($exp))));
}

if($_GET["kill_pid"] != "") stop_sipp($_GET["kill_pid"]);

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<link href="css/bg.css" rel="stylesheet" type="text/css">
<script language="javascript" type="text/javascript">
	function stop_process(pid) {
		var check = window.confirm("Are you sure you want to stop this process?");
		if(check) location.href = "?kill_pid="+pid;
	}
</script>
</head>
<body>
<? require_once "navigation/pagehead.php"; ?>
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="20" height="10">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="info.php" class="breadcumbs">System information </a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td class="text"><strong>Currently running SIPp instances :</strong><br>
      <br>
    <?
	$instances = shell_exec("ps -e -o pid,lstart,etime,command | grep \"\(PID[[:space:]]\+STARTED\)\|sipp\" | grep -v \"\([[:space:]]grep[[:space:]]\)\|\([[:space:]]ps[[:space:]]\)\|\(nohup[[:space:]]\)\"");
	if($instances == "") $instances = "none";
	$instances_array = split("\n", $instances);
	echo "<table border='0' cellpadding='0' cellspacing='0'>";
	foreach($instances_array as $inst) {
		$found = array();

		if(ereg("^[[:space:]]*([0-9]+)[ \n\r\t]+", $inst, $found) !== false) {
			echo "<tr><td valign='top'>";
			if($admin) echo "<a href=\"javascript: stop_process('".$found[1]."')\"><img src='pix/del.gif' width='14' height='14' border='0' alt='kill'>&nbsp;</a>";
			else echo "&nbsp;";
			echo "</td><td valign='top'><pre>".$inst."</pre></td></tr>";
		} else echo "<tr><td valign='top'>&nbsp;</td><td valign='top'><pre>".$inst."</pre></td></tr>";
	}
	echo "</table>";
	?>
    <br>
    <hr>
    <strong>Free disk space:</strong><br>
    <br>
    <? 	$df = disk_total_space("/"); echo getSymbolByQuantity($df); ?><br>
    <br>
	<hr>
    <strong>SIPp versions:</strong><br>
    <br>
	
	<?
	foreach($executables as $version => $path) {
		echo $version." : ".$path." -v<br>";
		$verbose = shell_exec($path." -v");
		if($verbose == "") $verbose = "WARNING: $path doesn't exist!";
		echo "<pre>".$verbose."</pre><hr>";
	}
	?>
    <br></td>
  </tr>
</table>



</body>
</html>
