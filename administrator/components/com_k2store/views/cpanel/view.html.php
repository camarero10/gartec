<?php
/*------------------------------------------------------------------------
# com_k2store - K2 Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/



// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class K2StoreViewCpanel extends K2StoreView
{

	function display($tpl = null) {
		$app = JFactory::getApplication();
		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__k2store_storeprofiles');
		$query->where('state=1');
		$query->order('store_id ASC LIMIT 1');
		$db->setQuery($query);
		$store =	$db->loadObject();

		if(!isset($store->store_id) || (int) $store->store_id < 1) {
			$redirect_url = 'index.php?option=com_k2store&view=postconfig';
			$app->redirect($redirect_url );
		}

		$this->assignRef('store', $store);
		$model = $this->getModel('cpanel');
		$model->checkCurrency();

		$params = JComponentHelper::getParams('com_k2store');

		$xmlfile = JPATH_ADMINISTRATOR.'/components/com_k2store/manifest.xml';
		$row = new JObject();
		$data = JApplicationHelper::parseXMLInstallFile($xmlfile);
			foreach($data as $key => $value) {
						$row->$key = $value;
				}
        $this->assignRef('params', $params);
        $this->assignRef('row', $row);
        require_once (JPATH_COMPONENT_ADMINISTRATOR.'/models/orders.php' );
        require_once (JPATH_COMPONENT_ADMINISTRATOR.'/library/prices.php' );
        $order_model = new K2StoreModelOrders();
        $order_model->setState('filter_limit', 5);
       	$latest_items= $order_model->getOrders();
       	$this->orders =$latest_items;
		$this->order_model = $order_model;
		$this->params = $params;
        $this->addToolBar();
        $toolbar = new K2StoreToolBar();
        $toolbar->renderLinkbar();
		parent::display($tpl);
	}

	function addToolBar() {
		JToolBarHelper::title(JText::_('K2STORE_DASHBOARD'),'k2store-logo');
		JToolBarHelper::preferences('com_k2store', '500', '850');
	}

}
