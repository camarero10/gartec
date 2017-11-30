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
 * Export controller.
 *
 * @package     CSVI
 * @subpackage  Export
 * @since       6.0
 */
class CsviControllerExports extends CsviControllerDefault
{
	/**
	 * Export for front-end.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function export()
	{
		// Get the template ID
		$templateId = $this->input->get('csvi_template_id', false);

		if ($templateId)
		{
			// Load the model
			$model = $this->getThisModel();

			// Initialise
			$model->initialise($templateId);

			// Get the run ID
			$runId = $model->getRunId();

			try
			{
				if ($runId)
				{
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
							// Setup the component autoloader
							JLoader::registerPrefix(ucfirst($component), JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component);

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
							if ($routine->runExport())
							{
								// Offer the file for download
								if ($this->input->get('exportto') == 'tofront')
								{
									$model->displayFile(basename($routine->getProcessfile()));
								}
								else
								{
									$model->downloadFile(basename($routine->getProcessfile()));
								}

								JFactory::getApplication()->close();
							}
							else
							{
								throw new CsviException(JText::_('COM_CSVI_EXPORT_RUN_FAILED'), 500);
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
				$model = $this->getThisModel();
				$model->setEndTimestamp($runId);

				// Redirect to the template view
				$this->setRedirect('index.php', $e->getMessage(), 'error');
				$this->redirect();
			}
		}
		else
		{
			// Redirect to the template view
			$this->setRedirect('index.php', JText::_('COM_CSVI_NO_TEMPLATE_ID_FOUND'), 'error');
			$this->redirect();
		}
	}
}
