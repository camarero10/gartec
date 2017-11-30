<?php
/**
 * @package     CSVI
 * @subpackage  Helper
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

/**
 * This is a CRON script which should be called from the command-line, not the
 * web. For example something like:
 * /usr/bin/php /path/to/site/cli/update_cron.php
 */

// Set flag that this is a parent file.
define('_JEXEC', 1);

// Configure error reporting to maximum for CLI output.
// Configure error reporting to maximum for CLI output.
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);
ini_set('display_errors', 1);

if (file_exists(dirname(dirname(dirname(dirname(__DIR__)))) . '/defines.php'))
{
	require_once dirname(dirname(dirname(dirname(__DIR__)))) . '/defines.php';
}

if (!defined('_JDEFINES'))
{
	define('JPATH_BASE', dirname(dirname(dirname(dirname(__DIR__)))));
	require_once JPATH_BASE . '/includes/defines.php';
}

if (file_exists(JPATH_LIBRARIES . '/import.legacy.php'))
{
	require_once JPATH_LIBRARIES . '/import.legacy.php';
}
elseif (file_exists(JPATH_LIBRARIES . '/import.php'))
{
	require_once JPATH_LIBRARIES . '/import.php';
}

require_once JPATH_LIBRARIES . '/cms.php';

// Load the configuration
require_once JPATH_CONFIGURATION . '/configuration.php';

// Import necessary classes not handled by the autoloaders
jimport('joomla.environment.uri');
jimport('joomla.event.dispatcher');
jimport('joomla.utilities.utility');
jimport('joomla.utilities.arrayhelper');
jimport('joomla.environment.request');
jimport('joomla.application.component.helper');
jimport('joomla.application.component.helper');
jimport('joomla.filesystem.path');

// Fool Joomla into thinking we're in the administrator with com_app as active component
JFactory::getApplication('administrator');
JFactory::getApplication()->input->set('option', 'com_csvi');

// All Joomla loaded, set our exception handler
require_once JPATH_BASE . '/administrator/components/com_csvi/rantai/error/exception.php';

// Set our component define
define('JPATH_COMPONENT', JPATH_BASE . '/components/com_csvi');
define('JPATH_COMPONENT_SITE', JPATH_BASE . '/components/com_csvi');
define('JPATH_COMPONENT_ADMINISTRATOR', JPATH_ADMINISTRATOR . '/components/com_csvi');

// Define our version number
define('CSVI_VERSION', '6.4.3');

// Define the tmp folder
$config = JFactory::getConfig();

define('CSVIPATH_TMP', JPath::clean($config->get('tmp_path') . '/com_csvi', '/'));
define('CSVIPATH_DEBUG', JPath::clean($config->get('log_path'), '/'));

// Setup the autoloader
JLoader::registerPrefix('Csvi', JPATH_ADMINISTRATOR . '/components/com_csvi', true);
JLoader::registerPrefix('Rantai', JPATH_ADMINISTRATOR . '/components/com_csvi/rantai', true);

// Load the default classes
require_once JPATH_ADMINISTRATOR . '/components/com_csvi/controllers/default.php';
require_once JPATH_ADMINISTRATOR . '/components/com_csvi/models/default.php';
require_once JPATH_ADMINISTRATOR . '/components/com_csvi/tables/default.php';

// Load FOF
include_once JPATH_LIBRARIES . '/fof/include.php';

if (!defined('FOF_INCLUDED'))
{
	throw new Exception('FOF is not installed', 500);
}

/**
 * Runs a CSVI cron job
 *
 * --arguments can have any value
 * -arguments are boolean
 *
 * @package     CSVI
 * @subpackage  CLI
 *
 * @since       6.0
 */
class Csvicron extends JApplicationCli
{
	/**
	 * Settings class
	 *
	 * @var    CsviHelperSettings
	 * @since  6.0
	 */
	private $settings = null;

	/**
	 * General CSVI helper class
	 *
	 * @var    CsviHelperCsvi
	 * @since  6.0
	 */
	private $helper = null;

	/**
	 * Template class
	 *
	 * @var    CsviHelperTemplate
	 * @since  6.0
	 */
	private $template = null;

	/**
	 * Database class
	 *
	 * @var    JDatabaseDriver
	 * @since  6.0
	 */
	private $db = null;

	/**
	 * Logger helper
	 *
	 * @var    CsviHelperLog
	 * @since  6.0
	 */
	protected $log = null;

	/**
	 * Load settings before we execute.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function onBeforeExecute()
	{
		// Merge the default translation with the current translation
		$jlang = JFactory::getLanguage();
		$jlang->load('com_csvi', JPATH_COMPONENT_ADMINISTRATOR, 'en-GB', true);
		$jlang->load('com_csvi', JPATH_COMPONENT_ADMINISTRATOR, $jlang->getDefault(), true);
		$jlang->load('com_csvi', JPATH_COMPONENT_ADMINISTRATOR, null, true);
	}

	/**
	 * Entry point for the script
	 *
	 * @return  void
	 *
	 * @throws  CsviException
	 *
	 * @since   2.5
	 */
	public function doExecute()
	{
		// Run the onBefore
		$this->onBeforeExecute();

		// Output the help command
		$this->out(JText::_('COM_CSVI_CRON_HELP_COMMAND'));

		// Check if we are being asked for help
		$help = $this->input->get('help', false, 'bool');

		if ($help)
		{
			$this->out(JText::_('COM_CSVI_CRON_HELP'));
			$this->out('============================');
			$this->out();
			$this->out(JText::_('COM_CSVI_USE_CRON'));
			$this->out();
		}
		else
		{
			// Load the database handler
			$this->db = JFactory::getDbo();

			// Load the settings
			$this->settings = new CsviHelperSettings($this->db);

			// Check if we have a task
			$task = $this->input->get('task', '', 'string');

			switch ($task)
			{
				case 'maintenance':
					$addon = $this->input->get('addon');
					$operation = $this->input->get('operation');
					$this->out(JText::sprintf('COM_CSVI_START_MAINTENANCE_OPERATION', $operation));
					$this->runMaintenance($addon, $operation);
					break;
				default:
					// Get the template ID
					$template_id = $this->loadTemplateId();

					if ($template_id)
					{
						/** @var CsviModelDefault $model */
						$model = FOFModel::getAnInstance('Default', 'CsviModel');

						// Load the template
						if ($model->loadTemplate($template_id))
						{
							// Retrieve the template
							$this->template = $model->getTemplate();

							// Set needed environment variables
							$domainname = $this->settings->get('hostname', 'www.example.com');
							$_SERVER['HTTP_HOST'] = $domainname;
							$_SERVER['REQUEST_METHOD'] = 'GET';

							$this->out(JText::sprintf('COM_CSVI_PROCESSING_STARTED', date('jS F Y, g:i a')));
							$this->out(JText::sprintf('COM_CSVI_TEMPLATE', $this->template->getName()));

							// Check if we can do an automated import/export
							if ($this->template->getFrontend())
							{
								$secret = $this->template->getSecret();

								if (!empty($secret))
								{
									// Check if the secret key matches
									$key = $this->input->get('key', '', 'string');

									if ($key == $secret)
									{
										// Check if we run an import or export
										switch ($this->template->get('action'))
										{
											case 'import':
												try
												{
													$this->runImport();
												}
												catch (Exception $e)
												{
													$this->log->add($e->getMessage());

													throw new CsviException($e->getMessage(), $e->getCode());
												}
												break;
											case 'export':
												try
												{
													$this->runExport();
												}
												catch (Exception $e)
												{
													$this->log->add($e->getMessage());

													throw new CsviException($e->getMessage(), $e->getCode());
												}
												break;
											default:
												$this->out(JText::_('COM_CSVI_NO_TEMPLATE_ACTION_FOUND'));
												break;
										}
									}
									else
									{
										$this->out(JText::sprintf('COM_CSVI_SECRET_KEY_DOES_NOT_MATCH', $key));
									}
								}
								else
								{
									$this->out(JText::_('COM_CSVI_SECRET_KEY_EMPTY'));
								}
							}
							else
							{
								$this->out(JText::_('COM_CSVI_TEMPLATE_FRONTEND_DISABLED'));
							}
						}
						else
						{
							$this->out(JText::sprintf('COM_CSVI_CANNOT_LOAD_TEMPLATE', $template_id));
						}
					}
					break;
			}
		}
	}

	/**
	 * Load the given template ID.
	 *
	 * @return  mixed  Int if template ID is found | False if template ID is not found.
	 *
	 * @throws  CsviException
	 *
	 * @since   6.0
	 */
	private function loadTemplateId()
	{
		// Check if we have a template name or ID
		$template_name = $this->input->get('template_name', false, 'string');
		$template_id = $this->input->get('template_id', false, 'int');

		if ($template_id || $template_name)
		{
			if (empty($template_id))
			{
				// There is a template name, get some details to streamline processing
				$query = $this->db->getQuery(true);
				$query->select($this->db->quoteName('csvi_template_id'))
					->from($this->db->quoteName('#__csvi_templates'))
					->where($this->db->quoteName('template_name') . ' = ' . $this->db->quote($template_name));
				$this->db->setQuery($query);
				$template_id = $this->db->loadResult();

				if ($template_id)
				{
					return $template_id;
				}
				else
				{
					throw new CsviException(JText::sprintf('COM_CSVI_CANNOT_LOAD_TEMPLATE_ID', $template_name), 401);
				}
			}
			else
			{
				return $template_id;
			}
		}
		else
		{
			throw new CsviException(JText::_('COM_CSVI_NO_TEMPLATE_SPECIFIED'), 402);
		}
	}

	/**
	 * Invoke a running import or export.
	 *
	 * @return  int  The run ID
	 *
	 * @since   6.0
	 */
	private function initialiseRun()
	{
		// Prepare the log
		$this->log = new CsviHelperLog($this->settings, $this->db);
		$this->log->setActive($this->template->getLog());
		$this->log->setAddon($this->template->get('component'));
		$this->log->setAction($this->template->get('action'));
		$this->log->setActionType($this->template->get('operation'));
		$this->log->setTemplateName($this->template->getName());
		$this->log->initialise();

		// Load the CSVI helper
		$this->helper = new CsviHelperCsvi;
		$this->helper->initialise($this->log);

		// Empty the processed table
		$this->db->truncateTable('#__csvi_processed');

		// Process the file to use for import
		$source = new CsviHelperSource;
		$data = array('file' => $this->input->getString('file', ''));
		$location = ($this->template->get('source') == 'fromupload') ? 'fromserver' : $this->template->get('source');
		$processfolder = $source->validateFile($location, $data, $this->template, $this->log, $this->helper);

		// Assemble the columns and values
		$columns = array($this->db->quoteName('csvi_template_id'), $this->db->quoteName('csvi_log_id'), $this->db->quoteName('userId'));
		$values = (int) $this->template->getId() . ', ' . (int) $this->log->getLogId() . ', 0';

		// Check if the process file exists
		if ($processfolder)
		{
			$columns[] = $this->db->quoteName('processfolder');
			$values .= ', ' . $this->db->quote($processfolder);
		}

		$query = $this->db->getQuery(true)
			->insert($this->db->quoteName('#__csvi_processes'))
			->columns($columns)
			->values($values);
		$this->db->setQuery($query);

		$this->db->execute();

		return $this->db->insertid();
	}

	/**
	 * Run an import.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function runImport()
	{
		$runId = $this->initialiseRun();

		JLoader::registerPrefix('Rantai', JPATH_BASE . '/administrator/components/com_csvi/rantai/');
		require_once JPATH_BASE . '/administrator/components/com_csvi/rantai/model.php';

		$model = new RantaiImportModel($this->input);

		// Set the result
		$result = array();

		if ($runId)
		{
			// 1. Initialise the import
			$model->initialiseImport($runId);

			// 2. onBeforeImport
			$model->onBeforeImport();

			// 3. runImport
			// Load the table
			require_once JPATH_BASE . '/administrator/components/com_csvi/tables/default.php';

			// Fire the import
			$model->runImport(true);

			// Store the lines processed
			$model->storeLinesProcessed();

			$result['records'] = $model->getLinesProcessed();
			$this->out(JText::_('COM_CSVI_IMPORT_FINISHED'));
		}
		else
		{
			$this->out(JText::_('COM_CSVI_NO_VALID_RUNID_FOUND'));
		}
	}

	/**
	 * Run an export.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function runExport()
	{
		// Get the component and operation
		$component = $this->template->get('component');
		$operation = $this->template->get('operation');

		// Setup the component autoloader
		JLoader::registerPrefix(ucfirst($component), JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component);

		// Load the export routine
		$classname = ucwords($component) . 'ModelExport' . ucwords($operation);
		/** @var CsviModelExports $exportModel */
		$exportModel = new $classname;

		// Add command line settings to the template
		$this->template->set('export_filename', $this->input->getString('file', $this->template->get('export_filename')));
		$this->template->set('ordernostart', $this->input->getInt('ordernostart', $this->template->get('ordernostart')));
		$this->template->set('ordernoend', $this->input->getInt('ordernoend', $this->template->get('ordernoend')));

		$exportModel->setTemplate($this->template);

		// Create a run ID
		$csvi_process_id = $exportModel->initialiseRun();

		// Store the file to use for export
		$localfile = $this->input->getString('file', '');

		if (empty($localfile))
		{
			$localfile = $exportModel->exportFilename();
		}

		if ($localfile)
		{
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__csvi_processes'))
				->set($this->db->quoteName('processfile') . ' = ' . $this->db->quote($localfile))
				->where($this->db->quoteName('csvi_process_id') . ' = ' . (int) $csvi_process_id);
			$this->db->setQuery($query)->execute();
		}

		// Prepare for export
		$exportModel->initialiseExport($csvi_process_id);

		// Run the onBefore
		$exportModel->onBeforeExport($component);

		// Start the import
		$exportModel->runExport();
	}

	/**
	 * Run a maintenance task.
	 *
	 * @param   string  $addon      The component to run the task for
	 * @param   string  $operation  The operation to execute
	 *
	 * @return  void.
	 *
	 * @throws  CsviException
	 *
	 * @since   6.0
	 */
	private function runMaintenance($addon, $operation)
	{
		if ($addon && $operation)
		{
			// Load the model
			require_once JPATH_ADMINISTRATOR . '/components/com_csvi/models/default.php';
			require_once JPATH_ADMINISTRATOR . '/components/com_csvi/models/maintenances.php';
			$model = new CsviModelMaintenances;

			// Get the result from the operation
			$result = $model->runOperation('com_' . $addon, $operation, 0, true);

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
		else
		{
			throw new CsviException(JText::sprintf('COM_CSVI_MISSING_COMPONENT_OR_OPERATION', $addon, $operation), 407);
		}
	}
}

try
{
	JApplicationCli::getInstance('Csvicron')->execute();
}
catch (Exception $e)
{

	echo $e->getMessage() . "\r\n";

	exit($e->getCode());
}
