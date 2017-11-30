/* Joomla user import */
INSERT IGNORE INTO `#__csvi_availablefields` (`csvi_name`, `component_name`, `component_table`, `component`, `action`) VALUES
('skip', 'skip', 'user', 'com_users', 'import'),
('combine', 'combine', 'usertimport', 'com_users', 'import'),
('password_crypt', 'password_crypt', 'user', 'com_users', 'import'),
('group_id', 'group_id', 'user', 'com_users', 'import'),
('usergroup_name', 'usergroup_name', 'user', 'com_users', 'import'),
('fullname', 'fullname', 'user', 'com_users', 'import'),

/* Joomla user export */
('custom', 'custom', 'user', 'com_users', 'export'),
('usergroup_name', 'usergroup_name', 'user', 'com_users', 'export'),
('fullname', 'fullname', 'user', 'com_users', 'export');