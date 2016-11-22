-- ----------------------------
-- Table structure for adcolony_transactions
-- ----------------------------
DROP TABLE IF EXISTS `adcolony_transactions`;
CREATE TABLE `adcolony_transactions` (
  `id`        bigint(20) NOT NULL AUTO_INCREMENT
, `amount`    int(11) DEFAULT NULL
, `username`  varchar(255) DEFAULT NULL
, `date`      timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
, PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
