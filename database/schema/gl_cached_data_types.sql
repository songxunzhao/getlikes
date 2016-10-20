-- ----------------------------
-- Table structure for gl_cached_data_types
-- ----------------------------
DROP TABLE IF EXISTS `gl_cached_data_types`;
CREATE TABLE `gl_cached_data_types` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `typecode` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;

INSERT INTO gl_cached_data_types
  (`id`, `name`,`description`, `typecode`)
VALUES
  (1, 'instagram_liked_media_list', 'Instagram media list which the user liked' ,1),
  (2, 'instagram_post_media_list',  'Instgram media list which the user posted' ,2),
  (3, 'instagram_user_private',     'Instagram user is private or public'       ,3);