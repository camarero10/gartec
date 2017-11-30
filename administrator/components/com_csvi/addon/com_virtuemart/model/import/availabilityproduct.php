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
 * Main processor for handling product availability.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportAvailabilityProduct extends RantaiImportEngine
{
	/**
	 * The product table
	 *
	 * @var    VirtueMartTableProduct
	 * @since  6.0
	 */
	private $product = null;

	/**
	 * Here starts the processing.
	 *
	 * @return  bool  Returns true on success | False on failure.
	 *
	 * @since   3.0
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
					case 'product_available_date':
						if (!empty($value))
						{
							$this->setState($name, $this->convertDate($value, 'sql', 'user'));
						}
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		// There must be a product SKU
		if ($this->getState('product_sku', false))
		{
			$field = 'product_sku';
			$this->product->setKeyName($field);

			$this->loaded = true;

			// Load the current content data
			if ($this->product->load($this->getState($field)))
			{
				if (!$this->template->get('overwrite_existing_data'))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_PRODUCT_SKU', $this->getState($field, '')));
					$this->loaded = false;
				}
			}

			// Find the content id
			$this->setState('virtuemart_product_id', $this->product->virtuemart_product_id);

			// Set the record identifier
			$this->recordIdentity = $this->getState($field, false);
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
	 * @return  bool  Returns true if all is OK | Returns false if no product SKU or product ID can be found.
	 *
	 * @since   3.0
	 */
	public function getProcessRecord()
	{
		if ($this->loaded)
		{
			if (!$this->getState('virtuemart_product_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new users when user chooses to ignore new users
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->recordIdentity));
			}
			else
			{
				$this->log->add(JText::sprintf('COM_CSVI_DEBUG_PROCESS_SKU', $this->recordIdentity));

				// Check if we need to do a stock calculation
				if ($this->getState('product_in_stock', false) !== false)
				{
					// Split the modification
					$operation = substr($this->getState('product_in_stock', 0), 0, 1);
					$value = substr($this->getState('product_in_stock', 0), 1);

					// Get the database value
					$stock = $this->product->product_in_stock;

					// Check what modification we need to do and apply it
					switch ($operation)
					{
						case '+':
							$stock += $value;
							break;
						case '-':
							$stock -= $value;
							break;
						case '/':
							$stock /= $value;
							break;
						case '*':
							$stock *= $value;
							break;
						default:
							// Assign the current price to prevent it being overwritten
							$stock = $this->getState('product_in_stock');
							break;
					}
				}
				else
				{
					$stock = $this->product->product_in_stock;
				}

				$this->setState('product_in_stock', $stock);

				// Bind the initial data
				$this->product->bind($this->state);

				// Set the modified date as we are modifying the product
				if (!$this->getState('modified_on', false))
				{
					$this->product->modified_on = $this->date->toSql();
					$this->product->modified_by = $this->userId;
				}

				// We have a successful save, get the product_id
				return $this->product->store();
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Load the product related tables.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function loadTables()
	{
		// Load the main tables
		$this->product = $this->getTable('Product');
	}

	/**
	 * Cleaning the product related tables.
	 *
	 * @return  void.
	 *
	 * @since   3.0
	 */
	public function clearTables()
	{
		// Clean the main tables
		$this->product->reset();
	}
}
