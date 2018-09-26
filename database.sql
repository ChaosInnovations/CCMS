SET time_zone = "+00:00";

/*
--------------------------
-- Drop Existing Tables --
--------------------------
*/

DROP TABLE IF EXISTS `access`;
DROP TABLE IF EXISTS `collab_chat`;
DROP TABLE IF EXISTS `collab_lists`;
DROP TABLE IF EXISTS `collab_rooms`;
DROP TABLE IF EXISTS `collab_todo`;
DROP TABLE IF EXISTS `config`;
DROP TABLE IF EXISTS `content_pages`;
DROP TABLE IF EXISTS `schedule`;
DROP TABLE IF EXISTS `tokens`;
DROP TABLE IF EXISTS `users`;

/*
-------------------
-- Create Tables --
-------------------
*/

/*-- User Tables*/
/* -- stores user info */
CREATE TABLE `users` (
  `uid` char(32) NOT NULL,UNIQUE KEY `uid` (`uid`),
  `email` tinytext NOT NULL,
  `name` tinytext NOT NULL,
  `registered` date NOT NULL,
  `permissions` text NOT NULL,
  `permviewbl` text NOT NULL,
  `permeditbl` text NOT NULL,
  `collab_lastseen` datetime DEFAULT NULL,
  `collab_pageid` tinytext,
  `collab_notifs` text NOT NULL,
  `notify` tinyint(1) NOT NULL DEFAULT '1',
  `last_notif` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/* -- stores uid/pwdhash pairs */
CREATE TABLE `access` (
  `uid` char(32) NOT NULL,UNIQUE KEY `uid` (`uid`),
  `pwd` char(128) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/* -- stores tokens` */
CREATE TABLE `tokens` (
  `uid` char(32) NOT NULL,
  `tid` char(32) NOT NULL,UNIQUE KEY `tid` (`tid`),
  `source_ip` varchar(45) NOT NULL,
  `start` date NOT NULL,
  `expire` date NOT NULL,
  `forcekill` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/* -- Site & Page Tables */
/* -- stores primary site properties */
CREATE TABLE `config` (
  `property` varchar(256) NOT NULL,UNIQUE KEY `property` (`property`),
  `value` varchar(256) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/* -- stores page content */
CREATE TABLE `content_pages` (
  `pageid` varchar(255) NOT NULL,UNIQUE KEY `pageid` (`pageid`),
  `title` text NOT NULL,
  `head` longtext NOT NULL,
  `body` longtext NOT NULL,
  `usehead` tinyint(1) NOT NULL DEFAULT '1',
  `usetop` tinyint(1) NOT NULL DEFAULT '1',
  `usebottom` tinyint(1) NOT NULL DEFAULT '1',
  `secure` tinyint(1) NOT NULL,
  `revision` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/* -- stores scheduled tasks */
CREATE TABLE `schedule` (
  `index` int(11) NOT NULL AUTO_INCREMENT,UNIQUE KEY `index` (`index`),
  `after` datetime DEFAULT CURRENT_TIMESTAMP,
  `function` tinytext,
  `args` text
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/* -- Collaboration Tables */
/* -- stores chat rooms */
CREATE TABLE `collab_rooms` (
  `room_id` char(32) NOT NULL,UNIQUE KEY `room_id` (`room_id`),
  `room_name` tinytext NOT NULL,
  `room_members` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/* -- stores todo lists */
CREATE TABLE `collab_lists` (
  `list_id` char(32) NOT NULL,UNIQUE KEY `list_id` (`list_id`),
  `list_name` tinytext NOT NULL,
  `list_participants` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/* -- stores chat messages */
CREATE TABLE `collab_chat` (
  `chat_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,UNIQUE KEY `chat_id` (`chat_id`),
  `chat_from` char(32) NOT NULL,
  `chat_to` char(33) NOT NULL,
  `chat_body` text NOT NULL,
  `chat_sent` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/* -- stores todo items */
CREATE TABLE `collab_todo` (
  `todo_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,UNIQUE KEY `todo_id` (`todo_id`),
  `list_id` char(32) NOT NULL,
  `todo_label` tinytext NOT NULL,
  `todo_done` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/* 
----------------------------
-- Insert Default Entries --
----------------------------
*/
 
INSERT INTO `config` VALUES('creationdate', UTC_DATE);
INSERT INTO `config` VALUES('email_notifs_from', '');
INSERT INTO `config` VALUES('email_notifs_host', '');
INSERT INTO `config` VALUES('email_notifs_pass', '');
INSERT INTO `config` VALUES('email_notifs_user', '');
INSERT INTO `config` VALUES('email_primary_from', '');
INSERT INTO `config` VALUES('email_primary_host', '');
INSERT INTO `config` VALUES('email_primary_pass', '');
INSERT INTO `config` VALUES('email_primary_user', '');
INSERT INTO `config` VALUES('websitetitle', 'CCMS Test');
INSERT INTO `content_pages` VALUES('', 'Home%20Page', '%3Cstyle%3E%0A%20%20h1%20%7B%0A%20%20%20%20font-size%3A%202rem%3B%0A%20%20%7D%0A%3C%2Fstyle%3E', '%3Cdiv%20class%3D%22container%22%3E%0A%20%20%3Ch1%3EContent!%3C%2Fh1%3E%0A%3C%2Fdiv%3E', 1, 0, 1, 0, '2018-09-21');
INSERT INTO `content_pages` VALUES('secure/newpage', 'Test', '', '%7B%7Bsitemap%7D%7D', 1, 1, 1, 1, '2018-09-21');
INSERT INTO `content_pages` VALUES('secureaccess', 'Secure%20Access%20Portal', '', '%7B%7Bloginform%7D%7D', 1, 1, 1, 0, '2017-01-29');
INSERT INTO `content_pages` VALUES('_default/bottom', 'Default%20Bottom', '', 'This%20goes%20after', 0, 0, 0, 1, '2018-09-21');
INSERT INTO `content_pages` VALUES('_default/head', 'Default%20Head', '', '', 0, 0, 0, 1, '0000-00-00');
INSERT INTO `content_pages` VALUES('_default/notfound', 'Page%20not%20found!', '', 'Page%20not%20Found%3A%20%3Ca%20href%3D%22%7B%7Bqueryerr%7D%7D%22%3E%7B%7Bqueryerr%7D%7D%3C%2Fa%3E', 1, 1, 1, 0, '2018-09-20');
INSERT INTO `content_pages` VALUES('_default/page', 'New%20Page', '', 'This%20is%20a%20new%20page!', 1, 1, 1, 1, '2018-09-21');
INSERT INTO `content_pages` VALUES('_default/top', 'Default%20Top', '', 'This%20goes%20before', 0, 0, 0, 1, '2018-09-21');

INSERT INTO `collab_rooms` VALUES('6fbde1fc117640048beb18f9d28cb39e', 'Everyone', '*');
INSERT INTO `collab_lists` VALUES('6fbde1fc117640048beb18f9d28cb39e', 'Example To-Do List', '*');
INSERT INTO `collab_todo` VALUES(NULL, '6fbde1fc117640048beb18f9d28cb39e', 'Read this item', 0);
INSERT INTO `collab_todo` VALUES(NULL, '6fbde1fc117640048beb18f9d28cb39e', 'Send a message', 1);

/*
---------
-- End --
---------
*/