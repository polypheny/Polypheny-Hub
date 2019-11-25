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
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8;

/* password: unibas */
INSERT INTO `dsm_user` (`id`, `user`, `email`, `password`, `secret`, `admin`)
VALUES
(1, 'unibas', NULL, '$2y$10$TISgV8AeKtysEmG1WoTXJO38wQnvqbvkicrquESI66Xez/CdwQwm.', NULL, 1);

DROP TABLE IF EXISTS `dsm_dataset`;
CREATE TABLE `dsm_dataset` (
`id` int(11) unsigned NOT NULL AUTO_INCREMENT,
`name` varchar(255) NOT NULL DEFAULT '',
`file` varchar(255) NOT NULL DEFAULT '',
`uploaded` datetime NOT NULL,
`public` tinyint(1) DEFAULT NULL,
`owner` int(11) DEFAULT NULL,
PRIMARY KEY (`id`),
UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8;
