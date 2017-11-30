<?php
/**
 * @package     CSVI
 * @subpackage  Tasks
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Tasks controller.
 *
 * @package     CSVI
 * @subpackage  Tasks
 * @since       6.0
 */
class CsviControllerTasks extends FOFController
{
	/**
	 * Load the available tasks.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function loadTasks()
	{
		$helper = new CsviHelperCsvi;
		$jinput = JFactory::getApplication()->input;
		$model = $this->getThisModel();
		$action = $jinput->get('action');
		$component = $jinput->get('component');

		// Load the language files
		$helper->loadLanguage($component);

		$operations = $model->loadTasks($action, $component);
		array_unshift($operations, JText::_('COM_CSVI_MAKE_CHOICE'));

		echo json_encode($operations);

		jexit();
	}

	/**
	 * Reset the tasks.
	 *
	 * @return  void.
	 *
	 * @since   3.1.1
	 */
	public function reload()
	{
		$model = $this->getThisModel();

		if ($model->reload())
		{
			$msg = JText::_('COM_CSVI_TEMPLATETYPE_RESET_SUCCESSFULLY');
			$msgtype = '';
		}
		else
		{
			$msg = $this->getError();
			$msgtype = 'error';
		}

		$this->setRedirect('index.php?option=com_csvi&view=tasks', $msg, $msgtype);
	}
}
