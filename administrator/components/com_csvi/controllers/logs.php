<?php
/**
 * @package     CSVI
 * @subpackage  Logs
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Log controller.
 *
 * @package     CSVI
 * @subpackage  Logs
 * @since       6.0
 */

class CsviControllerLogs extends FOFController
{
	/**
	 * Public constructor of the Controller class
	 *
	 * @param   array  $config  Optional configuration parameters
	 */
	public function __construct($config = array())
	{
		parent::__construct();

		// Redirects
		$this->registerTask('remove_all', 'remove');
	}


	/**
	 * Cancel the operation.
	 *
	 * @return  void.
	 *
	 * @since   3.5
	 */
	public function cancel()
	{
		$this->setRedirect('index.php?option=com_csvi&view=logs');
	}

	/**
	 * Download a debug log file.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */

	public function downloadDebug()
	{
		$this->getThisModel()->downloadDebug();
	}

	/**
	 * Read a logfile from disk and show it in a popup.
	 *
	 * @return  bool  True if task can be performed | False if task cannot be performed.
	 *
	 * @since   3.0
	 */
	public function logReader()
	{
		$this->layout = 'logreader';

		return parent::read();
	}

	/**
	 * Delete log files.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function remove()
	{
		$model = $this->getThisModel();
		$results = array();

		switch ($this->getTask())
		{
			case 'remove':
				$results = $model->getDelete();
				break;
			case 'remove_all':
				$results = $model->getDeleteAll();
				break;
		}

		if (!empty($results))
		{
			foreach ($results as $type => $messages)
			{
				foreach ($messages as $msg)
				{
					if ($type == 'ok')
					{
						$this->setMessage($msg);
					}
					elseif ($type == 'nok')
					{
						$this->setMessage($msg, 'error');
					}
				}
			}
		}

		$this->setRedirect('index.php?option=com_csvi&view=logs');
	}
}
