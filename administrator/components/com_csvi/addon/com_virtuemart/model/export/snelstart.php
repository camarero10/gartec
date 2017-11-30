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

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/models/exports.php';

/**
 * Export VirtueMart order data for SnelStart.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportSnelstart extends CsviModelExports
{
	/**
	 * Export the data.
	 *
	 * @return  bool  True if body is exported | False if body is not exported.
	 *
	 * @since   6.0
	 *
	 * @throws  CsviException
	 */
	protected function exportBody()
	{
		if (parent::exportBody())
		{
			// Check if we have a language set
			$language = $this->template->get('language', false);

			if (!$language)
			{
				throw new CsviException(JText::_('COM_CSVI_NO_LANGUAGE_SET'));
			}

			$order_discount = 0;
			$coupon_code = '';
			$coupon_discount = '';

			$address = strtoupper($this->template->get('order_address', false));

			// Build something fancy to only get the fieldnames the user wants
			$userfields = array();

			// Order ID is needed as controller
			$userfields[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');

			// Process the other export fields
			$exportfields = $this->fields->getFields();

			// Group by fields
			$groupbyfields = json_decode($this->template->get('groupbyfields', '', 'string'));
			$groupby = array();

			if (isset($groupbyfields->name))
			{
				$groupbyfields = array_flip($groupbyfields->name);
			}
			else
			{
				$groupbyfields = array();
			}

			// Sort selected fields
			$sortfields = json_decode($this->template->get('sortfields', '', 'string'));
			$sortby = array();

			if (isset($sortfields->name))
			{
				$sortbyfields = array_flip($sortfields->name);
			}
			else
			{
				$sortbyfields = array();
			}

			foreach ($exportfields as $field)
			{
				switch ($field->field_name)
				{
					case 'created_by':
					case 'created_on':
					case 'customer_note':
					case 'locked_by':
					case 'locked_on':
					case 'modified_by':
					case 'modified_on':
					case 'order_status':
					case 'virtuemart_user_id':
					case 'virtuemart_vendor_id':
					case 'virtuemart_order_id':
					case 'virtuemart_paymentmethod_id':
					case 'virtuemart_shipmentmethod_id':
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.' . $field->field_name);
						}
						break;
					case 'virtuemart_product_id':
						$userfields[] = $this->db->quoteName('#__virtuemart_order_items.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_order_items.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_order_items.' . $field->field_name);
						}
						break;
					case 'email':
					case 'company':
					case 'title':
					case 'last_name':
					case 'first_name':
					case 'middle_name':
					case 'phone_1':
					case 'phone_2':
					case 'fax':
					case 'address_1':
					case 'address_2':
					case 'city':
					case 'zip':
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('BT') . ' THEN ' . $this->db->quoteName('user_info.' . $field->field_name) . ' ELSE NULL END) AS ' . $this->db->quoteName($field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName($field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName($field->field_name);
						}
						break;
					case 'id':
						$userfields[] = $this->db->quoteName('#__users.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__users.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__users.' . $field->field_name);
						}
						break;
					case 'payment_element':
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_paymentmethod_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_paymentmethod_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_paymentmethod_id');
						}
						break;
					case 'shipment_element':
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_shipmentmethod_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_shipmentmethod_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_shipmentmethod_id');
						}
						break;
					case 'state_2_code':
					case 'state_3_code':
					case 'state_name':
						$userfields[] = $this->db->quoteName('user_info.virtuemart_state_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('user_info.virtuemart_state_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('user_info.virtuemart_state_id');
						}
						break;
					case 'country_2_code':
					case 'country_3_code':
					case 'country_name':
					case 'virtuemart_country_id':
						$userfields[] = $this->db->quoteName('user_info.virtuemart_country_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('user_info.virtuemart_country_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('user_info.virtuemart_country_id');
						}
						break;
					case 'user_currency':
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.user_currency_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.user_currency_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.user_currency_id');
						}
						break;
					case 'username':
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_user_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_user_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_user_id');
						}
						break;
					case 'full_name':
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('BT') . ' THEN ' . $this->db->quoteName('user_info.first_name') . ' ELSE NULL END) AS ' . $this->db->quoteName('first_name');
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('BT') . ' THEN ' . $this->db->quoteName('user_info.middle_name') . ' ELSE NULL END) AS ' . $this->db->quoteName('middle_name');
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('BT') . ' THEN ' . $this->db->quoteName('user_info.last_name') . ' ELSE NULL END) AS ' . $this->db->quoteName('last_name');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('first_name');
							$groupby[] = $this->db->quoteName('middle_name');
							$groupby[] = $this->db->quoteName('last_name');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('first_name');
							$sortby[] = $this->db->quoteName('middle_name');
							$sortby[] = $this->db->quoteName('last_name');
						}
						break;
					case 'product_price_total':
						$userfields[] = 'product_item_price*product_quantity AS product_price_total';

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('product_price_total');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('product_price_total');
						}
						break;
					case 'discount_percentage':
						$userfields[] = '('. $this->db->quoteName('order_discount') .' / ' . $this->db->quoteName('order_total') .') * 100 AS ' . $this->db->quoteName('discount_percentage');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('discount_percentage');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('discount_percentage');
						}
						break;
					case 'product_subtotal_discount_percentage':
						$userfields[] = $this->db->quoteName('#__virtuemart_order_items.product_basePriceWithTax');
						$userfields[] = $this->db->quoteName('#__virtuemart_order_items.product_final_price');
						$userfields[] = $this->db->quoteName('#__virtuemart_order_items.product_subtotal_discount');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_order_items.product_basePriceWithTax');
							$groupby[] = $this->db->quoteName('#__virtuemart_order_items.product_final_price');
							$groupby[] = $this->db->quoteName('#__virtuemart_order_items.product_subtotal_discount');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_order_items.product_basePriceWithTax');
							$sortby[] = $this->db->quoteName('#__virtuemart_order_items.product_final_price');
							$sortby[] = $this->db->quoteName('#__virtuemart_order_items.product_subtotal_discount');
						}
						break;
					case 'shipping_company':
					case 'shipping_title':
					case 'shipping_last_name':
					case 'shipping_first_name':
					case 'shipping_middle_name':
					case 'shipping_phone_1':
					case 'shipping_phone_2':
					case 'shipping_fax':
					case 'shipping_address_1':
					case 'shipping_address_2':
					case 'shipping_city':
					case 'shipping_zip':
					case 'shipping_email':
						$name = str_ireplace('shipping_', '', $field->field_name);
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('ST') . ' THEN ' . $this->db->quoteName('user_info.' . $name) . ' ELSE NULL END) AS ' . $this->db->quoteName($field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName($field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName($field->field_name);
						}
						break;
					case 'shipping_state_name':
					case 'shipping_state_2_code':
					case 'shipping_state_3_code':
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('ST') . ' THEN ' . $this->db->quoteName('user_info.virtuemart_state_id') . ' ELSE NULL END) AS ' . $this->db->quoteName('virtuemart_state_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName($field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName($field->field_name);
						}
						break;
					case 'shipping_country_name':
					case 'shipping_country_2_code':
					case 'shipping_country_3_code':
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('ST') . ' THEN ' . $this->db->quoteName('user_info.virtuemart_country_id') . ' ELSE NULL END) AS ' . $this->db->quoteName('virtuemart_country_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName($field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName($field->field_name);
						}
						break;
					case 'shipping_full_name':
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('ST') . ' THEN ' . $this->db->quoteName('user_info.first_name') . ' ELSE NULL END) AS ' . $this->db->quoteName('shipping_first_name');
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('ST') . ' THEN ' . $this->db->quoteName('user_info.middle_name') . ' ELSE NULL END) AS ' . $this->db->quoteName('shipping_middle_name');
						$userfields[] = 'MAX(CASE WHEN ' . $this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote('ST') . ' THEN ' . $this->db->quoteName('user_info.last_name') . ' ELSE NULL END) AS ' . $this->db->quoteName('shipping_last_name');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('shipping_first_name');
							$groupby[] = $this->db->quoteName('shipping_middle_name');
							$groupby[] = $this->db->quoteName('shipping_last_name');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('shipping_first_name');
							$sortby[] = $this->db->quoteName('shipping_middle_name');
							$sortby[] = $this->db->quoteName('shipping_last_name');
						}
						break;
					case 'coupon_discount':
						// Coupon fields
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.coupon_discount');
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.coupon_code');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.coupon_discount');
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.coupon_code');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.coupon_discount');
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.coupon_code');
						}
						break;
					case 'total_order_items':
					case 'custom':
						// These are man made fields, do not try to get them from the database
						break;
					default:
						$userfields[] = $this->db->quoteName($field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName($field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName($field->field_name);
						}
						break;
				}
			}

			// Build the query
			$userfields = array_unique($userfields);
			$query = $this->db->getQuery(true);
			$query->select(implode(",\n", $userfields));
			$query->from($this->db->quoteName('#__virtuemart_orders'));
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_order_items')
				. ' ON ' . $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id') . ' = ' . $this->db->quoteName('#__virtuemart_order_items.virtuemart_order_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_order_userinfos', 'user_info')
				. ' ON ' . $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id') . ' = ' . $this->db->quoteName('user_info.virtuemart_order_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_orderstates')
				. ' ON ' . $this->db->quoteName('#__virtuemart_orders.order_status') . ' = ' . $this->db->quoteName('#__virtuemart_orderstates.order_status_code')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_product_manufacturers')
				. ' ON ' . $this->db->quoteName('#__virtuemart_order_items.virtuemart_product_id') . ' = ' . $this->db->quoteName('#__virtuemart_product_manufacturers.virtuemart_product_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_manufacturers')
				. ' ON ' . $this->db->quoteName('#__virtuemart_product_manufacturers.virtuemart_manufacturer_id') . ' = '. $this->db->quoteName('#__virtuemart_manufacturers.virtuemart_manufacturer_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__users')
				. ' ON ' . $this->db->quoteName('#__users.id') . ' = ' . $this->db->quoteName('user_info.virtuemart_user_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_countries')
				. ' ON ' .
				$this->db->quoteName('#__virtuemart_countries.virtuemart_country_id') . ' = ' . $this->db->quoteName('user_info.virtuemart_country_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_invoices')
				. ' ON ' . $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id') . ' = ' . $this->db->quoteName('#__virtuemart_invoices.virtuemart_order_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_paymentmethods_' . $language)
				. ' ON ' . $this->db->quoteName('#__virtuemart_orders.virtuemart_paymentmethod_id') . ' = ' . $this->db->quoteName('#__virtuemart_paymentmethods_' . $language . '.virtuemart_paymentmethod_id')
			);
			$query->leftJoin(
				$this->db->quoteName('#__virtuemart_shipmentmethods_' . $language)
				. ' ON ' . $this->db->quoteName('#__virtuemart_orders.virtuemart_shipmentmethod_id') . ' = ' . $this->db->quoteName('#__virtuemart_shipmentmethods_' . $language . '.virtuemart_shipmentmethod_id')
			);

			// Filter by manufacturer
			$manufacturer = $this->template->get('ordermanufacturer', false);

			if ($manufacturer && $manufacturer[0] != 'none')
			{
				$query->where($this->db->quoteName('#__virtuemart_manufacturers.virtuemart_manufacturer_id') . ' IN (' . implode(',', $manufacturer) . ')');
			}

			// Filter by order number start
			$ordernostart = $this->template->get('ordernostart', 0, 'int');

			if ($ordernostart > 0)
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.virtuemart_order_id') . ' >= ' . (int) $ordernostart);
			}

			// Filter by order number end
			$ordernoend = $this->template->get('ordernoend', 0, 'int');

			if ($ordernoend > 0)
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.virtuemart_order_id') . ' <= ' . (int) $ordernoend);
			}

			// Filter by list of order numbers
			$orderlist = $this->template->get('orderlist');

			if ($orderlist)
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.virtuemart_order_id') . ' IN (' . $orderlist . ')');
			}

			// Check for a pre-defined date
			$daterange = $this->template->get('orderdaterange', '');

			if ($daterange != '')
			{
				$jdate = JFactory::getDate();

				switch ($daterange)
				{
					case 'lastrun':
						if (substr($this->template->getLastrun(), 0, 4) != '0000')
						{
							$query->where($this->db->quoteName('#__virtuemart_orders.created_on') . ' > ' . $this->db->quote($this->template->getLastrun()));
						}
						break;
					case 'yesterday':
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') = DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
						break;
					case 'thisweek':
						// Get the current day of the week
						$dayofweek = $jdate->__get('dayofweek');
						$offset = $dayofweek - 1;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= DATE_SUB(CURDATE(), INTERVAL ' . $offset . ' DAY)');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') <= CURDATE()');
						break;
					case 'lastweek':
						// Get the current day of the week
						$dayofweek = $jdate->__get('dayofweek');
						$offset = $dayofweek + 6;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= DATE_SUB(CURDATE(), INTERVAL ' . $offset . ' DAY)');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') <= DATE_SUB(CURDATE(), INTERVAL ' . $dayofweek . ' DAY)');
						break;
					case 'thismonth':
						// Get the current day of the week
						$dayofmonth = $jdate->__get('day');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= DATE_SUB(CURDATE(), INTERVAL ' . $dayofmonth . ' DAY)');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') <= CURDATE()');
						break;
					case 'lastmonth':
						// Get the current day of the week
						$dayofmonth = $jdate->__get('day');
						$month = date('n');
						$year = date('y');

						if ($month > 1)
						{
							$month--;
						}
						else
						{
							$month = 12;
							$year--;
						}

						$daysinmonth = date('t', mktime(0, 0, 0, $month, 25, $year));
						$offset = ($daysinmonth + $dayofmonth) - 1;

						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= DATE_SUB(CURDATE(), INTERVAL ' . $offset . ' DAY)');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') <= DATE_SUB(CURDATE(), INTERVAL ' . $dayofmonth . ' DAY)');
						break;
					case 'thisquarter':
						// Find out which quarter we are in
						$month = $jdate->__get('month');
						$year = date('Y');
						$quarter = ceil($month / 3);

						switch ($quarter)
						{
							case '1':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-01-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year . '-04-01'));
								break;
							case '2':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-04-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year . '-07-01'));
								break;
							case '3':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-07-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year . '-10-01'));
								break;
							case '4':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-10-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year++ . '-01-01'));
								break;
						}
						break;
					case 'lastquarter':
						// Find out which quarter we are in
						$month = $jdate->__get('month');
						$year = date('Y');
						$quarter = ceil($month / 3);

						if ($quarter == 1)
						{
							$quarter = 4;
							$year--;
						}
						else
						{
							$quarter--;
						}

						switch ($quarter)
						{
							case '1':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-01-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year . '-04-01'));
								break;
							case '2':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-04-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year . '-07-01'));
								break;
							case '3':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-07-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year . '-10-01'));
								break;
							case '4':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-10-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year++ . '-01-01'));
								break;
						}
						break;
					case 'thisyear':
						$year = date('Y');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-01-01'));
						$year++;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year . '-01-01'));
						break;
					case 'lastyear':
						$year = date('Y');
						$year--;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') >= ' . $this->db->quote($year . '-01-01'));
						$year++;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_orders.created_on') . ') < ' . $this->db->quote($year . '-01-01'));
						break;
				}
			}
			else
			{
				// Filter by order date start
				$orderdatestart = $this->template->get('orderdatestart', false);

				if ($orderdatestart)
				{
					$orderdate = JFactory::getDate($orderdatestart);
					$query->where($this->db->quoteName('#__virtuemart_orders') . '.' . $this->db->quoteName('created_on') . ' >= ' . $this->db->quote($orderdate->toSql()));
				}

				// Filter by order date end
				$orderdateend = $this->template->get('orderdateend', false);

				if ($orderdateend)
				{
					$orderdate = JFactory::getDate($orderdateend);
					$query->where($this->db->quoteName('#__virtuemart_orders') . '.' . $this->db->quoteName('created_on') . ' <= ' . $this->db->quote($orderdate->toSql()));
				}

				// Filter by order modified date start
				$ordermdatestart = $this->template->get('ordermdatestart', false);

				if ($ordermdatestart)
				{
					$ordermdate = JFactory::getDate($ordermdatestart);
					$query->where($this->db->quoteName('#__virtuemart_orders') . '.' . $this->db->quoteName('modified_on') . ' >= ' . $this->db->quote($ordermdate->toSql()));
				}

				// Filter by order modified date end
				$ordermdateend = $this->template->get('ordermdateend', false);

				if ($ordermdateend)
				{
					$ordermdate = JFactory::getDate($ordermdateend);
					$query->where($this->db->quoteName('#__virtuemart_orders') . '.' . $this->db->quoteName('modified_on') . ' <= ' . $this->db->quote($ordermdate->toSql()));
				}
			}

			// Filter by order status
			$orderstatus = $this->template->get('orderstatus', false);

			if ($orderstatus && $orderstatus[0] != '')
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.order_status') . ' IN (\'' . implode("','", $orderstatus) . '\')');
			}

			// Filter by order price start
			$pricestart = $this->template->get('orderpricestart', false, 'float');

			if ($pricestart)
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.order_total') . ' >= ' . $pricestart);
			}

			// Filter by order price end
			$priceend = $this->template->get('orderpriceend', false, 'float');

			if ($priceend)
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.order_total') . ' <= ' . $priceend);
			}

			// Filter by order user id
			$orderuser = $this->template->get('orderuser', false);

			if ($orderuser && $orderuser[0] != '')
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.virtuemart_user_id') . ' IN (\'' . implode("','", $orderuser) . '\')');
			}

			// Filter by order product
			$orderproduct = $this->template->get('orderproduct', false);

			if ($orderproduct && $orderproduct[0] != '')
			{
				$query->where($this->db->quoteName('#__virtuemart_order_items.order_item_sku') . ' IN (\'' . implode("','", $orderproduct) . '\')');
			}

			// Filter by address type
			if ($address)
			{
				switch (strtoupper($address))
				{
					case 'BTST':
						$query->where($this->db->quoteName('user_info.address_type') . ' IN (' . $this->db->quote('BT') . ', ' . $this->db->quote('ST') . ')');
						break;
					default:
						$query->where($this->db->quoteName('user_info.address_type') . ' = ' . $this->db->quote(strtoupper($address)));
						break;
				}
			}

			// Filter by order currency
			$ordercurrency = $this->template->get('ordercurrency', false);

			if ($ordercurrency && $ordercurrency[0] != '')
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.order_currency') . ' IN (\'' . implode("','", $ordercurrency) . '\')');
			}

			// Filter by payment method
			$orderpayment = $this->template->get('orderpayment', false);

			if ($orderpayment && $orderpayment[0] != '')
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.virtuemart_paymentmethod_id') . ' IN (\'' . implode("','", $orderpayment) . '\')');
			}

			// Group the fields
			$groupby = array_unique($groupby);

			if (!empty($groupby))
			{
				$query->group($groupby);
			}

			// Sort set fields
			$sortby = array_unique($sortby);

			if (!empty($sortby))
			{
				$query->order($sortby);
			}

			// Add export limits
			$limits = $this->getExportLimit();

			// Execute the query
			$this->csvidb->setQuery($query, $limits['offset'], $limits['limit']);
			$this->log->add('Export query' . $query->__toString(), false);

			// Check if there are any records
			$logcount = $this->csvidb->getNumRows();

			if ($logcount > 0)
			{
				$orderid = null;

				while ($record = $this->csvidb->getRow())
				{
					$this->log->incrementLinenumber();

					// Add an order
					if (is_null($orderid) || $record->virtuemart_order_id != $orderid)
					{
						if (!is_null($orderid))
						{
							// Output the contents
							$this->addExportContent($this->exportclass->NodeEnd());
							$this->writeOutput();
						}

						$orderid = $record->virtuemart_order_id;
						$this->addExportContent($this->exportclass->Order());
					}

					// Add an orderline
					$this->addExportContent($this->exportclass->Orderline());

					foreach ($exportfields as $field)
					{
						$fieldname = $field->field_name;

						// Set the field value
						if (isset($record->$fieldname))
						{
							$fieldvalue = $record->$fieldname;
						}
						else
						{
							$fieldvalue = '';
						}

						// Process the field
						switch ($fieldname)
						{
							case 'payment_element':
								$query = $this->db->getQuery(true);
								$query->select($fieldname);
								$query->from($this->db->quoteName('#__virtuemart_paymentmethods'));
								$query->where($this->db->quoteName('virtuemart_paymentmethod_id') . ' = ' . (int) $record->virtuemart_paymentmethod_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'shipment_element':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName($fieldname));
								$query->from($this->db->quoteName('#__virtuemart_shipmentmethods'));
								$query->where($this->db->quoteName('virtuemart_shipmentmethod_id') . ' = ' . (int) $record->virtuemart_shipmentmethod_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'state_2_code':
							case 'state_3_code':
							case 'state_name':
							case 'shipping_state_2_code':
							case 'shipping_state_3_code':
							case 'shipping_state_name':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName(str_ireplace('shipping_', '', $fieldname)));
								$query->from($this->db->quoteName('#__virtuemart_states'));
								$query->where($this->db->quoteName('virtuemart_state_id') . ' = ' . (int) $record->virtuemart_state_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'country_2_code':
							case 'country_3_code':
							case 'country_name':
							case 'shipping_country_2_code':
							case 'shipping_country_3_code':
							case 'shipping_country_name':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName(str_ireplace('shipping_', '', $fieldname)));
								$query->from($this->db->quoteName('#__virtuemart_countries'));
								$query->where($this->db->quoteName('virtuemart_country_id') . ' = ' . (int) $record->virtuemart_country_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'user_currency':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName('currency_code_3'));
								$query->from($this->db->quoteName('#__virtuemart_currencies'));
								$query->where($this->db->quoteName('virtuemart_currency_id') . ' = ' . (int) $record->user_currency_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'user_email':
								$fieldvalue = $record->email;
								break;
							case 'user_id':
								$fieldvalue = $record->virtuemart_user_id;
								break;
							case 'created_on':
							case 'modified_on':
							case 'locked_on':
								$date = JFactory::getDate($record->$fieldname);
								$fieldvalue = date($this->template->get('export_date_format'), $date->toUnix());
								break;
							case 'address_type':
								// Check if we have any content otherwise use the default value
								if (strlen(trim($fieldvalue)) == 0)
								{
									$fieldvalue = $field->default_value;
								}

								if ($fieldvalue == 'BT')
								{
									$fieldvalue = JText::_('COM_CSVI_BILLING_ADDRESS');
								}
								elseif ($fieldvalue == 'ST')
								{
									$fieldvalue = JText::_('COM_CSVI_SHIPPING_ADDRESS');
								}
								break;
							case 'full_name':
								$fieldvalue = str_replace('  ', ' ', $record->first_name . ' ' . $record->middle_name . ' ' . $record->last_name);
								break;
							case 'shipping_full_name':
								$fieldvalue = str_replace('  ', ' ', $record->shipping_first_name . ' ' . $record->shipping_middle_name . ' ' . $record->shipping_last_name);

								if (empty($fieldvalue))
								{
									$fieldvalue = str_replace('  ', ' ', $record->first_name . ' ' . $record->middle_name . ' ' . $record->last_name);
								}
								break;
							case 'total_order_items':
								$query = $this->db->getQuery(true);
								$query->select('COUNT(' . $this->db->quoteName('virtuemart_order_id') . ') AS ' . $this->db->quoteName('totalitems'));
								$query->from($this->db->quoteName('#__virtuemart_order_items'));
								$query->where($this->db->quoteName('virtuemart_order_id') . ' = ' . (int) $record->virtuemart_order_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'username':
								$query = $this->db->getQuery(true);
								$query->select($this->db->quoteName($fieldname));
								$query->from($this->db->quoteName('#__users'));
								$query->where($this->db->quoteName('id') . ' = ' . (int) $record->virtuemart_user_id);
								$this->db->setQuery($query);
								$fieldvalue = $this->db->loadResult();
								break;
							case 'order_tax':
							case 'order_total':
							case 'order_subtotal':
							case 'order_shipment':
							case 'order_shipment_tax':
							case 'order_payment':
							case 'order_payment_tax':
							case 'order_discount':
							case 'user_currency_rate':
							case 'product_price_total':
							case 'discount_percentage':
							case 'product_item_price':
							case 'product_tax':
							case 'product_basePriceWithTax':
							case 'product_final_price':
							case 'product_subtotal_discount':
							case 'product_subtotal_with_tax':
								if (!empty($fieldvalue))
								{
									$fieldvalue = number_format(
										$fieldvalue,
										$this->template->get('export_price_format_decimal', 2, 'int'),
										$this->template->get('export_price_format_decsep'),
										$this->template->get('export_price_format_thousep')
									);
								}
								break;
							case 'product_subtotal_discount_percentage':
								if ($record->product_basePriceWithTax > 0)
								{
									$fieldvalue = number_format(
										($record->product_subtotal_discount / $record->product_basePriceWithTax * 100) * -1, 3
									);
								}
								else
								{
									if ($record->product_subtotal_discount && $record->product_final_price)
									{
										$fieldvalue = number_format(
											($record->product_subtotal_discount / $record->product_final_price * 100) * -1, 3
										);
									}
								}
								break;
							case 'order_discountAmount':
								// Set some discounts
								$order_discount = number_format($fieldvalue * -1, 3);
								$fieldvalue = null;
								break;
							case 'coupon_discount':
								// For coupon field usage
								if ($record->coupon_discount > 0)
								{
									$coupon_code = $record->coupon_code;
									$coupon_discount = number_format($record->coupon_discount * -1, 3);
								}

								$fieldvalue = number_format(
									$fieldvalue,
									$this->template->get('export_price_format_decimal', 2, 'int'),
									$this->template->get('export_price_format_decsep'),
									$this->template->get('export_price_format_thousep')
								);
								break;
						}

						// Store the field value
						$this->fields->set($field->csvi_templatefield_id, $fieldvalue);
					}

					// Output the data
					$this->addExportFields(false);

					// Add the order discount row
					if (!empty($order_discount))
					{
						foreach ($this->fields as $field)
						{
							if ($field->enabled)
							{
								$value = null;

								switch ($field->field_name)
								{
									case 'order_item_sku':
										$value = 'Korting';
										break;
									case 'order_item_name':
										$value = 'Korting';
										break;
									case 'product_quantity':
										$value = '1';
										break;
									case 'product_item_price':
										$value = ($order_discount) ? $order_discount : 0;
										$order_discount = 0;
										break;
								}

								// Store the field value
								$this->fields->set($field->xml_node, $value);
							}
						}

						$this->addExportContent($this->exportclass->Orderline());
						$this->addExportFields(false);
					}

					// Add the coupon discount
					if (!empty($coupon_code) && !empty($coupon_discount))
					{
						foreach ($this->fields as $field)
						{
							$value = null;

							switch ($field->field_name)
							{
								case 'order_item_sku':
									$value = $coupon_code;
									break;
								case 'order_item_name':
									$value = $coupon_code;
									break;
								case 'product_quantity':
									$value = '1';
									break;
								case 'product_item_price':
									$value = ($coupon_discount) ? $coupon_discount : 0;
									break;
							}

							// Store the field value
							$this->fields->set($field->xml_node, $value);
						}

						$this->addExportContent($this->exportclass->Orderline());
						$this->addExportFields(false);
					}
				}

				// Close the XML structure
				$this->addExportContent($this->exportclass->NodeEnd());
				$this->writeOutput();
			}
			else
			{
				$this->addExportContent(JText::_('COM_CSVI_NO_DATA_FOUND'));

				// Output the contents
				$this->writeOutput();
			}
		}
	}

	/**
	 * Creates an array of custom database fields the user can use for import/export.
	 *
	 * @param   string  $table  The table to get the fields for.
	 *
	 * @return  array  List of custom database fields.
	 *
	 * @since   3.0
	 */
	private function dbFields($table)
	{
		$customfields = array();
		$q = 'SHOW COLUMNS FROM ' . $this->db->quoteName('#__' . $table);
		$this->db->setQuery($q);
		$fields = $this->db->loadObjectList();

		if (count($fields) > 0)
		{
			foreach ($fields as $field)
			{
				$customfields[$field->Field] = null;
			}
		}

		return $customfields;
	}
}
