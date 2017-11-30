<?php
/**
 * @package     CSVI
 * @subpackage  Maintenance
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Maintenance controller.
 *
 * @package     CSVI
 * @subpackage  Maintenance
 * @since       6.0
 */
class CsviControllerMaintenance extends CsviControllerDefault
{
	/**
	 * Set to override the task to execute
	 *
	 * @var    bool
	 * @since  6.0
	 */
	protected $override = true;

	/**
	 * Executes a given controller task. The onBefore<task> and onAfter<task>
	 * methods are called automatically if they exist.
	 *
	 * @param   string  $task  The task to execute, e.g. "browse"
	 *
	 * @return  null|bool  False on execution failure
	 *
	 * @since   6.0
	 */
	public function execute($task)
	{
		if (!in_array($task, array('read', 'runoperation', 'canceloperation')))
		{
			$task = 'detail';
		}

		parent::execute($task);
	}

	/**
	 * Single record read. The id set in the request is passed to the model and
	 * then the item layout is used to render the result.
	 *
	 * @return  bool
	 *
	 * @since   6.0
	 */
	public function read()
	{
		/** @var CsviModelMaintenances $model */
		$model = $this->getThisModel();

		// Load the component and operation
		$component = $this->input->get('component');
		$operation = strtolower($this->input->get('operation'));
		$subtask = strtolower($this->input->get('subtask'));

		switch ($subtask)
		{
			case 'options':
				$options = $model->getOptions($component, $operation);
				echo json_encode($options);
				JFactory::getApplication()->close();
				break;
			case 'operations':
				$options = $model->getOperations($component);
				echo json_encode($options);
				JFactory::getApplication()->close();
				break;
			default:
				// Need to store form options in the session otherwise they won't be available later
				$session = JFactory::getSession();
				$session->set('form', serialize($this->input->get('form', array(), 'array')), 'com_csvi');

				// Check if there is a file uploaded
				$files = $this->input->files->get('form', array(), 'raw');

				if (!empty($files))
				{
					$files = $model->storeUploadedFiles($files);

					$session->set('files', serialize($files), 'com_csvi');
				}

				// Load the language of the addon
				$model->loadLanguage($component);

				// Set the layout
				$this->layout = 'run';
				break;
		}

		// Tell the system we have no from
		$this->hasForm = false;

		// Display
		$this->display(in_array('read', $this->cacheableTasks));

		return true;
	}

	/**
	 * Run a maintenance operation.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function runOperation()
	{
		// CSRF prevention
		if ($this->csrfProtection)
		{
			$this->_csrfProtection();
		}

		// Set the output file
		/** @var CsviModelMaintenances $model */
		$model = $this->getThisModel();

		// Load the component and operation
		$component = $this->input->get('component', false);
		$operation = strtolower($this->input->get('operation', false));
		$key = $this->input->get('key', 0);

		$result = array('process' => false);

		if ($component && $operation)
		{
			// Load the form options
			$session = JFactory::getSession();
			$form = unserialize($session->get('form', null, 'com_csvi'));

			// Store the form values individually, so we can filter and apply default values
			if (is_array($form))
			{
				foreach ($form as $name => $value)
				{
					$this->input->set($name, $value);
				}
			}

			// Clean the session
			$session->clear('form', 'com_csvi');

			// Store the files
			$files = unserialize($session->get('files', null, 'com_csvi'));

			if (!empty($files))
			{
				foreach ($files as $name => $value)
				{
					$this->input->set($name, $value);
				}

				// Clean the session
				$session->clear('files', 'com_csvi');
			}

			try
			{
				// Get the result from the operation
				$result = $model->runOperation($component, $operation, $key);

				if (!$result['cancel'])
				{
					if (!$result['continue'])
					{
						$result['process'] = false;

						// Set the forward URL
						$result['url'] = JURI::root() . 'administrator/index.php?option=com_csvi&view=logdetails&run_id=' . $result['run_id'];
					}
					else
					{
						$result['process'] = true;
					}
				}
				else
				{
					/**
					 * Check for any cancellation settings
					 * This array takes 4 options
					 * - url: Where to send the user to
					 * - msg: The message to show to the user
					 */
					$jinput = JFactory::getApplication()->input;
					$canceloptions = $jinput->get('canceloptions', array(), 'array');

					if (!empty($canceloptions))
					{
						// Set the redirect options
						$result['url'] = $canceloptions['url'];
						$result['run_id'] = 0;
					}
				}
			}
			catch (Exception $e)
			{
				$csvihelper = new CsviHelperCsvi;
				$csvihelper->enqueueMessage($e->getMessage(), 'error');
				$result['url'] = JURI::root() . 'administrator/index.php?option=com_csvi&view=maintenances';
				$result['run_id'] = 0;
			}
		}

		echo json_encode($result);

		JFactory::getApplication()->close();
	}

	/**
	 * Cancel the maintenance operation.
	 *
	 * @return  void.
	 *
	 * @since   3.3
	 */
	public function cancelOperation()
	{
		// Load the component
		$csvi_log_id = $this->input->get('run_id');

		$model = $this->getThisModel();
		$model->cancel($csvi_log_id);

		// Redirect back to the maintenance page
		$this->setRedirect('index.php?option=com_csvi&view=maintenance', JText::_('COM_CSVI_MAINTENANCE_CANCELED'), 'notice');
	}

	/**
	 * Echoes a list of maintenance operations.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	public function operations()
	{
		$model = $this->getThisModel();
		$options = $model->getOperations();
		echo $options;

		JFactory::getApplication()->close();
	}
}
