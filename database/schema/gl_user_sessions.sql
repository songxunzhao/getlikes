CREATE TABLE `gl_user_sessions` (
  `id`          bigint(20) NOT NULL AUTO_INCREMENT
, `user_id`     bigint(20) DEFAULT NULL
, `token`       varchar(255) DEFAULT NULL
, `created_at`  timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
, `expires_at`  timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
, PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
