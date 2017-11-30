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
 * Export model.
 *
 * @package     CSVI
 * @subpackage  Export
 * @since       6.0
 */
class CsviModelExports extends CsviModelDefault
{
	/**
	 * The export file handler
	 *
	 * @var    object
	 * @since  6.0
	 */
	protected $exportclass = null;

	/**
	 * The file handler for writing the export file
	 *
	 * @var    resource
	 * @since  6.0
	 */
	private $handle = null;

	/**
	 * Set the export format being used
	 *
	 * @var    string
	 * @since  6.0
	 */
	protected $exportformat = null;

	/**
	 * Set the array of export formats that use nodes
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $nodeformats = array('xml', 'html');

	/**
	 * CSVI SEF processor
	 *
	 * @var    CsviHelperSef
	 * @since  6.0
	 */
	protected $sef = null;

	/**
	 * Contents to be exported
	 *
	 * @var    array
	 * @since  3.0
	 */
	private $contents = array();

	/**
	 * Export fields helper
	 *
	 * @var    CsviHelperExportFields
	 * @since  6.0
	 */
	protected $fields = null;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  The configuration array.
	 *
	 * @since   6.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config = array());

		$this->csvidb = new CsviHelperDb;

		// Load Joomla helpers
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
	}

	/**
	 * Initialise the needed classes, it all starts with the template ID
	 *
	 * @param   int  $template_id  The ID of the template to load
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   6.0
	 */
	public function initialise($template_id)
	{
		// Check the temporary folder
		$this->checkTmpFolder();

		// Load the language files
		$this->loadLanguageFiles();

		// Load the template
		$this->loadTemplate($template_id, $this->helper);

		// Generate the filename to create
		$this->exportFilename();

		// Initialise run
		$runId = $this->initialiseRun();

		// Prepare for export
		$this->initialiseExport($runId);

		return true;
	}

	/**
	 * Initialise the export.
	 *
	 * @param   int  $csvi_process_id  The ID of the import run
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   6.0
	 */
	public function initialiseExport($csvi_process_id)
	{
		parent::initialiseExport($csvi_process_id);

		// Load the log
		$this->initialiseLog();

		// Load the SEF helper
		$this->sef = new CsviHelperSef($this->settings, $this->template, $this->log);
	}

	/**
	 * Set the log basics.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   6.0
	 */
	public function initialiseLog()
	{
		$this->log->setAction('export');

		return parent::initialiseLog();
	}

	/**
	 * Load the export file handler.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function loadExportFile()
	{
		// Set the export format
		$this->exportformat = $this->template->get('export_file', 'csv');

		// Get the export site
		switch ($this->exportformat)
		{
			case 'xml':
			case 'html':
				$exportsite = $this->template->get('export_site', 'csvimproved');
				break;
			default:
				$exportsite = 'csvimproved';
				break;
		}

		// Construct the class name
		$classname = 'CsviHelperFileExport' . ucfirst($this->exportformat) . ucfirst($exportsite);

		// Instantiate the new export class
		$this->exportclass = new $classname($this->template);
	}

	/**
	 * Get the template ID.
	 *
	 * @param   int  $csvi_process_id  The ID of the export run
	 *
	 * @return  int  The template ID.
	 *
	 * @since   6.0
	 */
	public function getTemplateId($csvi_process_id)
	{
		// Load the run details
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('csvi_template_id'))
			->from($this->db->quoteName('#__csvi_processes'))
			->where($this->db->quoteName('csvi_process_id') . ' = ' . (int) $csvi_process_id);
		$this->db->setQuery($query);
		$templateId = $this->db->loadResult();

		if ($templateId)
		{
			return $templateId;
		}
		else
		{
			return 0;
		}
	}

	/**
	 * Returns a list of items
	 *
	 * @param   boolean  $overrideLimits  Should I override set limits?
	 * @param   string   $group           The group by clause
	 *
	 * @return  array  of items
	 *
	 * @since   6.0
	 */
	public function &getItemList($overrideLimits = false, $group = '')
	{
		$this->list = array();

		return $this->list;
	}

	/**
	 * Get the number of all items.
	 *
	 * This is always 0 as we don't have a traditional list.
	 *
	 * @return  integer  The number of records.
	 *
	 * @since   6.0
	 */
	public function getTotal()
	{
		$this->total = 0;

		return $this->total;
	}

	/**
	 * Prepare for export.
	 *
	 * @param   string  $addon  The addon to run the export for
	 *
	 * @return  bool  True if all is OK | False if something is not OK.
	 *
	 * @since   3.0
	 */
	public function onBeforeExport($addon)
	{
		// Setup the addon
		$this->initialiseAddon($addon);

		// Load the fields to export
		$this->retrieveConfigFields();

		if (!empty($this->fields))
		{
			// Allow big SQL selects
			$this->db->setQuery("SET SQL_BIG_SELECTS=1")->execute();

			// Get the filename for the export file
			$this->exportFilename();
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_NO_EXPORT_FIELDS');
			throw new RuntimeException(JText::_('COM_CSVI_NO_EXPORT_FIELDS'), 500);
		}

		// All is good
		return true;
	}

	/**
	 * Run the export.
	 *
	 * @return  bool  True if export started | False if export cannot be started.
	 *
	 * @since   6.0
	 */
	final public function runExport()
	{
		// Set the system limits to the user settings if needed
		$this->systemLimits();

		// Start export
		if ($this->startExport())
		{
			// Set the last run time for the template
			$templateTable = FOFTable::getInstance('Templates');
			$templateTable->load($this->template->getId());
			$templateTable->set('lastrun', JFactory::getDate()->toSql());
			$templateTable->store();

			// Export header
			$this->exportHeader();

			// Export body
			$this->exportBody();

			// Export footer
			$this->exportFooter();

			// Finalize export
			$this->endExport();

			// Finish processing
			$this->finishProcess(true);

			return true;
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_CANNOT_START_EXPORT');

			// Finish processing
			$this->finishProcess(true);

			return false;
		}
	}

	/**
	 * Start the export.
	 *
	 * @return  bool  True if export started | False if export cannot be started.
	 *
	 * @since   6.0
	 */
	protected function startExport()
	{
		// Write out some export settings
		if ($this->template->getLog())
		{
			$this->exportDetails();
		}

		// Create the temporary export file
		$error = false;

		// Check if the folder exists
		if (!JFolder::exists(dirname($this->processfile)))
		{
			if (!JFolder::create(dirname($this->processfile)))
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_CREATE_FOLDER', dirname($this->processfile)));

				$error = true;
			}
		}

		// Open the file for writing
		$this->handle = fopen($this->processfile, 'w+');

		if (!$this->handle)
		{
			$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_OPEN_FILE', $this->processfile));

			$error = true;
		}
		else
		{
			// Let's make sure the file exists and is writable first.
			if (!is_writable($this->processfile))
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_WRITE_FILE', $this->processfile));

				$error = true;
			}
		}

		// Start the export
		if ($error)
		{
			// Store the log results
			$this->finishProcess(true);

			return false;
		}
		else
		{
			// All good
			return true;
		}
	}

	/**
	 * Export header.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function exportHeader()
	{
		// Add signature for Excel
		if ($this->template->get('signature'))
		{
			$this->contents['signature'] = "\xEF\xBB\xBF";
		}

		// Add the header
		$exportfields = $this->fields->getFieldNames();
		$headerline = $this->exportclass->headerText($exportfields);

		if (!empty($headerline))
		{
			$this->contents[] = $headerline;
		}

		// Add header for XML
		if ($this->exportformat == 'xml')
		{
		}
		// Add header for HTML
		elseif ($this->exportformat == 'html')
		{
			$this->contents[] = $this->exportclass->bodyText();
		}

		// Write out the data
		$this->writeOutput();
	}

	/**
	 * Export the body contents.
	 *
	 * @return  bool  True if body is exported | False if body is not exported.
	 *
	 * @since   6.0
	 *
	 * @throws  CsviException
	 */
	protected function exportBody()
	{
		$exportfields = $this->fields->getFields();

		if (count($exportfields) == 0)
		{
			throw new CsviException(JText::_('COM_CSVI_NO_EXPORT_FIELDS_SET'), 517);
		}

		return true;
	}

	/**
	 * Export the footer.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function exportFooter()
	{
		// Get the footer from the export class
		$footer = $this->exportclass->footerText();

		// Write the footer
		if ($footer && !empty($footer))
		{
			$this->contents[] = $footer;
			$this->writeOutput();
		}
	}

	/**
	 * Close the output.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function endExport()
	{
		// Close the temporary file
		fclose($this->handle);

		// Get output destinations
		$destinations = $this->template->get('exportto');
		$keep = false;

		if (!is_array($destinations))
		{
			$destinations = array($destinations);
		}

		// Check output destinations
		foreach ($destinations as $destination)
		{
			switch ($destination)
			{
				case 'todownload':
					break;
				case 'tofile':
					// Copy the file to the correct location
					$destFile = $this->template->get('localpath', JPATH_SITE) . '/' . basename($this->processfile);

					if ($this->processfile == $destFile)
					{
						$keep = true;
					}

					if ($this->processfile == $destFile || JFile::copy($this->processfile, $destFile))
					{
						$this->log->setFilename($destFile);
						$this->log->addStats('information', JText::sprintf('COM_CSVI_EXPORTFILE_CREATED', $destFile));
					}
					else
					{
						$this->log->addStats('error', JText::sprintf('COM_CSVI_EXPORTFILE_NOT_CREATED', $destFile));
					}
					break;
				case 'toftp':
					if (JFile::exists($this->processfile))
					{
						// Start the FTP
						jimport('joomla.client.ftp');
						$ftp = JClientFtp::getInstance(
							$this->template->get('ftphost', '', 'string'),
							$this->template->get('ftpport', 21, 'int'),
							array(),
							$this->template->get('ftpusername', '', 'string'),
							$this->template->get('ftppass', '', 'string')
						);
						$ftp->chdir($this->template->get('ftproot', '/', 'string'));
						$ftp->store($this->processfile, $this->template->get('ftpfile', basename($this->processfile), 'string'));
						$ftp->quit();

						$this->log->addStats('information', JText::sprintf('COM_CSVI_FTP_EXPORTFILE_CREATED', $this->processfile));
					}
					else
					{
						$this->log->addStats('information', JText::sprintf('COM_CSVI_FTP_EXPORTFILE_NOT_CREATED', $this->processfile));
					}
					break;
				case 'toemail':
					// Load the mailer
					$app = Jfactory::getApplication();
					jimport('joomla.mail.helper');

					// Get the mailer
					$mailer = JFactory::getMailer();
					$mailer->isHTML(true);
					$mailer->From = $app->get('mailfrom');
					$mailer->FromName = $app->get('sitename');
					$mailer->AddReplyTo(array($app->get('mailfrom'), $app->get('sitename')));

					// Add the email address
					$addresses = explode(',', $this->template->get('export_email_addresses'));

					// Addresses
					foreach ($addresses as $address)
					{
						if (!empty($address))
						{
							$mailer->AddAddress($address);
						}
					}

					// Carbon copy addresses
					$addresses_cc = explode(',', $this->template->get('export_email_addresses_cc'));

					if (!empty($addresses_cc))
					{
						foreach ($addresses_cc as $address)
						{
							if (!empty($address))
							{
								$mailer->AddCC($address);
							}
						}
					}

					// Blind carbon copy addresses
					$addresses_bcc = explode(',', $this->template->get('export_email_addresses_bcc'));

					if (!empty($addresses_bcc))
					{
						foreach ($addresses_bcc as $address)
						{
							if (!empty($address))
							{
								$mailer->AddBCC($address);
							}
						}
					}

					// Set the body text
					$htmlmsg = '<html><body>' . $this->template->get('export_email_body') . '</body></html>';
					$mailer->setBody($htmlmsg);

					// Set the subject
					$mailer->setSubject($this->template->get('export_email_subject'));

					// Add the attachment
					$mailer->AddAttachment($this->processfile);

					// Send the mail
					$sendmail = $mailer->Send();

					if (is_a($sendmail, 'JException'))
					{
						$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_NO_MAIL_SEND', $sendmail->getMessage()));
					}
					else
					{
						$this->log->addStats('information', 'COM_CSVI_MAIL_SEND');
					}

					// Clear the mail details
					$mailer->ClearAddresses();

					$this->log->addStats('information', JText::sprintf('COM_CSVI_EMAIL_EXPORTFILE_CREATED', $this->processfile));
					break;
			}
		}

		// Remove the temporary file if needed
		if (!in_array('todownload', $destinations) && !$keep)
		{
			JFile::delete($this->processfile);
		}

		// Store the filename for the log
		$query = $this->db->getQuery(true);
		$query->update($this->db->quoteName('#__csvi_logs'))
			->set($this->db->quoteName('file_name') . ' = ' . $this->db->quote(basename($this->processfile)))
			->where($this->db->quoteName('csvi_log_id') . ' = ' . (int) $this->getLogId());
		$this->db->setQuery($query)->execute();
	}

	/**
	 * Write the output to download or to file.
	 *
	 * @param   bool  $emptyLine  Should the output be followed by an empty line.
	 *
	 * @return  bool  True if data is output | False if data is not output.
	 *
	 * @since   3.0
	 */
	protected function writeOutput($emptyLine = false)
	{
		// Let's take the local contents if nothing is supplied
		$contents = $this->contents;

		// Clean the local contents
		$this->contents = array();

		if (!empty($contents))
		{
			if (!is_array($contents))
			{
				$contents = (array) $contents;
			}

			// The content to write
			$writedata = '';

			// Check if there is a signature
			if (isset($contents['signature']))
			{
				$writedata = $contents['signature'];
				unset($contents['signature']);
			}

			// Prepare the data for writing
			$writedata .= $this->exportclass->prepareContent($contents);

			if ($emptyLine)
			{
				$writedata = "\r\n" . $writedata;
			}

			// Write the data to file
			if (fwrite($this->handle, $writedata . "\r\n") === false)
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_WRITE_FILE', $this->processfile));

				return false;
			}
		}

		return true;
	}

	/**
	 * Constructs a limit for a query.
	 *
	 * @return  string  The limit to apply to the query.
	 *
	 * @since   3.0
	 */
	protected function getExportLimit()
	{
		$recordstart = $this->template->get('recordstart', 0, 'int');
		$recordend = $this->template->get('recordend', 0, 'int');
		$limits = array();
		$limits['offset'] = 0;
		$limits['limit'] = 0;

		// Check if the user only wants to export some products
		if ($recordstart && $recordend)
		{
			// Check if both values are greater than 0
			if (($recordstart > 0) && ($recordend > 0))
			{
				// We have valid limiters, add the limit to the query
				// Recordend needs to have 1 deducted because MySQL starts from 0
				$limits['offset'] = $recordstart - 1;
				$limits['limit'] = $recordend - $recordstart;
			}
		}

		return $limits;
	}

	/**
	 * Process an array of data to add to the output.
	 *
	 * @param   boolean  $addXml  Set if the XML tags need to be added.
	 *
	 * @return  void.
	 *
	 * @since   5.0
	 */
	protected function addExportFields($addXml = true)
	{
		// Fire the rules
		$this->fields->runRules();

		// Create a clean row
		$row = array();
		$nodestart = false;
		$linecount = 0;

		// Add the start node
		if ($addXml && in_array($this->exportformat, $this->nodeformats))
		{
			$nodestart = $this->exportclass->NodeStart();
		}

		// Add all fields to the export
		foreach ($this->fields->getData() as $data)
		{
			// Get the field details
			$field = reset($data);

			if ($field->enabled)
			{
				$value = '';

				if (isset($field->value))
				{
					$value = $field->value;
				}

				if (strlen($value) > 0)
				{
					$linecount++;
				}

				// Format the contents
				$row[] = $this->exportclass->contentText($value, $field->column_header, $field->field_name, $field->cdata);
			}
		}

		if ($linecount > 0)
		{
			if ($addXml && $nodestart)
			{
				$this->contents[] = $nodestart;
			}

			// Add the content
			$this->contents = array_merge($this->contents, $row);

			if ($addXml)
			{
				// Add the end node
				if (in_array($this->exportformat, $this->nodeformats))
				{
					$this->contents[] = $this->exportclass->NodeEnd();
				}
			}
		}

		// All fields added for export, empty the fields
		$this->fields->reset();
	}

	/**
	 * Add data to the export content.
	 *
	 * @param   string  $content  The content to export
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	protected function addExportContent($content)
	{
		$this->contents[] = $content;
	}

	/**
	 * Setup the fields to export.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function retrieveConfigFields()
	{
		$query = $this->db->getQuery(true)
		->select(
				array(
						$this->db->quoteName('f.csvi_templatefield_id'),
						$this->db->quoteName('f.field_name'),
						$this->db->quoteName('f.column_header'),
						$this->db->quoteName('f.xml_node'),
						$this->db->quoteName('f.default_value'),
						$this->db->quoteName('f.enabled'),
						$this->db->quoteName('f.sort'),
						$this->db->quoteName('f.ordering'),
						$this->db->quoteName('f.cdata')
				)
		)
		->from($this->db->quoteName('#__csvi_templatefields', 'f'))
		->where($this->db->quoteName('f.csvi_template_id') . ' = ' . (int) $this->template->getId())
		->order($this->db->quoteName('f.ordering'));
		$this->db->setQuery($query);

		// Add the fields
		foreach ($this->db->loadAssocList() as $field)
		{
			/**
			 * Setup the XML fields, we mimic them for none XML files
			 */
			if (empty($field['xml_node']))
			{
				$field['xml_node'] = $field['field_name'];
			}

			// Load the associated rules for the field
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('csvi_rule_id'))
				->from($this->db->quoteName('#__csvi_templatefields_rules'))
				->where($this->db->quoteName('csvi_templatefield_id') . ' = ' . (int) $field['csvi_templatefield_id'])
				->order($this->db->quoteName('csvi_templatefields_rule_id'));
			$this->db->setQuery($query);
			$field['rules'] = $this->db->loadColumn();

			// Convert to the field to an JObject
			$data = new JObject($field);

			// Add the field to the field helper
			$this->fields->add($data);
		}
	}

	/**
	 * Create the export filename.
	 *
	 * @return  string  The name of the export file.
	 *
	 * @since   3.0
	 */
	public function exportFilename()
	{
		// Check if the export is limited, if so add it to the filename
		if (($this->template->get('recordstart') > 0) && ($this->template->get('recordend') > 0))
		{
			// We have valid limiters, add the limit to the filename
			$filelimit = "_" . $this->template->get('recordend') . '_' . (($this->template->get('recordend') - 1) + $this->template->get('recordstart'));
		}
		else
		{
			$filelimit = '';
		}

		// Set the filename to use for export
		$export_filename = trim($this->template->get('export_filename'));

		// Do some customizing
		// Replace year
		$export_filename = str_replace('[Y]', date('Y', time()), $export_filename);
		$export_filename = str_replace('[y]', date('y', time()), $export_filename);

		// Replace month
		$export_filename = str_replace('[M]', date('M', time()), $export_filename);
		$export_filename = str_replace('[m]', date('m', time()), $export_filename);
		$export_filename = str_replace('[F]', date('F', time()), $export_filename);
		$export_filename = str_replace('[n]', date('n', time()), $export_filename);

		// Replace day
		$export_filename = str_replace('[d]', date('d', time()), $export_filename);
		$export_filename = str_replace('[D]', date('D', time()), $export_filename);
		$export_filename = str_replace('[j]', date('j', time()), $export_filename);
		$export_filename = str_replace('[l]', date('l', time()), $export_filename);

		// Replace hour
		$export_filename = str_replace('[g]', date('g', time()), $export_filename);
		$export_filename = str_replace('[G]', date('G', time()), $export_filename);
		$export_filename = str_replace('[h]', date('h', time()), $export_filename);
		$export_filename = str_replace('[H]', date('H', time()), $export_filename);

		// Replace minute
		$export_filename = str_replace('[i]', date('i', time()), $export_filename);

		// Replace seconds
		$export_filename = str_replace('[s]', date('s', time()), $export_filename);

		if (!empty($export_filename))
		{
			$localfile = $export_filename;
		}
		else
		{
			$localfile = 'CSVI_' . $this->template->getName() . '_' . date("j-m-Y_H.i") . $filelimit . '.' . $this->exportformat;
		}

		// Clean up
		$localfile = JPath::clean(JPATH_SITE . '/tmp/com_csvi/export/' . $localfile, '/');

		// Set the process filename
		$this->processfile = $localfile;

		// Return the filename
		return $localfile;
	}

	/**
	 * Print out export details to the debug log.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	private function exportDetails()
	{
		$this->log->add(JText::_('COM_CSVI_CSVI_VERSION_TEXT') . JText::_('COM_CSVI_CSVI_VERSION'), false);
		$this->log->add(JText::sprintf('COM_CSVI_JOOMLA_VERSION', JVERSION), false);

		if (function_exists('phpversion'))
		{
			$this->log->add(JText::sprintf('COM_CSVI_PHP_VERSION', phpversion()), false);
		}

		// Push out all settings
		$this->log->add(str_repeat('=', 25), false);
		$settings = $this->template->getSettings();
		$this->processSettings($settings);
		$this->log->add(str_repeat('=', 25), false);

		// Push out all fields
		foreach ($this->fields->getFieldnames() as $fieldname)
		{
			$this->log->add('Export field: ' . $fieldname, false);
		}
	}

	/**
	 * Add all the settings to the debug log.
	 *
	 * @param   array  $data  An array of template settings to print
	 *
	 * @return  void.
	 *
	 * @since   5.3
	 */
	private function processSettings($data)
	{
		foreach ($data as $name => $value)
		{
			switch ($name)
			{
				default:
					if (is_object($value) || is_array($value))
					{
						$this->processSettings($value);
					}
					else
					{
						switch ($name)
						{
							case 'ftpusername':
							case 'ftppass':
							case 'export_email_addresses':
							case 'export_email_addresses_cc':
							case 'export_email_addresses_bcc':
								break;
							default:
								switch ($value)
								{
									case '0':
										$value = JText::_('JNO');
										break;
									case '1':
										$value = JText::_('JYES');
										break;
								}

								$this->log->add($name . ': ' . $value, false);
								break;
						}
					}
					break;
			}
		}
	}

	/**
	 * Handle the end of the import.
	 *
	 * @param   bool  $finished  Set if the import is finished or not.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	private function finishProcess($finished=false)
	{
		// Check if the import is finished or if we are going to reload
		if ($finished)
		{
			// Remove the running process
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__csvi_processes'))
				->where($this->db->quoteName('csvi_process_id') . ' = ' . (int) $this->runId);
			$this->db->setQuery($query)->execute();

			// Set the log end timestamp
			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__csvi_logs'))
				->set($this->db->quoteName('end') . ' = ' . $this->db->quote(JFactory::getDate()->toSql()))
				->set($this->db->quoteName('records') . ' = ' . (int) $this->log->getLinenumber())
				->where($this->db->quoteName('csvi_log_id') . ' = ' . (int) $this->log->getLogId());
			$this->db->setQuery($query)->execute();

			// Trigger any plugins to run after import completes
			$options = array();
			$options[] = $this->template->getSettings();
			$dispatcher = new RantaiPluginDispatcher;
			$dispatcher->importPlugins('csvi', $this->db);
			$dispatcher->trigger('onExportComplete', $options);
		}
	}

	/**
	 * Get the number of records exported.
	 *
	 * @return  int  The number of records exported.
	 *
	 * @since   6.0
	 */
	public function getRecords()
	{
		return $this->log->getLinenumber();
	}

	/**
	 * Get the download URL if file needs to be downloaded.
	 *
	 * @return  string  The URL of the download file.
	 *
	 * @since   6.0
	 */
	public function getDownloadUrl()
	{
		// Get output destinations
		$destinations = $this->template->get('exportto');

		if (!is_array($destinations))
		{
			$destinations = array($destinations);
		}

		// Remove the temporary file if needed
		if (in_array('todownload', $destinations))
		{
			return JURI::root() . 'administrator/index.php?option=com_csvi&view=exports&task=downloadfile&tmpl=component&file=' . base64_encode(basename($this->processfile));
		}
		else
		{
			return '';
		}
	}

	/**
	 * Download a generated file.
	 *
	 * @param   string  $downloadFile  The name of the file to download
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function downloadFile($downloadFile)
	{
		// Load the file class
		jimport('joomla.filesystem.file');

		// Prefix the path to the download file
		$localfile = JPATH_SITE . '/tmp/com_csvi/export/' . $downloadFile;

		// Check if the file exists
		if (JFile::exists($localfile))
		{
			if (preg_match('/Opera(\/| )([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT']))
			{
				$UserBrowser = "Opera";
			}
			elseif (preg_match('/MSIE ([0-9].[0-9]{1,2})/', $_SERVER['HTTP_USER_AGENT']))
			{
				$UserBrowser = "IE";
			}
			else
			{
				$UserBrowser = '';
			}

			$mime_type = ($UserBrowser == 'IE' || $UserBrowser == 'Opera') ? 'application/octetstream' : 'application/octet-stream';

			// Clean the buffer
			ob_end_clean();

			header('Content-Type: ' . $mime_type);
			header('Content-Encoding: UTF-8');
			header('Expires: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Content-Length: ' . filesize($localfile));

			if ($UserBrowser == 'IE')
			{
				header('Content-Disposition: inline; filename="' . $downloadFile . '"');
				header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
				header('Pragma: public');
			}
			else
			{
				header('Content-Disposition: attachment; filename="' . $downloadFile . '"');
			}

			// Output the file
			readfile($localfile);

			// Delete the file
			@unlink($localfile);
		}
	}

	/**
	 * Display a generated file.
	 *
	 * @param   string  $downloadFile  The name of the file to download
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function displayFile($downloadFile)
	{
		// Load the file class
		jimport('joomla.filesystem.file');

		// Prefix the path to the download file
		$localfile = JPATH_SITE . '/tmp/com_csvi/export/' . $downloadFile;

		// Check if the file exists
		if (JFile::exists($localfile))
		{
			// Clean the buffer
			ob_end_clean();

			// Output the file
			readfile($localfile);

			// Delete the file
			@unlink($localfile);
		}
	}

	/**
	 * Set the cancel status.
	 *
	 * @param   int  $csvi_process_id  The ID of the import process
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function setEndTimestamp($csvi_process_id)
	{
		if ($csvi_process_id > 0)
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('csvi_log_id'))
				->from($this->db->quoteName('#__csvi_processes'))
				->where($this->db->quoteName('csvi_process_id') . ' = ' . (int) $csvi_process_id);
			$this->db->setQuery($query);
			$csvi_log_id = $this->db->loadResult();

			$query = $this->db->getQuery(true)
				->update($this->db->quoteName('#__csvi_logs'))
				->set($this->db->quoteName('end') . ' = ' . $this->db->quote(JFactory::getDate()->toSql()))
				->set($this->db->quoteName('run_cancelled') . ' = 1')
				->where($this->db->quoteName('csvi_log_id') . ' = ' . (int) $csvi_log_id);
			$this->db->setQuery($query);
			$this->db->execute();

			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName('#__csvi_processes'))
				->where($this->db->quoteName('csvi_process_id') . ' = ' . (int) $csvi_process_id);
			$this->db->setQuery($query);
			$this->db->execute();
		}
	}

	/**
	 * Get a list of XML or HTML sites.
	 *
	 * @param   string  $type  The type of files to find (XML or HTML).
	 *
	 * @return  array  List of available sites.
	 *
	 * @since   4.0
	 */
	public function getExportSites($type)
	{
		$path = JPATH_ADMINISTRATOR . '/components/com_csvi/helper/file/export/' . $type;
		$options = array();

		if (JFolder::exists($path))
		{
			$files = JFolder::files($path, '.php', false, false, array('.svn', 'CVS', '.DS_Store', '__MACOSX', 'orderadvanced.php'));

			if (!empty($files))
			{
				foreach ($files as $file)
				{
					$file = basename($file, '.php');
					$options[$file] = JText::_('COM_CSVI_' . $file);
				}

				ksort($options);
			}
		}

		return $options;
	}
}
