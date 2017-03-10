<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * HTML View class for the HelloWorld Component
 */
class K2StoreViewCoupons extends K2StoreView
{

	function display($tpl = null) {
		$this->addToolBar();
		$toolbar = new K2StoreToolBar();
		$toolbar->renderLinkbar();
		
		parent::display($tpl);		
	}
	
	function addToolBar() {
		JToolBarHelper::title(JText::_('K2STORE_COUPONS'),'k2store-logo');	
	}
}