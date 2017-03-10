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

class TableProducts extends JTable
{
	function __construct(&$db )
	{
		parent::__construct('#__k2store_products', 'p_id', $db );
	}

}