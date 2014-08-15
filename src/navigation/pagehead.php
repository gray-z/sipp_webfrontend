<?
// If you want to add a new tab to the navigation, do it here. Syntax: addTab($title, $width, $url)
require_once "register.php";
 
if($admin) addTab("Manage scenarios", 150, "scenarios.php");
addTab("Manage tests", 150, "tests.php");
addTab("System information", 150, "info.php");

showTabs(); 

?>
<div style="width:100%; height:100px"></div>
<img src="pix/sipp-web-logo.gif" style="position:absolute; right:0px; top:0px;" />
