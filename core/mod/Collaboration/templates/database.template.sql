CREATE TABLE IF NOT EXISTS `collab_rooms` (
  `room_id` char(32) NOT NULL,UNIQUE KEY `room_id` (`room_id`),
  `room_name` tinytext NOT NULL,
  `room_members` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `collab_lists` (
  `list_id` char(32) NOT NULL,UNIQUE KEY `list_id` (`list_id`),
  `list_name` tinytext NOT NULL,
  `list_participants` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `collab_chat` (
  `chat_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,UNIQUE KEY `chat_id` (`chat_id`),
  `chat_from` char(32) NOT NULL,
  `chat_to` char(33) NOT NULL,
  `chat_body` text NOT NULL,
  `chat_sent` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
CREATE TABLE IF NOT EXISTS `collab_todo` (
  `todo_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,UNIQUE KEY `todo_id` (`todo_id`),
  `list_id` char(32) NOT NULL,
  `todo_label` tinytext NOT NULL,
  `todo_done` tinyint(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO `collab_rooms` VALUES('6fbde1fc117640048beb18f9d28cb39e', 'Everyone', '*');
INSERT INTO `collab_lists` VALUES('6fbde1fc117640048beb18f9d28cb39e', 'Example To-Do List', '*');
INSERT INTO `collab_todo` VALUES(NULL, '6fbde1fc117640048beb18f9d28cb39e', 'Read this item', 0);
INSERT INTO `collab_todo` VALUES(NULL, '6fbde1fc117640048beb18f9d28cb39e', 'Send a message', 1);