<?php
/**
 * @package     CSVI
 * @subpackage  Settings
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Settings model.
 *
 * @package     CSVI
 * @subpackage  Settings
 * @since       6.0
 */
class CsviModelSettings extends FOFModel
{
	/**
	 * Load the item with ID 1, this is fixed for settings.
	 *
	 * @param   integer  $id  Force a primary key ID to the model. Use null to use the id from the state.
	 *
	 * @return  FOFTable  A copy of the item's FOFTable array.
	 *
	 * @since   6.0
	 */
	public function &getItem($id=null)
	{
		return parent::getItem(1);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   FOFTable  &$record  The table instance we fetched
	 *
	 * @return 	mixed Object on success | false on failure
	 *
	 * @since   6.0
	 */
	public function onAfterGetItem(&$record)
	{
		$registry = new JRegistry;
		$registry->loadString($record->params);
		$record->options = $registry->toArray();
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
		// Check for the trailing slash at the domain name
		if (substr($data['jform']['hostname'], -1) == '/')
		{
			$data['jform']['hostname'] = substr($data['jform']['hostname'], 0, -1);
		}

		// Serialize the form data
		$data['params'] = json_encode($data['jform']);

		return parent::onBeforeSave($data, $table);
	}

	/**
	 * Reset the settings.
	 *
	 * @return  bool   true if settings reset | false if settings not reset.
	 *
	 * @since   3.1.1
	 */
	public function resetSettings()
	{
		// Create an entry for the settings in case it doesn't exist
		$db = JFactory::getDbo();
		$db->setQuery('INSERT IGNORE INTO '
				. $db->qn('#__csvi_settings')
				. ' (' . $db->qn('csvi_setting_id') . ', ' . $db->qn('params') . ')
				VALUES (' . $db->q('1') . ', ' . $db->q('') . ');');
		$db->execute();

		// Clean the params if needed
		$row  = $this->getTable('settings');
		$row->csvi_setting_id = 1;
		$row->params = '';

		return $row->store();
	}

	/**
	 * Load some ICEcat statistics.
	 *
	 * @return  array  The ICEcat statistics.
	 *
	 * @since   5.9
	 */
	public function icecatStats()
	{
		$stats = array();
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('COUNT(' . $db->qn('path') . ')')
			->from($db->qn('#__csvi_icecat_index'));
		$db->setQuery($query);
		$stats['index'] = $db->loadResult();

		$query = $db->getQuery(true)
			->select('COUNT(' . $db->qn('supplier_id') . ')')
			->from($db->qn('#__csvi_icecat_suppliers'));
		$db->setQuery($query);
		$stats['supplier'] = $db->loadResult();

		return $stats;
	}
}
