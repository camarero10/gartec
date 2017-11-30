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
 * Shopper field import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportShopperfield extends RantaiImportEngine
{
	/**
	 * User field table
	 *
	 * @var    VirtueMartTableUserfield
	 * @since  6.0
	 */
	private $userfieldTable = null;

	/**
	 * User info table
	 *
	 * @var    VirtueMartTableUserinfo
	 * @since  6.0
	 */
	private $userinfoTable = null;

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
					case 'name':
						$jfilter = new JFilterInput;
						$this->setState($name, strtolower($jfilter->clean($value, 'alnum')));
						break;
					case 'published':
						switch ($value)
						{
							case 'n':
							case 'no':
							case 'N':
							case 'NO':
							case '0':
								$value = 0;
								break;
							default:
								$value = 1;
								break;
						}

						$this->setState($name, $value);
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		// Required fields are calc_kind, calc_value_mathop, calc_value
		if ($this->getState('name', false))
		{
			// Check if we have a field ID
			if (!$this->getState('virtuemart_userfield_id', false))
			{
				$this->getFieldId($this->getState('name'));
			}

			// Bind the values
			$this->userfieldTable->bind($this->state);

			if ($this->userfieldTable->check())
			{
				$this->setState('virtuemart_userfield_id', $this->userfieldTable->virtuemart_userfield_id);

				// Check if we have an existing item
				if ($this->getState('virtuemart_userfield_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('name')));
					$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('name')));
					$this->loaded = false;
				}
				else
				{
					// Load the current content data
					$this->userfieldTable->load();
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
			// Get the needed data
			$virtuemart_userfield_id = $this->getState('virtuemart_userfield_id', false);
			$shopperfield_delete = $this->getState('shopperfield_delete', 'N');

			// Check if we need to delete the manufacturer
			if ($virtuemart_userfield_id && $shopperfield_delete == 'Y')
			{
				$this->deleteShopperField();
			}
			elseif (!$virtuemart_userfield_id && $shopperfield_delete == 'Y')
			{
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_NO_SHOPPERFIELD_ID_NO_DELETE', $this->getState('name')));
			}
			elseif (!$virtuemart_userfield_id && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new products when user chooses to ignore new products
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('name')));
			}
			else
			{
				// Check for modified data
				if (!$this->getState('modified_on'))
				{
					$this->userfieldTable->modified_on = $this->date->toSql();
					$this->userfieldTable->modified_by = $this->userId;
				}

				// Add a creating date if there is no virtuemart_userfield_id
				if (!$this->getState('virtuemart_userfield_id', false))
				{
					$this->userfieldTable->created_on = $this->date->toSql();
					$this->userfieldTable->created_by = $this->userId;
				}

				// Store the data
				if ($this->userfieldTable->store())
				{
					// Check if a column by this name already exists
					if (!$this->userinfoTable->fieldExists($this->userfieldTable->name))
					{
						$type = $this->getState('type');

						// Create a field in the userinfos table if needed
						if ($type != 'delimiter')
						{
							switch ($type)
							{
								case 'date':
									$fieldtype = 'DATE';
									break;
								case 'editorta':
								case 'textarea':
								case 'multiselect':
								case 'multicheckbox':
									$fieldtype = 'MEDIUMTEXT';
									break;
								case 'checkbox':
									$fieldtype = 'TINYINT';
									break;
								default:
									$fieldtype = 'VARCHAR(255)';
									break;
							}

							$this->userinfoTable->createField($this->userfieldTable->name, $fieldtype);

							// Store the debug message
							$this->log->add('COM_CSVI_USERINFO_TABLE_QUERY');
						}
					}
				}
				else
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_SHOPPERFIELD_NOT_ADDED', $this->userfieldTable->getError()));
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
		$this->userfieldTable = $this->getTable('Userfield');
		$this->userinfoTable = $this->getTable('Userinfo');
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
		$this->userfieldTable->reset();
		$this->userinfoTable->reset();
	}

	/**
	 * Load the field ID for a fieldname.
	 *
	 * @param   string  $name  The name of the field to find the ID for.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	private function getFieldId($name)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_userfield_id'))
			->from($this->db->quoteName('#__virtuemart_userfields'))
			->where($this->db->quoteName('name') . ' = ' . $this->db->quote($name));
		$this->db->setQuery($query);
		$this->setState('virtuemart_userfield_id', $this->db->loadResult());
		$this->log->add('COM_CSVI_GET_FIELD_ID');
	}

	/**
	 * Delete a shopper field.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	private function deleteShopperField()
	{
		// Delete the shopperfield
		if ($this->userfieldTable->delete($this->getState('virtuemart_userfield_id')))
		{
			// Delete the userinfos field
			$this->userinfoTable->deleteField($this->getState('name'));

			$this->log->addStats('deleted', JText::sprintf('COM_CSVIVIRTUEMART_SHOPPERFIELD_DELETED', $this->getState('name')));
		}
	}
}
