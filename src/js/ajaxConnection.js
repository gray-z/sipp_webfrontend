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
 
// Crossbrowser function to create a ajax request object
function getAjaxObject() {
	var ajax = null;

	// Mozilla, Opera, Safari and Internet Explorer 7
	if (typeof XMLHttpRequest != 'undefined') {
    	ajax = new XMLHttpRequest();
		if (ajax.overrideMimeType) {
			//ajax.overrideMimeType('text/xml');
		}
	}
	if (!ajax) {
    	// Internet Explorer 6 and older
	    try {
    	    ajax  = new ActiveXObject("Msxml2.XMLHTTP");
	    } catch(e) {
    	    try {
        	    ajax  = new ActiveXObject("Microsoft.XMLHTTP");
	        } catch(e) {
    	        ajax  = null;
        	}
	    }
	}
	return ajax;
}
