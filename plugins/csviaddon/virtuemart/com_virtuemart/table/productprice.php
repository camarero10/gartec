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
 * VirtueMart Product Price table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableProductPrice extends CsviTableDefault
{
	/**
	 * Table constructor.
	 *
	 * @param   string     $table   Name of the database table to model.
	 * @param   string     $key     Name of the primary key field in the table.
	 * @param   JDatabase  &$db     Database driver
	 * @param   array      $config  The configuration parameters array
	 *
	 * @since   4.0
	 */
	public function __construct($table, $key, &$db, $config = array())
	{
		parent::__construct('#__virtuemart_product_prices', 'virtuemart_product_price_id', $db, $config);
	}

	/**
	 * Check if a price already exists
	 *
	 * Criteria for an existing price are:
	 * - product id
	 * - shopper group id
	 * - product currency
	 * - price quantity start
	 * - price quantity end
	 * - publish up
	 * - publish down
	 * If all exists, price will be updated.
	 *
	 * @return  bool  True if price exists | False if price does not exist.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName($this->_tbl_key))
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $this->get('virtuemart_product_id'))
			->where($this->db->quoteName('price_quantity_start') . ' = ' . (int) $this->get('price_quantity_start', 0))
			->where($this->db->quoteName('price_quantity_end') . ' = ' . (int) $this->get('price_quantity_end', 0));

		if ($this->get('virtuemart_shoppergroup_id', false) !== false)
		{
			$query->where($this->db->quoteName('virtuemart_shoppergroup_id') . ' = ' . (int) $this->get('virtuemart_shoppergroup_id'));
		}
		else
		{
			$query->where($this->db->quoteName('virtuemart_shoppergroup_id') . ' IS NULL');
		}

		if ($this->get('product_currency', false))
		{
			$query->where($this->db->quoteName('product_currency') . ' = ' . (int) $this->get('product_currency'));
		}
		else
		{
			$query->where($this->db->quoteName('product_currency') . ' IS NULL');
		}

		if ($this->get('product_price_publish_up', false))
		{
			$query->where($this->db->quoteName('product_price_publish_up') . ' = ' . $this->db->quote($this->get('product_price_publish_up')));
		}
		else
		{
			$query->where($this->db->quoteName('product_price_publish_up') . ' IS NULL');
		}

		if ($this->get('product_price_publish_down', false))
		{
			$query->where($this->db->quoteName('product_price_publish_down') . ' = ' . $this->db->quote($this->get('product_price_publish_down')));
		}
		else
		{
			$query->where($this->db->quoteName('product_price_publish_down') . ' IS NULL');
		}

		$this->db->setQuery($query);
		$this->log->add('Finding a product_price_id');
		$id = $this->db->loadResult();

		if ($id)
		{
			$this->virtuemart_product_price_id = $id;
			$this->load($id);

			return true;
		}
		else
		{
			$this->virtuemart_product_price_id = null;

			return false;
		}
	}

	/**
	 * This function calculates the new price by adding the uploaded value
	 * to the current price
	 *
	 * Prices can be calculated with:
	 * - Add (+)
	 * - Subtract (-)
	 * - Divide (/)
	 * - Multiply (*)
	 *
	 * Add and subtract support percentages.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function CalculatePrice()
	{
		// Get the operation
		$operation = substr($this->product_price, 0, 1);

		if (strstr('+-/*', $operation))
		{
			// Get the price value
			$modify = $this->product_price;

			// Clone the current instance as we don't want the DB values overwrite the uploaded values */
			$newprice = clone $this;

			// Get the current price in the database
			$newprice->check();
			$newprice->load($this->virtuemart_product_price_id);
			$this->virtuemart_product_price_id = $newprice->virtuemart_product_price_id;

			// Set the price to calculate with
			$price = $newprice->product_price;

			// Check if we have a percentage value
			if (substr($modify, -1) == '%')
			{
				$modify = substr($modify, 0, -1);
				$percent = true;
			}
			else
			{
				$percent = false;
			}

			// Get the price value
			$value = substr($modify, 1);

			// Check what modification we need to do and apply it
			switch ($operation)
			{
				case '+':
					if ($percent)
					{
						$price += $price * ($value / 100);
					}
					else
					{
						$price += $value;
					}
					break;
				case '-':
					if ($percent)
					{
						$price -= $price * ($value / 100);
					}
					else
					{
						$price -= $value;
					}
					break;
				case '/':
					$price /= $value;
					break;
				case '*':
					$price *= $value;
					break;
				default:
					// Assign the current price to prevent it being overwritten
					$price = $this->product_price;
					break;
			}

			// Set the new price
			$this->product_price = $price;
		}
	}

	/**
	 * Reset the primary key.
	 *
	 * @return  bool  Always returns true.
	 *
	 * @since   4.0
	 */
	public function reset()
	{
		parent::reset();

		// Empty the primary key
		$this->virtuemart_product_price_id = null;

		return true;
	}
}
