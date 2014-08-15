<?

// Here the user can create and modify scenarios in the database.
session_start();
require_once "authentication.php";
if(!$admin) { header("location: tests.php"); die("Authentication required!"); }
require_once "db.php";
require_once "dbHelper.php";

$filename = "scenario_detail.php";

$error = false;
$errormessage = "";

$action = $_GET["action"];
$id = $_GET["id"];


if($action=="save" || $action=="update") {
	// check if there were automatically added slashes before " and ' where it is relevant, and remove them
	if(get_magic_quotes_gpc()) {
		$name = stripslashes($_POST["name"]);
		$description = stripslashes($_POST["description"]);
		$bind_local = stripslashes($_POST["bind_local"]);
	} else {
		$name = $_POST["name"];
		$description = $_POST["description"];
		$bind_local = $_POST["bind_local"];
	}
	
	$xml_isfile = is_file($_FILES["xml"]["tmp_name"]);
	$csv_isfile = is_file($_FILES["csv"]["tmp_name"]) == true ? true : false;

	if($xml_isfile) $xml = file_get_contents($_FILES["xml"]["tmp_name"]);
	if($csv_isfile) $csv = file_get_contents($_FILES["csv"]["tmp_name"]);
	else $csv = "NULL";
}

if($action == "save") {

	if($xml_isfile == false || ($_POST["csvvalue"] != "" && $csv_isfile == false)) {
		error("Upload of XML/CSV file failed! File not found! Scenario was not stored!");
		$row = new Scenario("", $name, $description, "", "", $bind_local);
		$action="";
	} else {
		$sObj = new Scenario("", $name, $description, $xml, $csv, $bind_local, "f", "t");
		$sObj->insert();
		die("<script language='javascript' type='text/javascript'>location.replace('scenarios.php');</script>");
	}
} else if($action == "update") {
	if(($_POST["xmlvalue"] != "" && $xml_isfile == false) || ($_POST["csvvalue"] != "" && $csv_isfile == false)) {
		error("Upload of XML/CSV file failed! File not found! Scenario was not stored!");
		$action="mod";
	} else {
		if($xml_isfile == false) $xml = "LEAVE ALONE";
		if($csv_isfile == false) $csv = "LEAVE ALONE";
		$sObj = new Scenario($id, $name, $description, $xml, $csv, $bind_local, "LEAVE ALONE", "LEAVE ALONE");
		$sObj->update();
		die("<script language='javascript' type='text/javascript'>location.replace('scenarios.php');</script>");
	}
} else if($action == "del" && $id != "") {
	$sObj = new Scenario($id);
	$row = $sObj->remove();
	die("<script language='javascript' type='text/javascript'>location.replace('scenarios.php');</script>");
} else if($action == "removecsv" && $id != "") {
	$sObj = new Scenario($id);
	$row = $sObj->removecsv();
	$action = "mod";
}




if($action == "mod" && $id != "") {
	$sObj = new Scenario($id);
	$row = $sObj->get("IF(ISNULL(csv),'f','t') AS csv_exists");
}

function error($msg) {
	global $errormessage;
	global $error;
	$errormessage = $msg;
	$error = true;
}


?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<link href="css/bg.css" rel="stylesheet" type="text/css">
<script language="javascript" type="text/javascript" src="js/helper.js"></script>
<script language="javascript" type="text/javascript">
	function removecsv() {
		var check = window.confirm("Sind sie sicher dass das CSV File entfernt werden soll?");
		if(check) location.href ="<? echo $filename."?action=removecsv&id=".$id; ?>";
	}

	function checkAndSubmit() {
		var f = document.someform;
		
		if(checkText("name", "Name", false, TEXT)
		<? if($action!="mod") { ?>&& checkText("xml", "XML-file", false, TEXT)<? } ?>
		&& checkText("bind_local", "bind_local", true, IP)) {
			document.getElementById("xmlvalue").value = trim(document.getElementById("xml").value);
			document.getElementById("csvvalue").value = trim(document.getElementById("csv").value);
			f.action = "<? echo $filename; if($action == "mod") echo "?action=update&id=".$id; else echo "?action=save"; ?>";
			f.submit();
		}
	}
</script>
</head>
<body>
<? require_once "navigation/pagehead.php"; ?>
<? if($error) { ?><span class="error"><? echo $errormessage; ?></span><br /><br /><? } ?>
<form enctype="multipart/form-data" action="" method="post" name="someform">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
  <tr>
    <td width="20" height="10">&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="scenarios.php" class="breadcumbs">Scenarios overview </a> <strong>&raquo;</strong> <a href="#" class="breadcumbs"><? if($action=="mod") echo "modify scenario (".$row->name.")"; else echo "create new scenario"; ?></a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><a href="scenario_detail.php">&raquo; create new scenario <img src="pix/new.gif" width="14" height="14" border="0" alt="view"></a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><? if($error) { ?><span class="error"><? echo $errormessage; ?></span><br /><br /><? } ?>
<table cellpadding="3" cellspacing="2" border="0" class="formtable">

	<tr style="background-color:#EEEEEE;">
		<td>Name*</td>
		<td><input type="text" name="name" id="name" value="<? if($action=="mod" || $error) echo $row->name; ?>" class="input" style="width:150px;"></td>
		</tr>
	<tr style="background-color:#DDDDDD;">
		<td>Description</td>
		<td><textarea name="description" rows="4" class="input" id="description" style="width:150px;"><? if($action=="mod" || $error) echo $row->description; ?></textarea></td>
		</tr>
	<tr style="background-color:#EEEEEE;">
		<td>XML-file*</td>
		<td><? if($action=="mod") { ?><a href="view_xml.php?id=<? echo $row->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"> view file</a><br><? } ?>
		  <input type="file" name="xml" id="xml" value="<? if($error) echo $row->xml; ?>" class="input"><input type="hidden" name="xmlvalue" id="xmlvalue"></td>
		</tr>
	<tr style="background-color:#DDDDDD;">
		<td>CSV -file </td>
		<td><? if($action=="mod" && $row->csv_exists == "t") { ?><a href="view_csv.php?id=<? echo $row->id; ?>"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"> view file</a> <a href="javascript: removecsv();"><img src="pix/del.gif" width="14" height="14" border="0" alt="view"> remove file</a> <br><? } ?>
		  <input type="file" name="csv" id="csv" value="<? if($error) echo $row->csv; ?>" class="input">
		  <input type="hidden" name="csvvalue" id="csvvalue"></td>
	</tr>
	<tr style="background-color:#EEEEEE;">
		<td>bind_local</td>
		<td><input type="text" name="bind_local" id="bind_local" value="<? if($action=="mod" || $error) echo $row->bind_local; ?>" class="input" style="width:150px;"></td>
		</tr>
</table>
      <br>
    <br>
    <a href="javascript: checkAndSubmit();">&raquo;save scenario <img src="pix/save.gif" width="14" height="14" border="0" alt="view"></a>&nbsp;<a href="<? echo $filename."?action=del&id=".$id; ?>">&raquo;remove scenario <img src="pix/del.gif" width="14" height="14" border="0" alt="view"></a></td>
  </tr>
</table>

</form>

</body>
</html>
