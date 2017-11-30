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
 * Waiting list importer.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportWaitinglist extends RantaiImportEngine
{
	/**
	 * Waiting user table
	 *
	 * @var    VirtueMartTableWaitinguser
	 * @since  6.0
	 */
	private $waitinguserTable = null;

	/**
	 * Prepare the import.
	 *
	 * @return  array  The field option objects.
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

				// Check if the field needs extra treatment
				switch ($name)
				{
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		// There must be a product SKU
		if ($this->getState('username', false) && $this->getState('product_sku')&& $this->getState('notify_email'))
		{
			$this->loaded = true;

			// Get the product ID
			$this->setState('virtuemart_product_id', $this->helper->getProductId());

			// Get the user ID
			if (!$this->getState('virtuemart_user_id', false))
			{
				$this->getUserId();

				if (!$this->getState('virtuemart_user_id', false))
				{
					$this->log->addStats('incorrect', 'COM_CSVI_WAITINGLIST_NO_USER_FOUND');

					$this->loaded = false;
				}
			}

			// Bind the values
			$this->waitinguserTable->bind($this->state);

			if ($this->waitinguserTable->check())
			{
				$this->setState('virtuemart_waitinguser_id', $this->waitinguserTable->virtuemart_waitinguser_id);

				// Check if we have an existing item
				if ($this->getState('virtuemart_waitinguser_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('username')));
					$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('username')));
					$this->loaded = false;
				}
				else
				{
					// Load the current content data
					$this->waitinguserTable->load();
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
	 * Process each record and store it in the database.
	 *
	 * @return  bool  True if record processed | False if record is not processed.
	 *
	 * @since   3.0
	 */
	public function getProcessRecord()
	{
		if ($this->loaded)
		{
			if (!$this->getState('virtuemart_waitinguser_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new rules when user chooses to ignore new rules
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('username')));
			}
			else
			{
				// Set the modified date as we are modifying the product
				if (!$this->getState('modified_on', false))
				{
					$this->waitinguserTable->modified_on = $this->date->toSql();
					$this->waitinguserTable->modified_by = $this->userId;
				}

				if (empty($this->waitinguserTable->virtuemart_waitinguser_id))
				{
					$this->waitinguserTable->created_on = $this->date->toSql();
					$this->waitinguserTable->created_by = $this->userId;
				}

				// Store the data
				if (!$this->waitinguserTable->store())
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_WAITINGLIST_NOT_ADDED', $this->waitinguserTable->getError()));
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
	 * Load the waiting list related tables.
	 *
	 * @return  void.
	 *
	 * @since   3.1
	 */
	public function loadTables()
	{
		$this->waitinguserTable = $this->getTable('Waitinguser');
	}

	/**
	 * Cleaning the waiting list related tables.
	 *
	 * @return  void.
	 *
	 * @since   3.1
	 */
	public function clearTables()
	{
		$this->waitinguserTable->reset();
	}

	/**
	 * Get the user ID.
	 *
	 * @return  void.
	 *
	 * @since   3.1
	 */
	private function getUserId()
	{
		if ($this->getState('username', false))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('id'))
				->from($this->db->quoteName('#__users'))
				->where($this->db->quoteName('username') . ' = ' . $this->db->quote($this->getState('username')));
			$this->db->setQuery($query);

			$this->setState('virtuemart_user_id', $this->db->loadResult());

			$this->log->add('COM_CSVI_FIND_USER_ID');
		}
	}
}
