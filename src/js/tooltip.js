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
 
// display a tooltip text with the function tooltip(txt) at the current mouseposition
var backgroundColor = "#ffffe0";
var borderColor = "#222222";
var fontColor = "#222222";
var fontFamily = "Verdana";
var fontSize = "10px";

var altTxt = "";
var altInterval;
var mX;
var mY;
		
var ttActive = false;
		
document.open();
document.write('<div id="tooltip" style="margin:10px; padding:5px; position:absolute; left:0px; top:0px; visibility:hidden"></div>');
document.close();
		
function mouse(ev) {
  	if (!ev) ev = window.event;
				
	mX = ev.pageX ? ev.pageX : ev.clientX;	
	mY = ev.pageY ? ev.pageY : (ev.clientY + document.body.scrollTop);	
}
document.onmousemove = mouse;
		
		
function tooltip(txt) {
	altTxt = txt;
	altInterval = window.setTimeout("startAlt()", 500);
}
		
function getWidth() {
	var wW = document.body.clientWidth ? document.body.clientWidth : window.innerWidth;
	return wW;
}
	
function noTooltip() {
	window.clearTimeout(altInterval);
	tt = document.getElementById("tooltip");
	tt.style.visibility = "hidden";
	if(ttActive == true) tt.removeChild(tt.firstChild);
	ttActive = false;			
}
		
function startAlt() {
	ttActive = true;
	tt = document.getElementById("tooltip");
	var wW = getWidth();
			
	if(mX > (wW/2)) {
		tt.style.left = "";
		tt.style.right = (wW - mX + 15 )+"px";
	} else {
		tt.style.right = "";
		var mNewX;
		if(document.all) mNewX =  mX + document.body.scrollLeft;
		else mNewX = mX;
		tt.style.left = (mNewX + 15)+"px";
	}

	tt.style.top = mY+"px";
	tt.style.backgroundColor = backgroundColor;
	tt.style.borderColor = borderColor;
	tt.style.borderStyle = "solid";
	tt.style.borderWidth = "1px";
	tt.style.color = fontColor;
	tt.style.fontFamily = fontFamily;
	tt.style.fontSize = fontSize;


	//var txtN = document.createTextNode("   "+altTxt+"   ");
	//tt.appendChild(txtN);
	tt.innerHTML = altTxt;
			
	tt.style.visibility = "visible";
	clearInterval(altInterval);			
}
