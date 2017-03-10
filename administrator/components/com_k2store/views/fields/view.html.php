<?php
// No direct access to this file
defined('_JEXEC') or die('Restricted access');

// import Joomla view library
jimport('joomla.application.component.view');

class K2StoreViewFields extends K2StoreView
{
function display($tpl = null) {

		$mainframe = JFactory::getApplication();
		$option = 'com_k2store';
		$ns='com_k2store.fields';

		$db		=JFactory::getDBO();
		$uri	=JFactory::getURI();
		$params = JComponentHelper::getParams('com_k2store');

		$filter_order		= $mainframe->getUserStateFromRequest( $ns.'filter_order',		'filter_order',		'a.field_id',	'cmd' );
		$filter_order_Dir	= $mainframe->getUserStateFromRequest( $ns.'filter_order_Dir',	'filter_order_Dir',	'ASC',				'word' );
		$filter_orderstate	= $mainframe->getUserStateFromRequest( $ns.'filter_orderstate',	'filter_orderstate',	'', 'string' );

		$search				= $mainframe->getUserStateFromRequest( $ns.'search',			'search',			'',				'string' );
		if (strpos($search, '"') !== false) {
			$search = str_replace(array('=', '<'), '', $search);
		}
		$search = JString::strtolower($search);

		// Get data from the model
		$items		=  $this->get( 'Data');
		$total		=  $this->get( 'Total');
		$pagination =  $this->get( 'Pagination' );

		// table ordering
		$lists['order_Dir'] = $filter_order_Dir;
		$lists['order'] = $filter_order;

		// search filter
		$lists['search']= $search;
		$this->assignRef('lists',		$lists);

		$this->assignRef('items',		$items);
		$this->assignRef('pagination',	$pagination);
		$this->params = $params;

		$model = $this->getModel();

		$this->addToolBar();
		$toolbar = new K2StoreToolBar();
        $toolbar->renderLinkbar();

		parent::display($tpl);
	}

	function addToolBar() {
		JToolBarHelper::title(JText::_('K2STORE_FIELDS'),'k2store-logo');
		JToolBarHelper::editList();
		JToolBarHelper::publishList();
		JToolBarHelper::unpublishList();
		JToolBarHelper::deleteList();

	}
}
