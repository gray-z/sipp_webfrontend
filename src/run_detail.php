<?

// On this page, the user can display and manage runs, download logfiles, run tests again...
// This page is also called immediatly after a test has been executed. Then the final success of the test is stored in the database.
session_start();
require_once "authentication.php";
require_once "garbagecollector.php";
require_once "db.php";
require_once "dbHelper.php";
require_once "sys_get_temp_dir.php";
require_once "helper.php";

// description of the exit codes. These are displayed as tooltip.
$exit_description = array("0"=>"all calls were successful", "1"=>"at least one call failed", "97"=>"exit on internal command. Calls may have been processed. Also exit on global timeout (see -timeout_global option)", "99"=>"normal exit without calls processed", "-1"=>"fatal error", "137"=>"process was killed with a SIGKILL (-9) signal", "255"=>"unknown error");


$run_id = get2Session("id");
$test_id = get2Session("test_id");
$test_version = get2Session("test_version");

$action = $_GET["action"];
$success = $_GET["success"];

$todel_id = $_GET["todel_id"];

if($action == "setsuccess" && $test_id != "" && $test_version != "" && $test_version != "-1" && $run_id != "" && $success != "") {
	$rObj = new Run($run_id, $test_id, $test_version, "", $success);
	$rObj->update();
} else if($admin && $action == "delrun" && $test_id != "" && $test_version != "" && $test_version != "-1" && $todel_id != "" && $run_id != "") {
	$rObj = new Run($todel_id, $test_id, $test_version);
	$rObj->remove();
	
	if($todel_id == $run_id) {
		header("location: test_detail.php?id=$test_id&version=$version");
		die();
	}
}

// load test detail data
$tObj = new Test($test_id);
$tRow = $tObj->get();

// load version detail data
$vObj = new Version($test_id, $test_version);
$vRow = $vObj->get();

// load run detail data
$rObj = new Run($run_id, $test_id, $test_version);
$rRow = $rObj->get();

// load runs 
$runsRes = $rObj->getAll();



?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<link href="css/bg.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="js/run.js"></script> 
<script type="text/javascript" src="js/tooltip.js"></script> 
<script language="javascript" type="text/javascript">
	function delRun(run_id) {
		var check = window.confirm("Are you sure you want to remove this run?");
		if(check) location.href = "run_detail.php?action=delrun&todel_id="+run_id+"&id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>";
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
    <td><a href="tests.php" class="breadcumbs">Tests overview </a> <strong>&raquo;</strong> <a href="test_detail.php?version=<? echo $test_version; ?>&id=<? echo $test_id; ?>" class="breadcumbs">modify test (<? echo  $tRow->name; ?> / version <? echo $test_version; ?>) </a> <strong>&raquo;</strong> <a href="" class="breadcumbs">show run (<? echo  $rRow->timestamp; ?>) </a> </td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><? if($admin) { ?><a href="javascript: delRun(<? echo $run_id; ?>)">&raquo;remove this run <img src="pix/del.gif" width="14" height="14" border="0" alt="delete"> </a>&nbsp;<? } ?><a href="javascript: run('run_progress.php',<? echo $test_id.",".$test_version; ?>)">&raquo;run test again <img src="pix/execute.gif" width="14" height="14" border="0" alt="run"> </a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top"><table cellpadding="3" cellspacing="2" border="0" class="datatable_noclick">
        <tr>
          <td height="32" colspan="15" class="tableheader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="tableheader">A party</td>
              <td align="right" class="tableheader"><? if($vRow->delay_party == "a" && $vRow->delay > 0) echo "delay ".$vRow->delay." sec"; ?></td>
              </tr>
          </table></td>
        </tr>
        <tr>
          <td class="tableheader">Timestamp</td>
          <td class="tableheader">Executeable</td>
          <td class="tableheader">Scenario</td>
          <td class="tableheader">type</td>
          <td class="tableheader">IP&nbsp;address </td>
          <td class="tableheader">monitor</td>
          <td class="tableheader">Options</td>
          <td class="tableheader">exit </td>
          <td align="center" class="tableheader">std<br>
          error</td>
          <td class="tableheader">rtt</td>
          <td class="tableheader">msg</td>
          <td class="tableheader">short<br>
          msg</td>
          <td class="tableheader">log</td>
          <td class="tableheader">stat</td>
          <td class="tableheader">err</td>
        </tr>
		<?
		// determine former working directory to check if there are (still) trace_messages-files
		$working_dir = get_working_dir($test_id, $test_version, $run_id);
		
		$rcObj = new Run_Call($run_id, $test_id, $test_version);
		$rcRes = $rcObj->getAll("a");
		
		$cObj = new SIPpCall();

		$even = true;

		while($rcRow = mysqli_fetch_object($rcRes)) {
			if($even) $bgcol = "#EEEEEE";
			else $bgcol = "#DDDDDD";

			$type = $rcRow->def=="t" ? "standard" : UACorUAS($rcRow->xml);
			
			$messages_file = $working_dir."messages_".$rcRow->call_id;
		?>
        <tr style="background-color:<? echo $bgcol; ?>;">
          <td><? echo $rcRow->timestamp; ?></td>
          <td><? echo $rcRow->executable; ?></td>
          <td><? echo $rcRow->name; ?></td>
          <td><? echo $type; ?></td>
          <td align="center"><? echo $rcRow->ip_address; ?></td>
          <td align="center"><? if($rcRow->monitor=="t") { ?><img src="pix/check_mark.gif" width="14" height="14" border="0" alt="true"><? } else echo "-"; ?></td>
          <td><? echo shorten($cObj->getOptions($rcRow), 10); ?></td>
          <td align="center" onMouseOut="noTooltip()" onMouseOver="tooltip('<? echo $exit_description[$rcRow->exit_code]; ?>')"><? echo $rcRow->exit_code; ?></td>
          <td align="center"><? echo shorten($rcRow->std_error, 10); ?></td>
          <td align="center"><? if($rcRow->rtt != "") { ?><a href="view_csv.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=rtt"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if(file_exists($messages_file)) { ?><a href="view_messages.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if($rcRow->shortmessages != "") { ?><a href="view_csv.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=shortmessages"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if($rcRow->log != "") { ?><a href="view_text.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=log"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if($rcRow->stat != "") { ?><a href="view_csv.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=stat"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if($rcRow->errors != "") { ?><a href="view_text.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=errors"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
        </tr>
		<? 
		$even = !$even;
		}
		?>
        <tr>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
          <td>&nbsp;</td>
        </tr>
		<?
		$rcRes = $rcObj->getAll("b");
		if(mysqli_num_rows($rcRes) > 0) {
		?>
        <tr>
          <td colspan="15" class="tableheader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="tableheader">B party</td>
              <td align="right" class="tableheader"><? if($vRow->delay_party == "b" && $vRow->delay > 0) echo "delay ".$vRow->delay." sec"; ?></td>
            </tr>
          </table></td>
        </tr>
        <tr>
          <td class="tableheader">Timestamp</td>
          <td class="tableheader">Executeable</td>
          <td class="tableheader">Scenario</td>
          <td class="tableheader">type</td>
          <td class="tableheader">IP&nbsp;address </td>
          <td class="tableheader">monitor</td>
          <td class="tableheader">Options</td>
          <td class="tableheader">exit </td>
          <td align="center" class="tableheader">std<br>
          error</td>
          <td class="tableheader">rtt</td>
          <td class="tableheader">msg</td>
          <td class="tableheader">short<br>
          msg</td>
          <td class="tableheader">log</td>
          <td class="tableheader">stat</td>
          <td class="tableheader">err</td>
        </tr>
		<?
		
		$even = true;

		while($rcRow = mysqli_fetch_object($rcRes)) {
			if($even) $bgcol = "#EEEEEE";
			else $bgcol = "#DDDDDD";

			$type = $rcRow->def=="t" ? "standard" : UACorUAS($rcRow->xml);
			
			$messages_file = $working_dir."messages_".$rcRow->call_id;
		?>
        <tr style="background-color:<? echo $bgcol; ?>;">
          <td><? echo $rcRow->timestamp; ?></td>
          <td><? echo $rcRow->executable; ?></td>
          <td><? echo $rcRow->name; ?></td>
          <td><? echo $type; ?></td>
          <td align="center"><? echo $rcRow->ip_address; ?></td>
          <td align="center"><? if($rcRow->monitor=="t") { ?><img src="pix/check_mark.gif" width="14" height="14" border="0" alt="true"><? } else echo "-"; ?></td>
          <td><? echo shorten($cObj->getOptions($rcRow), 10); ?></td>
		  <td align="center" onMouseOut="noTooltip()" onMouseOver="tooltip('<? echo $exit_description[$rcRow->exit_code]; ?>')"><? echo $rcRow->exit_code; ?></td>
          <td align="center"><? echo shorten($rcRow->std_error, 10); ?></td>
          <td align="center"><? if($rcRow->rtt != "") { ?><a href="view_csv.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=rtt"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if(file_exists($messages_file)) { ?><a href="view_messages.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if($rcRow->shortmessages != "") { ?><a href="view_csv.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=shortmessages"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if($rcRow->log != "") { ?><a href="view_text.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=log"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if($rcRow->stat != "") { ?><a href="view_csv.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=stat"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
          <td align="center"><? if($rcRow->errors != "") { ?><a href="view_text.php?run_id=<? echo $run_id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>&call_id=<? echo $rcRow->call_id; ?>&field=errors"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } else echo "&nbsp;"; ?></td>
        </tr>
		<? 
		$even = !$even;
		}
		}
		?>
      </table>    
      <br>
	<br>
	<strong class="text">Runs:</strong><br>
    <br>
      <table border="0" cellspacing="2" cellpadding="3" class="datatable">
        <tr>
          <th>Timestamp</th>
          <th>success</th>
		  <th>&nbsp;</th>
        </tr>
		<?
		$even = true;
		while($runsRow = mysqli_fetch_object($runsRes)) {
			if($even) $bgcol = "#EEEEEE";
			else $bgcol = "#DDDDDD";
			?>
    	    <tr style="background-color:<? echo $bgcol; ?>;" onMouseOver="this.style.background='#B9FAFD'" onMouseOut="this.style.background='<? echo $bgcol; ?>'">
        	  <td onClick="location.href='run_detail.php?id=<? echo $runsRow->id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>'" <? if($runsRow->id == $run_id) echo "style=\"font-weight:bold;\""; ?>><? echo $runsRow->timestamp; ?></td>
	          <td onClick="location.href='run_detail.php?id=<? echo $runsRow->id; ?>&test_id=<? echo $test_id; ?>&test_version=<? echo $test_version; ?>'" <? if($runsRow->id == $run_id) echo "style=\"font-weight:bold;\""; ?>><? echo $runsRow->success; ?></td>
			  <td><? if($admin) { ?><a href="javascript: delRun(<? echo $runsRow->id; ?>)"><img src="pix/del.gif" width="14" height="14" border="0" alt="remove"></a><? } ?></td>
        	</tr>
			<? 
			$even = !$even;
			}
			?>
      </table>

    <br>
	<br>	</td></tr>
</table>



</body>
</html>
