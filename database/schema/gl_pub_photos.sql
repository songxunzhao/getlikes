-- ----------------------------
-- Table structure for gl_pub_photos
-- ----------------------------
DROP TABLE IF EXISTS `gl_pub_photos`;
CREATE TABLE `gl_pub_photos` (
  `id` bigint(32) NOT NULL  AUTO_INCREMENT
, `phone_id` varchar(64) NOT NULL
, `user_id` bigint(20) NOT NULL
, PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
