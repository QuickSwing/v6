
-- This table is used for logging actions done on a User or an fnum.
CREATE TABLE IF NOT EXISTS `jos_emundus_logs` (
  `id` int(11) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `user_id_from` int(11) NOT NULL,
  `user_id_to` int(11) DEFAULT NULL,
  `fnum_to` varchar(255) DEFAULT NULL,
  `action_id` int(11) NOT NULL,
  `verb` char(1) NOT NULL,
  `message` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `jos_emundus_logs`
  ADD PRIMARY KEY (`id`), ADD KEY `actions` (`action_id`), ADD KEY `fnum to` (`fnum_to`), ADD KEY `user from` (`user_id_from`), ADD KEY `user to` (`user_id_to`);


ALTER TABLE `jos_emundus_logs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `jos_emundus_logs`
  ADD CONSTRAINT `user from` FOREIGN KEY (`user_id_from`) REFERENCES `jos_emundus_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `user to` FOREIGN KEY (`user_id_to`) REFERENCES `jos_emundus_users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;