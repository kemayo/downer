CREATE TABLE IF NOT EXISTS `files` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `file` varchar(128) NOT NULL,
      `active` tinyint(4) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `tokens` (
      `token` char(6) NOT NULL,
      `file` int(11) NOT NULL,
      `uses_remaining` tinyint(3) unsigned NOT NULL DEFAULT '1',
      `initial_uses` tinyint(3) unsigned NOT NULL DEFAULT '1',
      `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `expires` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
      PRIMARY KEY (`token`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `log` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `fileid` int(11) NOT NULL,
      `token` char(6) NOT NULL,
      `time_used` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      `ip_address` int(11) NOT NULL,
      PRIMARY KEY (`id`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

