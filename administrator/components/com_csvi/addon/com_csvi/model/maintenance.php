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
 * Performs CSVI specific maintenance operations.
 *
 * @package     CSVI
 * @subpackage  Maintenace
 * @since       6.0
 */
class Com_CsviMaintenance
{
	/**
	 * Database connector
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
	private $log = null;

	/**
	 * CSVI Helper.
	 *
	 * @var    CsviHelperCsvi
	 * @since  6.0
	 */
	private $csvihelper = null;

	/**
	 * Key tracker
	 *
	 * @var    int
	 * @since  6.0
	 */
	private $key = 0;

	/**
	 * Hold the message to show on a JSON run
	 *
	 * @var    string
	 * @since  6.0
	 */
	private $message = '';

	/**
	 * Set if we are running in CLI mode
	 *
	 * @var    bool
	 * @since  6.0
	 */
	private $isCli = false;

	/**
	 * Constructor.
	 *
	 * @param   JDatabaseDriver  $db          Joomla Database connector
	 * @param   CsviHelperLog    $log         An instance of CsviHelperLog
	 * @param   CsviHelperCsvi   $csvihelper  An instance of CsviHelperCsvi
	 * @param   bool             $isCli       Set if we are running CLI mode
	 *
	 * @since   6.0
	 */
	public function __construct(JDatabaseDriver $db, CsviHelperLog $log, CsviHelperCsvi $csvihelper, $isCli = false)
	{
		// Load the database class
		$this->db = $db;
		$this->log = $log;
		$this->csvihelper = $csvihelper;
		$this->isCli = $isCli;
	}

	/**
	 * Load a number of maintenance tasks.
	 *
	 * @return  array  The list of available tasks.
	 *
	 * @since   6.0
	 */
	public function getOperations()
	{
		return array('options' => array(
						'' => JText::_('COM_CSVI_MAKE_CHOICE'),
						'loadpatch' => JText::_('COM_CSVI_PATCH_FILE_LABEL'),
						'updateavailablefields' => JText::_('COM_CSVI_UPDATEAVAILABLEFIELDS_LABEL'),
						'cleantemp' => JText::_('COM_CSVI_CLEANTEMP_LABEL'),
						'icecatindex' => JText::_('COM_CSVI_ICECATINDEX_LABEL'),
						'backuptemplates' => JText::_('COM_CSVI_BACKUPTEMPLATES_LABEL'),
						'restoretemplates' => JText::_('COM_CSVI_RESTORETEMPLATES_LABEL'),
						'exampletemplates' => JText::_('COM_CSVI_EXAMPLETEMPLATES_LABEL'),
						'optimizetables' => JText::_('COM_CSVI_OPTIMIZETABLES_LABEL'),
						'deletetables' => JText::_('COM_CSVI_DELETETABLES_LABEL')
				)
		);
	}

	/**
	 * Load the options for a selected operation.
	 *
	 * @param   string  $operation  The operation to get the options for
	 *
	 * @return  string	the options for a selected operation.
	 *
	 * @since   6.0
	 */
	public function getOptions($operation)
	{
		switch ($operation)
		{
			case 'loadpatch':
				return '
				<div class="control-group">
					<label for="template_name" class="control-label ">
					' . JText::_('COM_CSVI_CHOOSE_PATCH_FILE_LABEL') . '
					</label>
					<div class="controls">
					<input type="file" name="form[patch_file]" id="file" class="span5" />
					<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_CHOOSE_PATCH_FILE_DESC') . '</span>
					</div>
				</div>';
				break;
			case 'updateavailablefields':
				return '<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_UPDATE_AVAILABLEFIELDS_DESC') . '</span>';
				break;
			case 'cleantemp':
				return '<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_CLEANTEMP_DESC') . '</span>';
				break;
			case 'backuptemplates':
				$html  = '<div class="control-group">
						<label for="template_name" class="control-label ">
						' . JText::_('COM_CSVI_CHOOSE_BACKUP_LOCATION_LABEL') . '
						</label>
						<div class="controls">
							<input type="text" name="backup_location" id="backup_location" value="' . JPATH_SITE . '/tmp/com_csvi" class="input-xxlarge" />
							<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_CHOOSE_BACKUP_LOCATION_DESC') . '</span>
						</div>
					</div>';

				// Load the list of templates
				$templates = FOFModel::getTmpInstance('Templates', 'CsviModel')->getTemplates();
				$html .= '<div class="control-group">
						<label for="template_name" class="control-label ">
						' . JText::_('COM_CSVI_BACKUPTEMPLATES_LABEL') . '
						</label>
						<div class="controls">
							<input
								type="checkbox"
								onclick="Joomla.checkAll(this)"
								checked="checked"
								title="' . JText::_('COM_CSVI_CHECK_ALL_FIELDS') . '"
								value=""
								name="checkall-toggle"
							/> '
							. JText::_('COM_CSVI_CHECK_ALL_FIELDS') . '
							<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_BACKUPTEMPLATES_DESC') . '</span>
						</div>
					</div>
					<div class="span12">';

				foreach ($templates as $key => $template)
				{
					if ($key > 0)
					{
						if (empty($template->value))
						{
							if ($key > 1)
							{
								$html .= '</ul></div>';
							}

							$html .= '<div class="span5"><label>' . $template->text . '</label>
							<ul class="unstyled">';
						}
						else
						{
							$html .= '
								<li>
									<input type="checkbox" checked="checked" name="form[templates][]" id="cb' . $key . '" value="' . $template->value . '" /> '
									. $template->text .
								'</li>';
						}
					}
				}

				$html .= '</div>';

				return $html;
				break;
			case 'restoretemplates':
				return '
				<div class="control-group">
					<label for="template_name" class="control-label ">
					' . JText::_('COM_CSVI_CHOOSE_RESTORE_FILE_LABEL') . '
					</label>
					<div class="controls">
					<input type="file" name="form[restore_file]" id="file" class="span5" />
					<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_CHOOSE_RESTORE_FILE_DESC') . '</span>
					</div>
				</div>';
				break;
			case 'icecatindex':
				$html = '
				<div class="span12">
					<h3>' . JText::_('COM_CSVI_MAINTENANCE_ICECAT') . '</h3>
					<div class="control-group">
						<label class="control-label" for="icecatlocation">
						' . JText::_('COM_CSVI_ICECAT_LOCATION_LABEL') . '
						</label>
						<div class="controls">
							<input type="text" id="icecatlocation" name="form[icecatlocation]" value="' . CSVIPATH_TMP . '" class="input-xxlarge" />
							<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_ICECAT_LOCATION_DESC') . '</span>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="form_icecat_gzip">
						' . JText::_('COM_CSVI_ICECAT_GZIP_LABEL') . '
						</label>
						<div class="controls">
							' . JHtml::_('select.booleanlist', 'form[icecat_gzip]', '', 1) . '
							<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_ICECAT_GZIP_DESC') . '</span>
						</div>
					</div>
				<div>
				<div class="span12">
					<h3>' . JText::_('COM_CSVI_MAINTENANCE_ICECAT_FILE') . '</h3>
					<div class="control-group">
						<label class="control-label" for="icecatfile">
						' . JText::_('COM_CSVI_ICECAT_FILE_LABEL') . '
						</label>
						<div class="controls">
							<input type="checkbox" id="icecatfile" name="form[icecat][]" value="icecat_index" checked="checked" />
							<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_ICECAT_FILE_DESC') . '</span>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="form_loadtype">
						' . JText::_('COM_CSVI_ICECAT_FILE_LOAD_LABEL') . '
						</label>
						<div class="controls">
							' . JHtml::_('select.booleanlist', 'form[loadtype]', '', 0, JText::_('COM_CSVI_ICECAT_FILE_SINGLE'), JText::_('COM_CSVI_ICECAT_FILE_FULL')) . '
							<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_ICECAT_FILE_LOAD_DESC') . '</span>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="icecat_records">
						' . JText::_('COM_CSVI_ICECAT_FILE_LOAD_RECORDS_LABEL') . '
						</label>
						<div class="controls">
							<input type="text" id="icecat_records" name="form[icecat_records]" value="1000" />
							<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_ICECAT_FILE_LOAD_RECORDS_DESC') . '</span>
						</div>
					</div>
					<div class="control-group">
						<label class="control-label" for="icecat_wait">
						' . JText::_('COM_CSVI_ICECAT_FILE_LOAD_WAIT_LABEL') . '
						</label>
						<div class="controls">
							<input type="text" id="icecat_wait" name="form[icecat_wait]" value="5" />
							<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_ICECAT_FILE_LOAD_WAIT_DESC') . '</span>
						</div>
					</div>
				<div>
				<div class="span12">
					<h3>' . JText::_('COM_CSVI_MAINTENANCE_ICECAT_SUPPLIER') . '</h3>
					<div class="control-group">
						<label class="control-label" for="icecat_supplier">
						' . JText::_('COM_CSVI_ICECAT_SUPPLIER_LABEL') . '
						</label>
						<div class="controls">
							<input type="checkbox" id="icecat_supplier" name="form[icecat][]" value="icecat_supplier" checked="checked" />
							<span class="help-block" style="display: none;">' . JText::_('COM_CSVI_ICECAT_SUPPLIER_DESC') . '</span>
						</div>
					</div>
				<div>';

				return $html;
				break;
			case 'deletetables':
				$layout = new JLayoutFile('csvi.modal');

				return $layout->render(
					array(
						'modal-header' => JText::_('COM_CSVI_' . $operation . '_LABEL'),
						'modal-body' => JText::_('COM_CSVI_CONFIRM_TABLES_DELETE'),
						'cancel-button' => true
					)
				);
				break;
			case 'optimizetables':
				return JText::_('COM_CSVI_OPTIMIZETABLES_DESC');
				break;
			case 'exampletemplates':
				return JText::_('COM_CSVI_EXAMPLETEMPLATES_DESC');
				break;
			default:
				return '';
				break;
		}
	}

	/**
	 * Optimize all database tables.
	 *
	 * @param   FOFInput  $input  The input model
	 * @param   mixed     $key    A reference used by the method.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   6.0
	 */
	public function optimizeTables(FOFInput $input, $key)
	{
		// Get the list of tables to optimize
		$tables = $this->db->getTableList();

		if ($this->isCli)
		{
			foreach ($tables as $table)
			{
				// Increment log linecounter
				$this->log->incrementLinenumber();

				$this->optimizeTable($table);
			}
		}
		else
		{
			if (isset($tables[$key]))
			{
				// Increment log linecounter
				$this->log->incrementLinenumber();

				if ($this->optimizeTable($tables[$key]))
				{
					$this->message = JText::sprintf('COM_CSVI_TABLE_HAS_BEEN_OPTIMIZED', $tables[$key]);
				}
				else
				{
					$this->message = JText::sprintf('COM_CSVI_TABLE_HAS_NOT_BEEN_OPTIMIZED', $tables[$key]);
				}

				// Set the key for post processing
				$key++;
				$this->key = $key;
			}
			else
			{
				$this->key = false;
			}
		}

		return true;
	}

	/**
	 * Optimize a table.
	 *
	 * @param   string  $table  The name of the table to optimize
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   6.0
	 */
	private function optimizeTable($table)
	{
		// Build the query
		$q = 'OPTIMIZE TABLE ' . $this->db->quoteName($table);
		$this->db->setQuery($q);

		// Execute query
		if ($this->db->execute())
		{
			$this->log->addStats('information', JText::sprintf('COM_CSVI_TABLE_HAS_BEEN_OPTIMIZED', $table), $table);

			return true;
		}
		else
		{
			$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_TABLE_HAS_NOT_BEEN_OPTIMIZED', $table), $table);

			return false;
		}
	}

	/**
	 * Post processing optimize tables.
	 *
	 * @return  array  Settings for continuing.
	 *
	 * @since   6.0
	 */
	public function onAfteroptimizeTables()
	{
		if ($this->key)
		{
			// Return data
			$results = array();
			$results['continue'] = true;
			$results['key'] = $this->key;
		}
		else
		{
			$results['continue'] = false;
		}

		$results['info'] = $this->message;

		return $results;
	}

	/**
	 * Update available fields.
	 *
	 * @param   FOFInput  $input  The input model
	 * @param   mixed     $key    A reference used by the method.
	 *
	 * @return  bool  True on success, false on failure.
	 *
	 * @since   3.3
	 */
	public function updateAvailableFields(FOFInput $input, $key)
	{
		$result = false;

		// Check if we need to prepare the available fields
		if ($key == 0)
		{
			$this->prepareAvailableFields();
		}

		// Check if we are running a cron job
		list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();

		if ($isCli)
		{
			$continue = true;

			while ($continue)
			{
				$result = $this->indexAvailableFields();
				$continue = $input->get('continue');
			}
		}
		else
		{
			$result = $this->indexAvailableFields();
		}

		return $result;
	}

	/**
	 * Prepare for available fields importing.
	 *
	 * 1. Set all tables to be indexed
	 * 2. Empty the available fields table
	 * 3. Import the extra availablefields sql file
	 * 4. Find what tables need to be imported and store them in the session.
	 *
	 * @return  void.
	 *
	 * @since   3.5
	 */
	private function prepareAvailableFields()
	{
		// Set all tables to be indexed
		$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__csvi_availabletables'))
			->set($this->db->quoteName('indexed') . ' = 0');
		$this->db->setQuery($query)->execute();

		// Empty the available fields first
		$q = 'TRUNCATE TABLE ' . $this->db->quoteName('#__csvi_availablefields');
		$this->db->setQuery($q);

		if ($this->db->execute())
		{
			$this->log->addStats('empty', 'COM_CSVI_AVAILABLE_FIELDS_TABLE_EMPTIED', 'availablefields');
		}
		else
		{
			$this->log->addStats('error', 'COM_CSVI_AVAILABLE_FIELDS_TABLE_COULD_NOT_BE_EMPTIED', 'availablefields');
		}

		// Add the custom fields for each specific custom table in use
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('template_table'))
			->from($this->db->quoteName('#__csvi_availabletables'))
			->where($this->db->quoteName('task_name') . ' = ' . $this->db->quote('custom'))
			->where($this->db->quoteName('component') . ' = ' . $this->db->quote('com_csvi'))
			->where($this->db->quoteName('action') . ' = ' . $this->db->quote('import'));
		$this->db->setQuery($query);

		$importTables = $this->db->loadColumn();

		$query->clear('where')
			->where($this->db->quoteName('task_name') . ' = ' . $this->db->quote('custom'))
			->where($this->db->quoteName('component') . ' = ' . $this->db->quote('com_csvi'))
			->where($this->db->quoteName('action') . ' = ' . $this->db->quote('export'));
		$this->db->setQuery($query);

		$exportTables = $this->db->loadColumn();

		$query = 'INSERT IGNORE INTO ' . $this->db->quoteName('#__csvi_availablefields')
			. '(' . $this->db->quoteName('csvi_name') . ', '
			. $this->db->quoteName('component_name') . ', '
			. $this->db->quoteName('component_table') . ', '
			. $this->db->quoteName('component') . ', '
			. $this->db->quoteName('action') . ')';

		$customFields = array();

		foreach ($importTables as $importTable)
		{
			$customFields[] = '(' . $this->db->quote('skip') . ', '
				. $this->db->quote('skip') . ', '
				. $this->db->quote($importTable) . ', '
				. $this->db->quote('com_csvi') . ', '
				. $this->db->quote('import') . ')';

			$customFields[] = '(' . $this->db->quote('combine') . ', '
				. $this->db->quote('combine') . ', '
				. $this->db->quote($importTable) . ', '
				. $this->db->quote('com_csvi') . ', '
				. $this->db->quote('import') . ')';
		}

		foreach ($exportTables as $exportTable)
		{
			$customFields[] = '(' . $this->db->quote('custom') . ', '
				. $this->db->quote('custom') . ', '
				. $this->db->quote($exportTable) . ', '
				. $this->db->quote('com_csvi') . ', '
				. $this->db->quote('export') . ')';
		}

		if (!empty($customFields))
		{
			$query .= ' VALUES ' . implode(', ', $customFields);

			$this->db->setQuery($query)->execute();
		}


		// Do component specific updates
		$override = new stdClass;
		$override->value = 'custom';
		$components = $this->csvihelper->getComponents();
		$components[] = $override;
		jimport('joomla.filesystem.file');

		foreach ($components as $component)
		{
			// Load any component specific file
			if ($component->value
				&& $component->value != 'com_csvi'
				&& $component->value != 'custom'
				&& file_exists(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component->value . '/model/maintenance.php'))
			{
				require_once JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component->value . '/model/maintenance.php';
				$extensionClassname = ucfirst($component->value) . 'Maintenance';
				$extensionModel = new $extensionClassname($this->db, $this->log, $this->csvihelper);

				if (method_exists($extensionModel, 'updateAvailableFields'))
				{
					$extensionModel->updateAvailableFields();
				}
			}

			// Process all extra available fields
			$filename = JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component->value . '/install/availablefields.sql';

			if (JFile::exists($filename))
			{
				// Check if the component is installed
				if (substr($component->value, 0, 4) == 'com_')
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('extension_id'))
						->from($this->db->quoteName('#__extensions'))
						->where($this->db->quoteName('element') . ' = ' . $this->db->quote($component->value));
					$this->db->setQuery($query);
					$ext_id = $this->db->loadResult();
				}
				else
				{
					$ext_id = true;
				}

				if ($ext_id)
				{
					// Increment line number
					$this->log->incrementLinenumber();

					$queries = JInstallerHelper::splitSql(JFile::read($filename));

					foreach ($queries as $step => $q)
					{
						// Clean the string of any trailing whitespace
						$q = trim($q);

						if (!empty($q))
						{
							$this->db->setQuery($q);

							if ($this->db->execute())
							{
								$this->log->addStats(
									'added',
									JText::sprintf('COM_CSVI_CUSTOM_AVAILABLE_FIELDS_HAVE_BEEN_ADDED', JText::_('COM_CSVI_' . $component->value), $step + 1),
									$component->value . '_CUSTOM'
								);
							}
							else
							{
								$this->log->add(
									'incorrect',
									JText::sprintf('COM_CSVI_CUSTOM_AVAILABLE_FIELDS_HAVE_NOT_BEEN_ADDED', JText::_('COM_CSVI_' . $component->value), $step + 1, $q),
									$component->value . '_CUSTOM'
								);
							}
						}
					}

					// Execute any specific available fields that are not in an SQL file
					if (file_exists(JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component->value . '/model/maintenance.php'))
					{
						require_once JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component->value . '/model/maintenance.php';
						$classname = $component->value . 'Maintenance';
						$addon = new $classname($this->db, $this->log, $this->csvihelper);

						if (method_exists($addon, 'customAvailableFields'))
						{
							$addon->customAvailableFields();
						}
					}
				}
			}
		}

		// Increment line number
		$this->log->decrementLinenumber();
	}

	/**
	 * Import the available fields in steps.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   3.5
	 */
	private function indexAvailableFields()
	{
		// Load the session data
		$lines = $this->log->getLinenumber();
		$lines++;

		// Set the line number
		$this->log->setLinenumber($lines);

		$query = $this->db->getQuery(true);
		$query->select(
				$this->db->quoteName('csvi_availabletable_id') . ',' .
				$this->db->quoteName('template_table') . ',' .
				$this->db->quoteName('component') . ',' .
				$this->db->quoteName('action')
			)
			->from($this->db->quoteName('#__csvi_availabletables'))
			->where($this->db->quoteName('indexed') . ' = 0')
			->where($this->db->quoteName('enabled') . ' = 1')
			->group($this->db->quoteName('template_table'));
		$this->db->setQuery($query, 0, 1);
		$table = $this->db->loadObject();

		if (is_object($table))
		{
			// Set the key that we started
			$this->key = 1;

			// Check if the table exists
			$tables = $this->db->getTableList();

			if (in_array($this->db->getPrefix() . $table->template_table, $tables))
			{
				// Increment line number
				$this->log->incrementLinenumber();

				$this->indexTable($table);
			}
			else
			{
				$this->message = $table->template_table . ' not an available table';
			}

			// Set the table to indexed
			$query = $this->db->getQuery(true);
			$query->update($this->db->quoteName('#__csvi_availabletables'))
				->set($this->db->quoteName('indexed') . ' = 1')
				->where($this->db->quoteName('csvi_availabletable_id') . ' = ' . (int) $table->csvi_availabletable_id);
			$this->db->setQuery($query);
			$this->db->execute();
		}
		else
		{
			$this->key = false;
		}

		return true;
	}

	/**
	 * Creates an array of custom database fields the user can use for import/export.
	 *
	 * @param   string  $table    The table name to get the fields for
	 * @param   bool    $addname  Add the table name to the list of fields
	 *
	 * @return  array  List of custom database fields.
	 *
	 * @since   3.0
	 */
	private function dbFields($table, $addname=false)
	{
		$customfields = array();
		$q = 'SHOW COLUMNS FROM ' . $this->db->quoteName('#__' . $table);
		$this->db->setQuery($q);
		$fields = $this->db->loadObjectList();

		if (count($fields) > 0)
		{
			foreach ($fields as $field)
			{
				if ($addname)
				{
					$customfields[$field->Field] = $table;
				}
				else
				{
					$customfields[$field->Field] = null;
				}
			}
		}

		return $customfields;
	}

	/**
	 * Index a single table.
	 *
	 * @param   object  $table  The table to index
	 *
	 * @return  void  .
	 *
	 * @since   6.0
	 */
	public function indexTable($table)
	{
		// Get the primary key for the table
		$primarykey = $this->csvihelper->getPrimaryKey($table->template_table);

		// Load the language
		$this->csvihelper->loadLanguage($table->component, false);

		$fields = $this->dbFields($table->template_table, true);

		if (is_array($fields))
		{
			// Process all fields
			foreach ($fields as $name => $value)
			{
				// Check if the field is a primary field
				if ($primarykey == $name)
				{
					$primary = 1;
				}
				else
				{
					$primary = 0;
				}

				if ($name)
				{
					$q = 'INSERT IGNORE INTO ' . $this->db->quoteName('#__csvi_availablefields') . ' VALUES ('
						. '0,'
						. $this->db->quote($name) . ','
						. $this->db->quote($name) . ','
						. $this->db->quote($value) . ','
						. $this->db->quote($table->component) . ','
						. $this->db->quote($table->action) . ','
						. $this->db->quote($primary) . ')';
					$this->db->setQuery($q);

					if ($this->db->execute())
					{
						$this->log->addStats('added', JText::sprintf('COM_CSVI_AVAILABLE_FIELDS_HAVE_BEEN_ADDED', $table->template_table), 'maintenance_index_availablefields');
						$this->message = JText::sprintf('COM_CSVI_AVAILABLE_FIELDS_HAVE_BEEN_ADDED', $table->template_table);
					}
					else
					{
						$this->log->addStats('error', 'COM_CSVI_AVAILABLE_FIELDS_HAVE_NOT_BEEN_ADDED', 'maintenance_index_availablefields');
						$this->message = $this->db->getErrorMsg();
					}
				}
			}
		}
	}

	/**
	 * This is called after the available fields have been updated for post-processing.
	 *
	 * @return  bool  Always true.
	 *
	 * @since   6.0
	 */
	public function onAfterUpdateAvailableFields()
	{
		if ($this->key)
		{
			// Return data
			$results = array();
			$results['continue'] = true;
			$results['key'] = $this->key;
		}
		else
		{
			$results['continue'] = false;
		}

		$results['info'] = $this->message;

		return $results;
	}

	/**
	 * Load a patch provided by the forum.
	 *
	 * @param   FOFInput  $input  The FOFInput class.
	 *
	 * @return  bool  True on success, false on failure.
	 *
	 * throws   RuntimeException
	 *
	 * @since   5.6
	 */
	public function loadPatch(FOFInput $input)
	{
		// Load the necessary libraries
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.archive');

		clearstatcache();

		// Get the uploaded details
		$upload = $input->get('patch_file', false);

		// Check if the file upload has an error
		if (empty($upload))
		{
			$this->log->addStats('incorrect', 'COM_CSVI_NO_UPLOADED_FILE_PROVIDED', 'maintenance');

			return false;
		}
		elseif ($upload['error'] == 0)
		{
			// Get some basic info
			$folder = CSVIPATH_TMP . '/patch/' . time();

			// Create the temp folder
			if (JFolder::create($folder))
			{
				// Move the uploaded file to its temp location
				if (JFile::copy($upload['tmp_name'], $folder . '/' . $upload['name']))
				{
					// Remove the temporary file
					JFile::delete($upload['tmp_name']);

					// Unpack the archive
					if (JArchive::extract($folder . '/' . $upload['name'], $folder))
					{
						// File is unpacked, remove the zip file so it won't get processed
						JFile::delete($folder . '/' . $upload['name']);

						// File is unpacked, let's process the folder
						if ($this->processFolder($folder, $folder))
						{
							// All good remove tempory folder
							JFolder::delete($folder);

							return true;
						}
					}
					else
					{
						$this->log->addStats('incorrect', 'COM_CSVI_CANNOT_UNPACK_UPLOADED_FILE', 'maintenance');

						throw new RuntimeException(JText::_('COM_CSVI_CANNOT_UNPACK_UPLOADED_FILE'));
					}
				}
			}
			else
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CANNOT_CREATE_UNPACK_FOLDER', $folder), 'maintenance');

				throw new RuntimeException(JText::sprintf('COM_CSVI_CANNOT_CREATE_UNPACK_FOLDER', $folder));
			}
		}
		else
		{
			// There was a problem uploading the file
			switch ($upload['error'])
			{
				case '1':
					$this->log->addStats('incorrect', 'COM_CSVI_THE_UPLOADED_FILE_EXCEEDS_THE_MAXIMUM_UPLOADED_FILE_SIZE', 'maintenance');
					break;
				case '2':
					$this->log->addStats('incorrect', 'COM_CSVI_THE_UPLOADED_FILE_EXCEEDS_THE_MAXIMUM_UPLOADED_FILE_SIZE', 'maintenance');
					break;
				case '3':
					$this->log->addStats('incorrect', 'COM_CSVI_THE_UPLOADED_FILE_WAS_ONLY_PARTIALLY_UPLOADED', 'maintenance');
					break;
				case '4':
					$this->log->addStats('incorrect', 'COM_CSVI_NO_FILE_WAS_UPLOADED', 'maintenance');
					break;
				case '6':
					$this->log->addStats('incorrect', 'COM_CSVI_MISSING_A_TEMPORARY_FOLDER', 'maintenance');
					break;
				case '7':
					$this->log->addStats('incorrect', 'COM_CSVI_FAILED_TO_WRITE_FILE_TO_DISK', 'maintenance');
					break;
				case '8':
					$this->log->addStats('incorrect', 'COM_CSVI_FILE_UPLOAD_STOPPED_BY_EXTENSION', 'maintenance');
					break;
				default:
					$this->log->addStats('incorrect', 'COM_CSVI_THERE_WAS_A_PROBLEM_UPLOADING_THE_FILE', 'maintenance');
					break;
			}

			throw new RuntimeException(JText::_('COM_CSVI_PATH_UPLOAD_ERROR'));
		}

		return true;
	}

	/**
	 * Walk through a folder to process all found files.
	 *
	 * @param   string  $folder  The name of the folder to process
	 * @param   string  $base    The base folder
	 *
	 * @return  bool  True on success, false on failure.
	 *
	 * @since   5.6
	 */
	private function processFolder($folder, $base = null)
	{
		$foundfiles = scandir($folder);

		foreach ($foundfiles as $ffkey => $ffname)
		{
			$src = $folder . '/' . $ffname;

			// Check if it is a folder
			if (is_dir($src))
			{
				switch ($ffname)
				{
					case '.':
					case '..':
						break;
					default:
						$this->processFolder($src, $base);
						break;
				}
			}
			else
			{
				// Create the destination name
				$destFile = str_ireplace($base, JPATH_SITE, $folder) . '/' . $ffname;

				// Check if the destination file exists
				if (file_exists($destFile))
				{
					JFile::move($destFile, $destFile . '.' . date('Ymd-His'));
				}

				// Copy the file to the destination location
				if (JFile::copy($src, $destFile))
				{
					$this->log->addStats('added', JText::sprintf('COM_CSVI_COPY_PATCHFILE', $src, $destFile), 'maintenance');
				}
				else
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CANT_COPY_PATCHFILE', $src, $destFile), 'maintenance');
				}
			}
		}

		return true;
	}

	/**
	 * Clean the CSVI cache.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   3.0
	 */
	public function cleanTemp()
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');
		$folder = CSVIPATH_TMP;

		if (JFolder::exists($folder))
		{
			// Delete all import files left behind in the folder
			JFile::delete(JFolder::files($folder, '.', false, true));

			// Delete all import folders left behind in the folder
			$folders = JFolder::folders($folder, '.', true, true, array('debug', 'export'));

			if (!empty($folders))
			{
				foreach ($folders as $path)
				{
					JFolder::delete($path);
				}
			}

			// Empty the export folder
			JFile::delete(JFolder::files($folder . '/export', '.', false, true));

			// Load the files
			if (JFolder::exists(CSVIPATH_DEBUG))
			{
				$files = JFolder::files(CSVIPATH_DEBUG, 'com_csvi', false, true);

				if ($files)
				{
					// Set all directory separators in the same direction
					foreach ($files as &$file)
					{
						$file = str_replace('\\', '/', $file);
					}

					// Remove any debug logs that are still there but not in the database
					$query = $this->db->getQuery(true)
						->select(
							'CONCAT('
								. $this->db->quote(CSVIPATH_DEBUG . '/com_csvi.log.')
								. ', '
								. $this->db->quoteName('csvi_log_id')
								. ', '
								. $this->db->quote('.php') . ') AS ' . $this->db->quoteName('filename')
						)
						->from($this->db->quoteName('#__csvi_logs'))
						->order($this->db->quoteName('csvi_log_id'));
					$this->db->setQuery($query);
					$ids = $this->db->loadColumn();

					if (!is_array($ids))
					{
						$ids = (array) $ids;
					}

					// Delete all obsolete files
					JFile::delete(array_diff($files, $ids));
				}
			}

			$this->log->addStats('delete', JText::sprintf('COM_CSVI_TEMP_CLEANED', $folder, CSVIPATH_DEBUG, $folder . '/export'), 'maintenance');
		}
		else
		{
			$this->log->addStats('information', JText::sprintf('COM_CSVI_TEMP_PATH_NOT_FOUND'), 'maintenance');
		}

		return true;
	}

	/**
	 * Backup selected templates.
	 *
	 * @param   FOFInput  $input  FOFInput object
	 *
	 * @return  bool  Always true.
	 *
	 * @throws  CsviException
	 *
	 * @since   3.0
	 */
	public function backupTemplates(FOFInput $input)
	{
		$linenumber = 1;
		$ids = $input->get('templates', array(), 'array');

		if (!$ids)
		{
			throw new CsviException(JText::_('COM_CSVI_NO_TEMPLATES_SELECTED'));
		}

		// Create the backup file
		$filepath = JPATH_SITE . $input->get('backup_location', '/tmp/com_csvi', 'string');
		$filename = 'csvi_templates_' . date('Ymd', time()) . '.xml';
		$file = JPath::clean($filepath . '/' . $filename, '/');

		$xml = new DOMDocument;
		$xml->formatOutput = true;
		$csvi_element = $xml->createElement('csvi');
		$templates = FOFModel::getTmpInstance('Templates', 'CsviModel')->getList();

		foreach ($templates as $template)
		{
			$template = (array) $template;

			if (in_array($template['csvi_template_id'], $ids))
			{
				// Create the template node
				$xml_template = $xml->createElement('template');

				// Add the settings
				$template_settings = $xml->createElement('settings');
				$settings = json_decode($template['settings']);

				foreach ($settings as $name => $value)
				{
					$ruleelement = $xml->createElement($name);

					if (is_array($value))
					{
						foreach ($value as $key => $subvalue)
						{
							$subelement = $xml->createElement('option');
							$subelement->appendChild($xml->createCDATASection($subvalue));
							$ruleelement->appendChild($subelement);
						}
					}
					else
					{
						$ruleelement->appendChild($xml->createCDATASection($value));
					}

					$template_settings->appendChild($ruleelement);
				}

				// Add the settings to the XML
				$xml_template->appendChild($template_settings);

				// Array of fields to export
				$nodes = array(
					'template_name',
					'advanced',
					'action',
					'frontend',
					'secret',
					'log',
					'lastrun',
					'enabled',
					'ordering',
				);

				// Add all the template options
				foreach ($nodes as $rulenode)
				{
					$ruleelement = $xml->createElement($rulenode);
					$ruleelement->appendChild($xml->createCDATASection($template[$rulenode]));
					$xml_template->appendChild($ruleelement);
				}

				// Add the fields for this template
				$fields = FOFModel::getTmpInstance('Templatefields', 'CsviModel')->csvi_template_id($template['csvi_template_id'])->getList();

				if (count($fields) > 0)
				{
					$nodes = array(
						'field_name',
						'xml_node',
						'column_header',
						'default_value',
						'enabled',
						'sort',
						'cdata',
						'ordering',
					);

					$template_fields = $xml->createElement('fields');

					foreach ($fields as $field)
					{
						$template_field = $xml->createElement('field');

						foreach ($nodes as $node)
						{
							$fieldelement = $xml->createElement($node);
							$fieldelement->appendChild($xml->createCDATASection($field->$node));
							$template_field->appendChild($fieldelement);
						}

						// Add the template field rules
						$query = $this->db->getQuery(true)
							->select(
								$this->db->quoteName(
									array(
										'name',
										'action',
										'ordering',
										'plugin',
										'plugin_params',
									)
								)
							)
							->from($this->db->quoteName('#__csvi_rules', 'r'))
							->leftJoin(
								$this->db->quoteName('#__csvi_templatefields_rules', 't')
								. ' ON ' . $this->db->quoteName('t.csvi_rule_id') . ' = ' . $this->db->quoteName('r.csvi_rule_id')
							)
							->where($this->db->quoteName('t.csvi_templatefield_id') . ' = ' . (int) $field->csvi_templatefield_id);
						$this->db->setQuery($query);

						$rules = $this->db->loadObjectList();

						if (count($rules) > 0)
						{
							$rulenodes = array(
								'name',
								'action',
								'ordering',
								'plugin',
								'plugin_params',
							);

							$fieldrules = $xml->createElement('fieldrules');

							foreach ($rules as $rule)
							{
								$fieldrule = $xml->createElement('rule');

								foreach ($rulenodes as $rulenode)
								{
									$ruleelement = $xml->createElement($rulenode);

									if ($rulenode == 'plugin_params')
									{
										$params = json_decode($rule->$rulenode);

										foreach ($params as $name => $value)
										{
											$element_param = $xml->createElement($name);
											$element_param->appendChild($xml->createCDATASection($value));
											$ruleelement->appendChild($element_param);
										}
									}
									else
									{
										$ruleelement->nodeValue = $rule->$rulenode;
									}

									$fieldrule->appendChild($ruleelement);
								}

								$fieldrules->appendChild($fieldrule);
							}

							$template_field->appendChild($fieldrules);
						}

						$template_fields->appendChild($template_field);
					}

					// Add the fields to the XML
					$xml_template->appendChild($template_fields);
				}

				// Add the template to the XML
				$this->log->setLinenumber($linenumber++);
				$csvi_element->appendChild($xml_template);
			}
		}

		$xml->appendChild($csvi_element);
		$xml->save($file);

		$this->log->addStats('information', JText::sprintf('COM_CSVI_BACKUP_TEMPLATE_PATH', $file), 'maintenance');


		// Store the log count
		$linenumber--;
		$input->set('logcount', $linenumber);

		return true;
	}

	/**
	 * Restore templates.
	 *
	 * @param   FOFInput  $input     FOFInput object
	 * @param   mixed     $key       A reference used by the method.
	 * @param   string    $filename  A local filename to use for import
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   3.0
	 */
	public function restoreTemplates(FOFInput $input, $key, $filename = '')
	{
		$linenumber = 1;
		jimport('joomla.filesystem.folder');
		jimport('joomla.filesystem.file');

		if (empty($filename))
		{
			$upload = $input->get('restore_file', false);

			// Check if the file upload has an error
			if (empty($upload))
			{
				$this->log->addStats('incorrect', JText::_('COM_CSVI_NO_UPLOADED_FILE_PROVIDED'), 'maintenance');

				return false;
			}

			$filename = $upload['tmp_name'];
		}

		$doc = new DOMDocument;
		$doc->load(realpath($filename));
		$data = $this->domnodeToArray($doc->documentElement);

		$file = basename($filename);

		// Check if it is a multi-dimensional array
		if (!isset($data['template'][0]))
		{
			// Make the array multi-dimensional
			$newtemplate = array();
			$newtemplate['template'][0] = $data['template'];
			$data = $newtemplate;
		}

		// Load the necessary tables
		$templateTable = FOFTable::getAnInstance('templates');
		$fieldTable = FOFTable::getAnInstance('templatefields');
		$ruleTable = FOFTable::getAnInstance('rules');
		$templatefieldrulesTable = FOFTable::getAnInstance('templatefields_rules');

		foreach ($data as $templates)
		{
			foreach ($templates as $template)
			{
				// Store the template
				$templateTable->reset();
				$templateTable->set('csvi_template_id', null);
				$templateTable->set('template_name', $template['template_name']);
				$templateTable->set('advanced', $template['advanced']);
				$templateTable->set('action', $template['action']);
				$templateTable->set('frontend', $template['frontend']);
				$templateTable->set('secret', $template['secret']);
				$templateTable->set('log', $template['log']);
				$templateTable->set('lastrun', $template['lastrun']);
				$templateTable->set('enabled', $template['enabled']);
				$templateTable->set('ordering', $template['ordering']);

				// Reformat the settings
				foreach ($template['settings'] as $name => $setting)
				{
					if (is_array($setting))
					{
						$template['settings'][$name] = $setting['option'];
					}
				}

				$templateTable->set('settings', json_encode($template['settings']));
				$templateTable->store();

				// Store the fields
				$fields = array();

				if (isset($template['fields']['field']))
				{
					$fields = $template['fields']['field'];
				}

				foreach ($fields as $field)
				{
					$fieldTable->set('csvi_template_id', $templateTable->get('csvi_template_id'));
					$fieldTable->save($field);

					// Store any field related rules
					if (isset($field['fieldrules']))
					{
						foreach ($field['fieldrules'] as $rules)
						{
							if (isset($rules['name']))
							{
								$rules = array($rules);
							}

							foreach ($rules as $rule)
							{
								$ruledata = array(
									'name' => $rule['name'],
									'action' => $rule['action'],
									'ordering' => $rule['ordering'],
									'plugin' => $rule['plugin'],
									'plugin_params' => json_encode($rule['plugin_params']),
									'csvi_templatefield_id' => $fieldTable->get('csvi_templatefield_id')
								);

								// Save the rule
								$ruleTable->save($ruledata);

								// Save the relation
								$templatefieldrulesTable->set('csvi_templatefield_id', $fieldTable->get('csvi_templatefield_id'));
								$templatefieldrulesTable->set('csvi_rule_id', $ruleTable->get('csvi_rule_id'));
								$templatefieldrulesTable->store();

								// Reset the relation table
								$templatefieldrulesTable->reset();
								$templatefieldrulesTable->csvi_templatefields_rule_id = null;

								// Reset the rule table
								$ruleTable->reset();
								$ruleTable->csvi_rule_id = null;
							}
						}
					}

					$fieldTable->reset();
					$fieldTable->csvi_templatefield_id = null;
				}

				// Increment the number of templates processed
				$this->log->setLinenumber($linenumber++);
			}
		}

		// Set the name of the file restore to logs display
		$this->log->setFilename($file);

		// Store the log count
		$linenumber--;
		$input->set('logcount', $linenumber);

		return true;
	}

	/**
	 * Turn the XML file into an associative array.
	 *
	 * @param   DOMElement  $node  The tree to turn into an array.
	 *
	 * @return  array  The  XML layout as associative array.
	 *
	 * @see     https://github.com/gaarf/XML-string-to-PHP-array
	 *
	 * @since   6.0
	 */
	private function domnodeToArray($node)
	{
		$output = array();

		switch ($node->nodeType)
		{
			case XML_CDATA_SECTION_NODE:
			case XML_TEXT_NODE:
				$output = trim($node->textContent);
				break;
			case XML_ELEMENT_NODE:
				for ($i = 0, $m = $node->childNodes->length; $i < $m; $i++)
				{
					$child = $node->childNodes->item($i);
					$v = $this->domnodeToArray($child);

					if (isset($child->tagName))
					{
						$t = $child->tagName;

						if (!isset($output[$t]))
						{
							$output[$t] = array();
						}

						if (empty($v))
						{
							$v = '';
						}


						$output[$t][] = $v;
					}
					elseif ($v)
					{
						$output = (string) $v;
					}
				}

				if (is_array($output))
				{
					if ($node->attributes->length)
					{
						$a = array();

						foreach ($node->attributes as $attrName => $attrNode)
						{
							$a[$attrName] = (string) $attrNode->value;
						}

						$output['@attributes'] = $a;
					}

					foreach ($output as $t => $v)
					{
						if (is_array($v) && count($v) == 1 && $t != '@attributes')
						{
							$output[$t] = $v[0];
						}
					}
				}

				break;
		}

		return $output;
	}

	/**
	 * Prepare the ICEcat index files for loading.
	 *
	 * @param   FOFInput  $input  The input model
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   6.0
	 */
	public function onBeforeIcecatIndex(FOFInput $input)
	{
		$session = JFactory::getSession();
		$settings = new CsviHelperSettings($this->db);
		$username = $settings->get('ice_username', false);
		$password = $settings->get('ice_password', false);
		$icecat_options = $input->get('icecat', array(), null);
		$icecat_gzip = $input->get('icecat_gzip', true, 'bool');
		$loadremote_index = false;
		$loadremote_supplier = false;

		// Check which indexes to load
		if (in_array('icecat_index', $icecat_options))
		{
			$load_index = true;
		}
		else
		{
			$load_index = false;
		}

		if (in_array('icecat_supplier', $icecat_options))
		{
			$load_supplier = true;
		}
		else
		{
			$load_supplier = false;
		}

		// Check if we have a username and password
		if ($username && $password)
		{
			// Joomla includes
			jimport('joomla.filesystem.folder');
			jimport('joomla.filesystem.file');
			jimport('joomla.filesystem.archive');

			// Check if the files are stored on the server
			$location = $input->get('icecatlocation', '', 'string');

			if ($load_index)
			{
				if (JFile::exists($location . '/icecat_index'))
				{
					$icecat_index_file = $location . '/icecat_index';
				}
				elseif (JFile::exists($location . '/icecat_index.gzip'))
				{
					$icecat_index_file = $location.'/icecat_index.gzip';
				}
				elseif (JFile::exists($location . '/icecat_index.zip'))
				{
					$icecat_index_file = $location.'/icecat_index.zip';
				}
				else
				{
					$loadremote_index = true;
				}
			}

			if ($load_supplier)
			{
				if (JFile::exists($location . '/icecat_supplier'))
				{
					$icecat_supplier_file = $location.'/icecat_supplier';
				}
				elseif (JFile::exists($location . '/icecat_supplier.gzip'))
				{
					$icecat_supplier_file = $location.'/icecat_supplier.gzip';
				}
				elseif (JFile::exists($location . '/icecat_supplier.zip'))
				{
					$icecat_supplier_file = $location.'/icecat_supplier.zip';
				}
				else
				{
					$loadremote_supplier = true;
				}
			}

			// Load the remote files if needed
			if ($loadremote_index || $loadremote_supplier)
			{
				// Context for retrieving files
				if ($icecat_gzip)
				{
					$gzip = "Accept-Encoding: gzip\r\n";
				}
				else
				{
					$gzip = '';
				}
				$context = stream_context_create(array(
						'http' => array(
								'header'  => "Authorization: Basic " . base64_encode($username . ':' . $password) . "\r\n" . $gzip
						)
				));

				if ($load_index && $loadremote_index)
				{
					// ICEcat index file
					$icecat_url = $settings->get('icecat.ice_index', 'http://data.icecat.biz/export/freexml.int/INT/files.index.csv');

					// Load the index file from the ICEcat server to a local file
					$icecat_index_file = CSVIPATH_TMP . '/icecat_index';

					if ($icecat_gzip)
					{
						$icecat_index_file .= '.gzip';
					}

					$fp_url = fopen($icecat_url, 'r', false, $context);
					$fp_local = fopen($icecat_index_file, 'w+');

					while($content = fread($fp_url, 1024536))
					{
						fwrite($fp_local, $content);
					}

					fclose($fp_url);
					fclose($fp_local);
				}

				if ($load_supplier && $loadremote_supplier)
				{
					// Load the manufacturer data
					$icecat_mf = $settings->get('icecat.ice_supplier', 'http://data.icecat.biz/export/freexml.int/INT/supplier_mapping.xml');

					// Load the index file from the ICEcat server to a local file
					$icecat_supplier_file = CSVIPATH_TMP . '/icecat_supplier';

					if ($icecat_gzip)
					{
						$icecat_supplier_file .= '.gzip';
					}

					$fp_url = fopen($icecat_mf, 'r', false, $context);
					$fp_local = fopen($icecat_supplier_file, 'w+');

					while($content = fread($fp_url, 1024536))
					{
						fwrite($fp_local, $content);
					}

					fclose($fp_url);
					fclose($fp_local);
				}
			}

			// Check if we need to unpack the files
			if ($load_index)
			{
				if (substr($icecat_index_file, -3) == 'zip')
				{
					if (!$this->unpackIcecat($icecat_index_file, CSVIPATH_TMP))
					{
						$this->log->addStats('incorrect', 'COM_CSVI_ICECAT_INDEX_NOT_UNPACKED', 'maintenance');

						return false;
					}
					else
					{
						$icecat_index_file = CSVIPATH_TMP . '/icecat_index';
					}
				}

				$session->set('icecat_index_file', serialize($icecat_index_file), 'com_csvi');
			}
			if ($load_supplier)
			{
				if (substr($icecat_supplier_file, -3) == 'zip')
				{
					if (!$this->unpackIcecat($icecat_supplier_file, CSVIPATH_TMP))
					{
						$this->log->addStats('incorrect', 'COM_CSVI_ICECAT_SUPPLIER_NOT_UNPACKED', 'maintenance');

						return false;
					}
					else
					{
						$icecat_supplier_file = CSVIPATH_TMP . '/icecat_supplier';
					}
				}

				$session->set('icecat_supplier_file', serialize($icecat_supplier_file), 'com_csvi');
			}
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_ICECAT_NO_USER_PASS', 'maintenance');

			return false;
		}

		return true;
	}

	/**
	 * Load the ICEcat indexes.
	 *
	 * @param   FOFInput  $input  The input model
	 * @param   mixed     $key    A reference used by the method.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   6.0
	 */
	public function icecatIndex(FOFInput $input, $key)
	{
		if ($key > 0)
		{
			$result = $this->indexIcecat($input, $key);
		}
		else
		{
			$linenumber = $this->log->getLineNumber();

			// Load the index files
			$session = JFactory::getSession();
			$icecat_index_file = unserialize($session->get('icecat_index_file', '', 'com_csvi'));
			$icecat_supplier_file = unserialize($session->get('icecat_supplier_file', '', 'com_csvi'));

			// Should we load the index file in 1 go
			$loadtype = $input->get('loadtype', true, 'bool');

			// Check which files to load
			$icecat_options = $input->get('icecat', array(), null);

			if (in_array('icecat_index', $icecat_options))
			{
				$load_index = true;
			}
			else
			{
				$load_index = false;
			}

			if (in_array('icecat_supplier', $icecat_options))
			{
				$load_supplier = true;
			}
			else
			{
				$load_supplier = false;
			}

			// First load the supplier file, it is small and easy to do
			if ($linenumber == 0 && $load_supplier && $icecat_supplier_file)
			{
				// Add the line number
				$this->log->setLinenumber(++$linenumber);

				// Empty the supplier table
				$this->db->truncateTable('#__csvi_icecat_suppliers');

				// Reset the supplier file
				$xmlstr = file_get_contents($icecat_supplier_file);
				$xml = new SimpleXMLElement($xmlstr);
				$supplier_data = array();

				foreach ($xml->SupplierMappings->children() as $mapping)
				{
					foreach ($mapping->attributes() as $attr_name => $attr_value)
					{
						switch($attr_name)
						{
							case 'supplier_id':
								$supplier_id = $attr_value;
								break;
							case 'name':
								$supplier_data[] = '(' . $this->db->quote($supplier_id) . ',' . $this->db->quote($attr_value) . ')';
						}
					}

					foreach ($mapping->children() as $symbol)
					{
						$supplier_data[] = '(' . $this->db->quote($supplier_id) . ',' . $this->db->quote($symbol) . ')';
					}
				}

				$q = "INSERT IGNORE INTO ".$this->db->quoteName('#__csvi_icecat_suppliers') . " VALUES " . implode(',', $supplier_data);
				$this->db->setQuery($q);

				if ($this->db->execute())
				{
					$input->set('linesprocessed', $this->db->getAffectedRows());
					$this->log->addStats('added', 'COM_CSVI_ICECAT_SUPPLIERS_LOADED');
				}
				else
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_ICECAT_SUPPLIERS_NOT_LOADED', $this->db->getErrorMsg()));
				}
			}

			if (!$loadtype && $load_index)
			{
				if ($icecat_index_file)
				{
					// Empty the index table
					$this->db->truncateTable('#__csvi_icecat_index');

					// Load the files using INFILE
					$q = "LOAD DATA LOCAL INFILE " . $this->db->quote($icecat_index_file) . "
						INTO TABLE " . $this->db->quoteName('#__csvi_icecat_index') . "
						FIELDS TERMINATED BY '\t' ENCLOSED BY '\"'
						IGNORE 1 LINES";
					$this->db->setQuery($q);

					// Add the line number
					$this->log->setLinenumber(++$linenumber);

					if ($result = $this->db->execute())
					{
						$input->set('linesprocessed', $input->get('linesprocessed') + $this->db->getAffectedRows());
						$this->log->addStats('added', 'COM_CSVI_ICECAT_INDEX_LOADED');
					}
					else
					{
						$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_ICECAT_INDEX_NOT_LOADED', $this->db->getErrorMsg()));
					}
				}
				else
				{
					$this->log->addStats('incorrect', 'COM_CSVI_ICECAT_INDEX_FILE_NOT_FOUND');
				}
			}
			else
			{
				// Check if we are running a cron job
				list($isCli, $isAdmin) = FOFDispatcher::isCliAdmin();

				// Load the files in 1 go using cron
				if ($isCli)
				{
					$continue = true;

					while ($continue)
					{
						$result = $this->indexIcecat($input, $key);
						$continue = $input->get('continue');
					}
				}
				// Load the files in steps using gui
				else
				{
					if ($key == 0)
					{
						// Empty the index table
						$this->db->truncateTable('#__csvi_icecat_index');
					}

					$result = $this->indexIcecat($input, $key);
				}
			}
		}

		return $result;
	}

	/**
	 * Post processing index ICEcat.
	 *
	 * @return  array  Settings for continuing.
	 *
	 * @since   6.0
	 */
	public function onAftericecatIndex()
	{
		if ($this->key)
		{
			// Return data
			$results = array();
			$results['continue'] = true;
			$results['key'] = $this->key;
		}
		else
		{
			$results['continue'] = false;
		}

		$results['info'] = $this->message;

		return $results;
	}

	/**
	 * Unpack the ICEcat index files.
	 *
	 * @param   string  $archivename  The full path and name of the file to extract
	 * @param   string  $extractdir   The folder to copy the extracted file to
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   3.0
	 */
	private function unpackIcecat($archivename, $extractdir)
	{
		$adapter = JArchive::getAdapter('gzip');

		if ($adapter)
		{
			$config = JFactory::getConfig();
			$tmpfname = $config->get('tmp_path') . '/' . uniqid('gzip');
			$gzresult = $adapter->extract($archivename, $tmpfname);

			if (JError::isError($gzresult))
			{
				@unlink($tmpfname);

				return false;
			}

			$path = JPath::clean($extractdir);
			JFolder::create($path);
			JFile::copy($tmpfname, $path . '/' . JFile::stripExt(basename(strtolower($archivename))));
			@unlink($tmpfname);
		}

		return true;
	}

	/**
	 * Load the ICEcat index in batches.
	 *
	 * @param   FOFInput  $input  The input model
	 * @param   mixed     $key    A reference used by the method.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   3.3
	 */
	private function indexIcecat(FOFInput $input, $key)
	{
		$linenumber = $this->log->getLineNumber();

		// Session init
		$session = JFactory::getSession();
		$icecat_index_file = unserialize($session->get('icecat_index_file', '', 'com_csvi'));
		$records = $input->get('icecat_records', 1000, 'int');
		$finished = false;
		$continue = true;

		if ($icecat_index_file)
		{
			// Sleep to please the server
			sleep($input->get('icecat_wait', 5));

			// Load the records line by line
			$query = $this->db->getQuery(true)
				->insert($this->db->quoteName('#__csvi_icecat_index'))
				->columns(
					$this->db->quoteName('path') . ','
					. $this->db->quoteName('product_id') . ','
					. $this->db->quoteName('updated') . ','
					. $this->db->quoteName('quality') . ','
					. $this->db->quoteName('supplier_id') . ','
					. $this->db->quoteName('prod_id') . ','
					. $this->db->quoteName('catid') . ','
					. $this->db->quoteName('m_prod_id') . ','
					. $this->db->quoteName('ean_upc') . ','
					. $this->db->quoteName('on_market') . ','
					. $this->db->quoteName('country_market') . ','
					. $this->db->quoteName('model_name') . ','
					. $this->db->quoteName('product_view') . ','
					. $this->db->quoteName('high_pic') . ','
					. $this->db->quoteName('high_pic_size') . ','
					. $this->db->quoteName('high_pic_width') . ','
					. $this->db->quoteName('high_pic_height') . ','
					. $this->db->quoteName('m_supplier_id') . ','
					. $this->db->quoteName('m_supplier_name')
				);

			if (($handle = fopen($icecat_index_file, "r")) !== false)
			{
				// Position pointers
				$row = 0;

				// Position file pointer
				fseek($handle, $key);

				// Start processing
				while ($continue)
				{
					if ($row < $records)
					{
						$data = fgetcsv($handle, 2048, "\t");

						if ($data)
						{
							$row++;
							$lines = array();

							foreach ($data as $item)
							{
								if (empty($item))
								{
									$lines[] = 'NULL';
								}
								else
								{
									$lines[] = $this->db->quote($item);
								}
							}

							$query->values(implode(',', $lines));
						}
						else
						{
							$finished = true;
							$continue = false;
						}
					}
					else
					{
						$continue = false;
					}
				}

				// Store the data
				$this->db->setQuery($query);

				if ($this->db->execute())
				{
					$this->log->setLineNumber(++$linenumber);
					$this->log->addStats('added', 'COM_CSVI_ICECAT_INDEX_LOADED');

					// Store for future use
					if (!$finished)
					{
						$this->key = ftell($handle);
						$this->message = JText::sprintf('COM_CSVI_PROCESS_LINES', $row);
					}
					else
					{
						$this->log->addStats('added', 'COM_CSVI_ICECAT_INDEX_LOADED');

						// Clear the session
						$session->clear('icecat_index_file', 'com_csvi');
						$session->clear('icecat_supplier_file', 'com_csvi');
						$session->clear('form', 'com_csvi');
					}

					$result = true;
				}
				else
				{
					$result = false;
				}

				fclose($handle);

				return $result;
			}
			else
			{
				return false;
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Clean up because the user has cancelled the operation.
	 *
	 * @return  bool  Returns true.
	 *
	 * @since   6.0
	 */
	public function cancelOperation()
	{
		// Clean the session
		$session = JFactory::getSession();

		$session->clear('icecat_index_file', 'com_csvi');
		$session->clear('icecat_supplier_file', 'com_csvi');

		return true;
	}

	/**
	 * Delete the CSVI tables.
	 *
	 * @return  bool  Returns true.
	 *
	 * @since   6.0
	 */
	public function deleteTables()
	{
		$tables = array(
			'csvi_availablefields',
			'csvi_availabletables',
			'csvi_currency',
			'csvi_icecat_index',
			'csvi_icecat_suppliers',
			'csvi_logdetails',
			'csvi_logs',
			'csvi_mapheaders',
			'csvi_maps',
			'csvi_processed',
			'csvi_related_categories',
			'csvi_related_products',
			'csvi_rules',
			'csvi_processes',
			'csvi_settings',
			'csvi_tasks',
			'csvi_templatefields',
			'csvi_templatefields_rules',
			'csvi_templates',
			'csvi_template_fields_combine',
			'csvi_template_fields_replacement'
		);

		foreach ($tables as $tablename)
		{
			$this->db->dropTable($this->db->getPrefix() . $tablename);
		}

		return true;
	}

	/**
	 * Post process table deletion.
	 *
	 * @return  bool  Returns true.
	 *
	 * @since   6.0
	 */
	public function onAfterDeleteTables()
	{
		// Store the message to show
		$this->csvihelper->enqueueMessage(JText::_('COM_CSVI_ALL_TABLES_DELETED'));

		// Since we have no tables left and user plans to uninstall, we need to redirect to the extension manager
		$cancel = array('url' => 'index.php?option=com_installer&view=manage');
		JFactory::getApplication()->input->set('canceloptions', $cancel);

		return array('cancel' => true);
	}

	/**
	 * Install any available example template.
	 *
	 * @param   FOFInput  $input  The input model
	 *
	 * @return  bool  True on success | false on failure.
	 *
	 * @since   6.4.0
	 */
	public function exampleTemplates(FOFInput $input)
	{
		// Get a list of example templates to install
		$components = $this->csvihelper->getComponents();
		jimport('joomla.filesystem.file');

		foreach ($components as $component)
		{
			// Process all extra available fields
			$filename = JPATH_ADMINISTRATOR . '/components/com_csvi/addon/' . $component->value . '/install/templates.xml';

			if (JFile::exists($filename))
			{
				// Check if the component is installed
				if (substr($component->value, 0, 4) == 'com_')
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('extension_id'))
						->from($this->db->quoteName('#__extensions'))
						->where($this->db->quoteName('element') . ' = ' . $this->db->quote($component->value));
					$this->db->setQuery($query);
					$ext_id = $this->db->loadResult();
				}
				else
				{
					$ext_id = true;
				}

				if ($ext_id)
				{
					$this->log->add('Processing template file ' . $filename);

					// Install the templates
					if ($this->restoreTemplates($input, 0, $filename))
					{
						$this->log->addStats('added', JText::sprintf('COM_CSVI_ADDED_EXAMPLE_TEMPLATEFILE', JText::_('COM_CSVI_' . $component->value)));
					}
				}
			}
		}
	}
}
