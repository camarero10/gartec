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
 * Order item import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportOrderitem extends RantaiImportEngine
{
	/**
	 * Order item table.
	 *
	 * @var    VirtueMartTableOrderItem
	 * @since  6.0
	 */
	private $orderItemTable = null;

	/**
	 * The Com_VirtuemartHelperCom_Virtuemart helper
	 *
	 * @var    Com_VirtuemartHelperCom_Virtuemart
	 * @since  6.0
	 */
	protected $helper = null;

	/**
	 * Start the product import process.
	 *
	 * @return  bool  Always returns true.
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
					case 'product_price':
						$this->setState('product_item_price', $this->cleanPrice($value));
						break;
					case 'product_item_price':
					case 'product_final_price':
						$this->setState($name, $this->cleanPrice($value));
						break;
					case 'product_sku':
						$this->setState('order_item_sku', $value);
						break;
					case 'product_name':
						$this->setState('order_item_name', $value);
						break;
					case 'created_on':
					case 'modified_on':
						$this->setState($name, $this->convertDate($value));
						break;
					case 'address_type':
						switch (strtolower($name))
						{
							case 'shipping address':
							case 'st':
								$value = 'ST';
								break;
							case 'billing address':
							case 'bt':
							default:
								$value = 'BT';
								break;
						}

						$this->setState($name, $value);
						break;
					case 'order_status_name':
						$this->setState('order_status', $this->helper->getOrderStatus($value));
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Reset loaded state
		$this->loaded = true;

		// Check for product ID
		if (!$this->getState('virtuemart_product_id', false) && $this->getState('order_item_sku', false))
		{
			// Check the virtuemart_product_id
			$this->setState('virtuemart_product_id', $this->helper->getProductId());

			if (!$this->getState('virtuemart_product_id', false))
			{
				$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_NO_PRODUCT_ID_FOUND', $this->getState('order_item_sku')));

				$this->loaded = false;
			}
		}
		elseif ($this->getState('virtuemart_product_id', false) && !$this->getState('order_item_sku', false))
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('product_sku'))
				->from($this->db->quoteName('#__virtuemart_products'))
				->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $this->getState('virtuemart_product_id'));
			$this->db->setQuery($query);
			$this->setState('order_item_sku', $this->db->loadResult());
		}
		else
		{
			$this->log->addStats('incorrect', 'COM_CSVI_NO_PRODUCT_ID_OR_SKU');

			$this->loaded = false;
		}

		// Required fields are virtuemart_order_id, order_item_sku or virtuemart_product_id
		if ($this->getState('virtuemart_order_id', false)
			&& ($this->getState('order_item_sku', false) || $this->getState('virtuemart_product_id', false)))
		{
			// Bind the values
			$this->orderItemTable->bind($this->state);

			if ($this->orderItemTable->check())
			{
				$this->setState('virtuemart_order_item_id', $this->orderItemTable->get('virtuemart_order_item_id'));

				// Check if we have an existing item
				if ($this->getState('virtuemart_order_item_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_ORDERITEM', $this->getState('order_item_sku'), $this->getState('virtuemart_order_id')));
					$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_ORDERITEM', $this->getState('order_item_sku'), $this->getState('virtuemart_order_id')));
					$this->loaded = false;
				}
				else
				{
					// Load the current content data
					$this->orderItemTable->load();
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
			if (!$this->getState('virtuemart_order_item_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new rules when user chooses to ignore new rules
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('order_item_sku')));
			}
			else
			{
				// Are we creating a new order item?
				if (!$this->getState('virtuemart_order_item_id', false))
				{
					// Check for the product_priceWithoutTax field
					$this->setState('product_priceWithoutTax', $this->getState('product_priceWithoutTax', $this->getState('product_item_price', 0)));

					// Check for the product_basepriceWithTax field
					$this->setState('product_basePriceWithTax', $this->getState('product_basePriceWithTax', ($this->getState('product_priceWithoutTax', 0) + $this->getState('product_tax', 0))));

					// Check for the product_discountedPriceWithoutTax field
					$this->setState('product_discountedPriceWithoutTax', $this->getState('product_discountedPriceWithoutTax', $this->getState('product_priceWithoutTax', 0)));

					// Check for the product_subtotal_with_tax field
					$this->setState('product_subtotal_with_tax', $this->getState('product_subtotal_with_tax', ($this->getState('product_final_price', 0) * $this->getState('product_quantity', 1))));

					// Check for product_attribute field
					$this->setState('product_attribute', $this->getState('product_attribute', '[]'));
				}

				// Set the modified date as we are modifying the product
				if (!$this->getState('modified_on', false))
				{
					$this->orderItemTable->modified_on = $this->date->toSql();
					$this->orderItemTable->modified_by = $this->userId;
				}

				if (!$this->getState('virtuemart_order_item_id', false) && !$this->getState('created_on'))
				{
					$this->orderItemTable->created_on = $this->date->toSql();
					$this->orderItemTable->created_by = $this->userId;
				}

				// Check if we have a product name
				if (!$this->orderItemTable->get('order_item_name', false))
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('product_name'))
						->from($this->db->quoteName('#__virtuemart_products_' . $this->template->get('language')))
						->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $this->getState('virtuemart_product_id'));
					$this->db->setQuery($query);
					$this->setState('order_item_name', $this->db->loadResult());
				}

				// Bind the data
				$this->orderItemTable->bind($this->state);

				// Store the data
				if (!$this->orderItemTable->store())
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_ORDER_ITEM_NOT_ADDED', $this->orderItemTable->getError()));
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
		$this->orderItemTable = $this->getTable('OrderItem');
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
		$this->orderItemTable->reset();
	}
}
