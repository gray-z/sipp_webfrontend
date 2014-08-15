<?

// Here the user can display and modify and create test detail data, navigate through versions, display runs.
session_start();
require_once "authentication.php";
require_once "db.php";
require_once "dbHelper.php";
require_once "helper.php";

// this array defines the delay values in seconds, that get listed in the delay dropdown boxes below
$delays = array(0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 15, 20, 30, 40, 50, 60);

$action = $_GET["action"];
$id = get2Session("id");
$version = get2Session("version");

$call_id = $_GET["call_id"];

$run_id = $_GET["run_id"];

if(get_magic_quotes_gpc()) {
	$name = stripslashes($_POST["name"]);
	$description = stripslashes($_POST["description"]);
} else {
	$name = $_POST["name"];
	$description = $_POST["description"];
}

$party = $_GET["party"];

$delaya = $_POST["delaya"];
$delayb = $_POST["delayb"];

if($admin) {
	if($action == "save") {
		$tObj = new Test("", $name, $description);
		$id = $tObj->insert();
		$version = "";
		$_SESSION["s_version"] = "";
		$_SESSION["s_id"] = $id;
	} else if($action == "update") {
		$tObj = new Test($id, $name, $description);
		$tObj->update();
	} else if($action == "duplicate") {
		$tObj = new Test($id);
		$id = $tObj->duplicate($version);
		$version = "";
		$_SESSION["s_version"] = "";
	} else if($action == "delay") {
		$delay=$party=="a"?$delaya:$delayb;
		if($delay!="") {
			$vObj = new Version($id, $version, $delay, $party);
			$vObj->update();
		} 
	} else if($action == "delversion" && $id != "" && $version != "" && $version != "-1") {
			$vObj = new Version($id, $version);
			$vObj->remove();
			$_SESSION["s_version"] = "-1";
	} else if($action == "delrun" && $id != "" && $version != "" && $version != "-1" && $run_id != "") {
			$rObj = new Run($run_id, $id, $version);
			$rObj->remove();
	}
}

if($admin && $call_id != "") {
	$cObj = new SIPpCall($call_id, $id, $version, $party);
	if($action == "up") $cObj->up();
	else if($action == "down") $cObj->down();
	else if($action == "delcall") $cObj->remove();
}

if($action != "new") {
	// load test detail data
	$tObj = new Test($id);
	$row = $tObj->get();
	
	// in case the version number changed, the new number is stored in $_SESSION["s_version"];
	$version = $_SESSION["s_version"];
	
	// load version numbers
	$vRes = $tObj->getVersions();
	if($version == "" || $version == "-1") {
		
		$vRow = mysqli_fetch_object($vRes);
		$version = $vRow->version;
		$_SESSION["s_version"] = $version;
		mysqli_data_seek($vRes, 0);
	}
	
	// load version detail data
	$vObj = new Version($id, $version);
	$vDetailRow = $vObj->get();
	
	// load runs 
	$rObj = new Run("", $id, $version);
	$rRes = $rObj->getAll();
	$run_exists = mysqli_num_rows($rRes) > 0;
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<link href="css/bg.css" rel="stylesheet" type="text/css">
<script language="javascript" type="text/javascript" src="js/helper.js"></script>
<script type="text/javascript" src="js/run.js"></script> 
<script language="javascript" type="text/javascript">

	<? if($admin) { ?>
	
	function checkVersion(url) {
		if(versionConfirm()) location.href = url;
	}
	
	function versionConfirm() {
		<? if(!$run_exists) { ?>
			return true;
		<? } else { ?>
		check = window.confirm("If you continue this action, a new version of this test will be created, because there are already Runs belonging to this version.\nAre you sure you want to go on?");
		return check;
		<? } ?>
	}
	
	

	function checkAndSubmit() {
		var f = document.tForm;
		
		if(checkText("name", "Name", false, TEXT)) {
			f.action = "<? echo $filename; if($action != "new") echo "?action=update&id=".$id; else echo "?action=save"; ?>";
			f.submit();
		}
	}
	
	function delCall(call_id) {
		var check = window.confirm("Are you sure you want to remove this call from the list?");
		if(check) checkVersion("?action=delcall&call_id="+call_id);
	}
	
	function delVersion() {
		var check = window.confirm("Are you sure you want to remove this version (version <? echo $version; ?>)?");
		if(check) location.href = "?action=delversion&version=<? echo $version; ?>";
	}
	
	function delTest() {
		var check = window.confirm("Are you sure you want to remove the entire test?");
		if(check) location.href = "tests.php?action=del&id=<? echo $id; ?>";
	}
	
	function delRun(run_id) {
		var check = window.confirm("Are you sure you want to remove this run?");
		if(check) location.href = "test_detail.php?action=delrun&run_id="+run_id+"&id=<? echo $id; ?>&version=<? echo $version; ?>";
	}
	
	function delay(party) {
		var otherparty = party=='a'?'b':'a';
		if(versionConfirm()) {
			document.getElementById('delay'+otherparty).selectedIndex=0;
			var f = document.cForm;
			f.action = "?action=delay&party="+party;
			f.submit();
		} else {
			<? if($vDetailRow->delay_party == "a") { ?>
				selectCombo("delaya", "<? echo $vDetailRow->delay; ?>");
				document.getElementById("delayb").selectedIndex = 0;						
			<? } else { ?>
				selectCombo("delayb", "<? echo $vDetailRow->delay; ?>");
				document.getElementById("delaya").selectedIndex = 0;										
			<? } ?>
		}
	}
	
	function duplicate() {
		var check = window.confirm("Are you sure that you want to make a duplicate of this test in it's active version (version <? echo $version; ?>)?");
		if(check) location.href = "?action=duplicate&id=<? echo $id; ?>&version=<? echo $version; ?>";
	}
	
	<? } ?>
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
    <td><a href="tests.php" class="breadcumbs">Tests overview </a> <strong>&raquo;</strong> <a href="" class="breadcumbs"><? if($action=="new") echo "Create new test"; else echo "modify test (".$row->name.")" ?> </a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><? if($admin) { ?><a href="test_detail.php?action=new">&raquo; create new test <img src="pix/new.gif" width="14" height="14" border="0" alt="view"> </a> <? if($action != "new") { ?><a href="javascript: duplicate()"> &raquo; duplicate test <img src="pix/dup.gif" width="14" height="14" border="0" alt="duplicate"></a><? } } ?></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td valign="top"><form action="" method="post" name="tForm"><table cellpadding="3" cellspacing="2" border="0" class="formtable">

	<tr style="background-color:#EEEEEE;">
		<td>Name*</td>
		<td><? if($admin) { ?><input name="name" type="text" class="input" id="name" style="width:150px;" value="<? if($action != "new") echo $row->name; ?>" maxlength="100"><? } else echo $row->name; ?></td>
		</tr>
	<tr style="background-color:#DDDDDD;">
		<td>Description</td>
		<td><? if($admin) { ?><textarea name="description" rows="4" class="input" id="description" style="width:150px;"><? if($action != "new") echo $row->description; ?></textarea><? } else echo nl2br($row->description); ?></td>
		</tr>
</table></form>
      <br>
	<? if($admin) { ?><br><a href="javascript: checkAndSubmit()">&raquo;save test <img src="pix/save.gif" width="14" height="14" border="0" alt="save"></a>&nbsp; <? } ?>
		
		
		<? if($action != "new") { ?>
		<? if($admin) { ?>		
		<a href="javascript: delTest()"> &raquo;remove entire test <img src="pix/del.gif" width="14" height="14" border="0" alt="delete"> </a>&nbsp;<br>
		<? } ?>
        <br>
       	<hr>
	    <br>
		<span class="text"><strong>Versions:</strong></span> <?
		$vCount = mysqli_num_rows($vRes);
		while($vRow = mysqli_fetch_object($vRes)) {
			?><a href='?version=<? echo $vRow->version; ?>' <? if($version==$vRow->version) echo "style='font-weight:bold'"; ?>> &raquo;<? echo $vRow->version; ?>&nbsp;</a><?
		}
        ?>
		<br>
        <br>
        <? if($admin) { if($vCount > 1) { ?><a href="javascript: delVersion()">&raquo;remove this version <img src="pix/del.gif" width="14" height="14" border="0" alt="delete"> </a>&nbsp;<? } } ?><a href="javascript: run('run_progress.php',<? echo $id.",".$version; ?>)">&raquo;run this version <img src="pix/execute.gif" width="14" height="14" border="0" alt="run"> </a><br>
        <br>
		<form action="" method="post" name="cForm">
      <table cellpadding="3" cellspacing="2" border="0" class="datatable">
        <tr>
          <td height="32" colspan="11" class="tableheader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="tableheader">A party  <? if($admin) { ?>/ <a href="javascript: checkVersion('add_call.php?action=new&test_id=<? echo $id; ?>&version=<? echo $version; ?>&party=a')">&raquo;add a call</a><? } ?></td>
              <td align="right" class="tableheader">delay
				<? if($admin) { ?>
                <select name="delaya" id="delaya" class="input" onChange="delay('a')">
                    <?
					foreach($delays as $d) {
					?>
					<option value="<? echo $d; ?>" <? if($vDetailRow->delay_party == "a" && $vDetailRow->delay == $d) echo "selected=\"selected\""; ?>><? echo $d; ?> s</option>
					<? } ?>
                </select>
				<? } else if($vDetailRow->delay_party == "a") echo $vDetailRow->delay." s"; else echo "0 s"; ?></td>
              </tr>
          </table></td>
        </tr>
        <tr>
          <td class="tableheader">Version</td>
          <td class="tableheader">Scenario</td>
          <td class="tableheader">XML<? if($admin) { ?> / CSV<? } ?></td>
          <td class="tableheader">type</td>
          <td class="tableheader">destination</td>
          <td class="tableheader">monitor</td>
          <td class="tableheader">Options</td>
          <td class="tableheader">log</td>
          <td class="tableheader">&nbsp;</td>
          <td class="tableheader">&nbsp;</td>
          <td class="tableheader">&nbsp;</td>
        </tr>
		<?
		$aObj = new SIPpCall("", $id, $version, "a");
		$aRes = $aObj->getAll();
		$even = true;

		while($aRow = mysqli_fetch_object($aRes)) {
			if($even) $bgcol = "#EEEEEE";
			else $bgcol = "#DDDDDD";

			$type = $aRow->def=="t" ? "standard" : UACorUAS($aRow->xml);

			?>
	        <tr style="background-color:<? echo $bgcol; ?>;" onMouseOver="this.style.background='#B9FAFD'" onMouseOut="this.style.background='<? echo $bgcol; ?>'">
        	  <td onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? echo $aRow->executable; ?></td>
    	      <td onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? echo $aRow->name; ?></td>
	          <td align="center"><a href="view_xml.php?callid=<? echo $aRow->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? if($admin) { if($aRow->csv_exists == "t") { ?> / <a href="view_csv.php?callid=<? echo $aRow->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } } ?></td>
	          <td onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? echo $type; ?></td>
        	  <td align="center" onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? if($aRow->ip_address!="") echo $aRow->ip_address; else echo "-"; ?></td>
    	      <td align="center" onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? if($aRow->monitor=="t") { ?><img src="pix/check_mark.gif" width="14" height="14" border="0" alt="true"><? } else echo "-"; ?></td>
	          <td onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? echo $aObj->getOptions($aRow); ?></td>
        	  <td align="center" onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? if($aRow->log=="t") { ?><img src="pix/check_mark.gif" width="14" height="14" border="0" alt="true"><? } else echo "-"; ?></td>
    	      <td align="center"><? if($admin) { ?><a href="javascript: checkVersion('?action=up&call_id=<? echo $aRow->id; ?>&party=a')"><img src="pix/sort_up.gif" width="13" height="14" border="0" alt="view"></a><? } ?></td>
	          <td align="center"><? if($admin) { ?><a href="javascript: checkVersion('?action=down&call_id=<? echo $aRow->id; ?>&party=a')"><img src="pix/sort_down.gif" width="13" height="14" border="0" alt="view"></a><? } ?></td>
        	  <td align="center"><? if($admin) { ?><a href="javascript: delCall(<? echo $aRow->id; ?>)"><img src="pix/del.gif" width="14" height="14" border="0" alt="view"></a><? } ?></td>
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
        </tr>
        <tr>
          <td colspan="11" class="tableheader"><table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="tableheader">B party  <? if($admin) { ?>/ <a href="javascript: checkVersion('add_call.php?action=new&test_id=<? echo $id; ?>&version=<? echo $version; ?>&party=b')">&raquo;add a call</a><? } ?></td>
              <td align="right" class="tableheader">delay
				<? if($admin) { ?>
                <select name="delayb" id="delayb" class="input" onChange="delay('b')">
                    <?
					foreach($delays as $d) {
					?>
					<option value="<? echo $d; ?>" <? if($vDetailRow->delay_party == "b" && $vDetailRow->delay == $d) echo "selected=\"selected\""; ?>><? echo $d; ?> s</option>
					<? } ?>
                </select>
				<? } else if($vDetailRow->delay_party == "b") echo $vDetailRow->delay." s"; else echo "0 s"; ?></td>
            </tr>
          </table></td>
        </tr>
        <tr>
          <td class="tableheader">Version</td>
          <td class="tableheader">Scenario</td>
          <td class="tableheader">XML<? if($admin) { ?> / CSV<? } ?></td>
          <td class="tableheader">type</td>
          <td class="tableheader">destination</td>
          <td class="tableheader">monitor</td>
          <td class="tableheader">Options</td>
          <td class="tableheader">log</td>
          <td class="tableheader">&nbsp;</td>
          <td class="tableheader">&nbsp;</td>
          <td class="tableheader">&nbsp;</td>
        </tr>
		<?
		$aObj = new SIPpCall("", $id, $version, "b");
		$aRes = $aObj->getAll();
		$even = true;

		while($aRow = mysqli_fetch_object($aRes)) {
			if($even) $bgcol = "#EEEEEE";
			else $bgcol = "#DDDDDD";
			$type = $aRow->def=="t" ? "standard" : UACorUAS($aRow->xml);

			?>
	        <tr style="background-color:<? echo $bgcol; ?>;" onMouseOver="this.style.background='#B9FAFD'" onMouseOut="this.style.background='<? echo $bgcol; ?>'">
        	  <td onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? echo $aRow->executable; ?></td>
    	      <td onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? echo $aRow->name; ?></td>
	          <td align="center"><a href="view_xml.php?callid=<? echo $aRow->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? if($admin) { if($aRow->csv_exists == "t") { ?> / <a href="view_csv.php?callid=<? echo $aRow->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } } ?></td>
	          <td onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? echo $type; ?></td>
        	  <td align="center" onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? if($aRow->ip_address!="") echo $aRow->ip_address; else echo "-"; ?></td>
    	      <td align="center" onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? if($aRow->monitor=="t") { ?><img src="pix/check_mark.gif" width="14" height="14" border="0" alt="true"><? } else echo "-"; ?></td>
	          <td onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? echo $aObj->getOptions($aRow); ?></td>
        	  <td align="center" onClick="checkVersion('add_call.php?id=<? echo $aRow->id; ?>&test_id=<? echo $id; ?>&version=<? echo $version; ?>');"><? if($aRow->log=="t") { ?><img src="pix/check_mark.gif" width="14" height="14" border="0" alt="true"><? } else echo "-"; ?></td>
    	      <td align="center"><? if($admin) { ?><a href="javascript: checkVersion('?action=up&call_id=<? echo $aRow->id; ?>&party=b')"><img src="pix/sort_up.gif" width="13" height="14" border="0" alt="view"></a><? } ?></td>
	          <td align="center"><? if($admin) { ?><a href="javascript: checkVersion('?action=down&call_id=<? echo $aRow->id; ?>&party=b')"><img src="pix/sort_down.gif" width="13" height="14" border="0" alt="view"></a><? } ?></td>
        	  <td align="center"><? if($admin) { ?><a href="javascript: delCall(<? echo $aRow->id; ?>)"><img src="pix/del.gif" width="14" height="14" border="0" alt="view"></a><? } ?></td>
    	    </tr>
			<?
			$even = !$even;
		}
		?>
      </table>    
		</form>
		<br>
		<hr>
        <br>
          <strong class="text">Runs:</strong><br>
          <br>
		  <? if(mysqli_num_rows($rRes) == 0) { ?>
		  <span class="error">There are currently no runs for this test version.</span>
	  <? } else { ?>
      <table border="0" cellspacing="2" cellpadding="3" class="datatable">
        <tr>
          <th>Timestamp</th>
          <th>success</th>
          <th>&nbsp;</th>
        </tr>
		<?
		$even = true;
		while($rRow = mysqli_fetch_object($rRes)) {
			if($even) $bgcol = "#EEEEEE";
			else $bgcol = "#DDDDDD";
			?>
    	    <tr style="background-color:<? echo $bgcol; ?>;" onMouseOver="this.style.background='#B9FAFD'" onMouseOut="this.style.background='<? echo $bgcol; ?>'">
        	  <td onClick="location.href='run_detail.php?id=<? echo $rRow->id; ?>&test_id=<? echo $id; ?>&test_version=<? echo $version; ?>'"><? echo $rRow->timestamp; ?></td>
	          <td onClick="location.href='run_detail.php?id=<? echo $rRow->id; ?>&test_id=<? echo $id; ?>&test_version=<? echo $version; ?>'"><? echo $rRow->success; ?></td>
    	      <td><? if($admin) { ?><a href="javascript: delRun(<? echo $rRow->id; ?>)"><img src="pix/del.gif" width="14" height="14" border="0" alt="remove"></a><? } ?></td>
        	</tr>
			<?
			$even = !$even;
			}
			?>
      </table>
	  
	  <? } ?>
	  
	  <? } ?>
	  
    <br>
        <br>
        <br>
        <br>
        <br>   </td>
  </tr>
</table>



</body>
</html>
