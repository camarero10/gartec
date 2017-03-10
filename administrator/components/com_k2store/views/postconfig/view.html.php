<?php

/*------------------------------------------------------------------------
 # com_k2store - K2Store
# ------------------------------------------------------------------------
# author    priya bose - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/



// no direct access
defined('_JEXEC') or die('Restricted access');
class K2StoreViewPostconfig extends K2StoreView
{
	function display($tpl=null)
	{
		JFormHelper::addFormPath(JPATH_ADMINISTRATOR.'/components/com_k2store/models/forms');
		JFormHelper::addFieldPath(JPATH_ADMINISTRATOR.'/components/com_k2store/models/fields');
		$this->form = JForm::getInstance('storeprofile', 'storeprofile');
		$this->addToolBar();
		parent::display();
	}

	protected function addToolBar() {
		// setting the title for the toolbar string as an argument
		JToolBarHelper::title(JText::_('K2STORE_POST_CONFIG'),'k2store-logo');
	}
}