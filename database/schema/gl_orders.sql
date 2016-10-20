-- ----------------------------
-- Table structure for gl_orders
-- ----------------------------
DROP TABLE IF EXISTS `gl_orders`;
CREATE TABLE `gl_orders` (
  `id`                bigint(32)  NOT NULL AUTO_INCREMENT
, `user_id`           bigint(20)  DEFAULT NULL
, `media_id`          varchar(64) DEFAULT NULL
, `amount`            int(11)     DEFAULT NULL
, `processed_amount`  int(11)     NOT NULL DEFAULT '0'
, PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1;
