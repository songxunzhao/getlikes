-- ----------------------------
-- Table structure for gl_liked
-- ----------------------------
DROP TABLE IF EXISTS `gl_liked`;
CREATE TABLE `gl_liked` (
  `id` bigint(20)         NOT NULL      AUTO_INCREMENT
, `user_id` varchar(20)   DEFAULT NULL
, `photo_id` varchar(64)  DEFAULT NULL
, PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
