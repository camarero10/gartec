<?php
/*------------------------------------------------------------------------
# com_k2store - J2 Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi- Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/


// No direct access
defined('_JEXEC') or die;

/**
 * Submenu helper.
 *
 * @package		Joomla.Administrator
 * @subpackage	com_k2store
 * @since		2.5
 */


if (version_compare(JVERSION, '3.0', 'ge'))
{
	require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/toolbar30.php');
	class K2StoreToolBar extends K2StoreToolBar30
	{

		public static function &getAnInstance($option = null, $config = array()) {

			if (!class_exists( $className )) {
				$className = 'K2StoreToolbar';
			}
			$instance = new $className($config);

			return $instance;

		}


		public function __construct($config = array()) {}

		}

		class JToolbarButtonK2Store extends JToolbarButtonK2Store30 {

		}

}
else
{
	require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/toolbar25.php');
	class K2StoreToolBar extends K2StoreToolBar25
	{

		public static function &getAnInstance($option = null, $config = array()) {

			if (!class_exists( $className )) {
				$className = 'K2StoreToolbar';
			}
			$instance = new $className($config);

			return $instance;

		}


		public function __construct($config = array()) {}
		}

		class JButtonK2Store extends JToolbarButtonK2Store25 {

		}

}