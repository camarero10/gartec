<?php
/**
 * @package     CSVI
 * @subpackage  Rules
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Rules model.
 *
 * @package     CSVI
 * @subpackage  Rules
 * @since       6.0
 */
class CsviModelRules extends FOFModel
{
	/**
	 * The database class
	 *
	 * @var    JDatabaseDriver
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * Public class constructor
	 *
	 * @param   array  $config  The configuration array
	 */
	public function __construct($config = array())
	{
		parent::__construct();

		$this->db = JFactory::getDbo();
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

		// Clean out some settings so we can reset them
		$query->clear('select');
		$query->clear('from');

		// Setup the new settings
		$query->select($this->db->quoteName('tbl') . '.*');
		$query->from($this->db->quoteName('#__csvi_rules', 'tbl'));

		// Join the user table to get the editor
		$query->select($this->db->quoteName('u.name', 'editor'));
		$query->leftJoin(
				$this->db->quoteName('#__users', 'u')
				. ' ON ' . $this->db->quoteName('u.id') . ' = ' . $this->db->quoteName('tbl.locked_by')
			);

		// Add the filter
		$name = $this->input->get('tbl_name', false);

		if ($name)
		{
			$query->where($this->db->quoteName('tbl.name') . ' LIKE ' . $this->db->quote('%' . $name . '%'));
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
	 */
	protected function onAfterGetItem(&$record)
	{
		// Get the plugin parameters
		$record->pluginform = json_decode($record->plugin_params);

		parent::onAfterGetItem($record);
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
	public function onBeforeSave(&$data, &$table)
	{
		if (isset($data['pluginform']))
		{
			$data['plugin_params'] = json_encode($data['pluginform']);
		}

		return parent::onBeforeSave($data, $table);
	}
}
