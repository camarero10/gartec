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
defined( '_JEXEC' ) or die( 'Restricted access' );
JHtml::_('behavior.modal');
JHTML::_('behavior.tooltip');
jimport('joomla.application.component.controller');
$app = JFactory::getApplication();

//j3 compatibility
if(!defined('DS')){
	define('DS',DIRECTORY_SEPARATOR);
}
JLoader::register('K2StoreController', JPATH_ADMINISTRATOR.'/components/com_k2store/controllers/controller.php');
JLoader::register('K2StoreView', JPATH_ADMINISTRATOR.'/components/com_k2store/views/view.php');
JLoader::register('K2StoreModel', JPATH_ADMINISTRATOR.'/components/com_k2store/models/model.php');
require_once (JPATH_SITE.'/components/com_k2store/helpers/utilities.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/base.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/toolbar.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/version.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/strapper.php');
$version = new K2StoreVersion();
$version->load_version_defines();
K2StoreStrapper::addJS();
K2StoreStrapper::addCSS();

//handle live update
require_once JPATH_ADMINISTRATOR.'/components/com_k2store/liveupdate/liveupdate.php';
if($app->input->getCmd('view','') == 'liveupdate') {
	LiveUpdate::handleRequest();
	return;
}

$controller = $app->input->getWord('view', 'cpanel');
if (JFile::exists(JPATH_COMPONENT.'/controllers/'.$controller.'.php')
		&& $controller !='countries' && $controller !='zones'
		&& $controller !='country' && $controller !='zone'
		&& $controller !='taxprofiles' && $controller !='taxprofile'
		&& $controller !='taxrates' && $controller !='taxrate'
		&& $controller !='geozones' && $controller !='geozone'
		&& $controller !='geozonerules' && $controller !='geozonerule'
		&& $controller !='storeprofiles' && $controller !='storeprofile'
		&& $controller !='lengths' && $controller !='length'
		&& $controller !='weights' && $controller !='weight'
		&& $controller !='currencies' && $controller !='currency'
		&& $controller !='orderstatuses' && $controller !='orderstatus'
)

{
	require_once (JPATH_COMPONENT.'/controllers/'.$controller.'.php');
	$classname = 'K2StoreController'.$controller;
	$controller = new $classname();

} else {
	$controller = JControllerLegacy::getInstance('K2Store');
}
$controller->execute($app->input->getWord('task'));
$controller->redirect();