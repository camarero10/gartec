DELETE FROM `#__csvi_availabletables` WHERE `component` = 'com_users';
INSERT IGNORE INTO `#__csvi_availabletables` (`task_name`, `template_table`, `component`, `action`, `enabled`) VALUES
('user', 'user', 'com_users', 'export', '0'),
('user', 'users', 'com_users', 'export', '1'),
('user', 'user', 'com_users', 'import', '0'),
('user', 'users', 'com_users', 'import', '1');

DELETE FROM `#__csvi_tasks` WHERE `component` = 'com_users';
INSERT IGNORE INTO `#__csvi_tasks` (`task_name`, `action`, `component`, `url`, `options`) VALUES
('user', 'export', 'com_users', '', 'source,file,layout,users,fields,limit.advancedUser'),
('user', 'import', 'com_users', '', 'source,file,fields,limit.advancedUser');