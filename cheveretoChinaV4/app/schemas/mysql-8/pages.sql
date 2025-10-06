DROP TABLE IF EXISTS `%table_prefix%pages`;
CREATE TABLE `%table_prefix%pages` (
  `page_id` bigint(32) NOT NULL AUTO_INCREMENT,
  `page_url_key` varchar(32) DEFAULT NULL,
  `page_type` enum('internal','link') NOT NULL DEFAULT 'internal',
  `page_file_path` varchar(255) DEFAULT NULL,
  `page_link_url` mediumtext,
  `page_icon` varchar(255) DEFAULT NULL,
  `page_title` varchar(255) NOT NULL,
  `page_description` mediumtext,
  `page_keywords` mediumtext,
  `page_is_active` tinyint(1) NOT NULL DEFAULT '1',
  `page_is_link_visible` tinyint(1) NOT NULL DEFAULT '1',
  `page_attr_target` enum('_self','_blank') DEFAULT '_self',
  `page_attr_rel` varchar(255) DEFAULT NULL,
  `page_sort_display` int(11) DEFAULT NULL,
  `page_internal` varchar(255) DEFAULT NULL,
  `page_code` mediumtext,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `page_internal` (`page_internal`),
  KEY `page_url_key` (`page_url_key`),
  KEY `page_type` (`page_type`),
  KEY `page_is_active` (`page_is_active`),
  KEY `page_is_link_visible` (`page_is_link_visible`),
  KEY `page_sort_display` (`page_sort_display`)
) ENGINE=%table_engine% DEFAULT CHARSET=utf8mb4;
INSERT INTO `%table_prefix%pages` VALUES ('1', 'tos', 'internal', null, null, 'fas fa-landmark', 'Terms of service', null, null, '1', '1', '_self', null, '1', 'tos', null);
INSERT INTO `%table_prefix%pages` VALUES ('2', 'privacy', 'internal', null, null, 'fas fa-lock', 'Privacy', null, null, '1', '1', '_self', null, '2', 'privacy', null);
INSERT INTO `%table_prefix%pages` VALUES ('3', 'contact', 'internal', null, null, 'fas fa-at', 'Contact', null, null, '1', '1', '_self', null, '3', 'contact', null);
