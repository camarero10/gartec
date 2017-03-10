<?php
/*------------------------------------------------------------------------
 # com_k2store - K2Store
# ------------------------------------------------------------------------
# author    Sasi varna kumar - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/


// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');


class K2StoreViewOrderstatuses extends K2StoreView
{

	protected $items;
	protected $pagination;
	protected $state;

	function display($tpl = null)
	{

		// Get data from the model
		$this->items = $this->get('Items');
		$this->pagination = $this->get('Pagination');
		// inturn calls getState in parent class and populateState() in model
		$this->state = $this->get('State');
		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode('<br />', $errors));
			return false;
		}

		//add toolbar
		$this->addToolBar();
		$toolbar = new K2StoreToolBar();
		$toolbar->renderLinkbar();
		// Display the template
		parent::display($tpl);
		$this->setDocument();
	}


	function addToolBar() {

		// setting the title for the toolbar string as an argument
		JToolBarHelper::title(JText::_('K2STORE_ORDERSTATUSES'),'k2store-logo');
		$state	= $this->get('State');
		JToolBarHelper::divider();
		// check permissions for the users
		JToolBarHelper::addNew('orderstatus.add','JTOOLBAR_NEW');
		JToolBarHelper::divider();
		JToolBarHelper::editList('orderstatus.edit','JTOOLBAR_EDIT');
		JToolBarHelper::divider();
		if($state == '-2' ) {
			JToolBarHelper::deleteList('', 'orderstatuss.delete','JTOOLBAR_EMPTY_TRASH');
		} else {
			JToolBarHelper::trash('orderstatuss.trash', 'JTOOLBAR_TRASH');
		}
	}

	protected function setDocument() {
		// get the document instance
		$document = JFactory::getDocument();
		// setting the title of the document
		$document->setTitle(JText::_('K2STORE_ORDERSTATUSES'));

	}


}
