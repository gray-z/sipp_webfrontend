<?

 
// This is where all the ajax happens. Brief summary what happens here:

// 1. When the user starts the test, the test-id and test-version is sent to the server (create_run.php),
// where a Run-entry (with success='error') and for each call a Run_Call entry is created in the database,
// so whatever happens then, the test is logged. After that, for each party (and maybe delayed) ...
//
// 2. ... the function startCall() sends call-id, party (a or b),
// run-id, test-id and test-version to the server (exec_call.php), where sipp is executed. If the execution
// is successful the process-id of the running sipp instance is returned (along with call-id and party).
//
// 3. Now the function start() in status_screen.php (running in the iframes) is called, which starts polling
// the the state of the running sipp instance periodically from the server.
//
// 4. When sipp terminates for whatever reason, status_screen.php gets informed, and calls the function stopProcess()
// of this file. If there are more calls to process the whole story starts over at point 2 with the next call.
session_start();

require_once "db.php";
require_once "dbHelper.php";
require_once "helper.php";

$minimum_iframe_height = 320;

$id = get2Session("id");
$version = get2Session("version");


// load version detail data
$vObj = new Version($id, $version);
$vDetailRow = $vObj->get();

// load calls from the a party
$aObj = new SIPpCall("", $id, $version, "a");
$aRes = $aObj->getAll();
$aCount = mysqli_num_rows($aRes);

// load calls from the b party
$bObj = new SIPpCall("", $id, $version, "b");
$bRes = $bObj->getAll();
$bCount = mysqli_num_rows($bRes);

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<script language="javascript" type="text/javascript" src="js/tooltip.js"></script>
<script language="javascript" type="text/javascript" src="js/ajaxConnection.js"></script>

<script language="javascript" type="text/javascript">

// store each call id from A party in an array
var calls_a = new Array(<? echo $aCount; ?>);
<?
	if($aCount > 0) {
		for($i=0; $i<$aCount; $i++) {
			$aRow = mysqli_fetch_object($aRes);
			echo "calls_a[".$i."] = ".$aRow->id."\n";
		}
		mysqli_data_seek($aRes, 0);
	}
?>

// store each call id from B party in an array
var calls_b = new Array(<? echo $bCount; ?>);
<?
	if($bCount > 0) {
		for($i=0; $i<$bCount; $i++) {
			$bRow = mysqli_fetch_object($bRes);
			echo "calls_b[".$i."] = ".$bRow->id."\n";
		}
		mysqli_data_seek($bRes, 0);
	}
?>

// index of the actually processing call for a-party and b-party
var call_idx_a;
var call_idx_b;

// this is set to true after the test gets started
var test_in_progress = false;

// this is set to true when all a-party calls are processed
var finished_a = true;
// this is set to true when all b-party calls are processed
var finished_b = true;

// this is set to either "success", "partly succeeded", "abort" or error "depending" on the exit codes of the calls
var globalExitstate = "success"

// which party should be delayed?
var delay_party = "<? echo $vDetailRow->delay_party; ?>";
// how much seconds should be delayed?
var delay = <? echo $vDetailRow->delay; ?>;

// Call state variables
var CALL_IDLE = 0;
var CALL_START = 1;
var CALL_PROCESSING = 2;
var state_a = CALL_IDLE;
var state_b = CALL_IDLE;


// Abort state variables
var ABORT_IDLE = 0;
var ABORT_START = 1;
var ABORT_DONE = 2;
var abort_state = ABORT_IDLE;

var process_ids = new Array();

var run_id;

// objects for ajax communication
var ajax_a = getAjaxObject();	// a party communication
var ajax_b = getAjaxObject();	// b party communication

var ajax_create_run = getAjaxObject();	// to tell database to create a new run

// this function is triggered from the user by starting the test
function run() {
	if(!test_in_progress) {
		test_in_progress = true;
	
		call_idx_a = 0;
		call_idx_b = 0;
	
		globalExitstate = "success";
	
		createRun();
	}
}
 
// Tell the server to create a Run entry, and for each call a Run_Call entry in the database.
function createRun() {
	if(ajax_create_run) {
    	ajax_create_run.open("GET", "create_run.php?<? echo "test_id=$id&test_version=$version"; ?>", true);
	    ajax_create_run.onreadystatechange = run_created;
	    ajax_create_run.send(null);
	}
}

// Callback function of the ajax request sent in createRun()
function run_created() {
        if (ajax_create_run.readyState == 4) {
			if (ajax_create_run.status == 200) {
                // responsetext hast form: callid;processid;party
				if(isNaN(ajax_create_run.responseText)) alert("Database wasn't able to create a Run. Cannot start test!");
				else {
					if(isNaN(ajax_create_run.responseText)) alert("There was an error trying to create a run in the database. Test aborted. Server returned following message:\n"+ajax_create_run.responseText);
					else {
						run_id = parseInt(ajax_create_run.responseText);
						startTest(run_id);
					}
				}
            } else {
                alert('There was an error during the request! Close the window and try again.');
            }
        }
}

// Start the calls for each party. If a delay is set, delay the call.
function startTest(run_id) {
	if(calls_a.length > 0) {
		if(delay_party == "a") window.setTimeout("startCall("+calls_a[0]+", 'a', "+run_id+")", delay*1000);
		else startCall(calls_a[0], 'a', run_id);
	}

	if(calls_b.length > 0) {
		if(delay_party == "b") window.setTimeout("startCall("+calls_b[0]+", 'b', "+run_id+")", delay*1000);
		else startCall(calls_b[0], 'b', run_id);
	}
}

// Tell the server to execute sipp
function startCall(id, party, runid) {
	if(abort_state != ABORT_IDLE) return;

	var ajax;
	if(party=="a") {
		ajax = ajax_a;
		finished_a = false;
	} else {
		ajax = ajax_b;
		finished_b = false;
	}

	if (ajax) {
		displayStatus("test in progress...", party);
    	ajax.open("GET", "exec_call.php?id="+id+"&party="+party+"&run_id="+runid+"<? echo "&test_id=$id&test_version=$version"; ?>", true);
	    ajax.onreadystatechange = party=="a" ? call_started_a : call_started_b;
	    ajax.send(null);
		if(party=="a") {
			state_a = CALL_START;
		} else {
			state_b = CALL_START;
		}
	}
}

// Callback funtion of the ajax request in startCall()
function call_started_a() {
	call_started(ajax_a);
}

// Callback funtion of the ajax request in startCall()
function call_started_b() {
	call_started(ajax_b);
}

// Callback funtion of the ajax request in startCall()
function call_started(ajax) {
        if (ajax.readyState == 4) {
			if (ajax.status == 200) {
                // responsetext hast form: callid;party;processid_or_exitcode
				var parts = ajax.responseText.split(";");

				// if exit_code was returned ( form: exit=<number> ) an error occured during the attempt to execute the call, thus stopProcess is called
				// otherwise if pid was returned the attempt to execute the call succeeded
				pid_or_exitcode = parts[2];
				if(pid_or_exitcode.substr(0, 5) == "exit=") {
					var exit_parts = pid_or_exitcode.split("&std_error&");
					var exit_code = exit_parts[0].substr(5);
					finalizeProcess(parts[0], parts[1], exit_code, exit_parts[1]);
				} else startProcess(parts[0], parts[1], parts[2]);
				
				if(abort_state == ABORT_START && state_a != CALL_START && state_b != CALL_START) killAllProcesses();
				
            } else {
                alert('There was an error during the request! Close the window and try again.');
            }
        }
}

// This function gets calles when sipp terminated immediatly after execution for some reason.
function finalizeProcess(id, party, exit_str, std_error) {
	var f = party=="a" ? window.frames['aframe'] : window.frames['bframe'];
	f.display("Finished with exit code "+exit_str+"<br>");
	f.display_error(std_error);
	// start over with the next call in the queue
	stopProcess(id, party, exit_str);
}

// Sipp is started, so update UI and start polling the state of the sipp instance frequently.
function startProcess(id, party, pid) {
	if(id == "" || party == "" || pid == "") alert("error"); 
	if(party=="a") state_a = CALL_PROCESSING;
	else if(party=="b") state_b = CALL_PROCESSING;

	// associate processid with the id of the call
	process_ids[id] = pid;
	
	var state_img = document.getElementById("state_"+id);
	state_img.src = "pix/execute_animated.gif";
	
	var stop_img = document.getElementById("stop_"+id);
	stop_img.style.visibility = "visible";
	
	var row = document.getElementById("row_"+id);
	row.style.backgroundColor = "#B9FAFD";
	
	// start polling realtime feedback in specified iframe
	var f = party=="a" ? window.frames['aframe'] : window.frames['bframe'];
	f.start(id, pid, party);
}


// This function gets called from one of the iframes (status_screen.php) when a sipp process terminates.
// UI is updated, and the next call in the queue is started.
function stopProcess(id, party, exit_str) {
	
	if(party=="a") state_a = CALL_IDLE;
	else if(party=="b") state_b = CALL_IDLE;
	
	var exit_code = parseInt(exit_str);
	
	var state_img = document.getElementById("state_"+id);

	if(exit_code == 0 || exit_code == 1 || exit_code == 97 || exit_code == 99) state_img.src = "pix/ok.gif";
	else state_img.src = "pix/del.gif";
	
	generateGlobalExitstate(exit_code);
	
	var stop_img = document.getElementById("stop_"+id);
	stop_img.style.visibility = "hidden";
	
	var row = document.getElementById("row_"+id);
	row.style.backgroundColor = "#EEEEEE";
	
	if(party=="a") {
		if(call_idx_a < calls_a.length-1 && abort_state == ABORT_IDLE) {
			call_idx_a++;
			startCall(calls_a[call_idx_a], "a", run_id);
		} else finished_a = true;
	} else if(party=="b") {
		if(call_idx_b < calls_b.length-1 && abort_state == ABORT_IDLE) {
			call_idx_b++;
			startCall(calls_b[call_idx_b], "b", run_id);
		} else finished_b = true;
	}

	if(finished_a && abort_state == ABORT_IDLE) displayStatus("finished!", "a");
	if(finished_b && abort_state == ABORT_IDLE) displayStatus("finished!", "b");
	
	// finished but not aborted?
	if(finished_a && finished_b && abort_state != ABORT_START) finishTest();
}

// Send request to the server to stop a sipp instance.
var ajax_kill =getAjaxObject();
function killProcess(id) {
	var pid = process_ids[id];
	if (ajax_kill) {
		generateGlobalExitstate(137);
    	ajax_kill.open("GET", "kill_process.php?pid="+pid, true);
	    ajax_kill.onreadystatechange = process_killed;
	    ajax_kill.send(null);
	}
}

// Callback function of the ajax request in killProcess().
function process_killed() {
        if (ajax_kill.readyState == 4) {
			if (ajax_kill.status == 200) {
                alert(ajax_kill.responseText);
            } else {
                alert('There was an error during the request! Close the window and try again.');
            }
        }
}


var ajax_kill_all = getAjaxObject();
function killAllProcesses() {
	if(finished_a && finished_b) return;
	
	var get = "";
	if(!finished_a) get += "pid1="+process_ids[calls_a[call_idx_a]];
	if(!finished_b) {
		if(get != "") get += "&";
		get += "pid2="+process_ids[calls_b[call_idx_b]];
	}
	
	if (ajax_kill_all) {
		generateGlobalExitstate(137);
    	ajax_kill_all.open("GET", "kill_process.php?"+get, true);
	    ajax_kill_all.onreadystatechange = all_processes_killed;
	    ajax_kill_all.send(null);
	}
}

// Callback function of the ajax request in killAllProcesses().
function all_processes_killed() {
	if (ajax_kill_all.readyState == 4) {
		if (ajax_kill_all.status == 200) {
			alert(ajax_kill_all.responseText);
			displayStatus("aborted!", "a");
			displayStatus("aborted!", "b");
			abort_state = ABORT_DONE;
			if(state_a == CALL_IDLE && state_b == CALL_IDLE) finishTest();
		} else {
			alert('There was an error during the request! Close the window and try again.');
		}
	}
}


// abort() is triggerd by the user, when he wants to abort the whole test for some reason.
function abort() {
	if(!test_in_progress || abort_state != ABORT_IDLE) return;
	var check = confirm("Are you sure you want to abort the whole test process?");
	if(check) {
		abort_state = ABORT_START;
		displayStatus("aborting...", "a");
		displayStatus("aborting...", "b");
		if(state_a != CALL_START && state_b != CALL_START) killAllProcesses();
	}
}

// When all the work is done, open run_detail.php in the previous window (send test-success) and close this window.
function finishTest() {
	opener.location.href="run_detail.php?id="+run_id+"&test_id=<? echo $id; ?>&test_version=<? echo $version; ?>&action=setsuccess&success="+globalExitstate;
	if(abort_state == ABORT_IDLE) alert("Finished!")
	close();
}

// Update status text in the UI.
function displayStatus(text, party) {
	var sb = party=="a" ? document.getElementById("satusbar_a") : document.getElementById("satusbar_b");
	if(sb == null) return;
	if(sb.firstChild != null) sb.removeChild(sb.firstChild);
	var t = document.createTextNode(text);
	sb.appendChild(t);
}

// Draw a red border around the iframe when it has focus.
function showFocus(party) {

	var aFrameTD = document.getElementById("aframetd");
	var bFrameTD = document.getElementById("bframetd");

	// all borders black
	if(aFrameTD != null) aFrameTD.style.borderColor = "#000000";
	if(bFrameTD != null) bFrameTD.style.borderColor = "#000000";
	
	if(party=="a" && aFrameTD != null) aFrameTD.style.borderColor = "#FF0000";
	else if(party=="b" && bFrameTD != null) bFrameTD.style.borderColor = "#FF0000";
}


/* globalExitstate is set to "success" only if all calls return with a successful exit code (0 or 99)
 * globalExitstate is set to "partly succeeded" if there is no error and no abort and at least one call patly succeeds (exit 1 or 97)
 * globalExitstate is set to "abort" if there is no error in any call, but at lest one call is abortet by the user (exit 137)
 * globalExitstate is set to "error" if any call returns with a bad exit code (-1 or 255)
*/
function generateGlobalExitstate(exitcode) {
	if(exitcode == -1 || exitcode == 255) globalExitstate = "error";
	else if(globalExitstate == "success" && (exitcode == 1 || exitcode == 97)) globalExitstate = "partly succeeded";
	else if(globalExitstate != "error" && exitcode == 137) globalExitstate = "abort";
}

// Remove red border around iframe when it looses focus.
function showNoFocus(e) {
	showFocus("");
}
document.onmouseup = showNoFocus;

// Adjust iframe height to it's content, so that no scrollbars are displayed.
function iFrameAdjustHeight(iframeid) {
	var browser = navigator.userAgent.toLowerCase();
	isIE = ((browser .indexOf( "msie" ) != -1) && (browser .indexOf( "opera" ) == -1) && (browser .indexOf( "webtv" ) == -1));

	var i_doc = getDocument(document.getElementById(iframeid));
	var h1 = i_doc.body.scrollHeight;
	var h2 = i_doc.body.offsetHeight;

	var h;
	if(isIE) h=h1;
	else h=h2;
	h += 25;
	if(h < <? echo $minimum_iframe_height; ?>) h = <? echo $minimum_iframe_height; ?>;
	document.getElementById(iframeid).style.height = h+"px";
}

// Get the document object of an iframe.
function getDocument(iframeNode){
	var doc = iframeNode.contentDocument || // W3
	(
		(iframeNode.contentWindow)&&(iframeNode.contentWindow.document)
	) ||  // IE
	(
		(iframeNode.name)&&(document.frames[iframeNode.name])&&
		(document.frames[iframeNode.name].document)
	) || null;
	return doc;
}

</script>


</head>
<body onUnload="if(!finished_a || !finished_b) alert('The Test has not finished yet. There may reside unterminated processes on the server.')">



<table border="0" cellspacing="0" cellpadding="0">
  <tr>
  	<? if($aCount > 0) { ?>
    <td width="5" height="10">&nbsp;</td>
    <td valign="top"><a href="javascript: run()" id="run_button">&raquo; Run test now <img src="pix/execute.gif" width="14" height="14" border="0" alt="run"></a> <a href="javascript: abort()" id="abort_button">&raquo; Abort test <img src="pix/del.gif" width="14" height="14" border="0" alt="abort"></a><span style="line-height:20px;">&nbsp;</span>
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
            <tr>
              <td class="tableheader">A party - <span style="font-weight:bold; color:#006633; font-style:italic;" id="satusbar_a">idle</span></td>
              <td align="right" class="tableheader"><? if($vDetailRow->delay_party == "a" && $vDetailRow->delay > 0) echo "delay ".$vDetailRow->delay." sec"; ?></td>
        	</tr>
		</table>

		<table width="100%" border="0" cellpadding="3" cellspacing="2" class="datatable_noclick">
      
      		<tr>
        		<td class="tableheader">&nbsp;</td>
        		<td class="tableheader">Version</td>
		        <td class="tableheader">Scenario</td>
		        <td class="tableheader">type</td>
		        <td class="tableheader">destination</td>
		        <td class="tableheader">monitor</td>
		        <td class="tableheader">Options</td>
			    <td class="tableheader">&nbsp;</td>
      		</tr>
			<?
			$even = true;
		
			while($aRow = mysqli_fetch_object($aRes)) {
				if($even) $bgcol = "#EEEEEE";
				else $bgcol = "#DDDDDD";
	
				$type = $aRow->def=="t" ? "standard" : UACorUAS($aRow->xml);
	
				?>
					<tr style="background-color:<? echo $bgcol; ?>;" id="row_<? echo $aRow->id; ?>">
		        		<td><img src="pix/ready.gif" width="14" height="14" border="0" id="state_<? echo $aRow->id; ?>"></td>
				        <td><? echo $aRow->executable; ?></td>
				        <td><? echo $aRow->name; ?></td>
		        		<td><? echo $type; ?></td>
				        <td align="center"><? if($aRow->ip_address!="") echo $aRow->ip_address; else echo "-"; ?></td>
				        <td align="center"><p><? if($aRow->monitor=="t") { ?><img src="pix/check_mark.gif" width="14" height="14" border="0" alt="true"><? } else echo "-"; ?></p></td>
		       			<td align="left"><? echo shorten($aObj->getOptions($aRow), 10); ?></td>
					    <td align="left"><img src="pix/del.gif" width="14" height="14" border="0" style="visibility:hidden; cursor:pointer;" onClick="killProcess(<? echo $aRow->id; ?>)" id="stop_<? echo $aRow->id; ?>" onMouseOut="noTooltip()" onMouseOver="tooltip('abort this call')"></td>
					</tr>
				<?
				$even = !$even;
			}
			?>
    	</table>
		<br></td>
	<? } 
	   if($bCount > 0) { 
	?>
    <td width="5">&nbsp;</td>
    <td valign="top"><? if($aCount == 0) { ?><a href="javascript: run()">&raquo; Run test now <img src="pix/execute.gif" width="14" height="14" border="0" alt="run"></a> <a href="javascript: abort()" id="abort_button">&raquo; Abort test <img src="pix/del.gif" width="14" height="14" border="0" alt="abort"></a><? } ?><span style="line-height:20px;">&nbsp;</span>
		<table width="100%" border="0" cellspacing="0" cellpadding="0">
		  <tr>
        	<td class="tableheader">B party - <span class="error" style="font-weight:bold; color:#006633; font-style:italic;" id="satusbar_b">idle</span></td>
        	<td align="right" class="tableheader"><? if($vDetailRow->delay_party == "b" && $vDetailRow->delay > 0) echo "delay ".$vDetailRow->delay." sec"; ?></td>
		  </tr>
    	</table>






		<table width="100%" border="0" cellpadding="3" cellspacing="2" class="datatable_noclick">

      		<tr>
		        <td class="tableheader">&nbsp;</td>
		        <td class="tableheader">Version</td>
        		<td class="tableheader">Scenario</td>
		        <td class="tableheader">type</td>
        		<td class="tableheader">destination</td>
		        <td class="tableheader">monitor</td>
        		<td class="tableheader">Options</td>
	            <td class="tableheader">&nbsp;</td>
      		</tr>
			<?
			$even = true;

			while($bRow = mysqli_fetch_object($bRes)) {
				if($even) $bgcol = "#EEEEEE";
				else $bgcol = "#DDDDDD";

				$type = $bRow->def=="t" ? "standard" : UACorUAS($bRow->xml);

				?>
				<tr style="background-color:<? echo $bgcol; ?>;" id="row_<? echo $bRow->id; ?>">
			        <td><img src="pix/ready.gif" width="14" height="14" border="0" alt="view" id="state_<? echo $bRow->id; ?>"></td>
		    	    <td><? echo $bRow->executable; ?></td>
		        	<td><? echo $bRow->name; ?></td>
			        <td><? echo $type; ?></td>
			        <td align="center"><? if($bRow->ip_address!="") echo $bRow->ip_address; else echo "-"; ?></td>
			        <td align="center"><p><? if($bRow->monitor=="t") { ?><img src="pix/check_mark.gif" width="14" height="14" border="0" alt="true"><? } else echo "-"; ?></p></td>
       				<td align="left"><? echo shorten($aObj->getOptions($bRow), 10); ?></td>
				    <td align="left"><img src="pix/del.gif" width="14" height="14" border="0" style="visibility:hidden; cursor:pointer;" onClick="killProcess(<? echo $bRow->id; ?>)" id="stop_<? echo $bRow->id; ?>" onMouseOut="noTooltip()" onMouseOver="tooltip('abort this call')"></td>
				</tr>
				<?
				$even = !$even;
			}
			?>
		</table>
	<br></td>
	<? } ?>
  </tr>
  <tr>
	<? if($aCount > 0) { ?>
    <td>&nbsp;</td>
    <td height="320" valign="top" id="aframetd" style="border-width:1px; border-style:solid; border-color:#000000"><iframe src="status_screen.php?party=a" name="aframe" id="aframe" frameborder="0" height="<? echo $minimum_iframe_height; ?>" width="480" marginheight="0" marginwidth="0" scrolling="no"></iframe></td>
	<? } 
	   if($bCount > 0) { 
	?>
    <td>&nbsp;</td>
    <td  id="bframetd" valign="top" style="border-width:1px; border-style:solid; border-color:#000000"><iframe src="status_screen.php?party=b" name="bframe" id="bframe" frameborder="0" height="<? echo $minimum_iframe_height; ?>" width="480" marginheight="0" marginwidth="0" scrolling="no"></iframe></td>
	<? } ?>
  </tr>
</table>



</body>
</html>
