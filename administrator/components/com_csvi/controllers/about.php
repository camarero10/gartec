<?php
/**
 * @package     CSVI
 * @subpackage  About
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * The about controller.
 *
 * @package     CSVI
 * @subpackage  About
 * @since       6.0
 */
class CsviControllerAbout extends CsviControllerDefault
{
	/**
	 * Execute a given task.
	 *
	 * @param   string  $task  The task to execute
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function execute($task)
	{
		if (!in_array(strtolower($task), array('fix', 'createfolder')))
		{
			$task = 'detail';
		}

		parent::execute($task);
	}

	/**
	 * Tries to fix missing database updates.
	 *
	 * @return  void.
	 *
	 * @since   5.7
	 */
	public function fix()
	{
		$model = $this->getThisModel();
		$model->fix();
		$this->setRedirect(JRoute::_('index.php?option=com_csvi&view=about', false));
	}

	/**
	 * Version check.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	public function createFolder()
	{
		/** @var CsviModelAbouts $model */
		$model = $this->getThisModel();
		$result = $model->fixFolder();
		echo json_encode($result);

		JFactory::getApplication()->close();
	}
}
