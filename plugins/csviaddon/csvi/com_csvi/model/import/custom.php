<?php
/**
 * @package     CSVI
 * @subpackage  CSVI
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Custom import.
 *
 * @package     CSVI
 * @subpackage  CSVI
 * @since       6.0
 */
class Com_CsviModelImportCustom extends RantaiImportEngine
{
	/**
	 * Custom table
	 *
	 * @var    CsviTableCustomtable
	 * @since  6.0
	 */
	private $customTable = null;

	/**
	 * The primary key field
	 *
	 * @var    string
	 * @since  6.0
	 */
	private $pk = null;

	/**
	 * Start the product import process.
	 *
	 * @return  bool  True on success | false on failure.
	 *
	 * @since   6.0
	 */
	public function getStart()
	{
		// Process data
		foreach ($this->fields->getData() as $fields)
		{
			foreach ($fields as $name => $details)
			{
				$value = $details->value;

				switch ($name)
				{
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Load the primary key
		$this->pk = $this->customTable->getKeyName();

		// Check if we have an existing item
		if ($this->getState($this->pk, 0) > 0 && !$this->template->get('overwrite_existing_data'))
		{
			$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CUSTOM', $this->getState($this->pk)));
			$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CUSTOM', $this->getState($this->pk)));
			$this->loaded = false;
		}
		else
		{
			// Load the current content data
			$this->customTable->load($this->getState($this->pk, 0));
			$this->loaded = true;
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
			if (!$this->getState($this->pk, false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new products when user chooses to ignore new products
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState($this->pk, '')));
			}
			else
			{
				// Bind the data
				$this->customTable->bind($this->state);

				// Store the data
				$this->customTable->store();
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
		$this->customTable = $this->getTable('CustomTable');
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
}
