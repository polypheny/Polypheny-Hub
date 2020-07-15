DROP TABLE IF EXISTS `dsm_user`;
CREATE TABLE `dsm_user` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`user` varchar(255) DEFAULT '',
`email` varchar(255) DEFAULT NULL,
`password` varchar(60) NOT NULL DEFAULT '',
`secret` varchar(60) DEFAULT NULL,
`admin` tinyint(1) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `user` (`user`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

/* password: unibas */
INSERT INTO `dsm_user` (`id`, `user`, `email`, `password`, `secret`, `admin`)
VALUES
(1, 'unibas', NULL, '$2y$10$TISgV8AeKtysEmG1WoTXJO38wQnvqbvkicrquESI66Xez/CdwQwm.', NULL, 1);

DROP TABLE IF EXISTS `dsm_dataset`;
CREATE TABLE `dsm_dataset` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL DEFAULT '',
`description` TEXT NULL,
`file` varchar(255) NOT NULL DEFAULT '',
`lines` int(11) NULL,
`zipSize` int(11) NULL,
`uploaded` datetime NOT NULL,
/* 0 stands for private, 1 stands for internal, 2 stands for public */
`public` tinyint(1) DEFAULT NULL,
`owner` int(11) DEFAULT NULL,
PRIMARY KEY (`id`),
INDEX `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=0 DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `dsm_session`;
CREATE TABLE `dsm_session` (
`user_id` int(11) unsigned NOT NULL,
`agent` varchar(255) DEFAULT NULL,
`secret` varchar(60) DEFAULT NULL,
`last_active` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
