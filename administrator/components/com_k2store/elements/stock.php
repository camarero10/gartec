<?php
/*------------------------------------------------------------------------
# com_k2store - K2Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org
-------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die;
/**
 * Stock Form Field class for the K2Store component
 */
require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/version.php');
class JElementStock extends JElement
{
	/**
	 * The field type.
	 *
	 * @var		string
	 */
	var	$_name = 'Stock';

	function fetchElement($name, $value, &$node, $control_name){

		//get libraries
		return K2StoreVersion::getPROVersion();
	}

}