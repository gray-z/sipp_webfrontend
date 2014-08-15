<?

// Here the user gets an overview of existing tests and can sort or remove them.

session_start();
require_once "authentication.php";
require_once "db.php";
require_once "dbHelper.php";
require_once "helper.php";

$action = $_GET["action"];
$id = $_GET["id"];


// manage ascending and descending sortable colums
if($_GET["sort"] != "") {
	if($_SESSION["s_sort"] != $_GET["sort"]) $order = "ASC";
	else {
		$order = $_SESSION["s_order"];
		if($order == "ASC") $order = "DESC";
		else $order = "ASC";
	}
	$_SESSION["s_order"] = $order;
	$_SESSION["s_sort"] = $_GET["sort"];
}

// define default sort parameters
if($_SESSION["s_sort"] == "") $_SESSION["s_sort"] = "name";
if($_SESSION["s_order"] == "") $_SESSION["s_order"] = "ASC";
$order = $_SESSION["s_order"];
$sort = $_SESSION["s_sort"];


if($admin && $action == "del" && id != "") {
	$tObj = new Test($id);
	$tObj->remove();
}


$tObj = new Test();
$res = $tObj->getOverview($sort." ".$order);
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<link href="css/styles.css" rel="stylesheet" type="text/css">
<link href="css/bg.css" rel="stylesheet" type="text/css">
<script src="js/tooltip.js" language="javascript" type="text/javascript"></script>
<script language="javascript" type="text/javascript">
	function delTest(id, name) {
		var check = window.confirm("Are you sure you want to remove the entire test ("+name+")?");
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
    <td><a href="tests.php" class="breadcumbs">Tests overview </a></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><? if($admin) { ?><a href="test_detail.php?action=new">&raquo; create new test <img src="pix/new.gif" width="14" height="14" border="0" alt="view"></a><? } ?></td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td>&nbsp;</td>
  </tr>
  <tr>
    <td>&nbsp;</td>
    <td><table cellpadding="3" cellspacing="2" border="0" class="datatable">
	<tr>
		<th><a href="?sort=name">name<? if($sort=="name") { ?>&nbsp;<img src="<? if($order == "ASC") echo "pix/ascending.gif"; else echo "pix/descending.gif"; ?>" width="9" height="7" border="0" alt="view"><? } ?></a></th>
		<th><a href="?sort=description">description<? if($sort=="description") { ?>&nbsp;<img src="<? if($order == "ASC") echo "pix/ascending.gif"; else echo "pix/descending.gif"; ?>" width="9" height="7" border="0" alt="view"><? } ?></a></th>
		<th><a href="?sort=created">created<? if($sort=="created") { ?>&nbsp;<img src="<? if($order == "ASC") echo "pix/ascending.gif"; else echo "pix/descending.gif"; ?>" width="9" height="7" border="0" alt="view"><? } ?></a></th>
		<th><a href="?sort=last_modified">last modified<? if($sort=="last_modified") { ?>&nbsp;<img src="<? if($order == "ASC") echo "pix/ascending.gif"; else echo "pix/descending.gif"; ?>" border="0" width="9" height="7" alt="view"><? } ?></a></th>
		<th><a href="?sort=run_count">runs<? if($sort=="run_count") { ?>&nbsp;<img src="<? if($order == "ASC") echo "pix/ascending.gif"; else echo "pix/descending.gif"; ?>" border="0" width="9" height="7" alt="view"><? } ?></a></th>
		<? if($admin) { ?>
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
		<td onClick="location.href='test_detail.php?version=-1&id=<? echo $row->id; ?>'"><? echo $row->name; ?></td>
		<td onClick="location.href='test_detail.php?version=-1&id=<? echo $row->id; ?>'"><? echo shorten($row->description, 40); ?></td>
		<td align="center" onClick="location.href='test_detail.php?version=-1&id=<? echo $row->id; ?>'"><? echo $row->created; ?></td>
		<td align="center" onClick="location.href='test_detail.php?version=-1&id=<? echo $row->id; ?>'"><? echo $row->last_modified; ?></td>
		<td align="center" onClick="location.href='test_detail.php?version=-1&id=<? echo $row->id; ?>'"><? echo $row->run_count; ?></td>
		<? if($admin) { ?>
		<td align="center"><a href="javascript: delTest(<? echo $row->id; ?>, '<? echo $row->name; ?>')"><img src="pix/del.gif" width="14" height="14" border="0" alt="remove"></a></td>
		<? } ?>
	</tr>
	<? 
	$even = !$even;
	} 
	?>
  </table>
    </td></tr>
</table>



</body>
</html>
