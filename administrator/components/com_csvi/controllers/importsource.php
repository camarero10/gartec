<?php
/**
 * @package     CSVI
 * @subpackage  Controller
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Import source controller.
 *
 * @package     CSVI
 * @subpackage  Controller
 * @since       6.0
 */
class CsviControllerImportsource extends CsviControllerDefault
{
	/**
	 * Execute a given task, but filter on custom tasks.
	 *
	 * @param   string  $task  The task to execute
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function execute($task)
	{
		if (!in_array(strtolower($task), array('cancel', 'preview')))
		{
			$task = 'detail';
		}

		parent::execute($task);
	}

	/**
	 * Show the form.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function detail()
	{
		// Get the template ID
		$runId = $this->input->getInt('runId', false);

		if ($runId)
		{
			// Load the model
			$model = $this->getThisModel();

			// Initialise the import
			try
			{
				$model->initialiseImport($runId);

				// Push the template into the view
				$this->getThisView()->set('template', $model->getTemplate());

				return parent::detail();
			}
			catch (Exception $e)
			{
				// We don't have valid data, return to the import page
				$this->setRedirect('index.php?option=com_csvi&view=imports', $e->getMessage(), 'error');
				$this->redirect();
			}
		}
	}

	/**
	 * Prepare for preview.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function preview()
	{
		// Get the template ID
		$runId = $this->input->getInt('runId', false);

		// Check if we have a valid template ID
		if ($runId)
		{
			try
			{
				// Get the model
				/** @var CsviModelImportsources $model */
				$model = $this->getThisModel();

				// Initialise the model
				$model->initialiseImport($runId);

				// Process the file
				$model->initialiseFile();

				// Redirect to the preview page
				$this->setRedirect('index.php?option=com_csvi&view=importpreview&runId=' . $runId);
				$this->redirect();
			}
			catch (Exception $e)
			{
				$this->setRedirect('index.php?option=com_csvi&view=imports', $e->getMessage(), 'error')->redirect();
			}
		}
		else
		{
			// We don't have valid data, return to the import page
			$this->setRedirect('index.php?option=com_csvi&view=imports', JText::_('COM_CSVI_IMPORTPREVIEW_NO_CSVIHELPERTEMPLATE_FOUND'), 'error')->redirect();
		}
	}

	/**
	 * Cancel the import and return to the import page.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function cancel()
	{
		// Redirect back to the import page
		$this->setRedirect('index.php?option=com_csvi&view=imports', JText::_('COM_CSVI_IMPORT_CANCELED'), 'notice');
		$this->redirect();
	}
}
