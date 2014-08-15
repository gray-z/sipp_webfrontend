<?php
/*
 * SIPp Webfrontend	- Web tool to create, manage and run SIPp test cases
 * Copyright (c) 2008 Mario Smeritschnig
 * Idea, support, planning, guidance Michael Hirschbichler
 *
 * * * BEGIN LICENCE * * *
 *
 * This file is part of SIPp Webfrontend.
 * 
 * SIPp Webfrontend is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * SIPp Webfrontend is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with SIPp Webfrontend.  If not, see <http://www.gnu.org/licenses/>.
 *
 * * * END LICENCE * * *
 *
 */
error_reporting(E_ALL ^ E_DEPRECATED); 
 
// Here is where the SQL happens. You find for every table in the database a corresponding class that handles databas communication.
// Some of the classes extend the classes StandardTable or SortableStandardTable. Standardtable offers basic functionality to insert,
// update, remove and query database-entries. SortableStandardTable additionally offers methods to sort and change order of records
// in tables that have a pos field.

class StandardTable {
	var $tabName;
	var $pk_name;
	var $pk_value;
	var $where;
	var $type_name;
	var $type_value;
	var $num_attributes;
	var $attributes;
	
	function __construct($tabName, $keys, $values, $type_name) {
		global $con;
		$this->num_attributes = count($keys);
		// array $values auffüllen damit gleich viele werte wie in $keys existieren
		for($i=0; $i<$this->num_attributes;$i++) {
			if($i < count($values))	{
				$this->attributes[$keys[$i]] = mysqli_real_escape_string($con,$values[$i]);
				$this->$keys[$i] = mysqli_real_escape_string($con,$values[$i]);
			} else {
				$this->attributes[$keys[$i]] = "";	
				$this->$keys[$i] = "";	
			}
		}
		
		$this->tabName = $tabName;
		$this->pk_name = $keys[0];
		$this->pk_value = $values[0];
		
		$this->type_name = $type_name;
		if($type_name != "") $this->type_value = $this->$type_name;
		
		if($this->type_name != "" && $this->type_value != "") $this->where = $this->type_name."='".$this->type_value."'";
		else $this->where = "";
	}
	
	function get($optSelect="") {
		global $con;
		if($optSelect != "") $optSelect =", ".$optSelect;
		
		$statement = "SELECT * $optSelect FROM ".$this->tabName." WHERE ".$this->pk_name."='".$this->pk_value."'";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode get()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		return $row;
	}
	
	function getOnly($select="") {
		global $con;
	
		$statement = "SELECT $select FROM ".$this->tabName." WHERE ".$this->pk_name."='".$this->pk_value."'";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode getOnly()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		return $row;
	}
	
	function getAll($optSelect="", $optWhere="", $optOrder="") {
		global $con;
		
		if($optSelect == "") $optSelect = "*";
		
		if($optOrder != "") $optOrder = " ORDER BY ".$optOrder;
		
		if($this->where != "") {
			if($optWhere != "") $optWhere .= " AND ";
			$statement = "SELECT $optSelect FROM ".$this->tabName." WHERE $optWhere".$this->where.$optOrder;
		} else {
			if($optWhere != "") $optWhere = " WHERE ".$optWhere;
			$statement = "SELECT $optSelect FROM ".$this->tabName.$optWhere.$optOrder;
		}
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode getAll()<br>".mysqli_error($con)."<br>".$statement);
		return $result;
	}
	
	function remove($optWhere="") {
		global $con;
		if($optWhere!="") $where = $optWhere;
		else if($this->pk_name=="" || $this->pk_value=="") die("Fehler: Tabelle ".$this->tabName.": Methode remove()<br>Ungültige Parameter!");
		else $where = $this->pk_name."='".$this->pk_value."'";
		$statement = "DELETE FROM ".$this->tabName." WHERE ".$where;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode remove()<br>".mysqli_error($con)."<br>".$statement);
	}

	function insert($transaction=true) {
		global $con;
		
		$attStr = "";
		$valStr = "";
		$keys = array_keys($this->attributes);
		for($i=1; $i<$this->num_attributes; $i++) {
			if($i > 1) {
				$attStr .= ",";
				$valStr .= ",";
			}
			$attStr .= $keys[$i];
			if($this->attributes[$keys[$i]]=="NULL") $valStr .= "NULL";
			else $valStr .= "'".$this->attributes[$keys[$i]]."'";
		}

		if($transaction) mysqli_query($con,"START TRANSACTION");
		
		$statement = "INSERT INTO ".$this->tabName." ($attStr) VALUES ($valStr)";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode insert()<br>".mysqli_error($con)."<br>".$statement);

		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode insert()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_array($result);
		
		if($transaction) mysqli_query($con,"COMMIT");
		
		return $row[$this->pk_name];
	}

	function update() {
		global $con;
		
		$firstinuse=false;
		$setStr = "";
		$keys = array_keys($this->attributes);
		for($i=1; $i<$this->num_attributes; $i++) {
			//if($this->attributes[$keys[$i]] != "") {
				if($firstinuse && $this->attributes[$keys[$i]]!="LEAVE ALONE") $setStr .= ",";
				if($this->attributes[$keys[$i]]=="NULL") {
					$setStr .= $keys[$i]."=NULL";
					$firstinuse = true;
				} else if($this->attributes[$keys[$i]]!="LEAVE ALONE") {
					$setStr .= $keys[$i]."='".$this->attributes[$keys[$i]]."'";
					$firstinuse = true;
				}
			//}
		}


		$statement = "UPDATE ".$this->tabName." SET $setStr WHERE ".$this->pk_name."='".$this->pk_value."'";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode update()<br>".mysqli_error($con)."<br>".$statement);
	}


}

class SortableStandardTable extends StandardTable {
	var $newFirst;
	function __construct($newFirst, $tabName, $attributes, $values, $type_name) {
		$this->newFirst = $newFirst;
		parent::__construct($tabName, $attributes, $values, $type_name);
	}
	
	function getAll($optSelect="", $optWhere="", $optOrder="") {
		global $con;
		
		if($optSelect == "") $optSelect = "*";

		if($this->newFirst) $order = "DESC";
		else $order = "ASC";
		
		if($optOrder != "") $optOrder .= ",";
		
		if($this->where != "") {
			if($optWhere != "") $optWhere .= " AND ";
			$statement = "SELECT $optSelect FROM ".$this->tabName." WHERE $optWhere".$this->where." ORDER BY $optOrder pos $order";
		} else {
			if($optWhere != "") $optWhere = "WHERE ".$optWhere;
			$statement = "SELECT $optSelect FROM ".$this->tabName." $optWhere ORDER BY $optOrder pos $order";
		}
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode getAll()<br>".mysqli_error($con)."<br>".$statement);
		return $result;
	}
	
	
	public function down($id="") {
		if($id=="" && $this->pk_name != "" && $this->pk_value != "") $id = $this->pk_value;
		
		if($id != "") {
			if($this->newFirst) $this->changePos($id, true);
			else $this->changePos($id, false);
		}
	}
	
	public function up($id="") {
		if($id=="" && $this->pk_name != "" && $this->pk_value != "") $id = $this->pk_value;

		if($id != "") {
			if($this->newFirst) $this->changePos($id, false);
			else $this->changePos($id, true);
		}
	}
	
	private function changePos($id, $down) {

		global $con;
		if($this->type_name != "" && $this->where != "") {
			$where = " AND ".$this->where;
			$sel_type = ", ".$this->type_name." ";
		} else {
			$where = "";
			$sel_type = "";
		}
		
		mysqli_query($con,"START TRANSACTION");
		
		$statement = "SELECT pos $sel_type FROM ".$this->tabName." WHERE ".$this->pk_name."='".$this->pk_value."'";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_row($result);
		$pos = $row[0];
		$type = $row[1];

		if($down) $statement = "SELECT max(pos) as mpos FROM ".$this->tabName." WHERE pos < $pos $where";
		else $statement = "SELECT min(pos) as mpos FROM ".$this->tabName." WHERE pos > $pos $where";
		$result = mysqli_query ($con,$statement) OR  die("Fehler: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		if($row->mpos != NULL) {
			$statement = "UPDATE ".$this->tabName." SET pos = $pos WHERE pos = ".$row->mpos." $where";
			$result = mysqli_query ($con,$statement) OR  die("Fehler: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysqli_error($con)."<br>".$statement);
	
			$statement = "UPDATE ".$this->tabName." SET pos = ".$row->mpos." WHERE ".$this->pk_name." = '$id' $where";
			$result = mysqli_query ($con,$statement) OR  die("Fehler: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysqli_error($con)."<br>".$statement);
		}
		mysqli_query($con,"COMMIT") OR die("Fehler: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode changePos()<br>".mysqli_error($con)."<br>".$statement);
		
	}
	
	function nextInsertPos() {
		global $con;
		
		if($this->type_name != "" && $this->where != "") $statement = "SELECT max(pos) as pos FROM ".$this->tabName." WHERE ".$this->where;
		else  $statement = "SELECT max(pos) as pos FROM ".$this->tabName;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Class SortableStandardTable: Methode nextInsertPos()<br>".mysqli_error($con)."<br>".$statement);

		if(mysqli_num_rows($result) == 0) $pos = 1;
		else {
			$row = mysqli_fetch_object($result);
			$pos = $row->pos + 1;
		}
		return $pos;
	}

	function insert($transaction=true) {
		global $con;
		
		$attStr = "";
		$valStr = "";
		$keys = array_keys($this->attributes);
		for($i=1; $i<$this->num_attributes; $i++) {
			if($i > 1) {
				$attStr .= ",";
				$valStr .= ",";
			}
			$attStr .= $keys[$i];
			if($this->attributes[$keys[$i]]=="NULL") $valStr .= "NULL";
			else $valStr .= "'".$this->attributes[$keys[$i]]."'";
		}

		$pos = $this->nextInsertPos();


		if($transaction) mysqli_query($con,"START TRANSACTION");
		
		$statement = "INSERT INTO ".$this->tabName." ($attStr,pos) VALUES ($valStr,$pos)";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode insert()<br>".mysqli_error($con)."<br>".$statement);
 
		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode insert()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		
		if($transaction) mysqli_query($con,"COMMIT");
		
		return $row->id;
	}

}

class Scenario extends SortableStandardTable {

	function __construct() {

		$attributes = array("id", "name", "description", "xml", "csv", "bind_local", "def", "visible");
		$values = func_get_args();
		$type_name = "visible";
		// __construct(newfirst, tablename, attributes, values, type_name);
		parent::__construct(true, "Scenario", $attributes, $values, $type_name);
	}
	
	function remove() {
		global $con;
		$statement = "UPDATE ".$this->tabName." SET visible='f' WHERE id=".$this->id;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Class Scenario: Methode remove()<br>".mysqli_error($con)."<br>".$statement);
	}

	function removecsv() {
		global $con;
		$statement = "UPDATE ".$this->tabName." SET csv=NULL WHERE id=".$this->id;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Class Scenario: Methode removecsv()<br>".mysqli_error($con)."<br>".$statement);
	
	}

}





class Test extends StandardTable {

	function __construct() {

		$attributes = array("id", "name", "description");
		$values = func_get_args();
		$type_name = "";
		// __construct(tablename, attributes, values, type_name);
		parent::__construct("Test", $attributes, $values, $type_name);
	}
	
	function insert($transaction=true) {
		global $con;
		
		if($transaction) mysqli_query($con,"START TRANSACTION");
		
		$statement = "INSERT INTO ".$this->tabName." (name, description) VALUES ('".$this->name."', '".$this->description."')";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode insert()<br>".mysqli_error($con)."<br>".$statement);

		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode insert()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		
		$vObj = new Version($row->id, "", 0, "a");
		$vObj->insert(false);
				
		if($transaction) mysqli_query($con,"COMMIT");
		
		return $row->id;
	}
	
	function duplicate($version) {
		global $con;
		
		mysqli_query($con,"START TRANSACTION");
		
		// load test data
		$row = $this->get();
		
		// insert new test
		$statement = "INSERT INTO ".$this->tabName." (name, description) VALUES ('".addslashes($row->name)." copy', '".addslashes($row->description)."')";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode duplicate()<br>".mysqli_error($con)."<br>".$statement);
		// get new test id
		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode duplicate()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		$newid = $row->id;
		// load version
		$vObj = new Version($this->id, $version);
		$row = $vObj->get();
		// insert new version
		$vObj = new Version($newid, "", $row->delay, $row->delay_party);
		$newversion = $vObj->insert(false);
		
		// load calls
		$cObj = new SIPpCall("", $this->id, $version);
		$cRes = $cObj->getAll();
		// insert calls
		while($cRow = mysqli_fetch_object($cRes)) {
			$cObj = new SIPpCall("", $newid, $newversion, $cRow->party, $cRow->description, addslashes($cRow->xml), addslashes($cRow->csv), $cRow->def, $cRow->bind_local, $cRow->executable, $cRow->ip_address, $cRow->monitor, $cRow->a_i, $cRow->a_m, $cRow->a_nd, $cRow->a_nr, $cRow->a_t, $cRow->a_p, $cRow->a_r, $cRow->a_timeout, $cRow->a_pause_msg_ign, $cRow->a_trace_msg, $cRow->a_trace_shortmsg, $cRow->extended_parameters, $cRow->log, $cRow->scenario_id);
			$cObj->insert(false);
		}
		
		mysqli_query($con,"COMMIT");
		
		return $newid;
	}
	
	function remove() {
		global $con;
		$statement = "UPDATE ".$this->tabName." SET visible='f' WHERE id=".$this->id;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode remove()<br>".mysqli_error($con)."<br>".$statement);
	}

	function getOverview($optOrder="") {
		global $con;
		
		if($optOrder != "") $optOrder = " ORDER BY ".$optOrder;
		
		$statement = "SELECT t.id, t.name, t.description, DATE_FORMAT(t.created,'%Y.%m.%d') AS created, DATE_FORMAT(MAX(v.created), '%Y.%m.%d') AS last_modified, COUNT(r.id) AS run_count FROM Test t, Version v LEFT JOIN Run r ON (r.test_version = v.version AND r.test_id = v.id) WHERE v.id=t.id AND t.visible='t' GROUP BY t.id".$optOrder;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode getOverview()<br>".mysqli_error($con)."<br>".$statement);	
		return $result;
	}
	
	function getVersions() {
		global $con;
		$statement = "SELECT version FROM Version WHERE id=".$this->id." AND visible='t' ORDER BY version DESC";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode getVersions()<br>".mysqli_error($con)."<br>".$statement);	
		return $result;
	}

}




class Version extends StandardTable {

	function __construct() {

		$attributes = array("id", "version", "delay", "delay_party");
		$values = func_get_args();
		$type_name = "id";
		// __construct(newfirst, tablename, attributes, values, type_name);
		parent::__construct("Version", $attributes, $values, $type_name);
	}
	
	function insert($transaction=true) {
		global $con;
		
		if($transaction) mysqli_query($con,"START TRANSACTION");
		
		// $pos = parent::nextInsertPos();
		if($this->version == "") {
			$statement = "SELECT MAX(version) as version_number FROM Version WHERE id=".$this->id;
			$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode insert()<br>".mysqli_error($con)."<br>".$statement);
			
			if(mysqli_num_rows($result) == 0) $version_number = 1;
			else {
				$row = mysqli_fetch_object($result);
				$version_number = $row->version_number + 1;
			}
		} else $version_number = $this->version;

		
		
		$statement = "INSERT INTO ".$this->tabName." (id, version, delay, delay_party, visible) VALUES (".$this->id.", ".$version_number.", ".$this->delay.", '".$this->delay_party."', 't')";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table ".$this->tabName.": Methode insert()<br>".mysqli_error($con)."<br>".$statement);
	
		if($transaction) mysqli_query($con,"COMMIT");
		
		return $version_number;
	}
	
	
	
	function update() {
		global $con;
		
		mysqli_query($con,"START TRANSACTION");
		
		// If this version has already been tested (and thus a run has been created), then create a new version.
		if($this->hasRun()) {
			$new_version_number = $this->duplicateVersion(false);
			$this->duplicateSIPpCalls($new_version_number, "", false);
			$this->version = $new_version_number;
		}
		
		$statement = "UPDATE Version SET delay=".$this->delay.", delay_party='".$this->delay_party."' WHERE id=".$this->id." AND version='".$this->version."'";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode update()<br>".mysqli_error($con)."<br>".$statement);
		
		mysqli_query($con,"COMMIT") OR die("Fehler: Class Version: Methode update()<br>".mysqli_error($con)."<br>COMMIT failed!");
	}
	
	function get() {
		global $con;
		$statement = "SELECT * FROM Version WHERE id=".$this->id." AND version='".$this->version."'";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode get()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		
		return $row;
	}
	
	function remove() {
		global $con;
		$statement = "UPDATE ".$this->tabName." SET visible='f' WHERE id=".$this->id." AND version='".$this->version."'";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Methode remove()<br>".mysqli_error($con)."<br>".$statement);
	}

	function hasRun() {
		$rObj = new Run("", $this->id, $this->version);
		$res = $rObj->getAll();
		return mysqli_num_rows($res) > 0;
	}
	
	function duplicateVersion($transaction=true) {
		global $con;
		
		if($transaction) mysqli_query($con,"START TRANSACTION");
		
		$detailRow = $this->get();
		$vObj = new Version($detailRow->id, "", $detailRow->delay, $detailRow->delay_party);
		$new_version_number = $vObj->insert(false);
		
		// test_detail.php should know the new version number
		$_SESSION["s_version"] = $new_version_number;
		
		if($transaction) mysqli_query($con,"COMMIT");
		
		return $new_version_number;
	}
	
	function duplicateSIPpCalls($new_version_number, $return_new_id, $transaction=true) {
		global $con;
		
		$new_id = false;
		
		if($transaction) mysqli_query($con,"START TRANSACTION");

		$cObj = new SIPpCall("", $this->id, $this->version);
		$cRes = $cObj->getAll();
		while($cRow = mysqli_fetch_object($cRes)) {
			$cObj = new SIPpCall("", $this->id, $new_version_number, $cRow->party, $cRow->description, addslashes($cRow->xml), addslashes($cRow->csv), $cRow->def, $cRow->bind_local, $cRow->executable, $cRow->ip_address, $cRow->monitor, $cRow->a_i, $cRow->a_m, $cRow->a_nd, $cRow->a_nr, $cRow->a_t, $cRow->a_p, $cRow->a_r, $cRow->a_timeout, $cRow->a_pause_msg_ign, $cRow->a_trace_msg, $cRow->a_trace_shortmsg, $cRow->extended_parameters, $cRow->log, $cRow->scenario_id);
			if($cRow->id == $return_new_id) $new_id = $cObj->insert(false);
			else $cObj->insert(false);
		}
		
		if($transaction) mysqli_query($con,"COMMIT");
		
		return $new_id;
	}

}




class SIPpCall {

	var $id;
	var $test_id;
	var $test_version;

	var $party;
		
	var $description;
	var $xml;
	var $csv;
	var $def;
	var $bind_local;
	
	var $executable;
	var $ip_address;
	var $monitor;
	var $a_i;
	var $a_m;
	var $a_nd;
	var $a_nr;
	var $a_t;
	var $a_p;
	var $a_r;
	var $a_timeout;
	var $a_pause_msg_ign;
	var $a_trace_msg;
	var $a_trace_shortmsg;
	var $extended_parameters;
	var $log;
	var $scenario_id;

	
	function SIPpCall($id="", $test_id="", $test_version="", $party="", $description="", $xml="", $csv="", $def="", $bind_local="", $executable="", $ip_address="", $monitor="", $a_i="", $a_m="", $a_nd="", $a_nr="", $a_t="", $a_p="", $a_r="", $a_timeout="", $a_pause_msg_ign="", $a_trace_msg="", $a_trace_shortmsg="", $extended_parameters="", $log="", $scenario_id="") {
		$this->id = $id;
		$this->test_id = $test_id;
		$this->test_version = $test_version;
		$this->party = $party;
		$this->description = $description;
		$this->xml = $xml;
		$this->csv = $csv;
		$this->def = $def;
		$this->bind_local = $bind_local;
		$this->executable = $executable;
		$this->ip_address = $ip_address;
		$this->monitor = $monitor;
		$this->a_i = $a_i;
		$this->a_m = $a_m;
		$this->a_nd = $a_nd;
		$this->a_nr = $a_nr;
		$this->a_t = $a_t;
		$this->a_p = $a_p;
		$this->a_r = $a_r;
		$this->a_timeout = $a_timeout;
		$this->a_pause_msg_ign = $a_pause_msg_ign;
		$this->a_trace_msg = $a_trace_msg;
		$this->a_trace_shortmsg = $a_trace_shortmsg;
		$this->extended_parameters = $extended_parameters;
		$this->log = $log;
		$this->scenario_id = $scenario_id;		
	}
	

	function insert($transaction=true) {
		global $con;
		
		if($transaction) mysqli_query($con,"START TRANSACTION");
		
		// If this version has already been tested (and thus a run has been created), then create a new version.
		$vObj = new Version($this->test_id, $this->test_version);
		if($vObj->hasRun()) {
			$new_version_number = $vObj->duplicateVersion(false);
			$vObj->duplicateSIPpCalls($new_version_number, "", false);
			$this->test_version = $new_version_number;
		}

				
		$pos = $this->nextInsertPos();
		
		$statement = "INSERT INTO SIPpCall (test_id, test_version, description, xml, csv, def, bind_local, executable, ip_address, monitor, a_i, a_m, a_nd, a_nr, a_t, a_p, a_r, a_timeout, a_pause_msg_ign, a_trace_msg, a_trace_shortmsg, extended_parameters, party, log, scenario_id, pos) VALUES (".$this->test_id.", '".$this->test_version."', '".$this->description."', '".$this->xml."',";
		
		if(trim($this->csv) == "" || $this->csv=="NULL") $statement.="NULL"; else $statement.="'".$this->csv."'";

		$statement .= ", '".$this->def."','".$this->bind_local."','".$this->executable."','".$this->ip_address."','".$this->monitor."','".$this->a_i."',";
		
		if(trim($this->a_m)=="" || $this->a_m=="NULL") $statement.="NULL"; else $statement.="'".$this->a_m."'";
		$statement .=",'".$this->a_nd."','".$this->a_nr."','".$this->a_t."',";
		
		if(trim($this->a_p)=="" || $this->a_p=="NULL") $statement.="NULL"; else $statement.="'".$this->a_p."'";
		$statement .=",";
		if(trim($this->a_r)=="" || $this->a_r=="NULL") $statement.="NULL"; else $statement.="'".$this->a_r."'";
		$statement .=",";
		if(trim($this->a_timeout)=="" || $this->a_timeout=="NULL") $statement.="NULL"; else $statement.="'".$this->a_timeout."'";
		
		$statement .= ",'".$this->a_pause_msg_ign."','".$this->a_trace_msg."','".$this->a_trace_shortmsg."','".$this->extended_parameters."','".$this->party."','".$this->log."',".$this->scenario_id.", $pos)";
		
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table SIPpCall: Methode insert()<br>".mysqli_error($con)."<br>".$statement);

		$statement = "SELECT LAST_INSERT_ID() as id";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table SIPpCall: Methode insert()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
	
		if($transaction) mysqli_query($con,"COMMIT");
		
		return $row->id;
	}
	
	function update($scenario=false) {
		global $con;

		mysqli_query($con,"START TRANSACTION");

		// If this version has already been tested (and thus a run has been created), then create a new version.
		$vObj = new Version($this->test_id, $this->test_version);
		if($vObj->hasRun()) {
			$new_version_number = $vObj->duplicateVersion(false);
			$new_call_id = $vObj->duplicateSIPpCalls($new_version_number, $this->id, false);
			$this->test_version = $new_version_number;
			$this->id = $new_call_id;
		}

		$statement = "UPDATE SIPpCall SET executable='".$this->executable."', ip_address='".$this->ip_address."', monitor='".$this->monitor."', a_i='".$this->a_i."', a_m=";
		
		if(trim($this->a_m)=="") $statement.="NULL"; else $statement.="'".$this->a_m."'";
		$statement .=", a_nd='".$this->a_nd."', a_nr='".$this->a_nr."', a_t='".$this->a_t."', a_p=";
		
		if(trim($this->a_p)=="") $statement.="NULL"; else $statement.="'".$this->a_p."'";
		$statement .=",a_r=";
		if(trim($this->a_r)=="") $statement.="NULL"; else $statement.="'".$this->a_r."'";
		$statement .=", a_timeout=";
		if(trim($this->a_timeout)=="") $statement.="NULL"; else $statement.="'".$this->a_timeout."'";

		
		$statement .= ", a_pause_msg_ign='".$this->a_pause_msg_ign."', a_trace_msg='".$this->a_trace_msg."', a_trace_shortmsg='".$this->a_trace_shortmsg."', extended_parameters='".$this->extended_parameters."', log='".$this->log."'";
		

		if($scenario) {
			$statement .= ", description='".$this->description."', xml='".$this->xml."', csv=";
			if(trim($this->csv)=="" || $this->csv == "NULL") $statement.="NULL"; else $statement.="'".$this->csv."'";
			$statement .=", def='".$this->def."', bind_local='".$this->bind_local."', scenario_id=".$this->scenario_id;
		}

		
		$statement .= " WHERE id=".$this->id;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table SIPpCall: Methode update()<br>".mysqli_error($con)."<br>".$statement);
		
		mysqli_query($con,"COMMIT") OR die("Fehler: Class SIPpCall: Methode update()<br>".mysqli_error($con)."<br>COMMIT failed!");
	}
	
	function nextInsertPos() {
		global $con;
		
		$statement = "SELECT max(pos) as pos FROM SIPpCall WHERE test_id=".$this->test_id." AND test_version='".$this->test_version."' AND party='".$this->party."'";

		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle SIPpCall: Methode nextInsertPos()<br>".mysqli_error($con)."<br>".$statement);

		if(mysqli_num_rows($result) == 0) $pos = 1;
		else {
			$row = mysqli_fetch_object($result);
			$pos = $row->pos + 1;
		}
		return $pos;
	}
	
	function getAll($optSelect="") {
		global $con;
		
		$select = $optSelect=="" ? "c.*, s.name, IF(ISNULL(c.csv),'f','t') AS csv_exists": $optSelect;
		$statement = "SELECT $select FROM SIPpCall c, Scenario s WHERE s.id=c.scenario_id";
		if($this->test_id != "") $statement .= " AND test_id=".$this->test_id;
		if($this->test_version != "") $statement .= " AND test_version='".$this->test_version."'";
		if($this->party != "") $statement .= " AND party='".$this->party."'";
		$statement .= " ORDER BY c.pos";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table SIPpCall: Methode getAll()<br>".mysqli_error($con)."<br>".$statement);

		return $result;
	}
	
	function remove() {
		global $con;
		
		mysqli_query($con,"START TRANSACTION") OR die("Fehler: Tabelle SIPpCall: Methode remove()<br>".mysqli_error($con)."<br>START TRANSACTION failed!");
		
		// If this version has already been tested (and thus a run has been created), then create a new version.
		$vObj = new Version($this->test_id, $this->test_version);
		if($vObj->hasRun()) {
			$new_version_number = $vObj->duplicateVersion(false);
			$new_call_id = $vObj->duplicateSIPpCalls($new_version_number, $this->id, false);
			$this->test_version = $new_version_number;
			$this->id = $new_call_id;
		}

		$statement = "DELETE FROM SIPpCall WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version='".$this->test_version."'";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table SIPpCall: Methode remove()<br>".mysqli_error($con)."<br>".$statement);
		
		mysqli_query($con,"COMMIT") OR die("Fehler: Tabelle SIPpCall: Methode remove()<br>".mysqli_error($con)."<br>COMMIT failed!");
	}
	
	function get($optSelect="") {
		global $con;

		if($optSelect != "") $optSelect = ", ".$optSelect;

		$statement = "SELECT * $optSelect FROM SIPpCall WHERE id=".$this->id;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table SIPpCall: Methode get()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		return $row;
	}
	
	function getOnly($select="") {
		global $con;

		$statement = "SELECT $select FROM SIPpCall WHERE id=".$this->id;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table SIPpCall: Methode getOnly()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		return $row;
	}
	
	function getOptions($row, $additionalParameters="") {
		$options = "";
		$this->addToOptions($options, $row->extended_parameters);
		$this->addToOptions($options, "-bind_local", $row->bind_local);
		$this->addToOptions($options, "-i", $row->a_i);
		$this->addToOptions($options, "-m", $row->a_m);
		if($row->a_nd == "t") $this->addToOptions($options, "-nd");
		if($row->a_nr == "t") $this->addToOptions($options, "-nr");
		$this->addToOptions($options, "-t", $row->a_t);
		$this->addToOptions($options, "-p", $row->a_p);
		$this->addToOptions($options, "-r", $row->a_r);
		$this->addToOptions($options, "-timeout", $row->a_timeout);
		if($row->a_pause_msg_ign == "t") $this->addToOptions($options, "-pause_msg_ign");
		if($row->a_trace_msg == "t") $this->addToOptions($options, "-trace_msg");
		if($row->a_trace_shortmsg == "t") $this->addToOptions($options, "-trace_shortmsg");
		if($additionalParameters != "") {
			$options .= " ".$additionalParameters;
			$this->addToOptions($options, $row->extended_parameters);
		}
		return $options;
	}
	
	private function addToOptions(&$options, $option, $value="-1") {
		$option = trim($option);
		$value = trim($value);
		if($option != "" && $value != "" && $value != "-1") {
			$options .= " ".$option;
			$options .= " ".$value;
		} else if($option != "" && $value == "-1") {
			$options .= " ".$option;		
		}
		
	}


	public function down() {
		$this->changePos(false);
	}
	
	public function up() {
		$this->changePos(true);
	}
	
	private function changePos($down) {

		global $con;
		
		mysqli_query($con,"START TRANSACTION");

		// If this version has already been tested (and thus a run has been created), then create a new version.
		$vObj = new Version($this->test_id, $this->test_version);
		if($vObj->hasRun()) {
			$new_version_number = $vObj->duplicateVersion(false);
			$new_call_id = $vObj->duplicateSIPpCalls($new_version_number, $this->id, false);
			$this->test_version = $new_version_number;
			$this->id = $new_call_id;
		}
	
		
		$statement = "SELECT pos FROM SIPpCall WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version='".$this->test_version."'";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle SIPpCall: Methode changePos()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_row($result);
		$pos = $row[0];

		if($down) $statement = "SELECT max(pos) as mpos FROM SIPpCall WHERE pos < $pos AND test_id=".$this->test_id." AND test_version='".$this->test_version."' AND party='".$this->party."'";
		else $statement = "SELECT min(pos) as mpos FROM SIPpCall WHERE pos > $pos AND test_id=".$this->test_id." AND test_version='".$this->test_version."' AND party='".$this->party."'";
		$result = mysqli_query ($con,$statement) OR  die("Fehler: Tabelle SIPpCall: Methode changePos()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		if($row->mpos != NULL) {
			$statement = "UPDATE SIPpCall SET pos = $pos WHERE pos = ".$row->mpos." AND test_id=".$this->test_id." AND test_version='".$this->test_version."' AND party='".$this->party."'";
			$result = mysqli_query ($con,$statement) OR  die("Fehler: Tabelle SIPpCall: Methode changePos()<br>".mysqli_error($con)."<br>".$statement);
	
			$statement = "UPDATE SIPpCall SET pos = ".$row->mpos." WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version='".$this->test_version."'";
			$result = mysqli_query ($con,$statement) OR  die("Fehler: Tabelle SIPpCall: Methode changePos()<br>".mysqli_error($con)."<br>".$statement);
		}
		mysqli_query($con,"COMMIT") OR die("Fehler: Tabelle SIPpCall: Methode changePos()<br>".mysqli_error($con)."<br>COMMIT failed.");
		
	}
}

class Run extends StandardTable {

	function __construct() {

		$attributes = array("id", "test_id", "test_version", "timestamp", "success");
		$values = func_get_args();
		$type_name = "";
		// __construct(tablename, attributes, values, type_name);
		parent::__construct("Run", $attributes, $values, $type_name);
	}
	
	function update() {
		global $con;

		
		$statement = "UPDATE ".$this->tabName." SET success='".$this->success."' WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle Run: Methode update()<br>".mysqli_error($con)."<br>".$statement);
		return $result;
	}

	function getAll($optSelect="", $optWhere="") {
		global $con;
		
		if($optSelect == "") $optSelect = "*";
		if($optWhere != "") $optWhere = " AND ".$optWhere;

		$statement = "SELECT $optSelect FROM Run WHERE test_id=".$this->test_id." AND test_version='".$this->test_version."' $optWhere ORDER BY timestamp DESC";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle Run: Methode getAll()<br>".mysqli_error($con)."<br>".$statement);
		return $result;
	}
	
	function get($optSelect="") {
		global $con;

		if($optSelect != "") $optSelect = ", ".$optSelect;

		$statement = "SELECT * $optSelect FROM Run WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Table Run: Methode get()<br>".mysqli_error($con)."<br>".$statement);
		$row = mysqli_fetch_object($result);
		return $row;
	}
	
	function remove() {
		global $con;
		$statement = "DELETE FROM ".$this->tabName." WHERE id=".$this->id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle ".$this->tabName.": Class Run: Methode remove()<br>".mysqli_error($con)."<br>".$statement);
	}
}

class Run_Call {
	var $run_id;
	var $call_id;
	var $test_id;
	var $test_version;
	var $std_error;
	var $exit_code;
	var $errors;
	var $rtt;
	var $log;
	var $shortmessages;
	var $stat;
	
	function Run_Call($run_id="", $test_id="", $test_version="", $call_id="", $std_error="", $exit_code="", $errors="", $rtt="", $log="", $shortmessages="", $stat="") {
		$this->run_id = $run_id;
		$this->call_id = $call_id;
		$this->test_id = $test_id;
		$this->test_version = $test_version;
		$this->std_error = $std_error;
		$this->exit_code = $exit_code;
		$this->errors = $errors;
		$this->rtt = $rtt;
		$this->log = $log;
		$this->shortmessages = $shortmessages;
		$this->stat = $stat;
	}
	
	function insert() {
		global $con;
		
		$statement = "INSERT INTO Run_Call (run_id, call_id, test_id, test_version, timestamp, std_error, exit_code, errors, rtt, log, shortmessages, stat) VALUES (".$this->run_id.", ".$this->call_id.", ".$this->test_id.", ".$this->test_version.",  0, '".$this->std_error."', '-1', '', '', '', '', '')";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle Run_Call: Methode insert()<br>".mysqli_error($con)."<br>".$statement);		
	}
	
	function update() {
		global $con;
		
		$statement = "UPDATE Run_Call SET std_error='".$this->std_error."', exit_code='".$this->exit_code."' , errors='".$this->errors."', rtt='".$this->rtt."', log='".$this->log."', shortmessages='".$this->shortmessages."', stat='".$this->stat."' WHERE run_id=".$this->run_id." AND call_id=".$this->call_id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle Run_Call: Methode update()<br>".mysqli_error($con)."<br>".$statement);		
	}
	
	function setTime() {
		global $con;
		
		$statement = "UPDATE Run_Call SET timestamp=CURRENT_TIMESTAMP() WHERE run_id=".$this->run_id." AND call_id=".$this->call_id." AND test_id=".$this->test_id." AND test_version=".$this->test_version;
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle Run_Call: Methode setTime()<br>".mysqli_error($con)."<br>".$statement);		
	}
	
	function getAll($party="") {
		global $con;
		
		if($party!="") $party="AND c.party='".$party."'";

		$statement = "SELECT c.*, s.name, rc.call_id, rc.run_id, rc.timestamp, rc.std_error, rc.exit_code, rc.errors, rc.rtt, rc.log, rc.shortmessages, rc.stat FROM Run_Call rc, SIPpCall c, Scenario s WHERE rc.run_id=".$this->run_id." AND rc.test_id=".$this->test_id." AND rc.test_version=".$this->test_version." AND rc.call_id=c.id AND rc.test_id=c.test_id AND rc.test_version=c.test_version AND c.scenario_id=s.id $party ORDER BY c.pos";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle Run_Call: Methode getAll()<br>".mysqli_error($con)."<br>".$statement);
		return $result;
	}
	
	function getOnly($field) {
		global $con;

		$statement = "SELECT $field FROM Run_Call rc WHERE run_id=".$this->run_id." AND test_id=".$this->test_id." AND test_version=".$this->test_version." AND call_id=".$this->call_id."";
		$result = mysqli_query ($con,$statement) OR die("Fehler: Tabelle Run_Call: Methode getAll()<br>".mysqli_error($con)."<br>".$statement);
		$row=mysqli_fetch_array($result,MYSQL_NUM);
		return $row;
	}
}




?>
