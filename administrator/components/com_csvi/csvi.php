<?php
/**
 * @package     CSVI
 * @subpackage  Administrator
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_csvi'))
{
	throw new Exception(JText::_('JERROR_ALERTNOAUTHOR'), 404);
}

// Get the input object
$jinput = JFactory::getApplication()->input;

// Include dependencies
jimport('joomla.application.component.controller');

// Define our version number
define('CSVI_VERSION', '6.4.3');

// Setup the autoloader
JLoader::registerPrefix('Csvi', JPATH_ADMINISTRATOR . '/components/com_csvi');
JLoader::registerPrefix('Rantai', JPATH_ADMINISTRATOR . '/components/com_csvi/rantai');

// All Joomla loaded, set our exception handler
require_once JPATH_BASE . '/components/com_csvi/rantai/error/exception.php';

// Get the database object
$db = JFactory::getDbo();

// Define the tmp folder
$config = JFactory::getConfig();

if (!defined('CSVIPATH_TMP'))
{
	define('CSVIPATH_TMP', JPath::clean(JPATH_SITE . '/tmp/com_csvi', '/'));
}

if (!defined('CSVIPATH_DEBUG'))
{
	define('CSVIPATH_DEBUG', JPath::clean(JPATH_SITE . '/logs/', '/'));
}

// Set the global settings
$csvisettings = new CsviHelperSettings($db);

// Start preparing
list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();

if (!$isCli)
{
	// Load jQuery framework because csvi.js depends on it. The rest is loaded by the Joomla template.
	JHtml::_('jquery.framework');

	// Add stylesheets
	$document = JFactory::getDocument();
	$document->addStyleSheet(JURI::root() . 'administrator/components/com_csvi/assets/css/display.css');

	// Load our own JS library
	$document->addScript(JURI::root() . 'administrator/components/com_csvi/assets/js/csvi.js');

	JForm::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_csvi/models/fields');

	// Add language strings to JavaScript
	// General
	JText::script('COM_CSVI_ERROR');
	JText::script('COM_CSVI_CLOSE_DIALOG');

	// About view
	JText::script('COM_CSVI_ERROR_CREATING_FOLDER');

	// Process
	JText::script('COM_CSVI_ERROR_DURING_PROCESS');
	JText::script('COM_CSVI_CHOOSE_TEMPLATE_FIELD');

	// Maintenance
	JText::script('COM_CSVI_ERROR_PROCESSING_RECORDS');
}

try
{
	FOFDispatcher::getTmpInstance('com_csvi')->dispatch();

	$format = $jinput->getCmd('format', $jinput->getCmd('tmpl', ''));

	if (empty($format))
	{
		?><div class="span-12 center"><a href="http://www.csvimproved.com/" target="_blank">CSVI Pro</a> 6.4.3 | Copyright (C) 2006 - 2015 <a href="http://www.rolandd.com/" target="_blank">RolandD Cyber Produksi</a></div><?php
	}
}
catch (Exception $e)
{
	$oldUrl = JURI::getInstance($_SERVER['HTTP_REFERER']);
	JFactory::getApplication()->redirect('index.php?option=com_csvi&view=' . $oldUrl->getVar('view', ''), $e->getMessage(), 'error');
}
