<?

// Here the navigation of the webfrontend is created
require_once "read_config.php";

$titles = array();
$widths = array();
$urls = array();



function getFilename($path) {
	$pos = strrpos($path, "/");
	if(!($pos === false)) $path = substr($path, $pos+1);
	
	$pos = strpos($path, "?");
	if($pos === false) return $path;
	else return substr($path, 0, $pos); 
}

function addTab($title, $width, $url) {
	global $titles;
	global $widths;
	global $urls;
	$titles[] = $title;
	$widths[] = $width;
	$urls[] = $url;
}

function showTabs() {
	global $titles, $widths, $urls, $admin;

	// Determine which tab is active
	$tabCount = count($titles);
	$thisfile = strtolower(getFilename($_SERVER['PHP_SELF']));
	for($i=0; $i<$tabCount; $i++) {
		if($thisfile==strtolower(getFilename($urls[$i]))) {
			$_SESSION["s_activeTabIndex"] = $i;
		}
	}
	$activeTabIndex = $_SESSION["s_activeTabIndex"];
	
	
	?>
	<form action="?action=login" method="post" name="loginForm"><input type="hidden" name="pwd" value="" /></form>
	<script type="text/javascript">
		function subm() {
			var f = document.loginForm;
			f.pwd.value = document.getElementById("pwd_field").value;
			f.submit();
		}
	</script>
	<table cellpadding="0" cellspacing="0" border="0" width="100%" style="position:absolute; top:69px; left:0px;">
		<tr>
			<td width="20"><img src="pix/spacer.gif" border="0" width="20" height="32"></td>
			<?
			for($i=0; $i<$tabCount; $i++) {
				if($i == $activeTabIndex-1) { ?>
				<td width="<? echo $widths[$i]+3; ?>">
					<table cellpadding="0" cellspacing="0" border="0" onClick="location.href='<? echo $urls[$i]; ?>'" style="background:#eff0f4; cursor:pointer;" onmouseover="this.style.background='#e5e8f8'" onmouseout="this.style.background='#eff0f4'">
					<tr>
						<td><img src="pix/spacer.gif" border="0" width="1" height="32"><img src="pix/bg_left.gif" border="0" width="4" height="32"></td>
						<td class="tab" style="width:<? echo $widths[$i]; ?>px; background-image:url(pix/bg_body.gif); background-repeat:repeat-x; background-position:bottom"><? echo $titles[$i]; ?></td>
					</tr>
					</table>
				</td>
				<? } else if($i == $activeTabIndex) { ?>

				<td width="<? echo $widths[$i]+4; ?>">
					<table cellpadding="0" cellspacing="0" border="0" onClick="location.href='<? echo $urls[$i]; ?>'" style="cursor:pointer;">
					<tr>
						<td><img src="pix/vg_left.gif" border="0" width="4" height="32"></td>
						<td class="tab" style="width:<? echo $widths[$i]; ?>px; background-image:url(pix/vg_body.gif); background-repeat:repeat-x; background-position:bottom"><? echo $titles[$i]; ?></td>
						<td><img src="pix/vg_right.gif" border="0" width="4" height="32"></td>
					</tr>
					</table>
				</td>
				<? } else if($i == $activeTabIndex+1) { ?>
				<td width="<? echo $widths[$i]+2; ?>">
					<table cellpadding="0" cellspacing="0" border="0" onClick="location.href='<? echo $urls[$i]; ?>'" style="background:#eff0f4; cursor:pointer;" onmouseover="this.style.background='#e5e8f8'" onmouseout="this.style.background='#eff0f4'">
					<tr>
						<td class="tab" style="width:<? echo $widths[$i]; ?>px; background-image:url(pix/bg_body.gif); background-repeat:repeat-x; background-position:bottom"><? echo $titles[$i]; ?></td>	
						<td><img src="pix/bg_right.gif" border="0" width="4" height="32"></td>
					</tr>
					</table>
				</td>
				<? } else { ?>
				<td width="<? echo $widths[$i]+5; ?>">
					<table cellpadding="0" cellspacing="0" border="0" onClick="location.href='<? echo $urls[$i]; ?>'" style=" background:#eff0f4; cursor:pointer;" onmouseover="this.style.background='#e5e8f8'" onmouseout="this.style.background='#eff0f4'">
					<tr>
						<td><img src="pix/spacer.gif" border="0" width="1" height="32"><img src="pix/bg_left.gif" border="0" width="4" height="32"></td>
						<td class="tab" style="width:<? echo $widths[$i]; ?>px; background-image:url(pix/bg_body.gif); background-repeat:repeat-x; background-position:bottom"><? echo $titles[$i]; ?></td>
						<td><img src="pix/bg_right.gif" border="0" width="4" height="32"></td>
					</tr>
					</table>
				</td>
				<? } ?>
				
			<? } ?>

			<td height="32" width="50px" align="left" style="background-image: url(pix/line_bottom.gif); background-repeat:repeat-x">&nbsp;</td>
			<td height="32" align="left" style="background-image: url(pix/line_bottom.gif); background-repeat:repeat-x"><? if(!$admin) { ?><input type="password" name="pwd_field" id="pwd_field" value="" style="font-family:Verdana, Arial, Helvetica, sans-serif; font-size:9px; border-style:solid; border-color:black; border-width:1px;" />&nbsp;<a href="javascript: subm()">&raquo; login</a><? } else { ?><a href="?action=logout">&raquo; logout</a><? } ?>&nbsp;</td>
		</tr>

	</table>
	<?
}
?>
