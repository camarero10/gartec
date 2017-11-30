<?php
/**
 * @package     CSVI
 * @subpackage  Logdetails
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Log details controller.
 *
 * @package     CSVI
 * @subpackage  Logdetails
 * @since       6.0
 */
class CsviControllerLogdetails extends FOFController
{
	/**
	 * Check if we have a log ID
	 *
	 * @return  boolean  True to allow the method to run
	 *
	 * @since   6.0
	 */
	public function onBeforeBrowse()
	{
		$runId = $this->input->get('run_id', 0, 'int');

		if ($runId > 0)
		{
			if (parent::onBeforeBrowse())
			{
				// Load the model
				$model = $this->getThisModel();

				// Load the view
				$view = $this->getThisView();

				// Set some values
				$view->set('runId', $runId);

				$view->set('logresult', $model->getStats($runId));
				$view->set('actions', $model->getActions($runId));
				$view->set('results', $model->getResults($runId));

				return true;
			}
			else
			{
				return false;
			}
		}
		else
		{
			$this->setRedirect('index.php?option=com_csvi&view=logs');
			$this->redirect();
		}

		return false;
	}

	/**
	 * Cancel the operation and return to the log view.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	public function cancel()
	{
		$this->setRedirect('index.php?option=com_csvi&view=logs');
	}
}
