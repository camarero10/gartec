<?php
/**
 * @package     CSVI
 * @subpackage  Export
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Export Controller.
 *
 * @package     CSVI
 * @subpackage  Export
 * @since       6.0
 */
class CsviControllerExport extends CsviControllerDefault
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
		if (!in_array(strtolower($task), array('start', 'export', 'cancel', 'downloadfile', 'getdata', 'loadsites')))
		{
			$task = 'detail';
			$this->view = 'exports';
		}

		parent::execute($task);
	}

	/**
	 * Show the detail page.
	 *
	 * @return  boolean  Always returns true.
	 *
	 * @since   6.0
	 */
	public function detail()
	{
		// Load the list of export template
		$templates = FOFModel::getTmpInstance('Templates', 'CsviModel')->action('export')->enabled(1)->filter_order('ordering')->getList();

		// Get the view
		$view = $this->getThisView();

		// Push the template list into the view
		$view->set('templates', $templates);

		return parent::detail();
	}

	/**
	 * Load the import page and start the import.
	 *
	 * @return  boolean  Always returns true.
	 *
	 * @since   6.0
	 */
	public function start()
	{
		// Load the model
		$model = $this->getThisModel();

		// Get the template ID
		$template_id = $this->input->get('csvi_template_id', false);

		if (!$template_id)
		{
			// Redirect to the template view
			$this->setRedirect('index.php?option=com_csvi&view=exports', JText::_('COM_CSVI_NO_TEMPLATE_ID_FOUND'), 'error');
			$this->redirect();
		}

		// Initialise
		$model->initialise($template_id);

		// Get the run ID
		$runId = $model->getRunId();

		// Make the template available
		$view = $this->getThisView();
		$view->set('template', $model->getTemplate());
		$view->set('runId', $runId);

		return parent::detail();
	}

	/**
	 * Export the requested data.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function export()
	{
		// Get the run ID
		$runId = $this->input->getInt('runId', false);

		// Get the model
		$model = $this->getThisModel();

		try
		{
			if ($runId)
			{
				// Load the template
				$templateId = $model->getTemplateId($runId);

				if ($templateId)
				{
					$model->loadTemplate($templateId);

					// Load the template
					$template = $model->getTemplate();

					// Get the component and operation
					$component = $template->get('component');
					$operation = $template->get('operation');

					if ($component && $operation)
					{
						// If the addon is not installed show message to install it
						if (file_exists(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component))
						{
							// Setup the component autoloader
							JLoader::registerPrefix(ucfirst($component), JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component);
						}
						else
						{
							throw new CsviException(JText::sprintf('COM_CSVI_NO_ADDON_INSTALLED', $component));
						}

						// Load the export routine
						$classname = ucwords($component) . 'ModelExport' . ucwords($operation);
						$routine = new $classname;

						// Prepare for export
						$routine->initialiseExport($runId);
						$routine->onBeforeExport($component);

						if (0)
						{
							// Set the override for the operation model if exists
							$overridefile = JPATH_COMPONENT_ADMINISTRATOR . '/addon/' . $component . '/override/export/' . $operation . '.php';

							if (file_exists($overridefile))
							{
								$this->addModelPath(JPATH_COMPONENT_ADMINISTRATOR . '/addon/' . $component . '/override/export/');
							}
							else
							{
								$this->addModelPath(JPATH_COMPONENT_ADMINISTRATOR . '/addon/' . $component . '/model/export');
							}
						}

						// Start the export
						try
						{
							$routine->runExport();

							$result['process'] = false;
							$result['records'] = $routine->getRecords();
							$result['downloadurl'] = $routine->getDownloadUrl();
							$result['url'] = JURI::root() . 'administrator/index.php?option=com_csvi&view=logdetails&run_id=' . $routine->getLogId();

							// Output the results in JSON
							echo json_encode($result);

							JFactory::getApplication()->close();
						}
						catch (Exception $e)
						{
							// Finalize the export
							$routine->setEndTimestamp($runId);

							// Enqueue the message
							$helper = new CsviHelperCsvi;
							$helper->enqueueMessage($e->getMessage(), 'error');

							// Send the user to the log details
							$result['process'] = false;
							$result['url'] = JURI::root() . 'administrator/index.php?option=com_csvi&view=logdetails&run_id=' . $routine->getLogId();

							// Output the results in JSON
							echo json_encode($result);
						}
					}
					else
					{
						throw new CsviException(JText::_('COM_CSVI_EXPORT_NO_COMPONENT_NO_OPERATION'), 514);
					}
				}
				else
				{
					throw new CsviException(JText::_('COM_CSVI_NO_TEMPLATEID_FOUND'), 509);
				}
			}
			else
			{
				throw new CsviException(JText::_('COM_CSVI_NO_VALID_RUNID_FOUND'), 506);
			}
		}
		catch (Exception $e)
		{
			// Finalize the export
			$model->setEndTimestamp($runId);

			// Enqueue the message
			$helper = new CsviHelperCsvi;
			$helper->enqueueMessage($e->getMessage(), 'error');

			// Send the user to the log details
			$result['process'] = false;
			$result['url'] = JURI::root() . 'administrator/index.php?option=com_csvi&view=logs';

			// Output the results in JSON
			echo json_encode($result);
		}
	}

	/**
	 * Cancel the export and return to the export page.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function cancel()
	{
		// Set the end timestamp
		$model = $this->getThisModel();
		$model->setEndTimestamp($this->input->getInt('runId', 0));

		$this->setRedirect('index.php?option=com_csvi&view=exports', JText::_('COM_CSVI_EXPORT_CANCELED'), 'notice');
		$this->redirect();
	}

	/**
	 * Download a generated export file.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function downloadfile()
	{
		// Load the model
		$model = $this->getThisModel();

		// Retrieve the file to download
		$downloadfile = base64_decode($this->input->getBase64('file', false));

		if ($downloadfile)
		{
			$model->downloadFile($downloadfile);
		}

		JFactory::getApplication()->close();
	}

	/**
	 * Retrieve different kinds of data in JSON format.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function getData()
	{
		$component = $this->input->getCmd('component', 'com_csvi');
		$function = $this->input->getCmd('function', '');
		$filter = $this->input->getCmd('filter', '');
		$db = JFactory::getDbo();
		$result = array();

		// Setup the auto loader
		JLoader::registerPrefix(ucfirst($component), JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component);

		// Load the addon helper
		$addon = ucfirst($component) . 'Helper' . ucfirst($component) . '_Json';

		$helper = new $addon($db);

		if (method_exists($helper, $function))
		{
			$result = $helper->$function($filter);
		}

		echo json_encode($result);
		jexit();
	}

	/**
	 * Load the available sites for XML or HTML export.
	 *
	 * @return  string  JSON encoded string of a select list.
	 *
	 * @since   4.0
	 */
	public function loadSites()
	{
		$model = $this->getThisModel();
		$sites = $model->getExportSites($this->input->get('exportsite'));

		echo json_encode($sites);

		jexit();
	}
}
