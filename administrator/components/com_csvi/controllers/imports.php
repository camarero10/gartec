<?php
/**
 * @package     CSVI
 * @subpackage  Import
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Import controller.
 *
 * @package     CSVI
 * @subpackage  Import
 * @since       6.0
 */
class CsviControllerImport extends CsviControllerDefault
{
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
		if (!in_array(strtolower($task), array('cancel', 'start', 'selectsource', 'import', 'clearsession')))
		{
			$task = 'detail';
		}

		parent::execute($task);
	}

	/**
	 * Handle the template selection.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function selectSource()
	{
		$template_id = $this->input->getInt('csvi_template_id', false);

		// Prepare the template
		/** @var CsviModelImports $model */
		$model = $this->getThisModel();
		$model->initialise($template_id);

		// Prepare the logger
		$model->initialiseLog();

		// Prepare the import run
		$csvi_log_id = $model->initialiseRun();

		// Redirect to the template view
		$this->setRedirect('index.php?option=com_csvi&view=importsource&runId=' . $csvi_log_id);
		$this->redirect();
	}

	/**
	 * Load the import page and start the import.
	 *
	 * @return  boolean  Always returns true.
	 *
	 * @throws  CsviException
	 *
	 * @since   6.0
	 */
	public function start()
	{
		// Get the template ID
		$runId = $this->input->getInt('runId', false);

		if ($runId)
		{
			// Load the model
			/** @var CsviModelImports $model */
			$model = $this->getThisModel();

			// Prepare for import
			$model->initialiseImport($runId);

			// Make the template available
			$view = $this->getThisView();
			$view->set('template', $model->getTemplate());
		}
		else
		{
			throw new CsviException(JText::_('COM_CSVI_NO_RUNID_FOUND'));
		}

		return parent::detail();
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
		// Set the end timestamp
		/** @var CsviModelImports $model */
		$model = $this->getThisModel();
		$model->setEndTimestamp($this->input->getInt('csvi_process_id', 0));

		// Redirect back to the import page
		$this->setRedirect('index.php?option=com_csvi&view=imports', JText::_('COM_CSVI_IMPORT_CANCELED'), 'notice');
		$this->redirect();
	}
}
