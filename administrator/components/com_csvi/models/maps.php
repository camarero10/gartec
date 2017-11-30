<?php
/**
 * @package     CSVI
 * @subpackage  Fieldmapper
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Field mapper model.
 *
 * @package     CSVI
 * @subpackage  Fieldmapper
 * @since       6.0
 */
class CsviModelMaps extends FOFModel
{
	/**
	 * Holds the database driver
	 *
	 * @var    JDatabase
	 * @since  6.0
	 */
	protected $db = null;

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
	 * Builds the SELECT query
	 *
	 * @param   boolean  $overrideLimits  Are we requested to override the set limits?
	 *
	 * @return  JDatabaseQuery
	 */
	public function buildQuery($overrideLimits = false)
	{
		// Get the parent query
		$query = parent::buildQuery($overrideLimits);

		// Join the user table to get the editor
		$query->select($this->db->quoteName('u.name', 'editor'));
		$query->leftJoin(
			$this->db->quoteName('#__users', 'u')
			. ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('#__csvi_maps.locked_by')
		);

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
	 */
	protected function onAfterGetItem(&$record)
	{
		if ($record->csvi_map_id)
		{
			// Load the options
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('action') . ',' . $this->db->quoteName('component') . ',' . $this->db->quoteName('operation'))
				->from($this->db->quoteName('#__csvi_maps'))
				->where($this->db->quoteName('csvi_map_id') . '=' . (int) $record->csvi_map_id);
			$this->db->setQuery($query);
			$record->options = $this->db->loadObject();

			// Load the header fields to match
			$query = $this->db->getQuery(true)
			->select($this->db->quoteName('csvheader') . ',' . $this->db->quoteName('templateheader'))
			->from($this->db->quoteName('#__csvi_mapheaders'))
			->where($this->db->quoteName('map_id') . '=' . (int) $record->csvi_map_id);
			$this->db->setQuery($query);
			$record->headers = $this->db->loadObjectList();
		}
	}

	/**
	 * This method runs before the $data is saved to the $table. Return false to
	 * stop saving.
	 *
	 * @param   array     &$data   The data to save
	 * @param   FOFTable  &$table  The table to save the data to
	 *
	 * @return  boolean  Return false to prevent saving, true to allow it
	 */
	protected function onBeforeSave(&$data, &$table)
	{
		$data['title'] = (string) $data['jform']['title'];
		$data['action'] = (string) $data['jform']['action'];
		$data['component'] = (string) $data['jform']['component'];
		$data['operation'] = (string) $data['jform']['operation'];
		$data['auto_detect_delimiters'] = (string) $data['jform']['auto_detect_delimiters'];
		$data['field_delimiter'] = (string) $data['jform']['field_delimiter'];
		$data['text_enclosure'] = (string) $data['jform']['text_enclosure'];

		return parent::onBeforeSave($data, $table);
	}

	/**
	 * This method runs after the data is saved to the $table.
	 *
	 * @param   FOFTable  &$table  The table which was saved
	 *
	 * @return  boolean
	 */
	protected function onAfterSave(&$table)
	{
		// Get the uploaded file
		$file = JFactory::getApplication()->input->files->get('jform', array(), 'array');

		// Let's see if the user uploaded a file to get new columns
		if (!empty($file['mapfile']['name']))
		{
			// Save the file
			$this->processFile($table, $file);
		}
		// Store any mapped fields
		else
		{
			$this->processHeader($table);
		}

		return parent::onAfterSave($table);
	}

	/**
	 * Process an uploaded file with headers.
	 *
	 * @param   object  $table  The map table.
	 * @param   array   $file   The posted file.
	 *
	 * @return  bool  True if file is processed | False if file is not processed.
	 *
	 * @since   5.8
	 */
	private function processFile($table, $file)
	{
		$jinput = JFactory::getApplication()->input;
		$data = $table->getData();

		// Get the file details
		$upload = array();
		$upload['name'] = $file['mapfile']['name'];
		$upload['type'] = $file['mapfile']['type'];
		$upload['tmp_name'] = $file['mapfile']['tmp_name'];
		$upload['error'] = $file['mapfile']['error'];

		if (!$upload['error'])
		{
			// Move the temporary file
			if (is_uploaded_file($upload['tmp_name']))
			{
				// Get some basic info
				jimport('joomla.filesystem.file');
				jimport('joomla.filesystem.folder');
				$folder = CSVIPATH_TMP . '/' . time();
				$upload_parts = pathinfo($upload['name']);

				// Create the temp folder
				if (JFolder::create($folder))
				{
					// Move the uploaded file to its temp location
					if (JFile::upload($upload['tmp_name'], $folder . '/' . $upload['name']))
					{
						if (array_key_exists('extension', $upload_parts))
						{
							// Set the file class name to import because that can read the file.
							$fileclass = 'CsviHelperFileImport';

							// Load the extension specific class
							switch ($upload_parts['extension'])
							{
								case 'xml':
									$fileclass .= 'Xml';
									break;
								case 'xls':
									$fileclass .= 'Xls';
									break;
								case 'ods':
									$fileclass .= 'Ods';
									break;
								default:
									// Treat any unknown type as CSV
									$fileclass .= 'Csv';
									break;
							}

							$csvihelper = new CsviHelperCsvi;
							$settings = new CsviHelperSettings($this->db);
							$log = new CsviHelperLog($settings, $this->db);
							$template = new CsviHelperTemplate(0, $csvihelper);
							$template->set('source', 'fromserver');
							$template->set('local_csv_file', $folder . '/' . $upload['name']);
							$template->set('auto_detect_delimiters', $data['auto_detect_delimiters']);
							$template->set('field_delimiter', $data['field_delimiter']);
							$template->set('text_enclosure', $data['text_enclosure']);

							// Get the file handler
							$file = new $fileclass($template, $log, $csvihelper, $jinput);

							// Set the fields
							$fields = new CsviHelperImportfields($template, $log, $this->db);
							$file->setFields($fields);

							// Validate and process the file
							$file->setFilename($folder . '/' . $upload['name']);
							$file->processFile(true);

							// Get the header
							if ($header = $file->loadColumnHeaders())
							{
								if (is_array($header))
								{
									// Load the table
									$headertable = $this->getTable('Mapheaders', 'CsviTable');

									// Remove existing entries
									$query = $this->db->getQuery(true)
										->delete($this->db->quoteName('#__csvi_mapheaders'))
										->where($this->db->quoteName('map_id') . ' = ' . (int) $table->csvi_map_id);
									$this->db->setQuery($query);
									$this->db->execute();

									// Store the headers
									$map = array();
									$map['map_id'] = $table->csvi_map_id;

									foreach ($header as $name)
									{
										$map['csvheader'] = $name;

										// Store the data
										$headertable->save($map);
										$headertable->reset();
									}
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

		return true;
	}

	/**
	 * Process the header mappings.
	 *
	 * @param   object  $table  The map table.
	 *
	 * @return  void.
	 *
	 * @since   5.8
	 */
	private function processHeader($table)
	{
		$jinput = JFactory::getApplication()->input;

		foreach ($jinput->get('templateheader', array(), 'array') as $csvheader => $templateheader)
		{
			$query = $this->db->getQuery(true)
			->update($this->db->quoteName('#__csvi_mapheaders'))
			->set($this->db->quoteName('templateheader') . ' = ' . $this->db->quote($templateheader))
			->where($this->db->quoteName('map_id') . ' = ' . (int) $table->csvi_map_id)
			->where($this->db->quoteName('csvheader') . ' = ' . $this->db->quote($csvheader));
			$this->db->setQuery($query);
			$this->db->execute();
		}
	}

	/**
	 * Create a template from mapped settings.
	 *
	 * @param   int     $mapId  The ID of the field map.
	 * @param   string  $title  The name of the template to create.
	 *
	 * @return  bool  True if table has been created | False if template has not been created.
	 *
	 * @since   5.8
	 */
	public function createTemplate($mapId, $title)
	{
		// Get the models
		$template = parent::getAnInstance('Templates', 'CsviModel');
		$templatefields = parent::getAnInstance('Templatefields', 'CsviModel');

		// Collect the data
		$data = $this->getTemplateData($mapId);

		// Add the title
		$data['template_name'] = $title;

		if ($data)
		{
			if ($template->save($data))
			{
				$fields = $this->getTemplateFields($mapId);

				foreach ($fields as $order => $field)
				{
					$saveField = new stdClass;
					$saveField->csvi_template_id = $template->getId();
					$saveField->field_name = $field;
					$saveField->enabled = 1;
					$saveField->ordering = $order + 1;

					$templatefields->save($saveField);
					$templatefields->reset();
				}

				return true;
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
	 * Get the data to create a new template.
	 *
	 * @param   int  $mapId  The ID of the field map.
	 *
	 * @return  array  The template data.
	 *
	 * @since   5.8
	 */
	private function getTemplateData($mapId)
	{
		$data = array();

		// Get the map details
		$query = $this->db->getQuery(true)
			->select(
				array(
					$this->db->quoteName('m.action'),
					$this->db->quoteName('m.component'),
					$this->db->quoteName('m.operation'),
					$this->db->quoteName('m.auto_detect_delimiters'),
					$this->db->quoteName('m.field_delimiter'),
					$this->db->quoteName('m.text_enclosure')
				)
			)
			->from($this->db->quoteName('#__csvi_maps', 'm'))
			->where($this->db->quoteName('m.csvi_map_id') . ' = ' . (int) $mapId);
		$this->db->setQuery($query);
		$map = $this->db->loadObject();

		// Get the options if we have a result
		if ($map)
		{
			$data['jform']['action'] = $map->action;
			$data['jform']['component'] = $map->component;
			$data['jform']['operation'] = $map->operation;
			$data['jform']['auto_detect_delimiters'] = $map->auto_detect_delimiters;
			$data['jform']['field_delimiter'] = $map->field_delimiter;
			$data['jform']['text_enclosure'] = $map->text_enclosure;
			$data['jform']['use_column_headers'] = 0;
		}

		// Return the data
		return $data;
	}

	/**
	 * Get the fields to create template fields.
	 *
	 * @param   int  $mapId  The ID of the field map.
	 *
	 * @return  array  The template fields.
	 *
	 * @since   5.8
	 */
	private function getTemplateFields($mapId)
	{
		$data = array();

		if ($mapId)
		{
			// Get the map details
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('templateheader'))
				->from($this->db->quoteName('#__csvi_mapheaders'))
				->where($this->db->quoteName('map_id') . ' = ' . (int) $mapId);
			$this->db->setQuery($query);
			$data = $this->db->loadColumn();
		}

		// Return the data
		return $data;
	}
}
