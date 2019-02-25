CREATE TABLE IF NOT EXISTS `content_pages` (
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

INSERT INTO `content_pages` VALUES('', 'Home%20Page', '%3Cstyle%3E%0A%20%20h1%20%7B%0A%20%20%20%20font-size%3A%202rem%3B%0A%20%20%7D%0A%3C%2Fstyle%3E', '%3Cdiv%20class%3D%22container%22%3E%0A%20%20%3Ch1%3EContent!%3C%2Fh1%3E%0A%3C%2Fdiv%3E', 1, 0, 1, 0, '2018-09-21');
INSERT INTO `content_pages` VALUES('secure/newpage', 'Test', '', '%7B%7Bsitemap%7D%7D', 1, 1, 1, 1, '2018-09-21');
INSERT INTO `content_pages` VALUES('secureaccess', 'Secure%20Access%20Portal', '', '%7B%7Bloginform%7D%7D', 1, 1, 1, 0, '2017-01-29');
INSERT INTO `content_pages` VALUES('_default/bottom', 'Default%20Bottom', '', 'This%20goes%20after', 0, 0, 0, 1, '2018-09-21');
INSERT INTO `content_pages` VALUES('_default/head', 'Default%20Head', '', '', 0, 0, 0, 1, '0000-00-00');
INSERT INTO `content_pages` VALUES('_default/notfound', 'Page%20not%20found!', '', 'Page%20not%20Found%3A%20%3Ca%20href%3D%22%7B%7Bpageid%7D%7D%22%3E%7B%7Bqueryerr%7D%7D%3C%2Fa%3E', 1, 1, 1, 0, '2018-09-20');
INSERT INTO `content_pages` VALUES('_default/page', 'New%20Page', '', 'This%20is%20a%20new%20page!', 1, 1, 1, 1, '2018-09-21');
INSERT INTO `content_pages` VALUES('_default/top', 'Default%20Top', '', 'This%20goes%20before', 0, 0, 0, 1, '2018-09-21');


/* TEMPORARY SITE CONFIGURATION DEFAULTS */
CREATE TABLE `config` (
  `property` varchar(256) NOT NULL,UNIQUE KEY `property` (`property`),
  `value` varchar(256) NOT NULL DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

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