SET AUTOCOMMIT=0;
START TRANSACTION;


DROP TABLE IF EXISTS `SIPpCall`;
CREATE TABLE IF NOT EXISTS `SIPpCall` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `description` text,
  `xml` text NOT NULL,
  `csv` text,
  `def` enum('t','f') NOT NULL default 'f',
  `bind_local` varchar(15) default NULL,
  `executable` varchar(10) NOT NULL default '',
  `ip_address` varchar(50) NOT NULL default '',
  `monitor` enum('t','f') NOT NULL default 'f',
  `a_i` varchar(50) default NULL,
  `a_m` int(10) unsigned default NULL,
  `a_nd` enum('t','f') NOT NULL default 't',
  `a_nr` enum('t','f') NOT NULL default 'f',
  `a_t` varchar(4) default NULL,
  `a_p` smallint(5) unsigned default NULL,
  `a_r` smallint(5) unsigned default NULL,
  `a_timeout` tinyint(3) unsigned default NULL,
  `a_pause_msg_ign` enum('t','f') NOT NULL default 't',
  `a_trace_msg` enum('t','f') NOT NULL default 'f',
  `a_trace_shortmsg` enum('t','f') NOT NULL default 'f',
  `extended_parameters` varchar(100) default NULL,
  `pos` tinyint(3) unsigned NOT NULL default '0',
  `party` enum('a','b') NOT NULL default 'a',
  `log` enum('t','f') NOT NULL default 'f',
  `scenario_id` smallint(5) unsigned NOT NULL default '0',
  `test_id` smallint(5) unsigned NOT NULL default '0',
  `test_version` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`,`test_id`,`test_version`),
  KEY `scenario_id` (`scenario_id`),
  KEY `test_id` (`test_id`,`test_version`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `Run`;
CREATE TABLE IF NOT EXISTS `Run` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `success` enum('abort','success','error','partly succeeded') default NULL,
  `test_id` smallint(5) unsigned NOT NULL default '0',
  `test_version` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`,`test_id`,`test_version`),
  KEY `test_id` (`test_id`,`test_version`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

DROP TABLE IF EXISTS `Run_Call`;
CREATE TABLE IF NOT EXISTS `Run_Call` (
  `run_id` smallint(5) unsigned NOT NULL default '0',
  `call_id` smallint(5) unsigned NOT NULL default '0',
  `test_id` smallint(5) unsigned NOT NULL default '0',
  `test_version` tinyint(3) unsigned NOT NULL default '0',
  `timestamp` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `std_error` text,
  `exit_code` char(3) NOT NULL default '',
  `errors` text,
  `rtt` text,
  `log` text,
  `shortmessages` text,
  `stat` text,
  PRIMARY KEY  (`run_id`,`call_id`,`test_id`,`test_version`),
  KEY `run_id` (`run_id`,`test_id`,`test_version`),
  KEY `call_id` (`call_id`,`test_id`,`test_version`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `Scenario`;
CREATE TABLE IF NOT EXISTS `Scenario` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text,
  `xml` text NOT NULL,
  `csv` text,
  `bind_local` varchar(15) default NULL,
  `def` enum('t','f') NOT NULL default 'f',
  `visible` enum('t','f') NOT NULL default 't',
  `pos` tinyint(3) unsigned NOT NULL default '0',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=6 ;


INSERT INTO `Scenario` (`id`, `name`, `description`, `xml`, `csv`, `bind_local`, `def`, `visible`, `pos`) VALUES (1, 'uac', 'Standard SipStone UAC (default).', '', NULL, NULL, 't', 't', 5);
INSERT INTO `Scenario` (`id`, `name`, `description`, `xml`, `csv`, `bind_local`, `def`, `visible`, `pos`) VALUES (2, 'uas', 'Simple UAS responder.', '', NULL, NULL, 't', 't', 4);
INSERT INTO `Scenario` (`id`, `name`, `description`, `xml`, `csv`, `bind_local`, `def`, `visible`, `pos`) VALUES (3, 'regexp', 'Standard SipStone UAC - with regexp and variables.', '', NULL, NULL, 't', 't', 3);
INSERT INTO `Scenario` (`id`, `name`, `description`, `xml`, `csv`, `bind_local`, `def`, `visible`, `pos`) VALUES (4, 'branchc', 'Branching and conditional branching in scenarios - client.', '', NULL, NULL, 't', 't', 2);
INSERT INTO `Scenario` (`id`, `name`, `description`, `xml`, `csv`, `bind_local`, `def`, `visible`, `pos`) VALUES (5, 'branchs', 'Branching and conditional branching in scenarios - server.', '', NULL, NULL, 't', 't', 1);



DROP TABLE IF EXISTS `Test`;
CREATE TABLE IF NOT EXISTS `Test` (
  `id` smallint(5) unsigned NOT NULL auto_increment,
  `name` varchar(100) NOT NULL default '',
  `description` text,
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP,
  `visible` enum('t','f') NOT NULL default 't',
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


DROP TABLE IF EXISTS `Version`;
CREATE TABLE IF NOT EXISTS `Version` (
  `id` smallint(5) unsigned NOT NULL default '0',
  `version` tinyint(3) unsigned NOT NULL default '0',
  `created` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `delay` smallint(5) unsigned NOT NULL default '0',
  `delay_party` enum('a','b') NOT NULL default 'a',
  `visible` enum('t','f') NOT NULL default 't',
  PRIMARY KEY  (`id`,`version`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `SIPpCall`
  ADD CONSTRAINT `Call_ibfk_1` FOREIGN KEY (`scenario_id`) REFERENCES `Scenario` (`id`),
  ADD CONSTRAINT `Call_ibfk_2` FOREIGN KEY (`test_id`, `test_version`) REFERENCES `Version` (`id`, `version`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `Run`
  ADD CONSTRAINT `Run_ibfk_1` FOREIGN KEY (`test_id`, `test_version`) REFERENCES `Version` (`id`, `version`) ON DELETE CASCADE ON UPDATE CASCADE;

ALTER TABLE `Run_Call`
  ADD CONSTRAINT `Run_Call_ibfk_1` FOREIGN KEY (`run_id`, `test_id`, `test_version`) REFERENCES `Run` (`id`, `test_id`, `test_version`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `Run_Call_ibfk_2` FOREIGN KEY (`call_id`, `test_id`, `test_version`) REFERENCES `SIPpCall` (`id`, `test_id`, `test_version`) ON DELETE CASCADE ON UPDATE CASCADE;


ALTER TABLE `Version`
  ADD CONSTRAINT `Version_ibfk_1` FOREIGN KEY (`id`) REFERENCES `Test` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

COMMIT;
