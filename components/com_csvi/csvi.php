<?php
/**
 * @package     CSVI
 * @subpackage  Frontend
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

$jinput = JFactory::getApplication()->input;

// Load FOF
include_once JPATH_LIBRARIES . '/fof/include.php';

if (!defined('FOF_INCLUDED'))
{
	throw new Exception('FOF is not installed', 500);
}

// Define the tmp folder
$config = JFactory::getConfig();

if (!defined('CSVIPATH_TMP'))
{
	define('CSVIPATH_TMP', JPath::clean($config->get('tmp_path') . '/com_csvi', '/'));
}

if (!defined('CSVIPATH_DEBUG'))
{
	define('CSVIPATH_DEBUG', JPath::clean($config->get('log_path'), '/'));
}

// Setup the autoloader
JLoader::registerPrefix('Csvi', JPATH_ADMINISTRATOR . '/components/com_csvi');
JLoader::registerPrefix('Rantai', JPATH_ADMINISTRATOR . '/components/com_csvi/rantai');

// All Joomla loaded, set our exception handler
require_once JPATH_BASE . '/administrator/components/com_csvi/rantai/error/exception.php';

// Execute CSVI
try
{
	FOFDispatcher::getTmpInstance('com_csvi')->dispatch();
}
catch (Exception $e)
{
	JFactory::getApplication()->redirect('index.php', $e->getMessage(), 'error');
}
