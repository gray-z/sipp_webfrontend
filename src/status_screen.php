<?

// This file is displayed in a iframe in run_progress.php during test.
// Here the status monitor screen of a running sipp instance is displayed by polling it from the server frequently.
// Further keyboard action is sent to send_key.php, that forwards it to the running sipp instance.
require_once "read_config.php";
	
$interval = $avp["f"] != "" ? $avp["f"] * 1000 : 1000;
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" /><style type="text/css">
<!--
body {
	margin-left: 0px;
	margin-top: 0px;
	margin-right: 0px;
	margin-bottom: 0px;
}
-->
</style>
<script language="javascript" type="text/javascript" src="js/ajaxConnection.js"></script>
<script language="javascript" type="text/javascript">
	
	var ajax = getAjaxObject();
	var ajax_sendkey = getAjaxObject();
	
	var id;
	var pid;
	var party="<? echo $_GET["party"]; ?>";
	
	var screen_number;
	
	var test_in_progress = false;
	
	// This function gets called from the parent frame (run_progress.php) when polling should begin.
	function start(p_id, p_pid, p_party) {
		id = p_id;
		pid = p_pid;
		party = p_party;
		screen_number = 1;
		test_in_progress = true;
		pollState();
	}

	// Send ajax request to get_screen.php
	function pollState() {
		if (ajax) {
	    	ajax.open("GET", "get_screen.php?pid="+pid+"&screen_number="+screen_number, true);
		    ajax.onreadystatechange = recvState;
	    	ajax.send(null);
		}
	}

	// Callback function of pollState()
	// The server either returns the current monitor screen or an exit code in the form:
	// exit=<exitcode>&stderror=<message from the stderr>
	function recvState() {
        if (ajax.readyState == 4) {
			if (ajax.status == 200) {
				if(ajax.responseText.substr(0, 5) == "exit=") {
					var parts = ajax.responseText.split("&std_error=");
					var exit_code = parts[0].substr(5);
					test_in_progress = false;
					display("Finished with exit code "+exit_code+".<br>");
					display_error(parts[1]);
					parent.stopProcess(id, party, exit_code);
				} else {
	                display(ajax.responseText);
					window.setTimeout("pollState()", <? echo $interval; ?>);
				}
            } else {
                alert('There was an error during the request! Close the window and try again.');
            }
        }
	}
	
	// Display text on screen.
	function display(text) {
		display_error("");
		var c = document.getElementById("content");
		c.innerHTML = document.all ? text.replace(/\n/g, "<br />\n") : text;
		parent.iFrameAdjustHeight(party+"frame");
	}
	
	// Display error message on screen.
	function display_error(text) {
		var c = document.getElementById("error_msg");
		c.innerHTML =  document.all ? text.replace(/\n/g, "<br />\n") : text;
	}
	
	
	
	
	
	
    document.onkeypress = detectEvent;

	function detectEvent(e) {
		var evt = e || window.event;
		var num = evt.charCode || evt.keyCode;
		if(test_in_progress) {
			if(num >= 49 && num <= 57 ) screen_number =  num - 48;
			else if(num == 81 || num == 113) sendKey("q");
			else if(num == 80 || num == 112) sendKey("p");
			else if(num == 42) sendKey("*");
			else if(num == 43) sendKey("+");
			else if(num == 45) sendKey("-");
			else if(num == 47) sendKey("/");

		}
	}
	
	function sendKey(char) {
		if (ajax_sendkey) {
	    	ajax_sendkey.open("GET", "send_key.php?pid="+pid+"&key="+encodeURIComponent(char), true);
		    ajax_sendkey.onreadystatechange = function() {
				 if (ajax_sendkey.readyState == 4) {
					if (ajax_sendkey.status == 200) {
		                if(ajax_sendkey.responseText != "") display(ajax_sendkey.responseText);
        		    } else {
		                alert('There was an error during the request! Close the window and try again.');
        		    }
        		}
			};
	    	ajax_sendkey.send(null);
		}	
	}

	document.onmouseup = gotFocus;
	
	function gotFocus(e) {
		parent.showFocus(party);
	}

</script>
</head>
<body>
<pre  style="font-family:Courier,monospace; font-size:12px; line-height:11px; letter-spacing:-2px;" id="content">

</pre><div id="error_msg"  style="font-family:Courier,monospace; font-size:12px; line-height:11px; letter-spacing:-2px; color:red;"></div>
</body>
</html>
