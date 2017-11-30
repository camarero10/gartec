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
 * Price import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportPrice extends RantaiImportEngine
{
	/**
	 * Price table.
	 *
	 * @var    VirtueMartTableProductPrice
	 * @since  6.0
	 */
	private $productPriceTable = null;

	/**
	 * The addon helper
	 *
	 * @var    Com_VirtuemartHelperCom_Virtuemart
	 * @since  6.0
	 */
	protected $helper = null;

	/**
	 * CSVI fields
	 *
	 * @var    CsviHelperImportfields
	 * @since  6.0
	 */
	protected $fields = null;

	/**
	 * Start the product import process.
	 *
	 * @return  bool  True on success | false on failure.
	 *
	 * @since   6.0
	 */
	public function getStart()
	{
		$this->setState('virtuemart_product_id', $this->helper->getProductId());
		$this->setState('virtuemart_vendor_id', $this->helper->getVendorId());

		// Process data
		foreach ($this->fields->getData() as $fields)
		{
			foreach ($fields as $name => $details)
			{
				$value = $details->value;

				switch ($name)
				{
					case 'product_price_publish_up':
					case 'product_price_publish_down':
						if (!empty($value))
						{
							$this->setState($name, $this->convertDate($value, 'sql', 'user'));
						}
						break;
					case 'product_currency':
						$this->setState($name, $this->helper->getCurrencyId(strtoupper($value), $this->getState('virtuemart_vendor_id')));
						break;
					case 'product_price':
					case 'product_price_new':
						$this->setState($name, $this->toPeriod($value));
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
		if ($this->getState('product_sku', false)
			&& $this->getState('product_price', false))
		{
			// Get the product ID if we don't already have it
			if (!$this->getState('virtuemart_product_id', false))
			{
				$this->setState('virtuemart_product_id', $this->helper->getProductId());
			}

			/**
			 * Get the shopper group ID
			 *
			 * The shopper group ID takes preference over the shopper group name
			 */
			if (strlen(trim($this->getState('virtuemart_shoppergroup_id', ''))) == 0)
			{
				if (strlen(trim($this->getState('shopper_group_name', ''))) > 0)
				{
					if ($this->getState('shopper_group_name') == '*')
					{
						$this->setState('virtuemart_shoppergroup_id', 0);
					}
					else
					{
						$this->setState('virtuemart_shoppergroup_id', $this->helper->getShopperGroupId($this->getState('shopper_group_name')));
					}
				}
				else
				{
					$this->setState('virtuemart_shoppergroup_id', $this->helper->getDefaultShopperGroupID());
				}
			}

			// Currency check as we need a currency, take VM default currency if not set
			if (!$this->getState('product_currency', false))
			{
				$this->setState('product_currency', $this->helper->getVendorCurrency($this->getState('virtuemart_vendor_id')));
			}

			// Bind the values
			$this->productPriceTable->bind($this->state);

			if ($this->productPriceTable->check())
			{
				$this->setState('virtuemart_product_price_id', $this->productPriceTable->get('virtuemart_product_price_id'));

				// Check if we have an existing item
				if ($this->getState('virtuemart_product_price_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('product_sku')));
					$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('product_sku')));
					$this->loaded = false;
				}
				else
				{
					// Load the current content data
					$this->productPriceTable->load();
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
			if (!$this->getState('virtuemart_product_price_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new rules when user chooses to ignore new rules
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('product_sku')));
			}
			else
			{
				// Let's check for modified and creation dates
				if (!$this->getState('virtuemart_product_price_id', false))
				{
					$this->productPriceTable->created_on = $this->date->toSql();
					$this->productPriceTable->modified_on = $this->date->toSql();

					// Check for some other default fields
					if (!$this->getState('override', false))
					{
						$this->productPriceTable->override = 0;
					}

					if (!$this->getState('product_override_price', false))
					{
						$this->productPriceTable->product_override_price = 0;
					}

					if (!$this->getState('product_tax_id', false))
					{
						$this->productPriceTable->product_tax_id = 0;
					}

					if (!$this->getState('product_discount_id', false))
					{
						$this->productPriceTable->product_discount_id = 0;
					}

					if (!$this->getState('product_price_publish_up', false))
					{
						$this->productPriceTable->product_price_publish_up = '0000-00-00 00:00:00';
					}

					if (!$this->getState('product_price_publish_down', false))
					{
						$this->productPriceTable->product_price_publish_down = '0000-00-00 00:00:00';
					}
				}
				else
				{
					$this->productPriceTable->modified_on = $this->date->toSql();
				}

				// Check if the user wants to delete a price
				if (strtoupper($this->getState('price_delete')) == 'Y')
				{
					if ($this->getState('virtuemart_product_price_id', false))
					{
						$this->productPriceTable->delete($this->getState('virtuemart_product_price_id'));
					}
					else
					{
						$this->log->addStats('incorrect', 'COM_CSVI_PRICE_NOT_DELETED_NO_ID');
					}
				}
				else
				{
					if (!$this->getState('virtuemart_product_id'))
					{
						$this->log->add(JText::sprintf('COM_CSVI_NO_PRODUCT_ID_FOUND', $this->getState('product_sku')));
						$this->log->AddStats('skipped', JText::sprintf('COM_CSVI_NO_PRODUCT_ID_FOUND', $this->getState('product_sku')));
					}
					elseif (!$this->getState('virtuemart_product_price_id', false) && !$this->getState('product_price'))
					{
						$this->log->add('COM_CSVI_NO_PRODUCT_PRICE_FOUND');
						$this->log->AddStats('skipped', 'COM_CSVI_NO_PRODUCT_PRICE_FOUND');
					}
					else
					{
						// Check if we need to change the product price
						if ($this->getState('product_price_new', false))
						{
							$this->productPriceTable->product_price = $this->getState('product_price_new', false);
						}

						// Check if there is an override price
						if ($this->getState('product_override_price'))
						{
							$this->productPriceTable->product_override_price = $this->getState('product_override_price', false);
						}

						// Check if we need to change the shopper group name
						$shopper_group_name_new = $this->getState('shopper_group_name_new', false);

						if ($shopper_group_name_new)
						{
							if ($shopper_group_name_new == '*')
							{
								$this->productPriceTable->virtuemart_shoppergroup_id = 0;
							}
							else
							{
								$this->productPriceTable->virtuemart_shoppergroup_id = $this->helper->getShopperGroupId($shopper_group_name_new);
							}
						}

						// See if there is any calculation needed on the prices
						if ($this->getState('virtuemart_product_price_id'))
						{
							$this->productPriceTable->CalculatePrice();
						}

						// Store the price
						if (!$this->productPriceTable->store())
						{
							$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_MULTIPLE_PRICES_NOT_ADDED', $this->productPriceTable->getError()));
						}
					}
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
		$this->productPriceTable = $this->getTable('ProductPrice');
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
		$this->productPriceTable->reset();
	}
}
