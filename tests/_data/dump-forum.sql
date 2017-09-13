

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `phorum_banlists`
--

DROP TABLE IF EXISTS `phorum_banlists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_banlists` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` tinyint(4) NOT NULL DEFAULT '0',
  `pcre` tinyint(1) NOT NULL DEFAULT '0',
  `string` varchar(255) NOT NULL DEFAULT '',
  `comments` text NOT NULL,
  PRIMARY KEY (`id`),
  KEY `forum_id` (`forum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_banlists`
--

LOCK TABLES `phorum_banlists` WRITE;
/*!40000 ALTER TABLE `phorum_banlists` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_banlists` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_files`
--

DROP TABLE IF EXISTS `phorum_files`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_files` (
  `file_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `filename` varchar(255) NOT NULL DEFAULT '',
  `filesize` int(10) unsigned NOT NULL DEFAULT '0',
  `file_data` mediumtext NOT NULL,
  `add_datetime` int(10) unsigned NOT NULL DEFAULT '0',
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `link` varchar(10) NOT NULL DEFAULT '',
  PRIMARY KEY (`file_id`),
  KEY `add_datetime` (`add_datetime`),
  KEY `message_id_link` (`message_id`,`link`),
  KEY `user_id_link` (`user_id`,`link`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_files`
--

LOCK TABLES `phorum_files` WRITE;
/*!40000 ALTER TABLE `phorum_files` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_files` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_forum_group_xref`
--

DROP TABLE IF EXISTS `phorum_forum_group_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_forum_group_xref` (
  `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `permission` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`forum_id`,`group_id`),
  KEY `group_id` (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_forum_group_xref`
--

LOCK TABLES `phorum_forum_group_xref` WRITE;
/*!40000 ALTER TABLE `phorum_forum_group_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_forum_group_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_forums`
--

DROP TABLE IF EXISTS `phorum_forums`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_forums` (
  `forum_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL DEFAULT '',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `description` text NOT NULL,
  `template` varchar(50) NOT NULL DEFAULT '',
  `folder_flag` tinyint(1) NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `list_length_flat` int(10) unsigned NOT NULL DEFAULT '0',
  `list_length_threaded` int(10) unsigned NOT NULL DEFAULT '0',
  `moderation` int(10) unsigned NOT NULL DEFAULT '0',
  `threaded_list` tinyint(1) NOT NULL DEFAULT '0',
  `threaded_read` tinyint(1) NOT NULL DEFAULT '0',
  `float_to_top` tinyint(1) NOT NULL DEFAULT '0',
  `check_duplicate` tinyint(1) NOT NULL DEFAULT '0',
  `allow_attachment_types` varchar(100) NOT NULL DEFAULT '',
  `max_attachment_size` int(10) unsigned NOT NULL DEFAULT '0',
  `max_totalattachment_size` int(10) unsigned NOT NULL DEFAULT '0',
  `max_attachments` int(10) unsigned NOT NULL DEFAULT '0',
  `pub_perms` int(10) unsigned NOT NULL DEFAULT '0',
  `reg_perms` int(10) unsigned NOT NULL DEFAULT '0',
  `display_ip_address` tinyint(1) NOT NULL DEFAULT '1',
  `allow_email_notify` tinyint(1) NOT NULL DEFAULT '1',
  `language` varchar(100) NOT NULL DEFAULT 'english',
  `email_moderators` tinyint(1) NOT NULL DEFAULT '0',
  `message_count` int(10) unsigned NOT NULL DEFAULT '0',
  `sticky_count` int(10) unsigned NOT NULL DEFAULT '0',
  `thread_count` int(10) unsigned NOT NULL DEFAULT '0',
  `last_post_time` int(10) unsigned NOT NULL DEFAULT '0',
  `display_order` int(10) unsigned NOT NULL DEFAULT '0',
  `read_length` int(10) unsigned NOT NULL DEFAULT '0',
  `vroot` int(10) unsigned NOT NULL DEFAULT '0',
  `edit_post` tinyint(1) NOT NULL DEFAULT '1',
  `template_settings` text NOT NULL,
  `forum_path` text NOT NULL,
  `count_views` tinyint(1) NOT NULL DEFAULT '0',
  `count_views_per_thread` tinyint(1) NOT NULL DEFAULT '0',
  `display_fixed` tinyint(1) NOT NULL DEFAULT '0',
  `reverse_threading` tinyint(1) NOT NULL DEFAULT '0',
  `inherit_id` int(10) unsigned DEFAULT NULL,
  `cache_version` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`forum_id`),
  KEY `name` (`name`),
  KEY `active` (`active`,`parent_id`),
  KEY `group_id` (`parent_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_forums`
--

LOCK TABLES `phorum_forums` WRITE;
/*!40000 ALTER TABLE `phorum_forums` DISABLE KEYS */;
INSERT INTO `phorum_forums` VALUES (1,'Announcements',1,'Read this forum first to find out the latest information.','emerald',0,0,30,15,0,0,0,1,0,'',0,0,0,1,15,0,1,'english',0,0,0,0,0,99,30,0,1,'','a:2:{i:0;s:8:\"Phorum 5\";i:1;s:13:\"Announcements\";}',0,0,0,0,0,0),(2,'Test Forum',1,'This is a test forum.  Feel free to delete it or edit after installation, using the admin interface.','emerald',0,0,30,15,0,0,0,1,0,'',0,0,0,1,15,0,1,'english',0,1,0,1,1442139722,0,30,0,1,'','a:2:{i:0;s:8:\"Phorum 5\";i:2;s:10:\"Test Forum\";}',0,0,0,0,0,1);
/*!40000 ALTER TABLE `phorum_forums` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_groups`
--

DROP TABLE IF EXISTS `phorum_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_groups` (
  `group_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL DEFAULT '',
  `open` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_groups`
--

LOCK TABLES `phorum_groups` WRITE;
/*!40000 ALTER TABLE `phorum_groups` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_groups` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_messages`
--

DROP TABLE IF EXISTS `phorum_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_messages` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
  `thread` int(10) unsigned NOT NULL DEFAULT '0',
  `parent_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `author` varchar(255) NOT NULL DEFAULT '',
  `subject` varchar(255) NOT NULL DEFAULT '',
  `body` text NOT NULL,
  `email` varchar(100) NOT NULL DEFAULT '',
  `ip` varchar(255) NOT NULL DEFAULT '',
  `status` tinyint(4) NOT NULL DEFAULT '2',
  `msgid` varchar(100) NOT NULL DEFAULT '',
  `modifystamp` int(10) unsigned NOT NULL DEFAULT '0',
  `thread_count` int(10) unsigned NOT NULL DEFAULT '0',
  `moderator_post` tinyint(1) NOT NULL DEFAULT '0',
  `sort` tinyint(4) NOT NULL DEFAULT '2',
  `datestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `meta` mediumtext,
  `viewcount` int(10) unsigned NOT NULL DEFAULT '0',
  `threadviewcount` int(10) unsigned NOT NULL DEFAULT '0',
  `closed` tinyint(1) NOT NULL DEFAULT '0',
  `recent_message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `recent_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `recent_author` varchar(255) NOT NULL DEFAULT '',
  `moved` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`message_id`),
  KEY `thread_message` (`thread`,`message_id`),
  KEY `thread_forum` (`thread`,`forum_id`),
  KEY `special_threads` (`sort`,`forum_id`),
  KEY `status_forum` (`status`,`forum_id`),
  KEY `list_page_float` (`forum_id`,`parent_id`,`modifystamp`),
  KEY `list_page_flat` (`forum_id`,`parent_id`,`thread`),
  KEY `new_count` (`forum_id`,`status`,`moved`,`message_id`),
  KEY `new_threads` (`forum_id`,`status`,`parent_id`,`moved`,`message_id`),
  KEY `recent_threads` (`status`,`parent_id`,`message_id`,`forum_id`),
  KEY `updated_threads` (`status`,`parent_id`,`modifystamp`),
  KEY `dup_check` (`forum_id`,`author`(50),`subject`,`datestamp`),
  KEY `forum_max_message` (`forum_id`,`message_id`,`status`,`parent_id`),
  KEY `last_post_time` (`forum_id`,`status`,`modifystamp`),
  KEY `next_prev_thread` (`forum_id`,`status`,`thread`),
  KEY `recent_user_id` (`recent_user_id`),
  KEY `user_messages` (`user_id`,`message_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_messages`
--

LOCK TABLES `phorum_messages` WRITE;
/*!40000 ALTER TABLE `phorum_messages` DISABLE KEYS */;
INSERT INTO `phorum_messages` VALUES (1,2,1,0,0,'Phorum Installer','Test Message','This is a test message. You can delete it after installation using the moderation tools. These tools will be visible in this screen if you log in as the administrator user that you created during install.\n\nPhorum 5 Team','','127.0.0.1',2,'',1442139722,1,0,2,1442139722,'a:2:{s:11:\"message_ids\";a:1:{i:0;i:1;}s:21:\"message_ids_moderator\";a:1:{i:0;i:1;}}',0,0,0,1,0,'Phorum Installer',0);
/*!40000 ALTER TABLE `phorum_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_messages_edittrack`
--

DROP TABLE IF EXISTS `phorum_messages_edittrack`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_messages_edittrack` (
  `track_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `time` int(10) unsigned NOT NULL DEFAULT '0',
  `diff_body` text,
  `diff_subject` text,
  PRIMARY KEY (`track_id`),
  KEY `message_id` (`message_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_messages_edittrack`
--

LOCK TABLES `phorum_messages_edittrack` WRITE;
/*!40000 ALTER TABLE `phorum_messages_edittrack` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_messages_edittrack` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_pm_buddies`
--

DROP TABLE IF EXISTS `phorum_pm_buddies`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_pm_buddies` (
  `pm_buddy_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `buddy_user_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`pm_buddy_id`),
  UNIQUE KEY `userids` (`user_id`,`buddy_user_id`),
  KEY `buddy_user_id` (`buddy_user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_pm_buddies`
--

LOCK TABLES `phorum_pm_buddies` WRITE;
/*!40000 ALTER TABLE `phorum_pm_buddies` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_pm_buddies` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_pm_folders`
--

DROP TABLE IF EXISTS `phorum_pm_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_pm_folders` (
  `pm_folder_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `foldername` varchar(20) NOT NULL DEFAULT '',
  PRIMARY KEY (`pm_folder_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_pm_folders`
--

LOCK TABLES `phorum_pm_folders` WRITE;
/*!40000 ALTER TABLE `phorum_pm_folders` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_pm_folders` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_pm_messages`
--

DROP TABLE IF EXISTS `phorum_pm_messages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_pm_messages` (
  `pm_message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `author` varchar(255) NOT NULL DEFAULT '',
  `subject` varchar(100) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `datestamp` int(10) unsigned NOT NULL DEFAULT '0',
  `meta` mediumtext NOT NULL,
  PRIMARY KEY (`pm_message_id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_pm_messages`
--

LOCK TABLES `phorum_pm_messages` WRITE;
/*!40000 ALTER TABLE `phorum_pm_messages` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_pm_messages` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_pm_xref`
--

DROP TABLE IF EXISTS `phorum_pm_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_pm_xref` (
  `pm_xref_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `pm_folder_id` int(10) unsigned NOT NULL DEFAULT '0',
  `special_folder` varchar(10) DEFAULT NULL,
  `pm_message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `read_flag` tinyint(1) NOT NULL DEFAULT '0',
  `reply_flag` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`pm_xref_id`),
  KEY `xref` (`user_id`,`pm_folder_id`,`pm_message_id`),
  KEY `read_flag` (`read_flag`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_pm_xref`
--

LOCK TABLES `phorum_pm_xref` WRITE;
/*!40000 ALTER TABLE `phorum_pm_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_pm_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_search`
--

DROP TABLE IF EXISTS `phorum_search`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_search` (
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
  `search_text` mediumtext NOT NULL,
  PRIMARY KEY (`message_id`),
  KEY `forum_id` (`forum_id`),
  FULLTEXT KEY `search_text` (`search_text`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_search`
--

LOCK TABLES `phorum_search` WRITE;
/*!40000 ALTER TABLE `phorum_search` DISABLE KEYS */;
INSERT INTO `phorum_search` VALUES (1,2,'Phorum Installer | Test Message | This is a test message. You can delete it after installation using the moderation tools. These tools will be visible in this screen if you log in as the administrator user that you created during install.\n\nPhorum 5 Team');
/*!40000 ALTER TABLE `phorum_search` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_settings`
--

DROP TABLE IF EXISTS `phorum_settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_settings` (
  `name` varchar(255) NOT NULL DEFAULT '',
  `type` enum('V','S') NOT NULL DEFAULT 'V',
  `data` text NOT NULL,
  PRIMARY KEY (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_settings`
--

LOCK TABLES `phorum_settings` WRITE;
/*!40000 ALTER TABLE `phorum_settings` DISABLE KEYS */;
INSERT INTO `phorum_settings` VALUES ('admin_session_salt','V','0.79293200 1442139722'),('cache','V','/tmp'),('cache_css','V','1'),('cache_javascript','V','1'),('cache_messages','V','0'),('cache_newflags','V','0'),('cache_rss','V','0'),('cache_users','V','0'),('default_feed','V','rss'),('default_forum_options','S','a:24:{s:8:\"forum_id\";i:0;s:10:\"moderation\";i:0;s:16:\"email_moderators\";i:0;s:9:\"pub_perms\";i:1;s:9:\"reg_perms\";i:15;s:13:\"display_fixed\";i:0;s:8:\"template\";s:7:\"emerald\";s:8:\"language\";s:7:\"english\";s:13:\"threaded_list\";i:0;s:13:\"threaded_read\";i:0;s:17:\"reverse_threading\";i:0;s:12:\"float_to_top\";i:1;s:16:\"list_length_flat\";i:30;s:20:\"list_length_threaded\";i:15;s:11:\"read_length\";i:30;s:18:\"display_ip_address\";i:0;s:18:\"allow_email_notify\";i:1;s:15:\"check_duplicate\";i:1;s:11:\"count_views\";i:2;s:15:\"max_attachments\";i:0;s:22:\"allow_attachment_types\";s:0:\"\";s:19:\"max_attachment_size\";i:0;s:24:\"max_totalattachment_size\";i:0;s:5:\"vroot\";i:0;}'),('description','V','Congratulations!  You have installed Phorum 5!  To change this text, go to your admin, choose General Settings and change the description'),('display_name_source','V','username'),('dns_lookup','V','1'),('enable_dropdown_userlist','V','1'),('enable_moderator_notifications','V','1'),('enable_new_pm_count','V','1'),('enable_pm','V','1'),('file_fileinfo_ext','V','1'),('file_offsite','V','0'),('file_space_quota','V',''),('file_types','V',''),('file_uploads','V','0'),('head_tags','V',''),('hide_forums','V','1'),('hooks','S','a:16:{s:12:\"after_header\";a:2:{s:4:\"mods\";a:3:{i:0;s:13:\"announcements\";i:1;s:12:\"editor_tools\";i:2;s:7:\"smileys\";}s:5:\"funcs\";a:3:{i:0;s:25:\"phorum_show_announcements\";i:1;s:36:\"phorum_mod_editor_tools_after_header\";i:2;s:31:\"phorum_mod_smileys_after_header\";}}s:6:\"common\";a:2:{s:4:\"mods\";a:2:{i:0;s:13:\"announcements\";i:1;s:12:\"editor_tools\";}s:5:\"funcs\";a:2:{i:0;s:26:\"phorum_setup_announcements\";i:1;s:30:\"phorum_mod_editor_tools_common\";}}s:12:\"css_register\";a:2:{s:4:\"mods\";a:4:{i:0;s:13:\"announcements\";i:1;s:12:\"editor_tools\";i:2;s:7:\"smileys\";i:3;s:6:\"bbcode\";}s:5:\"funcs\";a:4:{i:0;s:37:\"phorum_mod_announcements_css_register\";i:1;s:36:\"phorum_mod_editor_tools_css_register\";i:2;s:31:\"phorum_mod_smileys_css_register\";i:3;s:30:\"phorum_mod_bbcode_css_register\";}}s:4:\"lang\";a:2:{s:4:\"mods\";a:3:{i:0;s:12:\"editor_tools\";i:1;s:7:\"smileys\";i:2;s:6:\"bbcode\";}s:5:\"funcs\";a:3:{i:0;s:0:\"\";i:1;s:0:\"\";i:2;s:0:\"\";}}s:13:\"before_editor\";a:2:{s:4:\"mods\";a:1:{i:0;s:12:\"editor_tools\";}s:5:\"funcs\";a:1:{i:0;s:37:\"phorum_mod_editor_tools_before_editor\";}}s:26:\"tpl_editor_before_textarea\";a:2:{s:4:\"mods\";a:1:{i:0;s:12:\"editor_tools\";}s:5:\"funcs\";a:1:{i:0;s:50:\"phorum_mod_editor_tools_tpl_editor_before_textarea\";}}s:13:\"before_footer\";a:2:{s:4:\"mods\";a:1:{i:0;s:12:\"editor_tools\";}s:5:\"funcs\";a:1:{i:0;s:37:\"phorum_mod_editor_tools_before_footer\";}}s:19:\"javascript_register\";a:2:{s:4:\"mods\";a:3:{i:0;s:12:\"editor_tools\";i:1;s:7:\"smileys\";i:2;s:6:\"bbcode\";}s:5:\"funcs\";a:3:{i:0;s:43:\"phorum_mod_editor_tools_javascript_register\";i:1;s:38:\"phorum_mod_smileys_javascript_register\";i:2;s:37:\"phorum_mod_bbcode_javascript_register\";}}s:12:\"format_fixup\";a:2:{s:4:\"mods\";a:1:{i:0;s:7:\"smileys\";}s:5:\"funcs\";a:1:{i:0;s:31:\"phorum_mod_smileys_format_fixup\";}}s:18:\"editor_tool_plugin\";a:2:{s:4:\"mods\";a:2:{i:0;s:6:\"bbcode\";i:1;s:7:\"smileys\";}s:5:\"funcs\";a:2:{i:0;s:36:\"phorum_mod_bbcode_editor_tool_plugin\";i:1;s:37:\"phorum_mod_smileys_editor_tool_plugin\";}}s:5:\"addon\";a:2:{s:4:\"mods\";a:2:{i:0;s:7:\"smileys\";i:1;s:6:\"bbcode\";}s:5:\"funcs\";a:2:{i:0;s:24:\"phorum_mod_smileys_addon\";i:1;s:23:\"phorum_mod_bbcode_addon\";}}s:26:\"tpl_editor_disable_smileys\";a:2:{s:4:\"mods\";a:1:{i:0;s:7:\"smileys\";}s:5:\"funcs\";a:1:{i:0;s:45:\"phorum_mod_smileys_tpl_editor_disable_smileys\";}}s:21:\"posting_custom_action\";a:2:{s:4:\"mods\";a:2:{i:0;s:7:\"smileys\";i:1;s:6:\"bbcode\";}s:5:\"funcs\";a:2:{i:0;s:40:\"phorum_mod_smileys_posting_custom_action\";i:1;s:39:\"phorum_mod_bbcode_posting_custom_action\";}}s:6:\"format\";a:2:{s:4:\"mods\";a:1:{i:0;s:6:\"bbcode\";}s:5:\"funcs\";a:1:{i:0;s:24:\"phorum_mod_bbcode_format\";}}s:5:\"quote\";a:2:{s:4:\"mods\";a:1:{i:0;s:6:\"bbcode\";}s:5:\"funcs\";a:1:{i:0;s:23:\"phorum_mod_bbcode_quote\";}}s:25:\"tpl_editor_disable_bbcode\";a:2:{s:4:\"mods\";a:1:{i:0;s:6:\"bbcode\";}s:5:\"funcs\";a:1:{i:0;s:43:\"phorum_mod_bbcode_tpl_editor_disable_bbcode\";}}}'),('html_title','V','Phorum'),('http_path','V','http://localhost:8000'),('installed','V','1'),('internal_patchlevel','V','2016101000'),('internal_version','V','2010101500'),('max_file_size','V',''),('mods','S','a:12:{s:13:\"announcements\";i:1;s:6:\"bbcode\";i:1;s:12:\"editor_tools\";i:1;s:13:\"event_logging\";i:0;s:4:\"html\";i:0;s:9:\"smtp_mail\";i:0;s:14:\"modules_in_use\";i:0;s:7:\"replace\";i:0;s:7:\"smileys\";i:1;s:11:\"spamhurdles\";i:0;s:8:\"mod_tidy\";i:0;s:21:\"username_restrictions\";i:0;}'),('mod_announcements','S','a:7:{s:6:\"module\";s:11:\"modsettings\";s:3:\"mod\";s:13:\"announcements\";s:8:\"forum_id\";i:1;s:5:\"pages\";a:2:{s:5:\"index\";s:1:\"1\";s:4:\"list\";s:1:\"1\";}s:14:\"number_to_show\";i:5;s:16:\"only_show_unread\";N;s:12:\"days_to_show\";i:0;}'),('mod_bbcode_parser','S','a:3:{s:8:\"cachekey\";s:32:\"acc1bafb975cb64f0528995f81faced3\";s:7:\"taginfo\";a:20:{s:1:\"b\";a:11:{i:2;b:1;i:6;s:3:\"<b>\";i:7;s:4:\"</b>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:1:\"b\";i:14;N;}s:1:\"i\";a:11:{i:2;b:1;i:6;s:3:\"<i>\";i:7;s:4:\"</i>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:1:\"i\";i:14;N;}s:1:\"u\";a:11:{i:2;b:1;i:6;s:3:\"<u>\";i:7;s:4:\"</u>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:1:\"u\";i:14;N;}s:1:\"s\";a:11:{i:2;b:1;i:6;s:3:\"<s>\";i:7;s:4:\"</s>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:1:\"s\";i:14;N;}s:3:\"sub\";a:11:{i:2;b:1;i:6;s:5:\"<sub>\";i:7;s:6:\"</sub>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:3:\"sub\";i:14;N;}s:3:\"sup\";a:11:{i:2;b:1;i:6;s:5:\"<sup>\";i:7;s:6:\"</sup>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:3:\"sup\";i:14;N;}s:5:\"color\";a:12:{i:2;b:1;i:4;a:1:{s:5:\"color\";s:0:\"\";}i:8;s:20:\"bbcode_color_handler\";i:9;b:0;i:12;N;i:6;s:0:\"\";i:7;s:0:\"\";i:10;b:0;i:5;N;i:11;b:0;i:13;s:5:\"color\";i:14;a:1:{s:1:\"c\";a:1:{s:1:\"o\";a:1:{s:1:\"l\";a:1:{s:1:\"o\";a:1:{s:1:\"r\";a:1:{s:3:\"arg\";b:1;}}}}}}}s:4:\"size\";a:12:{i:2;b:1;i:4;a:1:{s:4:\"size\";s:0:\"\";}i:8;s:19:\"bbcode_size_handler\";i:9;b:0;i:12;N;i:6;s:0:\"\";i:7;s:0:\"\";i:10;b:0;i:5;N;i:11;b:0;i:13;s:4:\"size\";i:14;a:1:{s:1:\"s\";a:1:{s:1:\"i\";a:1:{s:1:\"z\";a:1:{s:1:\"e\";a:1:{s:3:\"arg\";b:1;}}}}}}s:5:\"small\";a:11:{i:2;b:1;i:6;s:7:\"<small>\";i:7;s:8:\"</small>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:5:\"small\";i:14;N;}s:5:\"large\";a:11:{i:2;b:1;i:6;s:31:\"<span style=\"font-size: large\">\";i:7;s:7:\"</span>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:5:\"large\";i:14;N;}s:3:\"url\";a:12:{i:2;b:1;i:4;a:1:{s:3:\"url\";s:0:\"\";}i:8;s:18:\"bbcode_url_handler\";i:9;b:0;i:12;N;i:6;s:0:\"\";i:7;s:0:\"\";i:10;b:0;i:5;N;i:11;b:0;i:13;s:3:\"url\";i:14;a:1:{s:1:\"u\";a:1:{s:1:\"r\";a:1:{s:1:\"l\";a:1:{s:3:\"arg\";b:1;}}}}}s:3:\"img\";a:12:{i:2;b:1;i:4;a:2:{s:3:\"img\";s:0:\"\";s:4:\"size\";s:0:\"\";}i:8;s:18:\"bbcode_img_handler\";i:9;b:0;i:12;N;i:6;s:0:\"\";i:7;s:0:\"\";i:10;b:0;i:5;N;i:11;b:0;i:13;s:3:\"img\";i:14;a:2:{s:1:\"i\";a:1:{s:1:\"m\";a:1:{s:1:\"g\";a:1:{s:3:\"arg\";b:1;}}}s:1:\"s\";a:1:{s:1:\"i\";a:1:{s:1:\"z\";a:1:{s:1:\"e\";a:1:{s:3:\"arg\";b:1;}}}}}}s:5:\"email\";a:12:{i:2;b:1;i:4;a:2:{s:5:\"email\";s:0:\"\";s:7:\"subject\";s:0:\"\";}i:8;s:20:\"bbcode_email_handler\";i:9;b:0;i:12;N;i:6;s:0:\"\";i:7;s:0:\"\";i:10;b:0;i:5;N;i:11;b:0;i:13;s:5:\"email\";i:14;a:2:{s:1:\"e\";a:1:{s:1:\"m\";a:1:{s:1:\"a\";a:1:{s:1:\"i\";a:1:{s:1:\"l\";a:1:{s:3:\"arg\";b:1;}}}}}s:1:\"s\";a:1:{s:1:\"u\";a:1:{s:1:\"b\";a:1:{s:1:\"j\";a:1:{s:1:\"e\";a:1:{s:1:\"c\";a:1:{s:1:\"t\";a:1:{s:3:\"arg\";b:1;}}}}}}}}}s:2:\"hr\";a:11:{i:2;b:1;i:9;b:1;i:6;s:20:\"<hr class=\"bbcode\"/>\";i:11;b:1;i:12;N;i:7;s:0:\"\";i:8;N;i:10;b:0;i:5;N;i:13;s:2:\"hr\";i:14;N;}s:4:\"list\";a:12:{i:2;b:1;i:4;a:1:{s:4:\"list\";s:1:\"b\";}i:8;s:19:\"bbcode_list_handler\";i:11;b:1;i:9;b:0;i:12;N;i:6;s:0:\"\";i:7;s:0:\"\";i:10;b:0;i:5;N;i:13;s:4:\"list\";i:14;a:1:{s:1:\"l\";a:1:{s:1:\"i\";a:1:{s:1:\"s\";a:1:{s:1:\"t\";a:1:{s:3:\"arg\";b:1;}}}}}}s:5:\"quote\";a:12:{i:2;b:1;i:10;b:1;i:4;a:1:{s:5:\"quote\";s:0:\"\";}i:8;s:20:\"bbcode_quote_handler\";i:9;b:0;i:12;N;i:6;s:0:\"\";i:7;s:0:\"\";i:5;N;i:11;b:0;i:13;s:5:\"quote\";i:14;a:1:{s:1:\"q\";a:1:{s:1:\"u\";a:1:{s:1:\"o\";a:1:{s:1:\"t\";a:1:{s:1:\"e\";a:1:{s:3:\"arg\";b:1;}}}}}}}s:4:\"code\";a:11:{i:2;b:1;i:6;s:20:\"<pre class=\"bbcode\">\";i:7;s:6:\"</pre>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:4:\"code\";i:14;N;}s:4:\"left\";a:11:{i:2;b:1;i:6;s:46:\"<div style=\"text-align: left;\" class=\"bbcode\">\";i:7;s:6:\"</div>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:4:\"left\";i:14;N;}s:6:\"center\";a:11:{i:2;b:1;i:6;s:23:\"<center class=\"bbcode\">\";i:7;s:9:\"</center>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:6:\"center\";i:14;N;}s:5:\"right\";a:11:{i:2;b:1;i:6;s:47:\"<div style=\"text-align: right;\" class=\"bbcode\">\";i:7;s:6:\"</div>\";i:9;b:0;i:12;N;i:8;N;i:10;b:0;i:5;N;i:11;b:0;i:13;s:5:\"right\";i:14;N;}}s:9:\"parsetree\";a:11:{s:1:\"/\";a:9:{s:1:\"b\";a:1:{s:3:\"tag\";a:2:{i:0;s:1:\"b\";i:1;b:1;}}s:1:\"i\";a:2:{s:3:\"tag\";a:2:{i:0;s:1:\"i\";i:1;b:1;}s:1:\"m\";a:1:{s:1:\"g\";a:1:{s:3:\"tag\";a:2:{i:0;s:3:\"img\";i:1;b:1;}}}}s:1:\"u\";a:2:{s:3:\"tag\";a:2:{i:0;s:1:\"u\";i:1;b:1;}s:1:\"r\";a:1:{s:1:\"l\";a:1:{s:3:\"tag\";a:2:{i:0;s:3:\"url\";i:1;b:1;}}}}s:1:\"s\";a:4:{s:3:\"tag\";a:2:{i:0;s:1:\"s\";i:1;b:1;}s:1:\"u\";a:2:{s:1:\"b\";a:1:{s:3:\"tag\";a:2:{i:0;s:3:\"sub\";i:1;b:1;}}s:1:\"p\";a:1:{s:3:\"tag\";a:2:{i:0;s:3:\"sup\";i:1;b:1;}}}s:1:\"i\";a:1:{s:1:\"z\";a:1:{s:1:\"e\";a:1:{s:3:\"tag\";a:2:{i:0;s:4:\"size\";i:1;b:1;}}}}s:1:\"m\";a:1:{s:1:\"a\";a:1:{s:1:\"l\";a:1:{s:1:\"l\";a:1:{s:3:\"tag\";a:2:{i:0;s:5:\"small\";i:1;b:1;}}}}}}s:1:\"c\";a:2:{s:1:\"o\";a:2:{s:1:\"l\";a:1:{s:1:\"o\";a:1:{s:1:\"r\";a:1:{s:3:\"tag\";a:2:{i:0;s:5:\"color\";i:1;b:1;}}}}s:1:\"d\";a:1:{s:1:\"e\";a:1:{s:3:\"tag\";a:2:{i:0;s:4:\"code\";i:1;b:1;}}}}s:1:\"e\";a:1:{s:1:\"n\";a:1:{s:1:\"t\";a:1:{s:1:\"e\";a:1:{s:1:\"r\";a:1:{s:3:\"tag\";a:2:{i:0;s:6:\"center\";i:1;b:1;}}}}}}}s:1:\"l\";a:3:{s:1:\"a\";a:1:{s:1:\"r\";a:1:{s:1:\"g\";a:1:{s:1:\"e\";a:1:{s:3:\"tag\";a:2:{i:0;s:5:\"large\";i:1;b:1;}}}}}s:1:\"i\";a:1:{s:1:\"s\";a:1:{s:1:\"t\";a:1:{s:3:\"tag\";a:2:{i:0;s:4:\"list\";i:1;b:1;}}}}s:1:\"e\";a:1:{s:1:\"f\";a:1:{s:1:\"t\";a:1:{s:3:\"tag\";a:2:{i:0;s:4:\"left\";i:1;b:1;}}}}}s:1:\"e\";a:1:{s:1:\"m\";a:1:{s:1:\"a\";a:1:{s:1:\"i\";a:1:{s:1:\"l\";a:1:{s:3:\"tag\";a:2:{i:0;s:5:\"email\";i:1;b:1;}}}}}}s:1:\"q\";a:1:{s:1:\"u\";a:1:{s:1:\"o\";a:1:{s:1:\"t\";a:1:{s:1:\"e\";a:1:{s:3:\"tag\";a:2:{i:0;s:5:\"quote\";i:1;b:1;}}}}}}s:1:\"r\";a:1:{s:1:\"i\";a:1:{s:1:\"g\";a:1:{s:1:\"h\";a:1:{s:1:\"t\";a:1:{s:3:\"tag\";a:2:{i:0;s:5:\"right\";i:1;b:1;}}}}}}}s:1:\"b\";a:1:{s:3:\"tag\";a:2:{i:0;s:1:\"b\";i:1;b:0;}}s:1:\"i\";a:2:{s:3:\"tag\";a:2:{i:0;s:1:\"i\";i:1;b:0;}s:1:\"m\";a:1:{s:1:\"g\";a:1:{s:3:\"tag\";a:3:{i:0;s:3:\"img\";i:1;b:0;i:2;a:2:{s:3:\"img\";s:0:\"\";s:4:\"size\";s:0:\"\";}}}}}s:1:\"u\";a:2:{s:3:\"tag\";a:2:{i:0;s:1:\"u\";i:1;b:0;}s:1:\"r\";a:1:{s:1:\"l\";a:1:{s:3:\"tag\";a:3:{i:0;s:3:\"url\";i:1;b:0;i:2;a:1:{s:3:\"url\";s:0:\"\";}}}}}s:1:\"s\";a:4:{s:3:\"tag\";a:2:{i:0;s:1:\"s\";i:1;b:0;}s:1:\"u\";a:2:{s:1:\"b\";a:1:{s:3:\"tag\";a:2:{i:0;s:3:\"sub\";i:1;b:0;}}s:1:\"p\";a:1:{s:3:\"tag\";a:2:{i:0;s:3:\"sup\";i:1;b:0;}}}s:1:\"i\";a:1:{s:1:\"z\";a:1:{s:1:\"e\";a:1:{s:3:\"tag\";a:3:{i:0;s:4:\"size\";i:1;b:0;i:2;a:1:{s:4:\"size\";s:0:\"\";}}}}}s:1:\"m\";a:1:{s:1:\"a\";a:1:{s:1:\"l\";a:1:{s:1:\"l\";a:1:{s:3:\"tag\";a:2:{i:0;s:5:\"small\";i:1;b:0;}}}}}}s:1:\"c\";a:2:{s:1:\"o\";a:2:{s:1:\"l\";a:1:{s:1:\"o\";a:1:{s:1:\"r\";a:1:{s:3:\"tag\";a:3:{i:0;s:5:\"color\";i:1;b:0;i:2;a:1:{s:5:\"color\";s:0:\"\";}}}}}s:1:\"d\";a:1:{s:1:\"e\";a:1:{s:3:\"tag\";a:2:{i:0;s:4:\"code\";i:1;b:0;}}}}s:1:\"e\";a:1:{s:1:\"n\";a:1:{s:1:\"t\";a:1:{s:1:\"e\";a:1:{s:1:\"r\";a:1:{s:3:\"tag\";a:2:{i:0;s:6:\"center\";i:1;b:0;}}}}}}}s:1:\"l\";a:3:{s:1:\"a\";a:1:{s:1:\"r\";a:1:{s:1:\"g\";a:1:{s:1:\"e\";a:1:{s:3:\"tag\";a:2:{i:0;s:5:\"large\";i:1;b:0;}}}}}s:1:\"i\";a:1:{s:1:\"s\";a:1:{s:1:\"t\";a:1:{s:3:\"tag\";a:3:{i:0;s:4:\"list\";i:1;b:0;i:2;a:1:{s:4:\"list\";s:1:\"b\";}}}}}s:1:\"e\";a:1:{s:1:\"f\";a:1:{s:1:\"t\";a:1:{s:3:\"tag\";a:2:{i:0;s:4:\"left\";i:1;b:0;}}}}}s:1:\"e\";a:1:{s:1:\"m\";a:1:{s:1:\"a\";a:1:{s:1:\"i\";a:1:{s:1:\"l\";a:1:{s:3:\"tag\";a:3:{i:0;s:5:\"email\";i:1;b:0;i:2;a:2:{s:5:\"email\";s:0:\"\";s:7:\"subject\";s:0:\"\";}}}}}}}s:1:\"h\";a:1:{s:1:\"r\";a:1:{s:3:\"tag\";a:2:{i:0;s:2:\"hr\";i:1;b:0;}}}s:1:\"q\";a:1:{s:1:\"u\";a:1:{s:1:\"o\";a:1:{s:1:\"t\";a:1:{s:1:\"e\";a:1:{s:3:\"tag\";a:3:{i:0;s:5:\"quote\";i:1;b:0;i:2;a:1:{s:5:\"quote\";s:0:\"\";}}}}}}}s:1:\"r\";a:1:{s:1:\"i\";a:1:{s:1:\"g\";a:1:{s:1:\"h\";a:1:{s:1:\"t\";a:1:{s:3:\"tag\";a:2:{i:0;s:5:\"right\";i:1;b:0;}}}}}}}}'),('mod_info_timestamps','S','a:4:{s:13:\"announcements\";i:1442000821;s:6:\"bbcode\";i:1442000834;s:12:\"editor_tools\";i:1442000821;s:7:\"smileys\";i:1377884382;}'),('mod_smileys','S','a:4:{s:6:\"prefix\";s:22:\"./mods/smileys/images/\";s:7:\"smileys\";a:21:{i:0;a:6:{s:6:\"search\";s:4:\"(:P)\";s:3:\"alt\";s:39:\"spinning smiley sticking its tongue out\";s:6:\"smiley\";s:12:\"smiley25.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:1;a:6:{s:6:\"search\";s:4:\"(td)\";s:3:\"alt\";s:11:\"thumbs down\";s:6:\"smiley\";s:12:\"smiley23.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:2;a:6:{s:6:\"search\";s:4:\"(tu)\";s:3:\"alt\";s:9:\"thumbs up\";s:6:\"smiley\";s:12:\"smiley24.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:3;a:6:{s:6:\"search\";s:4:\":)-D\";s:3:\"alt\";s:17:\"smileys with beer\";s:6:\"smiley\";s:12:\"smiley15.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:4;a:6:{s:6:\"search\";s:4:\">:D<\";s:3:\"alt\";s:17:\"the finger smiley\";s:6:\"smiley\";s:12:\"smiley14.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:5;a:6:{s:6:\"search\";s:3:\"(:D\";s:3:\"alt\";s:23:\"smiling bouncing smiley\";s:6:\"smiley\";s:12:\"smiley12.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:6;a:6:{s:6:\"search\";s:3:\"8-)\";s:3:\"alt\";s:18:\"eye rolling smiley\";s:6:\"smiley\";s:11:\"smilie8.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:7;a:6:{s:6:\"search\";s:3:\":)o\";s:3:\"alt\";s:15:\"drinking smiley\";s:6:\"smiley\";s:12:\"smiley16.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:8;a:6:{s:6:\"search\";s:3:\"::o\";s:3:\"alt\";s:18:\"eye popping smiley\";s:6:\"smiley\";s:12:\"smilie10.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:9;a:6:{s:6:\"search\";s:3:\"B)-\";s:3:\"alt\";s:14:\"smoking smiley\";s:6:\"smiley\";s:11:\"smilie7.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:10;a:6:{s:6:\"search\";s:2:\":(\";s:3:\"alt\";s:10:\"sad smiley\";s:6:\"smiley\";s:11:\"smilie2.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:11;a:6:{s:6:\"search\";s:2:\":)\";s:3:\"alt\";s:14:\"smiling smiley\";s:6:\"smiley\";s:11:\"smilie1.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:12;a:6:{s:6:\"search\";s:2:\":?\";s:3:\"alt\";s:12:\"moody smiley\";s:6:\"smiley\";s:12:\"smiley17.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:13;a:6:{s:6:\"search\";s:2:\":D\";s:3:\"alt\";s:15:\"grinning smiley\";s:6:\"smiley\";s:11:\"smilie5.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:14;a:6:{s:6:\"search\";s:2:\":P\";s:3:\"alt\";s:26:\"tongue sticking out smiley\";s:6:\"smiley\";s:11:\"smilie6.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:15;a:6:{s:6:\"search\";s:2:\":S\";s:3:\"alt\";s:15:\"confused smiley\";s:6:\"smiley\";s:12:\"smilie11.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:16;a:6:{s:6:\"search\";s:2:\":X\";s:3:\"alt\";s:12:\"angry smiley\";s:6:\"smiley\";s:11:\"smilie9.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:17;a:6:{s:6:\"search\";s:2:\":o\";s:3:\"alt\";s:14:\"yawning smiley\";s:6:\"smiley\";s:11:\"smilie4.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:18;a:6:{s:6:\"search\";s:2:\";)\";s:3:\"alt\";s:14:\"winking smiley\";s:6:\"smiley\";s:11:\"smilie3.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:19;a:6:{s:6:\"search\";s:2:\"B)\";s:3:\"alt\";s:11:\"cool smiley\";s:6:\"smiley\";s:8:\"cool.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}i:20;a:6:{s:6:\"search\";s:2:\"X(\";s:3:\"alt\";s:10:\"hot smiley\";s:6:\"smiley\";s:7:\"hot.gif\";s:4:\"uses\";i:2;s:6:\"active\";b:1;s:8:\"is_alias\";b:0;}}s:12:\"replacements\";a:2:{s:7:\"subject\";a:2:{i:0;a:21:{i:0;s:4:\"(:P)\";i:1;s:4:\"(td)\";i:2;s:4:\"(tu)\";i:3;s:4:\":)-D\";i:4;s:10:\"&gt;:D&lt;\";i:5;s:3:\"(:D\";i:6;s:3:\"8-)\";i:7;s:3:\":)o\";i:8;s:3:\"::o\";i:9;s:3:\"B)-\";i:10;s:2:\":(\";i:11;s:2:\":)\";i:12;s:2:\":?\";i:13;s:2:\":D\";i:14;s:2:\":P\";i:15;s:2:\":S\";i:16;s:2:\":X\";i:17;s:2:\":o\";i:18;s:2:\";)\";i:19;s:2:\"B)\";i:20;s:2:\"X(\";}i:1;a:21:{i:0;s:185:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley25.gif\" alt=\"spinning smiley sticking its tongue out\" title=\"spinning smiley sticking its tongue out\"/>\";i:1;s:129:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley23.gif\" alt=\"thumbs down\" title=\"thumbs down\"/>\";i:2;s:125:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley24.gif\" alt=\"thumbs up\" title=\"thumbs up\"/>\";i:3;s:141:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley15.gif\" alt=\"smileys with beer\" title=\"smileys with beer\"/>\";i:4;s:141:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley14.gif\" alt=\"the finger smiley\" title=\"the finger smiley\"/>\";i:5;s:153:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley12.gif\" alt=\"smiling bouncing smiley\" title=\"smiling bouncing smiley\"/>\";i:6;s:142:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie8.gif\" alt=\"eye rolling smiley\" title=\"eye rolling smiley\"/>\";i:7;s:137:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley16.gif\" alt=\"drinking smiley\" title=\"drinking smiley\"/>\";i:8;s:143:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie10.gif\" alt=\"eye popping smiley\" title=\"eye popping smiley\"/>\";i:9;s:134:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie7.gif\" alt=\"smoking smiley\" title=\"smoking smiley\"/>\";i:10;s:126:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie2.gif\" alt=\"sad smiley\" title=\"sad smiley\"/>\";i:11;s:134:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie1.gif\" alt=\"smiling smiley\" title=\"smiling smiley\"/>\";i:12;s:131:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley17.gif\" alt=\"moody smiley\" title=\"moody smiley\"/>\";i:13;s:136:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie5.gif\" alt=\"grinning smiley\" title=\"grinning smiley\"/>\";i:14;s:158:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie6.gif\" alt=\"tongue sticking out smiley\" title=\"tongue sticking out smiley\"/>\";i:15;s:137:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie11.gif\" alt=\"confused smiley\" title=\"confused smiley\"/>\";i:16;s:130:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie9.gif\" alt=\"angry smiley\" title=\"angry smiley\"/>\";i:17;s:134:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie4.gif\" alt=\"yawning smiley\" title=\"yawning smiley\"/>\";i:18;s:134:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie3.gif\" alt=\"winking smiley\" title=\"winking smiley\"/>\";i:19;s:125:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/cool.gif\" alt=\"cool smiley\" title=\"cool smiley\"/>\";i:20;s:122:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/hot.gif\" alt=\"hot smiley\" title=\"hot smiley\"/>\";}}s:4:\"body\";a:2:{i:0;a:21:{i:0;s:4:\"(:P)\";i:1;s:4:\"(td)\";i:2;s:4:\"(tu)\";i:3;s:4:\":)-D\";i:4;s:10:\"&gt;:D&lt;\";i:5;s:3:\"(:D\";i:6;s:3:\"8-)\";i:7;s:3:\":)o\";i:8;s:3:\"::o\";i:9;s:3:\"B)-\";i:10;s:2:\":(\";i:11;s:2:\":)\";i:12;s:2:\":?\";i:13;s:2:\":D\";i:14;s:2:\":P\";i:15;s:2:\":S\";i:16;s:2:\":X\";i:17;s:2:\":o\";i:18;s:2:\";)\";i:19;s:2:\"B)\";i:20;s:2:\"X(\";}i:1;a:21:{i:0;s:185:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley25.gif\" alt=\"spinning smiley sticking its tongue out\" title=\"spinning smiley sticking its tongue out\"/>\";i:1;s:129:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley23.gif\" alt=\"thumbs down\" title=\"thumbs down\"/>\";i:2;s:125:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley24.gif\" alt=\"thumbs up\" title=\"thumbs up\"/>\";i:3;s:141:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley15.gif\" alt=\"smileys with beer\" title=\"smileys with beer\"/>\";i:4;s:141:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley14.gif\" alt=\"the finger smiley\" title=\"the finger smiley\"/>\";i:5;s:153:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley12.gif\" alt=\"smiling bouncing smiley\" title=\"smiling bouncing smiley\"/>\";i:6;s:142:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie8.gif\" alt=\"eye rolling smiley\" title=\"eye rolling smiley\"/>\";i:7;s:137:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley16.gif\" alt=\"drinking smiley\" title=\"drinking smiley\"/>\";i:8;s:143:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie10.gif\" alt=\"eye popping smiley\" title=\"eye popping smiley\"/>\";i:9;s:134:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie7.gif\" alt=\"smoking smiley\" title=\"smoking smiley\"/>\";i:10;s:126:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie2.gif\" alt=\"sad smiley\" title=\"sad smiley\"/>\";i:11;s:134:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie1.gif\" alt=\"smiling smiley\" title=\"smiling smiley\"/>\";i:12;s:131:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smiley17.gif\" alt=\"moody smiley\" title=\"moody smiley\"/>\";i:13;s:136:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie5.gif\" alt=\"grinning smiley\" title=\"grinning smiley\"/>\";i:14;s:158:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie6.gif\" alt=\"tongue sticking out smiley\" title=\"tongue sticking out smiley\"/>\";i:15;s:137:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie11.gif\" alt=\"confused smiley\" title=\"confused smiley\"/>\";i:16;s:130:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie9.gif\" alt=\"angry smiley\" title=\"angry smiley\"/>\";i:17;s:134:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie4.gif\" alt=\"yawning smiley\" title=\"yawning smiley\"/>\";i:18;s:134:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/smilie3.gif\" alt=\"winking smiley\" title=\"winking smiley\"/>\";i:19;s:125:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/cool.gif\" alt=\"cool smiley\" title=\"cool smiley\"/>\";i:20;s:122:\"<img class=\"mod_smileys_img\" src=\"http://localhost:8000/mods/smileys/images/hot.gif\" alt=\"hot smiley\" title=\"hot smiley\"/>\";}}}s:10:\"do_smileys\";b:1;}'),('private_key','V','6&Q8D!xxaWcr853KvikarhEHAD8cJFkPVaYzlw$C'),('PROFILE_FIELDS','S','a:0:{}'),('redirect_after_post','V','list'),('registration_control','V','1'),('reply_on_read_page','V','1'),('session_domain','V',''),('session_path','V','/'),('session_timeout','V','30'),('short_session_timeout','V','60'),('show_new_on_index','V','1'),('status','V','normal'),('strip_quote_mail','V','0'),('strip_quote_posting_form','V','0'),('system_email_from_address','V','test@phorum.org'),('system_email_from_name','V',''),('tight_security','V','0'),('title','V','Phorum 5'),('track_edits','V','0'),('track_user_activity','V','86400'),('tz_offset','V','0'),('user_edit_timelimit','V','0'),('user_template','V','0'),('user_time_zone','V','1'),('use_bcc','V','1'),('use_cookies','V','1'),('use_new_folder_style','V','1'),('use_rss','V','1');
/*!40000 ALTER TABLE `phorum_settings` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_subscribers`
--

DROP TABLE IF EXISTS `phorum_subscribers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_subscribers` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
  `sub_type` tinyint(4) NOT NULL DEFAULT '0',
  `thread` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`forum_id`,`thread`),
  KEY `forum_id` (`forum_id`,`thread`,`sub_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_subscribers`
--

LOCK TABLES `phorum_subscribers` WRITE;
/*!40000 ALTER TABLE `phorum_subscribers` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_subscribers` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_user_custom_fields`
--

DROP TABLE IF EXISTS `phorum_user_custom_fields`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_user_custom_fields` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` int(10) unsigned NOT NULL DEFAULT '0',
  `data` text NOT NULL,
  PRIMARY KEY (`user_id`,`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_user_custom_fields`
--

LOCK TABLES `phorum_user_custom_fields` WRITE;
/*!40000 ALTER TABLE `phorum_user_custom_fields` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_user_custom_fields` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_user_group_xref`
--

DROP TABLE IF EXISTS `phorum_user_group_xref`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_user_group_xref` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `group_id` int(10) unsigned NOT NULL DEFAULT '0',
  `status` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`,`group_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_user_group_xref`
--

LOCK TABLES `phorum_user_group_xref` WRITE;
/*!40000 ALTER TABLE `phorum_user_group_xref` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_user_group_xref` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_user_newflags`
--

DROP TABLE IF EXISTS `phorum_user_newflags`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_user_newflags` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`forum_id`,`message_id`),
  KEY `move` (`message_id`,`forum_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_user_newflags`
--

LOCK TABLES `phorum_user_newflags` WRITE;
/*!40000 ALTER TABLE `phorum_user_newflags` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_user_newflags` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_user_permissions`
--

DROP TABLE IF EXISTS `phorum_user_permissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_user_permissions` (
  `user_id` int(10) unsigned NOT NULL DEFAULT '0',
  `forum_id` int(10) unsigned NOT NULL DEFAULT '0',
  `permission` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`,`forum_id`),
  KEY `forum_id` (`forum_id`,`permission`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_user_permissions`
--

LOCK TABLES `phorum_user_permissions` WRITE;
/*!40000 ALTER TABLE `phorum_user_permissions` DISABLE KEYS */;
/*!40000 ALTER TABLE `phorum_user_permissions` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `phorum_users`
--

DROP TABLE IF EXISTS `phorum_users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `phorum_users` (
  `user_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL DEFAULT '',
  `real_name` varchar(255) NOT NULL DEFAULT '',
  `display_name` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(50) NOT NULL DEFAULT '',
  `password_temp` varchar(50) NOT NULL DEFAULT '',
  `sessid_lt` varchar(50) NOT NULL DEFAULT '',
  `sessid_st` varchar(50) NOT NULL DEFAULT '',
  `sessid_st_timeout` int(10) unsigned NOT NULL DEFAULT '0',
  `email` varchar(100) NOT NULL DEFAULT '',
  `email_temp` varchar(110) NOT NULL DEFAULT '',
  `hide_email` tinyint(1) NOT NULL DEFAULT '0',
  `active` tinyint(1) NOT NULL DEFAULT '0',
  `signature` text NOT NULL,
  `threaded_list` tinyint(1) NOT NULL DEFAULT '0',
  `posts` int(10) NOT NULL DEFAULT '0',
  `admin` tinyint(1) NOT NULL DEFAULT '0',
  `threaded_read` tinyint(1) NOT NULL DEFAULT '0',
  `date_added` int(10) unsigned NOT NULL DEFAULT '0',
  `date_last_active` int(10) unsigned NOT NULL DEFAULT '0',
  `last_active_forum` int(10) unsigned NOT NULL DEFAULT '0',
  `hide_activity` tinyint(1) NOT NULL DEFAULT '0',
  `show_signature` tinyint(1) NOT NULL DEFAULT '0',
  `email_notify` tinyint(1) NOT NULL DEFAULT '0',
  `pm_email_notify` tinyint(1) NOT NULL DEFAULT '1',
  `tz_offset` float(4,2) NOT NULL DEFAULT '-99.00',
  `is_dst` tinyint(1) NOT NULL DEFAULT '0',
  `user_language` varchar(100) NOT NULL DEFAULT '',
  `user_template` varchar(100) NOT NULL DEFAULT '',
  `moderator_data` text NOT NULL,
  `moderation_email` tinyint(1) NOT NULL DEFAULT '1',
  `settings_data` mediumtext NOT NULL,
  `force_password_change` tinyint(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username` (`username`),
  KEY `active` (`active`),
  KEY `userpass` (`username`,`password`),
  KEY `sessid_st` (`sessid_st`),
  KEY `sessid_lt` (`sessid_lt`),
  KEY `activity` (`date_last_active`,`hide_activity`,`last_active_forum`),
  KEY `date_added` (`date_added`),
  KEY `email_temp` (`email_temp`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `phorum_users`
--

LOCK TABLES `phorum_users` WRITE;
/*!40000 ALTER TABLE `phorum_users` DISABLE KEYS */;
INSERT INTO `phorum_users` VALUES (1,'admin','','admin','7adc785be4a31eff6783871ff63e18f1','*NO PASSWORD SET*','','',0,'test@phorum.org','',0,1,'',0,0,1,0,1442139723,1442139723,0,0,0,0,1,-99.00,0,'','','',1,'');
/*!40000 ALTER TABLE `phorum_users` ENABLE KEYS */;
UNLOCK TABLES;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-09-13 10:24:06
