-- ----------------------------
-- Table structure for gl_user_cache_data
-- ----------------------------
DROP TABLE IF EXISTS `gl_user_cache_data`;
CREATE TABLE `gl_user_cache_data` (
  `id`          bigint(20)  NOT NULL AUTO_INCREMENT
, `user_id`     bigint(20)  DEFAULT NULL
, `data_type`   int(11)     DEFAULT NULL
, `data`        text
, `created_on`  timestamp   DEFAULT NULL
, `modified_on` timestamp   DEFAULT NULL
, PRIMARY KEY (`id`)
, UNIQUE KEY `unique_user_id_data_type_key` (`user_id`,`data_type`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
