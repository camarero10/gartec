<?php
/*------------------------------------------------------------------------
# mod_k2store_cart - K2Store Cart
# ------------------------------------------------------------------------
# author    Sasi varna kumar - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/



// no direct access
defined('_JEXEC') or die('Restricted access');

abstract class ModK2StoreCurrencyHelper
{
	public static function getCurrencies(&$params)
	{

		JModelLegacy::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2store/models');
		$model = JModelLegacy::getInstance('Currencies', 'K2StoreModel');
		return $model->getCurrencies();
	}

}

