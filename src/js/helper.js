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


// Here you find some nice helpful functions.

// remove whitespaces from the beginning and the end of a string
function trim(txt) {
	txt = txt.replace(/^\s+/, "");
	txt = txt.replace(/\s+$/, "");			
	return txt;
}


var TEXT = 0;
var NUMBER = 1;
var DATE = 2;
var IP = 3;
// This function is for validating HTML form data, and alerting adequate error messages.	
function checkText(txtID, caption, maybeempty, type) {
	var preis;
	var textFeld = document.getElementById(txtID);
			
	var txt = trim(textFeld.value);
	var number = parseInt(txt);

	if(!maybeempty && txt.length == 0) {
		alert("The field '"+caption+"' must not be empty.");
		textFeld.focus();
		return false;
	}
	if(type==NUMBER  && txt != "" && isNaN(number)) {
		alert("Please input a number into the  field '"+caption+"'.");
		textFeld.focus();
		textFeld.select();				
		return false;
	}
	if(type==DATE  && txt != "" && checkDate(txt) == false) {
		alert("Please input a date (dd.mm.yyyy) into the field '"+caption+"'.");
		textFeld.focus();
		textFeld.select();				
		return false;
	}	
	if(type==IP && txt != "" && checkIP(txt) == false) {
		alert("Please input a valid IP address into the field '"+caption+"'.");
		textFeld.focus();
		textFeld.select();				
		return false;
	}
			
	if(type == NUMBER && txt != "") textFeld.value = number;
	
	return true;
}



// is the HTML heckbox with the specified id checked?
function isChecked(checkBoxId) {
	var cb = document.getElementById(checkBoxId);
	return cb.checked;
}

// is z a number?
function istZahl(z) {
	var num = parseInt(z,10);
	return !isNaN(num);
}

// is txt an IP adress?
function checkIP(txt) {
	var parts = txt.split(".");	
	if(parts.length != 4) return false;
	if(istZahl(parts[0]) && parts[0] < 256 && istZahl(parts[1]) && parts[1] < 256 && istZahl(parts[2]) && parts[2] < 256 && istZahl(parts[3]) && parts[3] < 256) return true;
	return false;
}

function daysInFebruary (year){
	// February has 29 days in any year evenly divisible by four,
    // EXCEPT for centurial years which are not also divisible by 400.
    return (((year % 4 == 0) && ( (!(year % 100 == 0)) || (year % 400 == 0))) ? 29 : 28 );
}

function DaysArray(year) {
	for (var i = 1; i <= 12; i++) {
		this[i] = 31
		if (i==4 || i==6 || i==9 || i==11) {this[i] = 30}
		if (i==2) {this[i] = daysInFebruary(year)}
   	} 
   	return this
}

//is dtStr a valid date?
function checkDate(dtStr){
	var parts = dtStr.split(".");
	if(parts.length != 3) return false;
	
	var strDay=parts[0];
	var strMonth=parts[1];
	var strYear=parts[2];

	if(!istZahl(strDay) || !istZahl(strMonth) || !istZahl(strYear)) return false;

	day=parseInt(strDay,10)
	month=parseInt(strMonth,10)
	year=parseInt(strYear,10)

	var daysInMonth = DaysArray(year)
	
	if(year<1000 || year>9999) {
		//alert("Please enter a valid year")
		return false;
	}
	if (month<1 || month>12){
		//alert("Please enter a valid month")
		return false
	}
	if (day<1 || day > daysInMonth[month]){
		//alert("Please enter a valid day: "+day+" - "+dtStr)
		return false
	}
	
	return true
}

// select value in the HTML selectbox with comboID
function selectCombo(comboID, value) {
	var i = 0;
	var combo = document.getElementById(comboID);
	var anz = combo.options.length;
	while(i < anz && combo.options[i].value != value) i++;
	if(i < anz) combo.selectedIndex = i;
}



