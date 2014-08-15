<?

// Here the user can display, remove and change the order of scenarios.
session_start();

require_once "authentication.php";
if(!$admin) { header("location: tests.php"); die("Authentication required!"); }
require_once "db.php";
require_once "dbHelper.php";
require_once "helper.php";

$action = $_GET["action"];
$id = $_GET["id"];

if($admin && $id != "") {
	// in case of up() or down(), only visible scenarios should be considered
	$sObj = new Scenario($id,"","","","","","","t");

	if($action == "del") $sObj->remove();
	else if($action == "up") $sObj->up();
	else if($action == "down") $sObj->down();
}

$sObj = new Scenario();
$res = $sObj->getAll("id, name, description, IF(ISNULL(csv),'f','t') AS csv_exists, def", "visible='t'");
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<link href="css/bg.css" rel="stylesheet" type="text/css">
<script language="javascript" src="js/tooltip.js" type="text/javascript"></script>
<script language="javascript" type="text/javascript">
	function del(id) {
		var check = window.confirm("Are you sure you want to remove this scenario?");
		if(check) location.href = "?action=del&id="+id;
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
    <td><a href="#" class="breadcumbs">Scenarios overview </a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><? if($admin) { ?><a href="scenario_detail.php">&raquo; create new scenario <img src="pix/new.gif" width="14" height="14" border="0" alt="view"></a><? } ?></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><table cellpadding="3" cellspacing="2" border="0" class="datatable">
	<tr>
		<th>name</th>
		<th>description</th>
		<th>XML</th>
		<th>CSV</th>
		<? if($admin) { ?>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<th>&nbsp;</th>
		<? } ?>
	</tr>
	<?
	$even = true;
	while($row = mysqli_fetch_object($res)) {
	if($even) $bgcol = "#EEEEEE";
	else $bgcol = "#DDDDDD";
	?>
	<tr style="background-color:<? echo $bgcol; ?>;" onMouseOver="this.style.background='#B9FAFD'" onMouseOut="this.style.background='<? echo $bgcol; ?>'">
		<td <? if($row->def == 'f' && $admin) { ?>onClick="location.href='scenario_detail.php?action=mod&id=<? echo $row->id; ?>'"<? } ?>><? echo $row->name; ?></td>
		<td <? if($row->def == 'f' && $admin) { ?>onClick="location.href='scenario_detail.php?action=mod&id=<? echo $row->id; ?>'"<? } ?>><? echo shorten($row->description, 40); ?></td>
		<? if($row->def == 'f') { ?>
		<td align="center"><a href="view_xml.php?id=<? echo $row->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a></td>
		<td align="center"><? if($row->csv_exists == "t") { ?><a href="view_csv.php?id=<? echo $row->id; ?>" target="_blank"><img src="pix/view.gif" width="14" height="14" border="0" alt="view"></a><? } ?></td>
		<? } else { ?>
		<td colspan="2" align="center">default</td>
		<? } ?>
		<? if($admin) { ?>
		<td align="center"><a href="?action=up&id=<? echo $row->id; ?>"><img src="pix/sort_up.gif" width="13" height="14" border="0" alt="view"></a></td>
		<td align="center"><a href="?action=down&id=<? echo $row->id; ?>"><img src="pix/sort_down.gif" width="13" height="14" border="0" alt="view"></a></td>
		<td align="center"><? if($row->def == 'f') { ?><a href="javascript: del(<? echo $row->id; ?>)"><img src="pix/del.gif" width="14" height="14" border="0" alt="view"><? } else echo "&nbsp;" ?></td>
		<? } ?>
	</tr>
	<? 
	$even = !$even;
	} 
	?>
</table></td>
  </tr>
</table>



</body>
</html>
