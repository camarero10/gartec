<?php
/*
 * --------------------------------------------------------------------------------
   Weblogicx India  - K2 Store v 1.2
 * --------------------------------------------------------------------------------
 * @package		Joomla! 1.5x
 * @subpackage	K2 Store
 * @author    	Weblogicx India http://www.weblogicxindia.com
 * @copyright	Copyright (c) 2010 - 2015 Weblogicx India Ltd. All rights reserved.
 * @license		GNU/GPL license: http://www.gnu.org/copyleft/gpl.html
 * @link		http://weblogicxindia.com
 * --------------------------------------------------------------------------------
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die( 'Restricted access' );

class TableOrderInfo extends JTable
{

	/**
	* @param database A database connector object
	*/
	function __construct(&$db)
	{
		parent::__construct('#__k2store_orderinfo', 'orderinfo_id', $db );
	}

}
