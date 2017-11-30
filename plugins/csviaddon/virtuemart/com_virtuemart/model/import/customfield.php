<?php
/**
 * @package     CSVI
 * @subpackage  VirtueMart
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Custom field import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportCustomfield extends RantaiImportEngine
{
	/**
	 * Custom table
	 *
	 * @var    VirtueMartTableCustomfield
	 * @since  6.0
	 */
	private $customTable = null;

	/**
	 * Start the product import process.
	 *
	 * @return  bool  True on success | false on failure.
	 *
	 * @since   6.0
	 */
	public function getStart()
	{
		$this->setState('virtuemart_vendor_id', $this->helper->getVendorId());

		// Process data
		foreach ($this->fields->getData() as $fields)
		{
			foreach ($fields as $name => $details)
			{
				$value = $details->value;

				switch ($name)
				{
					case 'created_on':
					case 'modified_on':
					case 'locked_on':
						$this->setState($name, $this->convertDate($value));
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		if ($this->getState('custom_title', false))
		{
			// Bind the values
			$this->customTable->bind($this->state);

			if ($this->customTable->check())
			{
				$this->setState('virtuemart_custom_id', $this->customTable->virtuemart_custom_id);

				// Check if we have an existing item
				if ($this->getState('virtuemart_custom_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CUSTOMFIELD', $this->getState('custom_element') . ' - ' . $this->getState('custom_title')));
					$this->log->addStats(
						'skipped',
						JText::sprintf('COM_CSVI_DATA_EXISTS_CUSTOMFIELD', $this->getState('custom_element') . ' - ' . $this->getState('custom_title'))
					);
					$this->loaded = false;
				}
				else
				{
					// Load the current content data
					$this->customTable->load($this->getState('virtuemart_custom_id', 0));
					$this->loaded = true;
				}
			}
		}
		else
		{
			$this->loaded = false;

			$this->log->addStats('skipped', JText::_('COM_CSVI_MISSING_REQUIRED_FIELDS'));
		}

		return true;
	}

	/**
	 * Process a record.
	 *
	 * @return  bool  Returns true if all is OK | Returns false if no product SKU or product ID can be found.
	 *
	 * @since   6.0
	 */
	public function getProcessRecord()
	{
		if ($this->loaded)
		{
			if (!$this->getState('virtuemart_custom_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new custom fields when user chooses to ignore new custom fields
				$this->log->addStats(
					'skipped',
					JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('custom_element') . ' - ' . $this->getState('custom_title'))
				);
			}
			else
			{
				// Get the plugin ID if needed
				if ($this->getState('field_type') == 'E')
				{
					$custom_jplugin_id = $this->getState('custom_jplugin_id', false);
					$custom_element = $this->getState('custom_element', false);

					if (!$custom_jplugin_id && $custom_element)
					{
						$custom_jplugin_id = $this->getPluginId();

						if (empty($custom_jplugin_id))
						{
							$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_NO_PLUGIN_FOUND', $this->getState('plugin_name')));
							$this->log->add(JText::sprintf('COM_CSVI_NO_PLUGIN_FOUND', $this->getState('plugin_name')));

							return false;
						}
						else
						{
							$this->setState('custom_jplugin_id', $custom_jplugin_id);

							// Make sure the custom_value is the same as custom_element when dealing with a plugin
							// This is needed as otherwise the plugin is not called
							$this->setState('custom_value', $custom_element);
						}
					}
				}

				// Bind the data
				$this->customTable->bind($this->state);

				// Set the modified date as we are modifying the product
				if (!$this->getState('modified_on', false))
				{
					$this->customTable->modified_on = $this->date->toSql();
					$this->customTable->modified_by = $this->userId;
				}

				if (empty($this->customTable->virtuemart_custom_id))
				{
					$this->customTable->custom_params = $this->getState('custom_params', '');
					$this->customTable->custom_desc = $this->getState('custom_desc', '');
					$this->customTable->created_on = $this->date->toSql();
					$this->customTable->created_by = $this->userId;
				}

				// Store the data
				if (!$this->customTable->store())
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_CUSTOMFIELD_NOT_ADDED', $this->customTable->getError()));
				}
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load the necessary tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function loadTables()
	{
		$this->customTable = $this->getTable('Customfield');
	}

	/**
	 * Clear the loaded tables.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function clearTables()
	{
		$this->customTable->reset();
	}

	/**
	 * Get the plugin ID.
	 *
	 * @return  mixed  Extension ID if found | False otherwise.
	 *
	 * @since   3.1
	 */
	private function getPluginId()
	{
		$plugin_name = $this->getState('plugin_name', false);

		if ($plugin_name)
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('extension_id'))
				->from($this->db->quoteName('#__extensions'))
				->where($this->db->quoteName('name') . ' = ' . $this->db->quote($plugin_name))
				->where($this->db->quoteName('type') . ' = ' . $this->db->quote('plugin'))
				->where($this->db->quoteName('folder') . ' = ' . $this->db->quote('vmcustom'));
			$this->db->setQuery($query);
			$result = $this->db->loadResult();
			$this->log->add('COM_CSVI_FIND_PLUGIN_ID', true);

			if ($result)
			{
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
}
