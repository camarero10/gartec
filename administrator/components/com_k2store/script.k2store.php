<?php
/*------------------------------------------------------------------------
 # com_k2store - K2 Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
-------------------------------------------------------------------------*/

// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.file');

class Com_K2StoreInstallerScript {

	/** @var string The component's name */
	protected $_extension_name = 'com_k2store';
	private $RemovePlugins = array(
			'user' => array(
					'k2store'
			)
	);

	private $RemoveFilesAdmin = array(
			'controllers' => array(
					'shippingmethods'
			),
			'models' => array(
					'shippingmethods',
					'shippingrates'
			),
			'views' => array(
					'shippingrates'
			),
			'tables' => array(
					'shippingmethods',
					'shippingrates'
			)

	);

	private $RemoveFilesSite = array();

	function preflight( $type, $parent ) {
		$jversion = new JVersion();
		//check for minimum requirement
		// abort if the current Joomla release is older
		if( version_compare( $jversion->getShortVersion(), '2.5.6', 'lt' ) ) {
			Jerror::raiseWarning(null, 'Cannot install K2Store in a Joomla release prior to 2.5.6');
			return false;
		}


		// Only allow to install on Joomla! 2.5.0 or later with PHP 5.3.0 or later
		if(defined('PHP_VERSION')) {
			$version = PHP_VERSION;
		} elseif(function_exists('phpversion')) {
			$version = phpversion();
		} else {
			$version = '5.0.0'; // all bets are off!
		}

		if(!version_compare($version, '5.3.1', 'ge')) {
			$msg = "<p>You need PHP 5.3.1 or later to install this component</p>";
			if(version_compare(JVERSION, '3.0', 'gt'))
			{
				JLog::add($msg, JLog::WARNING, 'jerror');
			}
			else
			{
				JError::raiseWarning(100, $msg);
			}
			return false;
		}

		// Bugfix for "Can not build admin menus"
		if(in_array($type, array('install')))
		{
			$this->_bugfixDBFunctionReturnedNoError();
		} elseif ($type != 'discover_install')
		{
			$this->_bugfixCantBuildAdminMenus();
			$this->_resetLiveUpdate();
		}

		//check k2store

		$xmlfile = JPATH_ADMINISTRATOR.'/components/com_k2store/manifest.xml';
		if(JFile::exists($xmlfile)) {
			$xml = JFactory::getXML($xmlfile);
			$version=(string)$xml->version;

			//check for minimum requirement
			// abort if the current K2Store release is older
			if( version_compare( $version, '3.6.0', 'lt' ) ) {
				Jerror::raiseWarning(null, 'You should first upgrade to K2Store 3.6.0 and then install 3.7.x version. Otherwise, the changes made till 3.6 series wont be reflected in your install');
				return false;
			}

			//check the previous version in case the user intalls it twice.
			$file = JPATH_ADMINISTRATOR.'/components/com_k2store/pre-version.txt';
			$buffer = $version;
			JFile::write($file, $buffer);
		}

	}

	function install() {

		$this->_doDBChanges('install');
		$this->_modifyExistingTables('install');
	}

	function update($parent) {

		jimport('joomla.filesystem.file');
		//lets delete the admin.k2store.php if exists
		$old_entry = JPATH_ADMINISTRATOR.'/components/com_k2store/admin.k2store.php';
		if(JFile::exists($old_entry)) {
			JFile::delete($old_entry);
		}

		$this->_doDBChanges('update');
		$this->_modifyExistingTables('update');
		$previous_version = $this->_getPreviousVersion();
		if($previous_version == '3.6.0') {
			//compatibility checks
			$this->_doCompatibilityChecks('update');
		}

	}

		public function postflight($type, $parent)
		{
			$app = JFactory::getApplication('site');
			$db = JFactory::getDBO();
			$status = new stdClass;
			$status->modules = array();
			$status->plugins = array();
			$src = $parent->getParent()->getPath('source');
			$manifest = $parent->getParent()->manifest;
			$modules = $manifest->xpath('modules/module');
			foreach ($modules as $module)
			{
				$name = (string)$module->attributes()->module;
				$client = (string)$module->attributes()->client;
				if (is_null($client))
				{
					$client = 'site';
				}
				($client == 'administrator') ? $path = $src.'/administrator/modules/'.$name : $path = $src.'/modules/'.$name;
				$installer = new JInstaller;
				$result = $installer->install($path);
				$status->modules[] = array('name' => $name, 'client' => $client, 'result' => $result);
			}

			$plugins = $manifest->xpath('plugins/plugin');
			foreach ($plugins as $plugin)
			{
				$name = (string)$plugin->attributes()->plugin;
				$group = (string)$plugin->attributes()->group;
				$path = $src.'/plugins/'.$group;
				if (JFolder::exists($src.'/plugins/'.$group.'/'.$name))
				{
					$path = $src.'/plugins/'.$group.'/'.$name;
				}
				$installer = new JInstaller;
				$result = $installer->install($path);
				if($type !='update') {
					$query = "UPDATE #__extensions SET enabled=1 WHERE type='plugin' AND element=".$db->Quote($name)." AND folder=".$db->Quote($group);
					$db->setQuery($query);
					$db->query();
				}
				$status->plugins[] = array('name' => $name, 'group' => $group, 'result' => $result);
			}

			//remove obsolete plugins
			$this->_removeObsoletePlugins($parent);

			//remove obsolete files
			$this->_removeObsoleteFiles($parent);

			$this->_rebuildMenus();
			$this->_configMigration($type, $parent);
			$this->installationResults($status);

		}


		public function uninstall($parent)
		{
			$db = JFactory::getDBO();
			$status = new stdClass;
			$status->modules = array();
			$status->plugins = array();
			$manifest = $parent->getParent()->manifest;
			$plugins = $manifest->xpath('plugins/plugin');
			foreach ($plugins as $plugin)
			{
				$name = (string)$plugin->attributes()->plugin;
				$group = (string)$plugin->attributes()->group;
				$query = "SELECT `extension_id` FROM #__extensions WHERE `type`='plugin' AND element = ".$db->Quote($name)." AND folder = ".$db->Quote($group);
				$db->setQuery($query);
				$extensions = $db->loadColumn();
				if (count($extensions))
				{
					foreach ($extensions as $id)
					{
						$installer = new JInstaller;
						$result = $installer->uninstall('plugin', $id);
					}
					$status->plugins[] = array('name' => $name, 'group' => $group, 'result' => $result);
				}

			}
			$modules = $manifest->xpath('modules/module');
			foreach ($modules as $module)
			{
				$name = (string)$module->attributes()->module;
				$client = (string)$module->attributes()->client;
				$db = JFactory::getDBO();
				$query = "SELECT `extension_id` FROM `#__extensions` WHERE `type`='module' AND element = ".$db->Quote($name)."";
				$db->setQuery($query);
				$extensions = $db->loadColumn();
				if (count($extensions))
				{
					foreach ($extensions as $id)
					{
						$installer = new JInstaller;
						$result = $installer->uninstall('module', $id);
					}
					$status->modules[] = array('name' => $name, 'client' => $client, 'result' => $result);
				}

			}
			$this->uninstallationResults($status);
		}

		private function _doDBChanges($type) {

			$db = JFactory::getDbo();
			//get the table list
			$tables = $db->getTableList();
			//get prefix
			$prefix = $db->getPrefix();

			//add the field table
			if(!in_array($prefix.'k2store_field', $tables)){
				$query = "
				CREATE TABLE `#__k2store_field` (
				  `field_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
				  `field_table` varchar(50) DEFAULT NULL,
				  `field_name` varchar(250) NOT NULL,
				  `field_namekey` varchar(50) NOT NULL,
				  `field_type` varchar(50) DEFAULT NULL,
				  `field_value` longtext NOT NULL,
				  `published` tinyint(3) unsigned NOT NULL DEFAULT '1',
				  `ordering` smallint(5) unsigned DEFAULT '99',
				  `field_options` text,
				  `field_core` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `field_required` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `field_default` varchar(250) DEFAULT NULL,
				  `field_access` varchar(255) NOT NULL DEFAULT 'all',
				  `field_categories` varchar(255) NOT NULL DEFAULT 'all',
				  `field_with_sub_categories` tinyint(1) NOT NULL DEFAULT '0',
				  `field_frontend` tinyint(3) unsigned NOT NULL DEFAULT '0',
				  `field_backend` tinyint(3) unsigned NOT NULL DEFAULT '1',
				  `field_display` text NOT NULL,
				  `field_display_billing` int(11) NOT NULL,
				  `field_display_register` smallint(5) NOT NULL DEFAULT '0',
				  `field_display_shipping` int(11) NOT NULL,
				  `field_display_guest` smallint(5) NOT NULL DEFAULT '0',
				  `field_display_guest_shipping` smallint(5) NOT NULL DEFAULT '0',
				  `field_display_payment` int(11) NOT NULL,
				  PRIMARY KEY (`field_id`),
				  UNIQUE KEY `field_namekey` (`field_namekey`)
				) AUTO_INCREMENT=14 DEFAULT CHARSET=utf8;
				";

				$this->_executeQuery($query);

				$query ="
				INSERT IGNORE INTO `#__k2store_field` (`field_id`, `field_table`, `field_name`, `field_namekey`, `field_type`, `field_value`, `published`, `ordering`, `field_options`, `field_core`, `field_required`, `field_default`, `field_access`, `field_categories`, `field_with_sub_categories`, `field_frontend`, `field_backend`, `field_display`, `field_display_billing`, `field_display_register`, `field_display_shipping`, `field_display_guest`, `field_display_guest_shipping`, `field_display_payment`) VALUES
				('1', 'address', 'First Name', 'first_name', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:22:\"This field is required\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '1', '', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('2', 'address', 'Last Name', 'last_name', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:22:\"This field is required\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '1', '', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('3', 'address', 'Email', 'email', 'email', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:36:\"K2STORE_VALIDATION_ENTER_VALID_EMAIL\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '1', '', 'all', 'all', '0', '0', '1', '', '0', '1', '0', '1', '0', '0'),
				('4', 'address', 'Address Line 1', 'address_1', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:22:\"This field is required\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '1', '', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('5', 'address', 'Address Line 2', 'address_2', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:0:\"\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '0', '', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('6', 'address', 'City', 'city', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:22:\"This field is required\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '1', '', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('7', 'address', 'Postcode', 'zip', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:22:\"This field is required\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '1', '', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('8', 'address', 'Telephone', 'phone_1', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:0:\"\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '0', '', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('9', 'address', 'Mobile', 'phone_2', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:22:\"This field is required\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '1', '', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('10', 'address', 'Company Name', 'company', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:0:\"\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '0', '', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('11', 'address', 'VAT/Tax Number', 'tax_number', 'text', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:0:\"\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '0', '', 'all', 'all', '0', '0', '1', '', '1', '1', '0', '1', '0', '0'),
				('12', 'address', 'Country', 'country_id', 'zone', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:22:\"This field is required\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:7:\"country\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '1', '222', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0'),
				('13', 'address', 'Zone/State', 'zone_id', 'zone', '', '1', '99', 'a:9:{s:12:\"errormessage\";s:0:\"\";s:9:\"filtering\";s:1:\"1\";s:9:\"maxlength\";s:1:\"0\";s:4:\"size\";s:0:\"\";s:4:\"rows\";s:0:\"\";s:4:\"cols\";s:0:\"\";s:9:\"zone_type\";s:4:\"zone\";s:6:\"format\";s:0:\"\";s:8:\"readonly\";s:1:\"0\";}', '1', '1', '0', 'all', 'all', '0', '0', '1', '', '1', '1', '1', '1', '1', '0');
				";
				$this->_executeQuery($query);
			}
				if(!in_array($prefix.'k2store_taxprofiles', $tables)){
					//create a new one
					$query = "CREATE TABLE IF NOT EXISTS `#__k2store_taxprofiles` (
				`taxprofile_id` int(11) NOT NULL AUTO_INCREMENT,
				`taxprofile_name` varchar(255) NOT NULL,
				`state` int(11) NOT NULL,
				`ordering` int(11) NOT NULL,
				PRIMARY KEY (`taxprofile_id`)
				) DEFAULT CHARSET=utf8;
				";
					$this->_executeQuery($query);

				}

				//geozonerules
				if(!in_array($prefix.'k2store_geozonerules', $tables)){
					$query = "
					CREATE TABLE IF NOT EXISTS `#__k2store_geozonerules` (
					  `geozonerule_id` int(11) NOT NULL AUTO_INCREMENT,
					  `geozone_id` int(11) NOT NULL,
					  `country_id` int(11) NOT NULL,
					  `zone_id` int(11) NOT NULL,
					  `ordering` int(11) NOT NULL,
					  PRIMARY KEY (`geozonerule_id`)
					)DEFAULT CHARSET=utf8;
					";
					$this->_executeQuery($query);
				}

				//geozones
				if(!in_array($prefix.'k2store_geozones', $tables)){
					$query = "
					 CREATE TABLE IF NOT EXISTS `#__k2store_geozones` (
					  `geozone_id` int(11) NOT NULL AUTO_INCREMENT,
					  `geozone_name` varchar(255) NOT NULL,
					  `state` int(11) NOT NULL,
					  `ordering` int(11) NOT NULL,
					  PRIMARY KEY (`geozone_id`)
					)DEFAULT CHARSET=utf8;
					";
					$this->_executeQuery($query);
				}

				//tax rates
				if(!in_array($prefix.'k2store_taxrates', $tables)){
					$query = "
					 CREATE TABLE IF NOT EXISTS `#__k2store_taxrates` (
						 `taxrate_id` int(11) NOT NULL AUTO_INCREMENT,
						  `geozone_id` int(11) NOT NULL,
						  `taxrate_name` varchar(255) NOT NULL,
						  `tax_percent` decimal(11,3) NOT NULL,
						  `state` int(11) NOT NULL,
						  `ordering` int(11) NOT NULL,
						  PRIMARY KEY (`taxrate_id`)
						)DEFAULT CHARSET=utf8;
					";
					$this->_executeQuery($query);
				}

				//tax rules
				if(!in_array($prefix.'k2store_taxrules', $tables)){
					$query = "
					CREATE TABLE IF NOT EXISTS `#__k2store_taxrules` (
					  `taxrule_id` int(11) NOT NULL AUTO_INCREMENT,
					  `taxprofile_id` int(11) NOT NULL,
					  `taxrate_id` int(11) NOT NULL,
					  `address` varchar(255) NOT NULL,
					  `ordering` int(11) NOT NULL,
					  `state` int(11) NOT NULL,
					  PRIMARY KEY (`taxrule_id`)
					)DEFAULT CHARSET=utf8;
					";
					$this->_executeQuery($query);
				}

				//currency
				if(!in_array($prefix.'k2store_currency', $tables)){
					$query = "
					CREATE TABLE IF NOT EXISTS `#__k2store_currency` (
					`currency_id` int(11) NOT NULL AUTO_INCREMENT,
					`currency_title` varchar(32) NOT NULL,
					`currency_code` varchar(3) NOT NULL,
					`currency_position` varchar(12) NOT NULL,
					`currency_symbol` varchar(255) NOT NULL,
					`currency_num_decimals` int(4) NOT NULL,
					`currency_decimal` varchar(12) NOT NULL,
					`currency_thousands` char(1) NOT NULL,
					`currency_value` float(15,8) NOT NULL,
					`state` smallint(1) NOT NULL,
					`currency_modified` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
					PRIMARY KEY (`currency_id`)
					) DEFAULT CHARSET=utf8;
					";
					$this->_executeQuery($query);

					if($type=='install') {
						$query="
						INSERT IGNORE INTO `#__k2store_currency` (`currency_id`, `currency_title`, `currency_code`, `currency_position`, `currency_symbol`, `currency_num_decimals`, `currency_decimal`, `currency_thousands`, `currency_value`, `state`, `currency_modified`) VALUES
						(1, 'US Dollar', 'USD', 'pre', '$', 2, '.', ',', 1.00000000, 1, '2013-11-27 21:09:28');
						";
						$this->_executeQuery($query);
					}
				}

				if(!in_array($prefix.'k2store_orderstatuses', $tables)){
					$query ="
					CREATE TABLE IF NOT EXISTS `#__k2store_orderstatuses` (
					`orderstatus_id` int(11) NOT NULL AUTO_INCREMENT,
					`orderstatus_name` varchar(32) NOT NULL,
					`orderstatus_cssclass` text NOT NULL,
					`orderstatus_core` int(1) NOT NULL DEFAULT '0',
					PRIMARY KEY (`orderstatus_id`)
					) DEFAULT CHARSET=utf8 AUTO_INCREMENT=6 ;
					";
					$this->_executeQuery($query);

					$query ="
					INSERT IGNORE INTO `#__k2store_orderstatuses` (`orderstatus_id`, `orderstatus_name`, `orderstatus_cssclass`, `orderstatus_core`) VALUES
					(1, 'K2STORE_CONFIRMED', 'label-success', 1),
					(2, 'K2STORE_PROCESSED', 'label-info', 1),
					(3, 'K2STORE_FAILED', 'label-important', 1),
					(4, 'K2STORE_PENDING', 'label-warning', 1),
					(5, 'K2STORE_INCOMPLETE', 'label-important', 1);
					";

					$this->_executeQuery($query);
				}

		}

		private function _modifyExistingTables($type) {

			$db = JFactory::getDbo();
			//get the table list
			$tables = $db->getTableList();
			//get prefix
			$prefix = $db->getPrefix();

			//modify store profiles table
			if(in_array($prefix.'k2store_storeprofiles', $tables)){
				$fields = $db->getTableColumns('#__k2store_storeprofiles');

				if (!array_key_exists('store_min_out_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_storeprofiles ADD `store_min_out_qty` varchar(255) NOT NULL AFTER `ordering`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('store_min_sale_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_storeprofiles ADD `store_min_sale_qty` varchar(255) NOT NULL AFTER `store_min_out_qty`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('store_max_sale_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_storeprofiles ADD `store_max_sale_qty` varchar(255) NOT NULL AFTER `store_min_sale_qty`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('store_notify_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_storeprofiles ADD `store_notify_qty` varchar(255) NOT NULL AFTER `store_max_sale_qty`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('store_register_layout', $fields)) {
					$this->_executeQuery("ALTER TABLE #__k2store_storeprofiles ADD `store_register_layout` longtext NOT NULL AFTER `store_notify_qty`");
				}

				if (!array_key_exists('store_billing_layout', $fields)) {
					$this->_executeQuery("ALTER TABLE #__k2store_storeprofiles ADD `store_billing_layout` longtext NOT NULL AFTER `store_register_layout`");
				}

				if (!array_key_exists('store_shipping_layout', $fields)) {
					$this->_executeQuery("ALTER TABLE #__k2store_storeprofiles ADD `store_shipping_layout` longtext NOT NULL AFTER `store_billing_layout`");
				}

				if (!array_key_exists('store_guest_layout', $fields)) {
					$this->_executeQuery("ALTER TABLE #__k2store_storeprofiles ADD `store_guest_layout` longtext NOT NULL AFTER `store_shipping_layout`");
				}

				if (!array_key_exists('store_guest_shipping_layout', $fields)) {
					$this->_executeQuery("ALTER TABLE #__k2store_storeprofiles ADD `store_guest_shipping_layout` longtext NOT NULL AFTER `store_guest_layout`");
				}

				if (!array_key_exists('store_payment_layout', $fields)) {
					$this->_executeQuery("ALTER TABLE #__k2store_storeprofiles ADD `store_payment_layout` longtext NOT NULL AFTER `store_guest_shipping_layout`");
				}

				if (!array_key_exists('config_currency', $fields)) {
					$query = "ALTER TABLE #__k2store_storeprofiles ADD `config_currency` varchar(255) NOT NULL AFTER `zone_name`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('config_currency_auto', $fields)) {
					$query = "ALTER TABLE #__k2store_storeprofiles ADD `config_currency_auto` smallint(5) NOT NULL AFTER `config_currency`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('config_continue_shopping_url', $fields)) {
					$query = "ALTER TABLE #__k2store_storeprofiles ADD `config_continue_shopping_url` varchar(255) NOT NULL AFTER `config_default_category`";
					$this->_executeQuery($query);
				}

			}

			//modify product quantities table
			if(in_array($prefix.'k2store_productquantities', $tables)){
				$fields = $db->getTableColumns('#__k2store_productquantities');

				if (!array_key_exists('manage_stock', $fields)) {
					$query = "ALTER TABLE #__k2store_productquantities ADD `manage_stock` smallint(5) NOT NULL DEFAULT '0' AFTER `quantity`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('min_out_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_productquantities ADD `min_out_qty` decimal(12,4) NOT NULL AFTER `manage_stock`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('use_store_config_min_out_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_productquantities ADD `use_store_config_min_out_qty` smallint(5) NOT NULL DEFAULT '1' AFTER `min_out_qty`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('min_sale_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_productquantities ADD `min_sale_qty` decimal(12,4) NOT NULL AFTER `use_store_config_min_out_qty`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('use_store_config_min_sale_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_productquantities ADD `use_store_config_min_sale_qty` smallint(5) NOT NULL DEFAULT '1' AFTER `min_sale_qty`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('max_sale_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_productquantities ADD `max_sale_qty` decimal(12,4) NOT NULL AFTER `use_store_config_min_sale_qty`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('use_store_config_max_sale_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_productquantities ADD `use_store_config_max_sale_qty` smallint(5) NOT NULL DEFAULT '1' AFTER `max_sale_qty`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('notify_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_productquantities ADD `notify_qty` decimal(12,4) NOT NULL AFTER `use_store_config_max_sale_qty`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('use_store_config_notify_qty', $fields)) {
					$query = "ALTER TABLE #__k2store_productquantities ADD `use_store_config_notify_qty` smallint(5) NOT NULL DEFAULT '1' AFTER `notify_qty`";
					$this->_executeQuery($query);
				}

			}


			//orders table modifications
			if(in_array($prefix.'k2store_orders', $tables)){
				$fields = $db->getTableColumns('#__k2store_orders');

				if (!array_key_exists('user_email', $fields)) {
					$query = "ALTER TABLE #__k2store_orders ADD `user_email` varchar(255) NOT NULL AFTER `user_id`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('token', $fields)) {
					$query = "ALTER TABLE #__k2store_orders ADD `token` varchar(255) NOT NULL AFTER `user_email`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('customer_language', $fields)) {
					$query = "ALTER TABLE #__k2store_orders ADD `customer_language` varchar(255) NOT NULL AFTER `customer_note`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('order_shipping_tax', $fields)) {
					$query = "ALTER TABLE #__k2store_orders ADD `order_shipping_tax` decimal(10,2) NOT NULL AFTER `user_email`";
					$this->_executeQuery($query);
				}


				if (!array_key_exists('currency_id', $fields)) {
					$query = "ALTER TABLE #__k2store_orders ADD `currency_id` int(11) NOT NULL AFTER `order_discount`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('currency_code', $fields)) {
					$query = "ALTER TABLE #__k2store_orders ADD `currency_code` varchar(5) NOT NULL AFTER `currency_id`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('currency_value', $fields)) {
					$query = "ALTER TABLE #__k2store_orders ADD `currency_value` decimal(15,8) NOT NULL AFTER `currency_code`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('order_surcharge', $fields)) {
					$query = "ALTER TABLE #__k2store_orders ADD `order_surcharge` decimal(15,8) NOT NULL DEFAULT '0.00' AFTER `order_discount`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('stock_adjusted', $fields)) {
					$query = "ALTER TABLE #__k2store_orders ADD `stock_adjusted` smallint(5) NOT NULL AFTER `order_state_id`";
					$this->_executeQuery($query);
				}

			}


			//orders table modifications
			if(in_array($prefix.'k2store_orderinfo', $tables)){
				$fields = $db->getTableColumns('#__k2store_orderinfo');

				if (!array_key_exists('all_billing', $fields)) {
					$query = "ALTER TABLE #__k2store_orderinfo ADD `all_billing` longtext NOT NULL AFTER `user_id`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('all_shipping', $fields)) {
					$query = "ALTER TABLE #__k2store_orderinfo ADD `all_shipping` longtext NOT NULL AFTER `all_billing`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('all_payment', $fields)) {
					$query = "ALTER TABLE #__k2store_orderinfo ADD `all_payment` longtext NOT NULL AFTER `all_shipping`";
					$this->_executeQuery($query);
				}

			}

			//tax profiles
			if(in_array($prefix.'k2store_taxprofiles', $tables)){
				$fields = $db->getTableColumns('#__k2store_taxprofiles');

				if (!array_key_exists('taxprofile_id', $fields) && array_key_exists('id', $fields) ) {

					//we have the old table. drop it
					$query = "DROP TABLE #__k2store_taxprofiles";
					$this->_executeQuery($query);

					//create a new one
					$query = "CREATE TABLE IF NOT EXISTS `#__k2store_taxprofiles` (
				`taxprofile_id` int(11) NOT NULL AUTO_INCREMENT,
				`taxprofile_name` varchar(255) NOT NULL,
				`state` int(11) NOT NULL,
				`ordering` int(11) NOT NULL,
				PRIMARY KEY (`taxprofile_id`)
				) DEFAULT CHARSET=utf8;
				";
					$this->_executeQuery($query);
				}

			}

			//coupons
			if(in_array($prefix.'k2store_coupons', $tables)){
				$fields = $db->getTableColumns('#__k2store_coupons');

				if (!array_key_exists('products', $fields)) {
					$query = "ALTER TABLE #__k2store_coupons ADD `products` varchar(255) NOT NULL AFTER `product_category`";
					$this->_executeQuery($query);
				}

			}

			//product option values
			if(in_array($prefix.'k2store_product_optionvalues', $tables)){
				$fields = $db->getTableColumns('#__k2store_product_optionvalues');

				if (!array_key_exists('pov_short_desc', $fields)) {
					$query = "ALTER TABLE #__k2store_product_optionvalues ADD `pov_short_desc` text NOT NULL AFTER `ordering`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('pov_long_desc', $fields)) {
					$query = "ALTER TABLE #__k2store_product_optionvalues ADD `pov_long_desc` text NOT NULL AFTER `pov_short_desc`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('pov_ref', $fields)) {
					$query = "ALTER TABLE #__k2store_product_optionvalues ADD `pov_ref` text NOT NULL AFTER `pov_long_desc`";
					$this->_executeQuery($query);
				}

			}

			//address
			if(in_array($prefix.'k2store_address', $tables)){
				$fields = $db->getTableColumns('#__k2store_address');

				if (!array_key_exists('email', $fields)) {
					$query = "ALTER TABLE #__k2store_address ADD `email` varchar(255) NOT NULL AFTER `last_name`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('zone_id', $fields)) {
					$query = "ALTER TABLE #__k2store_address ADD `zone_id` varchar(255) NOT NULL AFTER `zip`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('country_id', $fields)) {
					$query = "ALTER TABLE #__k2store_address ADD `country_id` varchar(255) NOT NULL AFTER `zone_id`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('type', $fields)) {
					$query = "ALTER TABLE #__k2store_address ADD `type` varchar(255) NOT NULL AFTER `fax`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('company', $fields)) {
					$query = "ALTER TABLE #__k2store_address ADD `company` varchar(255) NOT NULL AFTER `fax`";
					$this->_executeQuery($query);
				}

				if (!array_key_exists('tax_number', $fields)) {
					$query = "ALTER TABLE #__k2store_address ADD `tax_number` varchar(255) NOT NULL AFTER `company`";
					$this->_executeQuery($query);
				}
			}



		}

		private function _doCompatibilityChecks($type) {

			JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2store/tables');
			$db = JFactory::getDbo();
			$query = $db->getQuery(true);
			$query->select('*')->from('#__k2store_productquantities');
			$db->setQuery($query);
			$rows = $db->loadObjectList();

			if(count($rows)) {
				foreach($rows as $row) {

					//if quantity is greater than 0, then stock is being tracked. So set the Manage Stock to 1
					if($row->quantity > 0) {

						$item = JTable::getInstance('ProductQuantities', 'Table');
						$item->load($row->productquantity_id);
						$item->manage_stock = 1;
						$item->store();
						unset($item);
					}

				}

			}

		}

		private function _configMigration($type, $parent) {
			$db = JFactory::getDbo();
			//migrate the currency params from general options to currency table
			if($type=='update') {

				// Load the component parameters, not using JComponentHelper to avoid conflicts ;)
				JLoader::import('joomla.html.parameter');
				JLoader::import('joomla.application.component.helper');

				$sql = $db->getQuery(true)
				->select($db->qn('params'))
				->from($db->qn('#__extensions'))
				->where($db->qn('type').' = '.$db->q('component'))
				->where($db->qn('element').' = '.$db->q($this->_extension_name));
				$db->setQuery($sql);
				$rawparams = $db->loadResult();
				$params = new JRegistry();
				if(version_compare(JVERSION, '3.0', 'ge')) {
					$params->loadString($rawparams, 'JSON');
				} else {
					$params->loadJSON($rawparams);
				}

				JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2store/tables');
				$query = $db->getQuery(true);

				//copy params only when the currency table is empty. Otherwise, the user is either doing a fresh install or already updated
				$query->select('*')->from('#__k2store_currency');
				$db->setQuery($query);
				$rows = $db->loadObjectList();

				if(count($rows) < 1) {

					//store data to the currency table only if data exists in params.
					$currency_code = $params->get('currency_code');
					if(isset($currency_code) && strlen($currency_code)) {
						$item = JTable::getInstance('Currency', 'Table');
						$item->currency_title = $currency_code;
						$item->currency_code = $currency_code;
						$item->currency_position = $params->get('currency_position');
						$item->currency_symbol = $params->get('currency');
						$item->currency_num_decimals = $params->get('currency_num_decimals')?$params->get('currency_num_decimals'):'2';
						$item->currency_decimal = $params->get('currency_decimal')?$params->get('currency_decimal'):'.';
						$item->currency_thousands = $params->get('currency_thousands');
						$item->currency_value = '1.00000'; //default currency is one always
						$item->currency_modified = JFactory::getDate()->toSql();
						$item->state = 1;
						$item->store();
						$currency_id = $item->currency_id;

						//now update the store profiles table

						//first get the active store profile
						$query = $db->getQuery(true);
						$query->select('*');
						$query->from('#__k2store_storeprofiles');
						$query->where('state=1');
						$query->order('store_id ASC LIMIT 1');
						$db->setQuery($query);
						$row =	$db->loadObject();
						if($row->store_id) {
							$store = JTable::getInstance('Storeprofile', 'Table');
							$store->load($row->store_id);
							if($currency_id) {
								$store->config_currency = $currency_code;
								$store->config_currency_auto = 1;
								$store->store();
							}

						}

						//now we have to update all the previous order records with the currency value 1
						$sql = $db->getQuery(true);
						$sql->select('*')->from('#__k2store_orders');
						$db->setQuery($sql);
						$orders = $db->loadObjectList();

						//if we have order records
						if(count($orders)) {
							$query = $db->getQuery(true);
							$query->update('#__k2store_orders')
							->set('currency_id='.$currency_id)
							->set('currency_code='.$db->q($currency_code))
							->set('currency_value=1');
							$db->setQuery($query);
							$db->execute();
						}

					}
				}

			}

		}



		private function _removeObsoletePlugins($parent)
		{
			$src = $parent->getParent()->getPath('source');
			$db = JFactory::getDbo();

			foreach($this->RemovePlugins as $folder => $plugins) {
				foreach($plugins as $plugin) {
					$sql = $db->getQuery(true)
					->select($db->qn('extension_id'))
					->from($db->qn('#__extensions'))
					->where($db->qn('type').' = '.$db->q('plugin'))
					->where($db->qn('element').' = '.$db->q($plugin))
					->where($db->qn('folder').' = '.$db->q($folder));
					$db->setQuery($sql);
					$id = $db->loadResult();
					if($id)
					{
						$installer = new JInstaller;
						$result = $installer->uninstall('plugin',$id,1);
					}
				}
			}
		}

		private function _removeObsoleteFiles($parent)
		{

			if(count($this->RemoveFilesAdmin)) {
				foreach($this->RemoveFilesAdmin as $folder => $files) {

					if($folder!='views') {
						foreach($files as $filename) {
							if(JFile::exists(JPATH_ADMINISTRATOR.'/components/com_k2store/'.$folder.'/'.$filename.'.php')) {
								try {
									JFile::delete(JPATH_ADMINISTRATOR.'/components/com_k2store/'.$folder.'/'.$filename.'.php');
								} catch (Exception $exc) {
									//if error, dont sweat about
								}
							}
						}
					}

					if($folder=='views') {
						foreach($files as $filename) {
							if(JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_k2store/'.$folder.'/'.$filename)) {
								try {
									JFolder::delete(JPATH_ADMINISTRATOR.'/components/com_k2store/'.$folder.'/'.$filename);
								} catch (Exception $exc) {
									//if error, dont sweat about
								}
							}
						}
					}
				}
			}

			if(count($this->RemoveFilesSite)) {
				foreach($this->RemoveFilesSite as $folder => $files) {
					if($folder!='views') {
						foreach($files as $filename) {
							if(JFile::exists(JPATH_SITE.'/components/com_k2store/'.$folder.'/'.$filename.'.php')) {
								try {
									JFile::delete(JPATH_SITE.'/components/com_k2store/'.$folder.'/'.$filename.'.php');
								} catch (Exception $exc) {
									//if error, dont sweat about
								}
							}
						}
					}

					if($folder=='views') {
						foreach($files as $filename) {
							if(JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_k2store/'.$folder.'/'.$filename)) {
								try {
									JFolder::delete(JPATH_ADMINISTRATOR.'/components/com_k2store/'.$folder.'/'.$filename);
								} catch (Exception $exc) {
									//if error, dont sweat about
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Joomla! 1.6+ bugfix for "DB function returned no error"
		 */
		private function _bugfixDBFunctionReturnedNoError()
		{
			$db = JFactory::getDbo();

			// Fix broken #__assets records
			$query = $db->getQuery(true);
			$query->select('id')
			->from('#__assets')
			->where($db->qn('name').' = '.$db->q($this->_extension_name));
			$db->setQuery($query);
			$ids = $db->loadColumn();
			if(!empty($ids)) foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__assets')
				->where($db->qn('id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->query();
			}

			// Fix broken #__extensions records
			$query = $db->getQuery(true);
			$query->select('extension_id')
			->from('#__extensions')
			->where($db->qn('element').' = '.$db->q($this->_extension_name));
			$db->setQuery($query);
			$ids = $db->loadColumn();
			if(!empty($ids)) foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__extensions')
				->where($db->qn('extension_id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->query();
			}

			// Fix broken #__menu records
			$query = $db->getQuery(true);
			$query->select('id')
			->from('#__menu')
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('menutype').' = '.$db->q('main'))
			->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_extension_name));
			$db->setQuery($query);
			$ids = $db->loadColumn();
			if(!empty($ids)) foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__menu')
				->where($db->qn('id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->query();
			}
		}


		/**
		 * Joomla! 1.6+ bugfix for "Can not build admin menus"
		 */
		private function _bugfixCantBuildAdminMenus()
		{
			$db = JFactory::getDbo();

			// If there are multiple #__extensions record, keep one of them
			$query = $db->getQuery(true);
			$query->select('extension_id')
			->from('#__extensions')
			->where($db->qn('element').' = '.$db->q($this->_extension_name));
			$db->setQuery($query);
			$ids = $db->loadColumn();
			if(count($ids) > 1) {
				asort($ids);
				$extension_id = array_shift($ids); // Keep the oldest id

				foreach($ids as $id) {
					$query = $db->getQuery(true);
					$query->delete('#__extensions')
					->where($db->qn('extension_id').' = '.$db->q($id));
					$db->setQuery($query);
					$db->query();
				}
			}

			// @todo

			// If there are multiple assets records, delete all except the oldest one
			$query = $db->getQuery(true);
			$query->select('id')
			->from('#__assets')
			->where($db->qn('name').' = '.$db->q($this->_extension_name));
			$db->setQuery($query);
			$ids = $db->loadObjectList();
			if(count($ids) > 1) {
				asort($ids);
				$asset_id = array_shift($ids); // Keep the oldest id

				foreach($ids as $id) {
					$query = $db->getQuery(true);
					$query->delete('#__assets')
					->where($db->qn('id').' = '.$db->q($id));
					$db->setQuery($query);
					$db->query();
				}
			}

			// Remove #__menu records for good measure!
			$query = $db->getQuery(true);
			$query->select('id')
			->from('#__menu')
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('menutype').' = '.$db->q('main'))
			->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_extension_name));
			$db->setQuery($query);
			$ids1 = $db->loadColumn();
			if(empty($ids1)) $ids1 = array();
			$query = $db->getQuery(true);
			$query->select('id')
			->from('#__menu')
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('menutype').' = '.$db->q('main'))
			->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_extension_name.'&%'));
			$db->setQuery($query);
			$ids2 = $db->loadColumn();
			if(empty($ids2)) $ids2 = array();
			$ids = array_merge($ids1, $ids2);
			if(!empty($ids)) foreach($ids as $id) {
				$query = $db->getQuery(true);
				$query->delete('#__menu')
				->where($db->qn('id').' = '.$db->q($id));
				$db->setQuery($query);
				$db->query();
			}
		}

		private function _rebuildMenus() {

			$db = JFactory::getDbo();

			$query = $db->getQuery(true);
			$query->select('extension_id')
			->from('#__extensions')
			->where($db->qn('element').' = '.$db->q($this->_extension_name));
			$db->setQuery($query);
			$extension_id = $db->loadResult();
			if($extension_id) {
				$query = $db->getQuery(true);
				$query->select('*')
				->from('#__menu')
				->where($db->qn('type').' = '.$db->q('component'))
				->where($db->qn('menutype').' != '.$db->q('main'))
				->where($db->qn('link').' LIKE '.$db->q('index.php?option='.$this->_extension_name.'&%'));
				$db->setQuery($query);
				$menus = $db->loadObjectList();

				if(count($menus)) {
					foreach($menus as $menu){
						if($menu->component_id != $extension_id) {
							$table = JTable::getInstance('Menu', 'JTable', array());
							$table->load($menu->id);
							$table->component_id= $extension_id;
							if(!$table->store()) {
								//dont do anything stupid. Just return true. This can be done manually too.
								return true;
							}
						}
					}
				}
			}

		return true;
		}


		private function _getPreviousVersion() {

			jimport('joomla.filesystem.file');
			$target = JPATH_ADMINISTRATOR.'/components/com_k2store/pre-version.txt';
			$version = null;
			if(JFile::exists($target)) {
				$rawData = JFile::read($target);
				$info = explode("\n", $rawData);
				$version = trim($info[0]);
			}
			return $version;

		}


		/**
		 * Deletes the Live Update information, forcing its reload during the first
		 * run of the component. This makes sure that the Live Update doesn't show
		 * an update available right after installing the component.
		 */
		private function _resetLiveUpdate()
		{
			// Load the component parameters, not using JComponentHelper to avoid conflicts ;)
			JLoader::import('joomla.html.parameter');
			JLoader::import('joomla.application.component.helper');
			$db = JFactory::getDbo();
			$sql = $db->getQuery(true)
			->select($db->qn('params'))
			->from($db->qn('#__extensions'))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q($this->_extension_name));
			$db->setQuery($sql);
			$rawparams = $db->loadResult();
			$params = new JRegistry();
			if(version_compare(JVERSION, '3.0', 'ge')) {
				$params->loadString($rawparams, 'JSON');
			} else {
				$params->loadJSON($rawparams);
			}

			// Reset the liveupdate key
			$params->set('liveupdate', null);

			// Save the modified component parameters
			$data = $params->toString();
			$sql = $db->getQuery(true)
			->update($db->qn('#__extensions'))
			->set($db->qn('params').' = '.$db->q($data))
			->where($db->qn('type').' = '.$db->q('component'))
			->where($db->qn('element').' = '.$db->q($this->_extension_name));

			$db->setQuery($sql);
			$db->execute();
		}

		private function _executeQuery($query) {

			$db = JFactory::getDbo();
			$db->setQuery($query);
			try {
				$db->query();
			}catch (Exception $e) {
				//do nothing. we dont want to fail the install process.
			}
		}

		private function installationResults($status)
		{
			$language = JFactory::getLanguage();
			$language->load('com_k2store');
		        $rows = 0; ?>
		        <img src="<?php echo JURI::root(true); ?>/media/k2store/images/k2store-logo.png" width="109" height="48" alt="K2Store Component" align="right" />
		        <div class="alert alert-block alert-danger">
		        		<?php echo JText::_('K2STORE_ATTRIBUTE_MIGRATION_ALERT'); ?>
		        </div>
		        <h2><?php echo JText::_('K2STORE_INSTALLATION_STATUS'); ?></h2>
		        <table class="adminlist table table-striped">
		            <thead>
		                <tr>
		                    <th class="title" colspan="2"><?php echo JText::_('K2STORE_EXTENSION'); ?></th>
		                    <th width="30%"><?php echo JText::_('K2STORE_STATUS'); ?></th>
		                </tr>
		            </thead>
		            <tfoot>
		                <tr>
		                    <td colspan="3"></td>
		                </tr>
		            </tfoot>
		            <tbody>
		                <tr class="row0">
		                    <td class="key" colspan="2"><?php echo 'K2Store '.JText::_('K2STORE_COMPONENT'); ?></td>
		                    <td><strong><?php echo JText::_('K2STORE_INSTALLED'); ?></strong></td>
		                </tr>
		                <?php if (count($status->modules)): ?>
		                <tr>
		                    <th><?php echo JText::_('K2STORE_MODULE'); ?></th>
		                    <th><?php echo JText::_('K2STORE_CLIENT'); ?></th>
		                    <th></th>
		                </tr>
		                <?php foreach ($status->modules as $module): ?>
		                <tr class="row<?php echo(++$rows % 2); ?>">
		                    <td class="key"><?php echo $module['name']; ?></td>
		                    <td class="key"><?php echo ucfirst($module['client']); ?></td>
		                    <td><strong><?php echo ($module['result'])?JText::_('K2STORE_INSTALLED'):JText::_('K2_NOT_INSTALLED'); ?></strong></td>
		                </tr>
		                <?php endforeach; ?>
		                <?php endif; ?>
		                <?php if (count($status->plugins)): ?>
		                <tr>
		                    <th><?php echo JText::_('K2STORE_PLUGIN'); ?></th>
		                    <th><?php echo JText::_('K2STORE_GROUP'); ?></th>
		                    <th></th>
		                </tr>
		                <?php foreach ($status->plugins as $plugin): ?>
		                <tr class="row<?php echo(++$rows % 2); ?>">
		                    <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
		                    <td class="key"><?php echo ucfirst($plugin['group']); ?></td>
		                    <td><strong><?php echo ($plugin['result'])?JText::_('K2STORE_INSTALLED'):JText::_('K2STORE_NOT_INSTALLED'); ?></strong></td>
		                </tr>
		                <?php endforeach; ?>
		                <?php endif; ?>
		            </tbody>
		        </table>
		    <?php
		    }

		    private function uninstallationResults($status)
		    {
		    $language = JFactory::getLanguage();
		    $language->load('com_k2store');
		    $rows = 0;
		 ?>
		        <h2><?php echo JText::_('K2STORE_REMOVAL_STATUS'); ?></h2>
		        <table class="adminlist">
		            <thead>
		                <tr>
		                    <th class="title" colspan="2"><?php echo JText::_('K2STORE_EXTENSION'); ?></th>
		                    <th width="30%"><?php echo JText::_('K2STORE_STATUS'); ?></th>
		                </tr>
		            </thead>
		            <tfoot>
		                <tr>
		                    <td colspan="3"></td>
		                </tr>
		            </tfoot>
		            <tbody>
		                <tr class="row0">
		                    <td class="key" colspan="2"><?php echo 'K2Store '.JText::_('K2STORE_COMPONENT'); ?></td>
		                    <td><strong><?php echo JText::_('K2STORE_REMOVED'); ?></strong></td>
		                </tr>
		                <?php if (count($status->modules)): ?>
		                <tr>
		                    <th><?php echo JText::_('K2STORE_MODULE'); ?></th>
		                    <th><?php echo JText::_('K2STORE_CLIENT'); ?></th>
		                    <th></th>
		                </tr>
		                <?php foreach ($status->modules as $module): ?>
		                <tr class="row<?php echo(++$rows % 2); ?>">
		                    <td class="key"><?php echo $module['name']; ?></td>
		                    <td class="key"><?php echo ucfirst($module['client']); ?></td>
		                    <td><strong><?php echo ($module['result'])?JText::_('K2STORE_REMOVED'):JText::_('K2STORE_NOT_REMOVED'); ?></strong></td>
		                </tr>
		                <?php endforeach; ?>
		                <?php endif; ?>

		                <?php if (count($status->plugins)): ?>
		                <tr>
		                    <th><?php echo JText::_('K2STORE_PLUGIN'); ?></th>
		                    <th><?php echo JText::_('K2STORE_GROUP'); ?></th>
		                    <th></th>
		                </tr>
		                <?php foreach ($status->plugins as $plugin): ?>
		                <tr class="row<?php echo(++$rows % 2); ?>">
		                    <td class="key"><?php echo ucfirst($plugin['name']); ?></td>
		                    <td class="key"><?php echo ucfirst($plugin['group']); ?></td>
		                    <td><strong><?php echo ($plugin['result'])?JText::_('K2STORE_REMOVED'):JText::_('K2STORE_NOT_REMOVED'); ?></strong></td>
		                </tr>
		                <?php endforeach; ?>
		                <?php endif; ?>
		            </tbody>
		        </table>
		    <?php
		    }

	}