<?php
function get2Session($getName, $default="") {
	$name = $_GET[$getName];
	
	if($name == "" && $_SESSION["s_$getName"] == "") $name = $default;
	
	if($name == "") $name = $_SESSION["s_$getName"];
	else $_SESSION["s_$getName"] = $name;
	return $name;
}

$shortenindex = 0;
function shorten($txt, $size) {
	global $shortenindex;
	$shortenindex++;
	$txt = stripnl($txt);
	$nohtml = striphtml($txt);

	if(strlen($nohtml) <= $size) return "<script language='javascript' type='text/javascript'>var tttext".$shortenindex."='".addslashes($txt)."';</script><div onmouseout=\"noTooltip()\" onmouseover=\"tooltip(tttext".$shortenindex.")\">".$nohtml."</div>";
	else return "<script language='javascript' type='text/javascript'>var tttext".$shortenindex."='".addslashes($txt)."';</script><div onmouseout=\"noTooltip()\" onmouseover=\"tooltip(tttext".$shortenindex.")\">".substr($nohtml,0,$size)."...</div>";
}

function striphtml($html) {
	$html = preg_replace('~<[^>]+>~s','',$html);
	return $html;
}

function stripnl($txt) {
	return 	preg_replace("(\r\n|\n|\r)", "", $txt);
}

function UACorUAS($xml) {
	$tags = array();
	$success = preg_match("/(\<\s*recv)|(\<\s*send)/i", $xml, $tags);
	if($success == 0 || $success === false) return "?";
	else {
		if($tags[1] != "") return "server";
		else return "client";
	}
}
?>
