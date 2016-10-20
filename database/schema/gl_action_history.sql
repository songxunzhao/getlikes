-- ----------------------------
-- Table structure for gl_action_history
-- ----------------------------
DROP TABLE IF EXISTS `gl_action_history`;
CREATE TABLE `gl_action_history` (
  `uuid`    varchar(32) NOT NULL
, `user_id` varchar(20) DEFAULT NULL
, `api`     varchar(256) DEFAULT NULL
, `req`     text
, `res`     text
, `date`    timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
, PRIMARY KEY (`uuid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
