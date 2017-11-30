<?php
/**
 * @package     CSVI
 * @subpackage  Template
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Template model.
 *
 * @package     CSVI
 * @subpackage  Template
 * @since       6.0
 */
class CsviModelTemplates extends FOFModel
{
	/**
	 * Holds the database driver
	 *
	 * @var    JDatabase
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * Holds the template settings
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $options = null;

	/**
	 * Construct the class.
	 *
	 * @since   6.0
	 */
	public function __construct()
	{
		parent::__construct();

		// Load the basics
		$this->db = $this->getDbo();
	}

	/**
	 * Get the filter values.
	 *
	 * @return  object  The list of filters.
	 *
	 * @since   6.0
	 */
	private function getFilterValues()
	{
		return (object) array(
				'name'			=> $this->getState('name', '', 'string'),
				'action'		=> $this->getState('action', '', 'string')
		);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   bool  $overrideLimits  Set to override the page limits.
	 *
	 * @return  object  The query to execute.
	 *
	 * @since   4.0
	 */
	public function buildQuery($overrideLimits = false)
	{
		// Get the parent query
		$query = parent::buildQuery($overrideLimits);
		$query->clear('select');
		$query->clear('from');
		$query->from($this->db->quoteName('#__csvi_templates', 'tbl'));
		$query->leftJoin(
				$this->db->quoteName('#__users', 'u')
				. ' ON ' . $this->db->quoteName('tbl.locked_by') . ' = ' . $this->db->quoteName('u.id')
			);

		$query->select($this->db->quoteName('tbl') . '.*');
		$query->select($this->db->quoteName('enabled', 'published'));
		$query->select($this->db->quoteName('u.name', 'editor'));

		$state = $this->getFilterValues();

		if ($state->name)
		{
			$query->where($this->db->quoteName('tbl.template_name') . ' LIKE ' . $this->db->quote('%' . $state->name . '%'));
		}

		if ($state->action)
		{
			$query->where($this->db->quoteName('tbl.action') . ' = ' . $this->db->quote($state->action));
		}

		return $query;
	}

	/**
	 * This method runs after an item has been gotten from the database in a read
	 * operation. You can modify it before it's returned to the MVC triad for
	 * further processing.
	 *
	 * @param   FOFTable  &$record  The table instance we fetched
	 *
	 * @return  void
	 *
	 * @since   6.0
	 */
	protected function onAfterGetItem(&$record)
	{
		$options = new JRegistry;
		$options->loadArray(json_decode($record->settings, true));
		$record->options = $options;
	}

	/**
	 * Get a list of templates.
	 *
	 * @return  array  List of template objects.
	 *
	 * @since   3.0
	 */
	public function getTemplates()
	{
		$query = $this->db->getQuery(true);
		$query->select(
				array(
					$this->db->quoteName('template_name', 'text'),
					$this->db->quoteName('csvi_template_id', 'value'),
					$this->db->quoteName('action')
				)
			)
			->from($this->db->quoteName('#__csvi_templates'))
			->order($this->db->quoteName('template_name'));

		$this->db->setQuery($query);
		$loadtemplates = $this->db->loadObjectList();

		if (!is_array($loadtemplates))
		{
			$templates = array();
			$templates[] = JHtml::_('select.option', '', JText::_('COM_CSVI_SAVE_AS_NEW_FOR_NEW_TEMPLATE'));
		}

		$import = array();
		$export = array();

		// Group the templates by process
		if (!empty($loadtemplates))
		{
			foreach ($loadtemplates as $tmpl)
			{
				if ($tmpl->action == 'import')
				{
					$import[] = $tmpl;
				}
				elseif ($tmpl->action == 'export')
				{
					$export[] = $tmpl;
				}
			}
		}

		// Merge the whole thing together
		$templates[] = JHtml::_('select.option', '', JText::_('COM_CSVI_SELECT_TEMPLATE'));
		$templates[] = JHtml::_('select.option', '', JText::_('COM_CSVI_TEMPLATE_IMPORT'), 'value', 'text', true);
		$templates = array_merge($templates, $import);
		$templates[] = JHtml::_('select.option', '', JText::_('COM_CSVI_TEMPLATE_EXPORT'), 'value', 'text', true);
		$templates = array_merge($templates, $export);

		return $templates;
	}

	/**
	 * Save the template settings.
	 *
	 * @param   array     &$data   The data to save
	 * @param   FOFTable  &$table  The table to save the data to
	 *
	 * @return  boolean  Return false to prevent saving, true to allow it
	 *
	 * @since   3.0
	 */
	protected function onBeforeSave(&$data, &$table)
	{
		// Prepare the settings
		if (isset($data['jform']))
		{
			$data['settings'] = json_encode($data['jform']);
			$data['action'] = $data['jform']['action'];
		}

		$query = $this->db->getQuery(true);

		// Store the table to the custom available fields if needed
		if (isset($data['jform']['custom_table']))
		{
			// Check if the table is already listed
			$query->clear()
				->select($this->db->quoteName('csvi_availabletable_id'))
				->from($this->db->quoteName('#__csvi_availabletables'))
				->where($this->db->quoteName('template_table') . ' = ' . $this->db->quote($data['jform']['custom_table']))
				->where($this->db->quoteName('component') . ' = ' . $this->db->quote('com_csvi'))
				->where($this->db->quoteName('action') . ' = ' . $this->db->quote($data['action']));
			$this->db->setQuery($query);
			$csvi_availabletable_id = $this->db->loadResult();

			// Add the table to the available fields table if needed
			if (!$csvi_availabletable_id)
			{
				$query->clear()
					->insert($this->db->quoteName('#__csvi_availabletables'))
					->columns(
						$this->db->quoteName('task_name') . ',' .
						$this->db->quoteName('template_table') . ',' .
						$this->db->quoteName('component') . ',' .
						$this->db->quoteName('action') . ',' .
						$this->db->quoteName('enabled')
					)
					->values(
						$this->db->quote('custom') . ',' .
						$this->db->quote($data['jform']['custom_table']) . ',' .
						$this->db->quote('com_csvi') . ',' .
						$this->db->quote($data['action']) . ',' .
						$this->db->quote('1')
					);
				$this->db->setQuery($query);
				$this->db->execute();

				// Load the helpers
				$csvihelper = new CsviHelperCsvi;
				$settings = new CsviHelperSettings($this->db);
				$log = new CsviHelperLog($settings, $this->db);

				// Index the table
				require_once JPATH_ADMINISTRATOR . '/components/com_csvi/addon/com_csvi/model/maintenance.php';
				$maintenanceModel = new Com_CsviMaintenance($this->db, $log, $csvihelper);
				$customtable = new stdClass;
				$customtable->template_table = $data['jform']['custom_table'];
				$customtable->component = 'com_csvi';
				$customtable->action = $data['action'];
				$maintenanceModel->indexTable($customtable);
			}
		}

		// Check if the chosen table is the same as the one already stored, if not, we need to remove the template fields
		$settings = json_decode($table->settings);

		if (isset($settings->custom_table) && isset($data['jform']['custom_table']))
		{
			if ($settings->custom_table != $data['jform']['custom_table'])
			{
				// Remove all associated fields
				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__csvi_templatefields'))
					->where($this->db->quoteName('csvi_template_id') . ' = ' . (int) $table->csvi_template_id);
				$this->db->setQuery($query)->execute();
			}
		}

		return parent::onBeforeSave($data, $table);
	}

	/**
	 * This method runs after a record with key value $id is deleted
	 *
	 * @param   integer  $id  The id of the record which was deleted
	 *
	 * @return  boolean  Return false to raise an error, true otherwise
	 */
	protected function onAfterDelete($id)
	{
		// Delete the template field rules
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('csvi_templatefield_id'))
			->from($this->db->quoteName('#__csvi_templatefields'))
			->where($this->db->quoteName('csvi_template_id') . ' = ' . (int) $id);
		$this->db->setQuery($query);
		$fieldIds = $this->db->loadColumn();

		if (!empty($fieldIds))
		{
			$query->clear()
				->delete($this->db->quoteName('#__csvi_templatefields_rules'))
				->where($this->db->quoteName('csvi_templatefield_id') . ' IN (' . implode(',', $fieldIds) . ')');
			$this->db->setQuery($query)->execute();
		}

		// Delete the template fields
		$query->clear()
			->delete($this->db->quoteName('#__csvi_templatefields'))
			->where($this->db->quoteName('csvi_template_id') . ' = ' . (int) $id);
		$this->db->setQuery($query)->execute();

		JFactory::getApplication()->enqueueMessage(JText::_('COM_CSVI_TEMPLATE_REMOVED'));

		return true;
	}

	/**
	 * Process JSON data request.
	 *
	 * @param   string  $addon   The addon to call for the data.
	 * @param   string  $method  The method to execute.
	 * @param   string  $args    The arguments to pass.
	 *
	 * @return  array  The requested data.
	 *
	 * @since   6.0
	 */
	public function loadJsonData($addon, $method, $args)
	{
		// Setup the addon autoloader
		JLoader::registerPrefix(ucfirst($addon), JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $addon);
		$classname = ucfirst($addon) . 'HelperAjax';

		$helper = new $classname;

		if (method_exists($helper, $method))
		{
			$result = $helper->$method($args);
		}
		else
		{
			$result = array();
		}

		return $result;
	}

	/**
	 * Copy one ore more templates to a new one.
	 *
	 * @param   array  $templateIds  The IDs of the template(s) to copy.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @throws  Exception
	 *
	 * @since   6.0
	 */
	public function createCopy($templateIds)
	{
		if (!is_array($templateIds))
		{
			$templateIds = (array) $templateIds;
		}

		$table = $this->getTable('Templates');

		foreach ($templateIds as $templateId)
		{
			$table->load($templateId);
			$table->set('csvi_template_id', 0);
			$table->set('lastrun', $this->db->getNullDate());
			$table->set('template_name', $table->get('template_name') . ' copy');

			if ($table->store())
			{
				// Copy also the template fields
				$query = $this->db->getQuery(true)
					->select($this->db->quoteName('csvi_templatefield_id'))
					->from($this->db->quoteName('#__csvi_templatefields'))
					->where($this->db->quoteName('csvi_template_id') . ' = ' . (int) $templateId);
				$this->db->setQuery($query);
				$fieldIds = $this->db->loadColumn();

				$ftable = $this->getTable('Templatefields');

				foreach ($fieldIds as $fieldId)
				{
					$ftable->load($fieldId);
					$ftable->set('csvi_templatefield_id', 0);
					$ftable->set('csvi_template_id', $table->get('csvi_template_id'));
					$ftable->store();

					// Copy the template field rules
					$query->clear()
						->select($ftable->get('csvi_templatefield_id'))
						->select($this->db->quoteName('csvi_rule_id'))
						->from($this->db->quoteName('#__csvi_templatefields_rules'))
						->where($this->db->quoteName('csvi_templatefield_id') . ' = ' . (int) $fieldId);
					$this->db->setQuery($query);
					$templatefieldruleIds = $this->db->loadAssocList();

					if (count($templatefieldruleIds) > 0)
					{
						$query->clear()
							->insert($this->db->quoteName('#__csvi_templatefields_rules'))
							->columns(
								$this->db->quoteName(
									array(
										'csvi_templatefield_id',
										'csvi_rule_id'
									)
								)
							);

						foreach ($templatefieldruleIds as $rule)
						{
							$query->values(implode(',', $rule));
						}

						$this->db->setQuery($query)->execute();
					}
				}
			}
			else
			{
				throw new Exception(JText::sprintf('COM_CSVI_CANNOT_COPY_TEMPLATE', $table->getError()));
			}
		}

		return true;
	}

	/**
	 * Test the FTP details.
	 *
	 * @return  bool  True if connection works | Fails if connection fails.
	 *
	 * @since   4.3.2
	 */
	public function testFtp()
	{
		$ftphost = $this->input->get('ftphost', '', 'string');
		$ftpport = $this->input->get('ftpport');
		$ftpusername = $this->input->get('ftpusername', '', 'string');
		$ftppass = $this->input->get('ftppass', '', 'string');
		$ftproot = $this->input->get('ftproot', '', 'string');
		$ftpfile = $this->input->get('ftpfile', '', 'string');
		$action = $this->input->get('action');

		// Set up the ftp connection
		jimport('joomla.client.ftp');
		$ftp = JFTP::getInstance($ftphost, $ftpport, array(), $ftpusername, $ftppass);

		if ($ftp->isConnected())
		{
			// See if we can change folder
			if ($ftp->chdir($ftproot))
			{
				if ($action == 'import')
				{
					// Check if the file exists
					$files = $ftp->listNames(null, false);

					if (is_array($files))
					{
						if (!in_array($ftpfile, $files))
						{
							$this->setError(JText::sprintf('COM_CSVI_FTP_FILE_NOT_FOUND', $ftpfile, $ftp->pwd()));
							$result = false;
						}
						else
						{
							$result = true;
						}
					}
					else
					{
						$this->setError(JText::sprintf('COM_CSVI_FTP_NO_FILES_FOUND', $ftp->pwd()));
						$result = false;
					}
				}
				else
				{
					$result = true;
				}
			}
			else
			{
				$this->setError(JText::sprintf('COM_CSVI_FTP_FOLDER_NOT_FOUND', $ftproot));
				$result = false;
			}
		}
		else
		{
			// Get the latest error
			$app = JFactory::getApplication();
			$queue = $app->getMessageQueue();
			$this->setError($queue[0]['message']);
			$result = false;
		}

		// Close up
		$ftp->quit();

		return $result;
	}
}
