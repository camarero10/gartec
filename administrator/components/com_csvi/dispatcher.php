<?php
/**
 * @package     CSVI
 * @subpackage  Dispatcher
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Dispatcher.
 *
 * @package     CSVI
 * @subpackage  Dispatcher
 * @since       6.0
 */
class CsviDispatcher extends FOFDispatcher
{
	/**
	 * Executes right before the dispatcher tries to instantiate and run the
	 * controller.
	 *
	 * @return  boolean  Return false to abort
	 */
	public function onBeforeDispatch()
	{
		if (parent::onBeforeDispatch())
		{
			// Load the default classes
			require_once JPATH_ADMINISTRATOR . '/components/com_csvi/controllers/default.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_csvi/models/default.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_csvi/tables/default.php';

			// Add the path of the form location
			JFormHelper::addFormPath(JPATH_ADMINISTRATOR . '/components/com_csvi/models/forms/');
			JFormHelper::addFieldPath(JPATH_ADMINISTRATOR . '/components/com_csvi/models/fields/');

			if (JFactory::getApplication()->isAdmin())
			{
				// Load Akeeba Strapper
				include_once JPATH_ROOT . '/media/akeeba_strapper/strapper.php';
				AkeebaStrapper::bootstrap();
			}

			return true;
		}
		else
		{
			return false;
		}
	}
}
