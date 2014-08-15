<?
session_start();
require_once "authentication.php";
if(!$admin) { header("location: tests.php"); die("Authentication required!"); }
require_once "read_config.php";
require_once "db.php";
require_once "dbHelper.php";

$id = $_GET["id"];
$action = $_GET["action"];

$test_id = $_GET["test_id"];
$version = $_GET["version"];
$party = $_GET["party"];


// check if there were automatically added slashes before " and ' where it is relevant, and remove them
if(get_magic_quotes_gpc()) {
	$executable = stripslashes($_POST["executable"]);
	$ip_address = stripslashes($_POST["ip_address"]);
	$a_i = stripslashes($_POST["a_i"]);
	$a_m = stripslashes($_POST["a_m"]);
	$a_t = stripslashes($_POST["a_t"]);
	$a_p = stripslashes($_POST["a_p"]);
	$a_r = stripslashes($_POST["a_r"]);
	$a_timeout = stripslashes($_POST["a_timeout"]);
	$extended_parameters = stripslashes($_POST["extended_parameters"]);
} else {
	$executable = $_POST["executable"];
	$ip_address = $_POST["ip_address"];
	$a_i = $_POST["a_i"];
	$a_m = $_POST["a_m"];
	$a_t = $_POST["a_t"];
	$a_p = $_POST["a_p"];
	$a_r = $_POST["a_r"];
	$a_timeout = $_POST["a_timeout"];
	$extended_parameters = $_POST["extended_parameters"];
}

$scenario = $_POST["scenario"];
$monitor = $_POST["monitor"] == "t" ? "t" : "f";
$log = $_POST["log"] == "t" ? "t" : "f";
$a_trace_msg =$_POST["a_trace_msg"] == "t" ? "t" : "f";
$a_trace_shortmsg = $_POST["a_trace_shortmsg"] == "t" ? "t" : "f";
$a_nd = $_POST["a_nd"] == "t" ? "t" : "f";
$a_nr = $_POST["a_nr"] == "t" ? "t" : "f";	
$a_pause_msg_ign = $_POST["a_pause_msg_ign"] == "t" ? "t" : "f";




if($action == "save") {

	$sObj = new Scenario($scenario);
	$sRow = $sObj->get("IF(ISNULL(csv),'f','t') AS csv_exists");
	
	$cObj = new SIPpCall("", $test_id, $version, $party, $sRow->description, $sRow->def=="t" ? $sRow->name :  addslashes($sRow->xml), $sRow->csv_exists=="t"?addslashes($sRow->csv):"NULL", $sRow->def, $sRow->bind_local, $executable, $ip_address, $monitor, $a_i, $a_m, $a_nd, $a_nr, $a_t, $a_p, $a_r, $a_timeout, $a_pause_msg_ign, $a_trace_msg, $a_trace_shortmsg, $extended_parameters, $log, $scenario);
	$id = $cObj->insert();
	
	header( 'Location: test_detail.php' ) ;
	die();
} else if($action == "update") {

	if($scenario != "-1") {
		$sObj = new Scenario($scenario);
		$sRow = $sObj->get("IF(ISNULL(csv),'f','t') AS csv_exists");
		$cObj = new SIPpCall($id, $test_id, $version, "", $sRow->description, $sRow->def=="t" ? $sRow->name : addslashes($sRow->xml), $sRow->csv_exists=="t"?addslashes($sRow->csv):"NULL", $sRow->def, $sRow->bind_local, $executable, $ip_address, $monitor, $a_i, $a_m, $a_nd, $a_nr, $a_t, $a_p, $a_r, $a_timeout, $a_pause_msg_ign, $a_trace_msg, $a_trace_shortmsg, $extended_parameters, $log, $scenario);
		$cObj->update(true);
	} else {
		$cObj = new SIPpCall($id, $test_id, $version, "", "", "", "", "", "", $executable, $ip_address, $monitor, $a_i, $a_m, $a_nd, $a_nr, $a_t, $a_p, $a_r, $a_timeout, $a_pause_msg_ign, $a_trace_msg, $a_trace_shortmsg, $extended_parameters, $log, $scenario);
		$cObj->update();
	}
	header( 'Location: test_detail.php' ) ;
	die();
}

// load call information
if($id != "" && $action != "new") {
	$cObj = new SIPpCall($id);
	$cRow = $cObj->get("IF(ISNULL(csv),'f','t') AS csv_exists");
}

// load test information
$tObj = new Test($test_id);
$tRow = $tObj->get();

// load scenario information
$sObj = new Scenario();
$sRes = $sObj->getAll("id, name","visible='t'");



?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<link href="css/bg.css" rel="stylesheet" type="text/css">
<script language="javascript" type="text/javascript" src="js/helper.js"></script>
<script language="javascript" type="text/javascript">
function checkAndSubmit() {
		var f = document.cForm;
		
		if(checkText("a_m", "Stop after calls", true, NUMBER)
		&& checkText("a_p", "Local port", true, NUMBER)
		&& checkText("a_r", "Call rate", true, NUMBER)
		&& checkText("a_timeout", "Timeaout", true, NUMBER)) {
			f.action = "<? if($action != "new") echo "?action=update&id=".$id."&test_id=".$test_id."&version=".$version."&party=".$party; else echo "?action=save&test_id=".$test_id."&version=".$version."&party=".$party; ?>";
			f.submit();
		}
}
</script>
</head>
<body>
<? require_once "navigation/pagehead.php"; ?>
<form action="" method="post" name="cForm">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="20" height="10">&nbsp;</td>
    <td>&nbsp;</td>
    <td width="10">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="tests.php" class="breadcumbs">Tests overview </a> <strong>&raquo;</strong> <a href="test_detail.php" class="breadcumbs"> modify test (<? echo $tRow->name; ?>) </a><strong>&raquo;</strong> <a href="" class="breadcumbs"><? if($action=="new") echo "Create new call"; else echo "Modify call"; ?> </a></td>
	<td valign="top" class="smalltext">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="javascript: checkAndSubmit()"><br>&raquo;save call <img src="pix/save.gif" width="14" height="14" border="0" alt="view"></a><br></td>
	<td valign="top" class="smalltext">&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top"><table cellpadding="3" cellspacing="2" border="0" class="formtable">
	<tr style="background-color:#EEEEEE;">
	  <td valign="top">Executable</td>
	  <td valign="top"><select name="executable" class="input" style="width:150px;">
		<?
		foreach($executables as $version => $path) {
			?><option value="<? echo $version; ?>" <? if($action != "new" && $cRow->executable == $version) echo "selected=\"selected\""; ?>><? echo $version; ?></option><? 
		} 
		?>
      </select></td>
	  <td valign="top" class="smalltext">&nbsp;</td>
	  </tr>
	<tr style="background-color:#DDDDDD;">
		<td valign="top">Scenario</td>
		<td valign="top"><? if($action!="new") { if($cRow->def == "f") { ?><a href="view_xml.php?callid=<? echo $cRow->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"> view XML</a>   
		
		<? if($cRow->csv_exists == "t") { ?><br><a href="view_csv.php?callid=<? echo $cRow->id; ?>"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"> view CSV</a><? } } ?>
		<? if($cRow->bind_local != "") { ?><br>bind_local: <? echo $cRow->bind_local; } ?>
		
		<? } ?>
		<br>
		<select name="scenario" class="input" style="width:150px;">
		<? if($action != "new") { ?><option value="-1">-- ersetzen mit --</option><? } ?>
		<?
		while($sRow=mysqli_fetch_object($sRes)) {
			?><option value="<? echo $sRow->id; ?>"><? echo $sRow->name; ?></option><?
		}
		?>
        </select>		</td>
		<td valign="top" class="smalltext">&nbsp;</td>
	</tr>
	<tr style="background-color:#EEEEEE;">
      <td valign="top">Remote host </td>
	  <td valign="top"><input type="text" name="ip_address" id="ip_address" value="<? if($action != "new") echo $cRow->ip_address; ?>" class="input" style="width:150px;"></td>
	  <td valign="top" class="smalltext">remote host[:port]<br>
	    The destination host (and port), either a host name or an ip address. Only relevant with a client scenario file. </td>
	</tr>
	<tr style="background-color:#DDDDDD;">
      <td valign="top">Monitor call </td>
	  <td valign="top"><input type="checkbox" name="monitor" id="monitor" value="t" <? if($action != "new" && $cRow->monitor=="t") echo "checked=\"checked\""; ?>></td>
	  <td valign="top" class="smalltext">If this is checked, you recieve realtime feedback during test. Further you  can manipulate the test progress by adjusting the call rate or pausing the traffic. SIPp stores the visual feedback in a file that may grow very fast (depending on the statistics report frequency -f), so take care with long calls. </td>
	</tr>
	<tr style="background-color:#EEEEEE;">
      <td valign="top">Log</td>
	  <td valign="top"><input type="checkbox" name="log" id="log" value="t" <? if($action != "new" && $cRow->log=="t") echo "checked=\"checked\""; ?>></td>
	  <td valign="top" class="smalltext">Should log information be stored? </td>
	</tr>
	<tr style="background-color:#DDDDDD;">
	  <td valign="top">Debug <span class="smalltext">(-trace_msg) </span></td>
	  <td valign="top"><input type="checkbox" name="a_trace_msg" id="a_trace_msg" value="t" <? if($action != "new" && $cRow->a_trace_msg=="t") echo "checked=\"checked\""; ?>></td>
	  <td valign="top" class="smalltext">Displays sent and received SIP messages in &lt;scenario file name&gt;_&lt;pid&gt;_messages.log</td>
	</tr>
	<tr style="background-color:#EEEEEE;">
	  <td valign="top">Short messages <span class="smalltext">(-trace_shortmsg) </span></td>
	  <td valign="top"><input type="checkbox" name="a_trace_shortmsg" id="a_trace_shortmsg" value="t" <? if($action != "new" && $cRow->a_trace_shortmsg=="t") echo "checked=\"checked\""; ?>></td>
	  <td valign="top" class="smalltext"><p>Take care that this option isn't supported by all sipp versions, and thus may lead to an error...<br>
	    Displays sent and received SIP messages as CSV in &lt;scenario file name&gt;_&lt;pid&gt;_shortmessages.log</p>
	    </td>
	  </tr>
	<tr style="background-color:#DDDDDD;">
      <td valign="top">Reply address <span class="smalltext">(-i)</span> </td>
	  <td valign="top"><input type="text" name="a_i" id="a_i" value="<? if($action != "new") echo $cRow->a_i; ?>" class="input" style="width:150px;"></td>
	  <td valign="top" class="smalltext">Set the local IP address for 'Contact:','Via:', and 'From:' headers. Default is primary host IP address.</td>
	</tr>
	<tr style="background-color:#EEEEEE;">
      <td valign="top">Stop after calls <span class="smalltext">(-m)</span> </td>
	  <td valign="top"><input type="text" name="a_m" id="a_m" value="<? if($action != "new") echo $cRow->a_m; ?>" class="input" style="width:150px;"></td>
	  <td valign="top" class="smalltext">Stop the test and exit when 'calls' calls are processed</td>
	</tr>
	<tr style="background-color:#DDDDDD;">
      <td valign="top">No default <span class="smalltext">(-nd) </span></td>
	  <td valign="top"><input type="checkbox" name="a_nd" id="a_nd" value="t" <? if($action != "new" && $cRow->a_nd=="t") echo "checked=\"checked\""; ?>></td>
	  <td valign="top" class="smalltext">No Default. Disable all default behavior of SIPp which are the following:
	  <br>- On UDP retransmission timeout, abort the call by sending a BYE or a CANCEL
	  <br>- On receive timeout with no ontimeout attribute, abort the call by sending a BYE or a CANCEL
	  <br>- On unexpected BYE send a 200 OK and close the call
	  <br>- On unexpected CANCEL send a 200 OK and close the call
	  <br>- On unexpected PING send a 200 OK and continue the call
	  <br>- On any other unexpected message, abort the call by sending a BYE or a CANCEL</td>
	</tr>
	<tr style="background-color:#EEEEEE;">
	  <td valign="top">No retransmission <span class="smalltext">(-nr)</span> </td>
	  <td valign="top"><input type="checkbox" name="a_nr" id="a_nr" value="t" <? if($action != "new" && $cRow->a_nr=="t") echo "checked=\"checked\""; ?>></td>
	  <td valign="top" class="smalltext">Disable retransmission in UDP mode.</td>
	</tr>
	<tr style="background-color:#DDDDDD;">
		<td valign="top">Transport mode <span class="smalltext">(-t) </span></td>
		<td valign="top"><select name="a_t" id="a_t" class="input" style="width:150px;">
		  <option value=""></option>
          <?
			$modi = array("un", "ui", "t1", "tn", "l1", "ln", "c1", "cn");
			foreach($modi as $m) {
				?>
		  <option value="<? echo $m; ?>" <? if($action != "new" && $cRow->a_t == $m) echo "selected=\"selected\""; ?>><? echo $m; ?></option>
		  <?
			}
		?>
        </select></td>
		<td valign="top" class="smalltext">Set the transport mode:
		<br>
		- u1: UDP with one socket (default),
		<br>- un: UDP with one socket per call,
		<br>- ui: UDP with one socket per IP address The IP addresses must be defined in the injection file.
		<br>- t1: TCP with one socket,
		<br>- tn: TCP with one socket per call,
		<br>- l1: TLS with one socket,
		<br>- ln: TLS with one socket per call,
		<br>- c1: u1 + compression (only if compression plugin loaded),
		<br>- cn: un + compression (only if compression plugin loaded). This plugin is not provided with sipp.</td>
	</tr>
	<tr style="background-color:#EEEEEE;">
		<td valign="top">Local port <span class="smalltext">(-p)</span> </td>
		<td valign="top"><input type="text" name="a_p" id="a_p" value="<? if($action != "new") echo $cRow->a_p; ?>" class="input" style="width:150px;"></td>
		<td valign="top" class="smalltext">Set the local port number.  By default, the system tries to find a free port, starting at 5060.</td>
	</tr>
	<tr style="background-color:#DDDDDD;">
      <td valign="top">Call rate <span class="smalltext">(-r)</span> </td>
	  <td valign="top"><input type="text" name="a_r" id="a_r" value="<? if($action != "new") echo $cRow->a_r; ?>" class="input" style="width:150px;"></td>
	  <td valign="top" class="smalltext">Set the call rate (in calls per seconds).  Default is 10.</td>
	</tr>
	<tr style="background-color:#EEEEEE;">
      <td valign="top">Timeout <span class="smalltext">(-timeout)</span> </td>
	  <td valign="top"><input type="text" name="a_timeout" id="a_timeout" value="<? if($action != "new") echo $cRow->a_timeout; ?>" class="input" style="width:150px;"></td>
	  <td valign="top" class="smalltext">Global timeout. Default unit is seconds.  If this option is set, SIPp quits after nb units (-timeout 20s quits after 20 seconds).</td>
	</tr>
	<tr style="background-color:#DDDDDD;">
      <td valign="top">Pause message ignore <span class="smalltext">(-pause_msg_ign)</span></td>
	  <td valign="top"><input type="checkbox" name="a_pause_msg_ign" id="a_pause_msg_ign" value="t" <? if($action == "new" || $cRow->a_pause_msg_ign=="t") echo "checked=\"checked\""; ?>></td>
	  <td valign="top" class="smalltext">Ignore the messages received during a pause defined in the scenario</td>
	</tr>
	<tr style="background-color:#EEEEEE;">
	  <td valign="top">Extended parameters </td>
	  <td valign="top"><textarea name="extended_parameters" rows="3" class="input" id="extended_parameters" style="width:150px;"><? if($action != "new") echo $cRow->extended_parameters; ?></textarea></td>
	  <td valign="top" class="smalltext">Here you can specify additional commandline parameters. </td>
	</tr>

</table>
      <br>
        <br>
      <a href="javascript: checkAndSubmit()">&raquo;save call <img src="pix/save.gif" width="14" height="14" border="0" alt="view"></a><br>
<br>
<br>
</td>
    <td valign="top">&nbsp;</td>
  </tr>
</table>

</form>

</body>
</html>
