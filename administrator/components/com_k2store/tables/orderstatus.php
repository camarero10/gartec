<?php
/**
 * @version		$Id: k2store.php 18287 2010-07-28 19:09:44Z ian $
 * @copyright	Copyright (C) 2005 - 2010 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access
defined('_JEXEC') or die;

/**
 * Weblink Table class
 *
 * @package		Joomla.Administrator
 * @subpackage	com_k2store
 * @since		1.5
 */
class TableOrderstatus extends JTable
{


	/**
	 * Constructor
	 *
	 * @param JDatabase A database connector object
	 */
	public function __construct(&$db)
	{
		parent::__construct('#__k2store_orderstatuses', 'orderstatus_id', $db);
	}

	public function check()
	{
		if(!isset($this->orderstatus_core)) {
			$this->orderstatus_core = 0;
		}
		return true;
	}

}