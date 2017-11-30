<?php
/**
 * @package     CSVI
 * @subpackage  Settings
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Settings controller.
 *
 * @package     CSVI
 * @subpackage  Settings
 * @since       6.0
 */
class CsviControllerSettings extends FOFController
{
	/**
	 * Set the execute option.
	 *
	 * @param   string  $task  The task to execute, e.g. "browse"
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function execute($task)
	{
		if (!in_array($task, array('save', 'reset')))
		{
			$task = 'edit';
		}

		parent::execute($task);
	}

	/**
	 * Reset the settings.
	 *
	 * @return  void.
	 *
	 * @since   3.1.1
	 */
	public function reset()
	{
		$model = $this->getThisModel();

		if ($model->resetSettings())
		{
			$msg = JText::_('COM_CSVI_SETTINGS_RESET_SUCCESSFULLY');
			$msgtype = '';
		}
		else
		{
			$msg = JText::_('COM_CSVI_SETTINGS_NOT_RESET_SUCCESSFULLY');
			$msgtype = 'error';
		}

		$this->setRedirect('index.php?option=com_csvi&view=settings', $msg, $msgtype);
	}
}
