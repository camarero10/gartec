<?php
/*------------------------------------------------------------------------
 # com_k2store - K2Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/
// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controllerform');

class K2StoreControllerCurrency extends JControllerForm
{

	function save($key = null, $urlVar = null) {
		if(parent::save($key = null, $urlVar = null)) {
			require_once (JPATH_SITE.'/components/com_k2store/helpers/cart.php');
			$storeprofile = K2StoreHelperCart::getStoreAddress();

			if($storeprofile->config_currency_auto) {
				$model = $this->getModel('currencies');
				$model->updateCurrencies(true);
			}

		}
	}
}
