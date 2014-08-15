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

// Opens a window for a test run.
function run(url, id, version) {
	Runwindow = window.open(url+"?id="+id+"&version="+version, "SIPpRun", "width=1005,height=500,menubar=no,status=no,toolbar=no,location=no,resizable=yes,scrollbars=yes");
	Runwindow.focus();
}