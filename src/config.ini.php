;<?/* with this line a browser is not able to view this file, so please leave it.
;
;  SIPp Webfrontend	- Web tool to create, manage and run SIPp test cases
;  Copyright (c) 2008 Mario Smeritschnig
;  Idea, support, planning, guidance Michael Hirschbichler
; 
; * * * BEGIN LICENCE * * *
; 
;  This file is part of SIPp Webfrontend.
;  
;  SIPp Webfrontend is free software: you can redistribute it and/or modify
;  it under the terms of the GNU General Public License as published by
;  the Free Software Foundation, either version 3 of the License, or
;  (at your option) any later version.
;  
;  SIPp Webfrontend is distributed in the hope that it will be useful,
;  but WITHOUT ANY WARRANTY; without even the implied warranty of
;  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
;  GNU General Public License for more details.
;  
;  You should have received a copy of the GNU General Public License
;  along with SIPp Webfrontend.  If not, see <http://www.gnu.org/licenses/>
;
; 
; * * * END LICENCE * * *
; 
;
;
;
;
; This configuration file is divided in three sections respectively tagged with [EXECUTABLES], [AVP], and [CONFIG].

; In the [EXECUTABLES]-section SIPp executables and their versions can be specified. At least one executable is mandatory.

; In the [AVP]-section global command line parameters for SIPp can be specified in form of attribute-value pairs (attribute = value).
; I.e. most parameters of SIPp (without the leading -) will be accepted (see sipp -h for more details).
; These parameters can be overwritten in each individual call by using the 'Extended parameters' textarea in the web tool.
; Take care that you only use parameters that work with every version of SIPp you are using with this tool.
; If a attribute has no value, but is a switch, use TRUE as a value (e.g. aa = TRUE)
; If a value contains non-alphanumeric characters it needs to be enclosed in double-quotes (").
; Parameters not allowed are:
;   i, m, nd, nr, t, p, r, timeout, pause_msg_ign, v, bind_local, inf, sd, sf, sn, stf, trace_msg,
;   trace_shortmsg, trace_screen, trace_err, trace_timeout, trace_stat, trace_rtt, trace_logs
;
; In the [CONFIG]-section you will find program specific parameters, like admin password or mysql-database connection information...



; Executables section starts here
[EXECUTABLES]

; Specify the absolute paths to the SIPp executables using the syntax: version_number = path
; The first occurrence of such a line, defines the default version. At least one version is mandatory.
; e.g.
; 2.0 = "/usr/bin/sipp2/sipp"
; 1.0 = "/usr/bin/sipp1/sipp"

; Attribute-value-pairs section starts here
[AVP]

; Set the local control port number. Default is 8888.
; cp = 8888

; Set the statistics report frequency on screen. Default is
; 1 and default unit is seconds. Mandatory.
f = 1


; Set the maximum number of simultaneous calls. Once this
; limit is reached, traffic is decreased until the number
: of open calls goes down. Default:
; (3 * call_duration (s) * rate).
l = 10000

; Set the local port number.  By default, the system tries to find a free port, starting at 5060. Mandatory.
p = 5060

; The response times are dumped in the 
; log file defined by -trace_rtt each time 'freq' measures are performed.
; Default value is 200.
; rtt_freq = 200

; Set the timer resolution. Default unit is milliseconds. 
; This option has an impact on timers precision. Small
; values allow more precise scheduling but impacts CPU
; usage. If the compression is on, the value is set to
; 50ms. The default value is 10ms.
; timer_resol = 10



; Config section starts here
[CONFIG]

; Specify the host, user, password, and the database name to access the mysql-database.
db_host = "localhost"
db_user = "user"
db_pwd = "passwd"
db_name = "SIPpDB"

; With csv_separator you can set the separator for downloadable csv files (log files, injection files ...).
; Default separator is ;
csv_separator = ";"

; Specify the password for the admin section. Use admin_pwd = "" to disable admin-authentication.
admin_pwd = "secret"

; Here you can specify the minimum time, sipp processes and tempfiles may reside after creation. The unit is seconds, the default value 3600.
remove_garbage_after = 3600

; with this line a browser is not able to view this file, so please leave it. */?>
