-- ----------------------------
-- Table structure for gl_users
-- ----------------------------
DROP TABLE IF EXISTS `gl_users`;
CREATE TABLE `gl_users` (
    `id`                bigint(11)    NOT NULL  AUTO_INCREMENT
,   `username`          varchar(255)  DEFAULT NULL
,   `password`          varchar(255)  DEFAULT NULL
,   `instagram_user_id` varchar(64)   DEFAULT NULL
,   `coin`              int(11)       NOT NULL
,   `membership`        tinyint(4)    NOT NULL
,    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;