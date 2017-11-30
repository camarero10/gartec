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
 * Coupon import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportCoupon extends RantaiImportEngine
{
	/**
	 * Coupon table
	 *
	 * @var    VirtueMartTableCoupon
	 * @since  6.0
	 */
	private $couponTable = null;

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
					case 'published':
						switch ($value)
						{
							case 'n':
							case 'N':
							case '0':
								$value = 0;
								break;
							default:
								$value = 1;
								break;
						}

						$this->setState('published', $value);
						break;
					case 'coupon_value':
					case 'coupon_value_valid':
						$this->setState($name, $this->cleanPrice($value));
						break;
					case 'coupon_start_date':
					case 'coupon_expiry_date':
						$this->setState($name, $this->convertDate($value, 'sql', 'user'));
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		if ($this->getState('coupon_code', false))
		{
			// Bind the values
			$this->couponTable->bind($this->state);

			if ($this->couponTable->check())
			{
				$this->setState('virtuemart_coupon_id', $this->couponTable->virtuemart_coupon_id);

				// Check if we have an existing item
				if ($this->getState('virtuemart_coupon_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('coupon_code')));
					$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('coupon_code')));
					$this->loaded = false;
				}
				else
				{
					// Load the current content data
					$this->couponTable->load($this->getState('virtuemart_coupon_id', 0));
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
			if (!$this->getState('virtuemart_coupon_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new coupons when user chooses to ignore new coupons
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('coupon_code')));
			}
			else
			{
				// Set some basic values
				if (!$this->getState('modified_on', false))
				{
					$this->couponTable->modified_on = $this->date->toSql();
					$this->couponTable->modified_by = $this->userId;
				}

				// Add a creating date if there is no product_id
				if (!$this->getState('virtuemart_coupon_id', false))
				{
					$this->couponTable->created_on = $this->date->toSql();
					$this->couponTable->created_by = $this->userId;
				}

				// Bind the data
				$this->couponTable->bind($this->state);

				// Store the data
				if (!$this->couponTable->store())
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_COUPON_NOT_ADDED', $this->couponTable->getError()));
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
		$this->couponTable = $this->getTable('Coupon');
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
		$this->couponTable->reset();
	}
}
