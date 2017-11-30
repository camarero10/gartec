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
 * Export VirtueMart orders.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportOrder extends CsviModelExports
{
	/**
	 * Cache for shipment elements.
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $shipmentElements = array();

	/**
	 * Export the data.
	 *
	 * @return  bool  True if body is exported | False if body is not exported.
	 *
	 * @throws  CsviException
	 *
	 * @since   6.0
	 */
	protected function exportBody()
	{
		if (parent::exportBody())
		{
			// Check if a language is set
			$language  = $this->template->get('language');

			if (empty($language))
			{
				throw new CsviException(JText::_('COM_CSVI_NO_LANGUAGE_HAS_BEEN_SET'));
			}

			$address = strtoupper($this->template->get('order_address', false));

			if ($address == 'BTST')
			{
				$user_info_fields = $this->dbFields('virtuemart_order_userinfos');
			}
			else
			{
				$user_info_fields = array();
			}

			// Build something fancy to only get the fieldnames the user wants
			$userfields = array();
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
					case 'delivery_date':
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
					case 'payment_name':
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_paymentmethod_id');
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
					case 'email':
						$userfields[] = $this->db->quoteName('user_info1.' . $field->field_name);

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('user_info1.' . $field->field_name);
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('user_info1.' . $field->field_name);
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
					case 'invoice_number':
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');
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
					case 'shipment_name':
					case 'order_weight':
						$userfields[] = $this->db->quoteName('#__virtuemart_shipmentmethods.shipment_element');
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_shipmentmethods.shipment_element');
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_shipmentmethods.shipment_element');
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');
						}
						break;
					case 'customer_note':
						$userfields[] = $this->db->quoteName('user_info1.customer_note');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('user_info1.customer_note');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('user_info1.customer_note');
						}
						break;
					case 'state_2_code':
					case 'state_3_code':
					case 'state_name':
						if ($address == 'BTST')
						{
							$userfields[] = 'COALESCE('
												. $this->db->quoteName('user_info2.virtuemart_state_id') . ', ' . $this->db->quoteName('user_info1.virtuemart_state_id')
											. ') AS ' . $this->db->quoteName('virtuemart_state_id');

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName('virtuemart_state_id');
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName('virtuemart_state_id');
							}
						}
						else
						{
							$userfields[] = $this->db->quoteName('user_info1.virtuemart_state_id');

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName('user_info1.virtuemart_state_id');
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName('user_info1.virtuemart_state_id');
							}
						}
						break;
					case 'country_2_code':
					case 'country_3_code':
					case 'country_name':
					case 'virtuemart_country_id':
						if ($address == 'BTST')
						{
							$userfields[] = 'COALESCE('
												. $this->db->quoteName('user_info2.virtuemart_country_id') . ', ' . $this->db->quoteName('user_info1.virtuemart_country_id')
											. ') AS ' . $this->db->quoteName('virtuemart_country_id');

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName('virtuemart_country_id');
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName('virtuemart_country_id');
							}
						}
						else
						{
							$userfields[] = $this->db->quoteName('user_info1.virtuemart_country_id');

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName('user_info1.virtuemart_country_id');
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName('user_info1.virtuemart_country_id');
							}
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
						$userfields[] = $this->db->quoteName('user_info1.first_name', 'billing_first_name');
						$userfields[] = $this->db->quoteName('user_info1.middle_name', 'billing_middle_name');
						$userfields[] = $this->db->quoteName('user_info1.last_name', 'billing_last_name');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('user_info1.first_name');
							$groupby[] = $this->db->quoteName('user_info1.middle_name');
							$groupby[] = $this->db->quoteName('user_info1.last_name');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('user_info1.first_name');
							$sortby[] = $this->db->quoteName('user_info1.middle_name');
							$sortby[] = $this->db->quoteName('user_info1.last_name');
						}
						break;
					case 'first_name':
						if ($address == 'BTST')
						{
							$userfields[] = 'COALESCE('
								. $this->db->quoteName('user_info2.' . $field->field_name)
								. ', ' . $this->db->quoteName('user_info1.' . $field->field_name)
								. ') AS ' . $this->db->quoteName($field->field_name);

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName($field->field_name);
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName($field->field_name);
							}
						}
						else
						{
							$userfields[] = $this->db->quoteName('user_info1.first_name');

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName('user_info1.first_name');
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName('user_info1.first_name');
							}
						}
						break;
					case 'middle_name':
						if ($address == 'BTST')
						{
							$userfields[] = 'COALESCE('
								. $this->db->quoteName('user_info2.' . $field->field_name)
								. ', ' . $this->db->quoteName('user_info1.' . $field->field_name)
								. ') AS ' . $this->db->quoteName($field->field_name);

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName($field->field_name);
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName($field->field_name);
							}
						}
						else
						{
							$userfields[] = $this->db->quoteName('user_info1.middle_name');

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName('user_info1.middle_name');
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName('user_info1.middle_name');
							}
						}
						break;
					case 'last_name':
						if ($address == 'BTST')
						{
							$userfields[] = 'COALESCE('
								. $this->db->quoteName('user_info2.' . $field->field_name)
								. ', ' . $this->db->quoteName('user_info1.' . $field->field_name)
								. ') AS ' . $this->db->quoteName($field->field_name);

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName($field->field_name);
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName($field->field_name);
							}
						}
						else
						{
							$userfields[] = $this->db->quoteName('user_info1.last_name');

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName('user_info1.last_name');
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName('user_info1.last_name');
							}
						}
						break;
					case 'shipping_full_name':
						if ($address == 'BTST')
						{
							$userfields[] = $this->db->quoteName('user_info2.first_name', 'shipping_first_name');
							$userfields[] = $this->db->quoteName('user_info2.middle_name', 'shipping_middle_name');
							$userfields[] = $this->db->quoteName('user_info2.last_name', 'shipping_last_name');

							if (array_key_exists($field->field_name, $groupbyfields))
							{
								$groupby[] = $this->db->quoteName('user_info2.first_name');
								$groupby[] = $this->db->quoteName('user_info2.middle_name');
								$groupby[] = $this->db->quoteName('user_info2.last_name');
							}

							if (array_key_exists($field->field_name, $sortbyfields))
							{
								$sortby[] = $this->db->quoteName('user_info2.first_name');
								$sortby[] = $this->db->quoteName('user_info2.middle_name');
								$sortby[] = $this->db->quoteName('user_info2.last_name');
							}
						}
						break;
					case 'total_order_items':
						$userfields[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id');
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
					case 'virtuemart_product_id':
						$userfields[] = $this->db->quoteName('#__virtuemart_order_items.virtuemart_product_id');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_order_items.virtuemart_product_id');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_order_items.virtuemart_product_id');
						}
						break;
					case 'custom':
						// These are man made fields, do not try to get them from the database
						break;
					default:
						if ($address == 'BTST' && preg_match("/" . $field->field_name . "/i", join(",", array_keys($user_info_fields))))
						{
							$userfields[] = 'COALESCE('
								. $this->db->quoteName('user_info2.' . $field->field_name)
								. ', ' . $this->db->quoteName('user_info1.' . $field->field_name)
								. ') AS ' . $this->db->quoteName($field->field_name);
						}
						else
						{
							$userfields[] = $this->db->quoteName($field->field_name);
						}

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
					$this->db->quoteName('#__virtuemart_order_userinfos', 'user_info1')
					. ' ON ' . $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id') . ' = ' . $this->db->quoteName('user_info1.virtuemart_order_id')
			);

			if ($address == 'BTST')
			{
				$query->leftJoin(
						$this->db->quoteName('#__virtuemart_order_userinfos', 'user_info2')
						. ' ON ' . $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id') . ' = ' . $this->db->quoteName('user_info2.virtuemart_order_id')
						. ' AND ' . $this->db->quoteName('user_info2.address_type') . ' = ' . $this->db->quote('ST')
				);
			}

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
					. ' ON ' . $this->db->quoteName('#__users.id') . ' = ' . $this->db->quoteName('user_info1.virtuemart_user_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_countries')
					. ' ON ' .
					$this->db->quoteName('#__virtuemart_countries.virtuemart_country_id') . ' = ' . $this->db->quoteName('user_info1.virtuemart_country_id')
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
					$this->db->quoteName('#__virtuemart_shipmentmethods')
					. ' ON ' . $this->db->quoteName('#__virtuemart_orders.virtuemart_shipmentmethod_id') . ' = ' . $this->db->quoteName('#__virtuemart_shipmentmethods.virtuemart_shipmentmethod_id')
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
						$query->where($this->db->quoteName('user_info1.address_type') . ' = ' . $this->db->quote('BT'));
						break;
					default:
						$query->where($this->db->quoteName('user_info1.address_type') . ' = ' . $this->db->quote(strtoupper($address)));
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
				// Check if we need to split the orderline
				$splitLine = $this->getTemplate()->get('splitorderline', 'jform');

				if ($splitLine)
				{
					// Set the order ID
					$orderId = 0;
				}

				while ($record = $this->csvidb->getRow())
				{
					if ($splitLine)
					{
						// Set the order ID
						if ($orderId == 0)
						{
							$orderId = $record->virtuemart_order_id;
							$emptyLine = false;
						}
					}
					else
					{
						$emptyLine = false;
					}

					// Set the log line
					$this->log->incrementLinenumber();

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
								if ($record->virtuemart_paymentmethod_id)
								{
									$query = $this->db->getQuery(true);
									$query->select($fieldname);
									$query->from($this->db->quoteName('#__virtuemart_paymentmethods'));
									$query->where($this->db->quoteName('virtuemart_paymentmethod_id') . ' = ' . (int) $record->virtuemart_paymentmethod_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
								}
								break;
							case 'payment_name':
								if ($record->virtuemart_paymentmethod_id)
								{
									$query = $this->db->getQuery(true);
									$query->select($fieldname);
									$query->from($this->db->quoteName('#__virtuemart_paymentmethods_' . $language));
									$query->where($this->db->quoteName('virtuemart_paymentmethod_id') . ' = ' . (int) $record->virtuemart_shipmentmethod_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
								}
								break;
							case 'shipment_element':
								if ($record->virtuemart_shipmentmethod_id)
								{
									if (!isset($this->shipmentElements[$record->virtuemart_shipmentmethod_id]))
									{
										$query = $this->db->getQuery(true);
										$query->select($this->db->quoteName($fieldname));
										$query->from($this->db->quoteName('#__virtuemart_shipmentmethods'));
										$query->where($this->db->quoteName('virtuemart_shipmentmethod_id') . ' = ' . (int) $record->virtuemart_shipmentmethod_id);
										$this->db->setQuery($query);
										$fieldvalue = $this->db->loadResult();

										$this->shipmentElements[$record->virtuemart_shipmentmethod_id] = $fieldvalue;
									}
									else
									{
										$fieldvalue = $this->shipmentElements[$record->virtuemart_shipmentmethod_id];
									}
								}
								break;
							case 'invoice_number':
								if ($record->virtuemart_order_id)
								{
									$query = $this->db->getQuery(true);
									$query->select($this->db->quoteName($fieldname));
									$query->from($this->db->quoteName('#__virtuemart_invoices'));
									$query->where($this->db->quoteName('virtuemart_order_id') . ' = ' . (int) $record->virtuemart_order_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
								}
								break;
							case 'state_2_code':
							case 'state_3_code':
							case 'state_name':
								if ($record->virtuemart_state_id)
								{
									$query = $this->db->getQuery(true);
									$query->select($this->db->quoteName($fieldname));
									$query->from($this->db->quoteName('#__virtuemart_states'));
									$query->where($this->db->quoteName('virtuemart_state_id') . ' = ' . (int) $record->virtuemart_state_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
								}
								break;
							case 'country_2_code':
							case 'country_3_code':
							case 'country_name':
								if ($record->virtuemart_country_id)
								{
									$query = $this->db->getQuery(true);
									$query->select($this->db->quoteName($fieldname));
									$query->from($this->db->quoteName('#__virtuemart_countries'));
									$query->where($this->db->quoteName('virtuemart_country_id') . ' = ' . (int) $record->virtuemart_country_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
								}
								break;
							case 'user_currency':
								if ($record->user_currency_id)
								{
									$query = $this->db->getQuery(true);
									$query->select($this->db->quoteName('currency_code_3'));
									$query->from($this->db->quoteName('#__virtuemart_currencies'));
									$query->where($this->db->quoteName('virtuemart_currency_id') . ' = ' . (int) $record->user_currency_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
								}
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
								$fieldvalue = str_replace(
									'  ',
									' ',
									$record->billing_first_name . ' ' . $record->billing_middle_name . ' ' . $record->billing_last_name
								);
								break;
							case 'shipping_full_name':
								if ($address == 'BTST')
								{
									$fieldvalue = str_replace(
										'  ',
										' ',
										$record->shipping_first_name . ' ' . $record->shipping_middle_name . ' ' . $record->shipping_last_name
									);
								}
								break;
							case 'total_order_items':
								if ($record->virtuemart_order_id)
								{
									$query = $this->db->getQuery(true);
									$query->select('COUNT(' . $this->db->quoteName('virtuemart_order_id') . ') AS ' . $this->db->quoteName('totalitems'));
									$query->from($this->db->quoteName('#__virtuemart_order_items'));
									$query->where($this->db->quoteName('virtuemart_order_id') . ' = ' . (int) $record->virtuemart_order_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
								}
								break;
							case 'username':
								if ($record->virtuemart_user_id)
								{
									$query = $this->db->getQuery(true);
									$query->select($this->db->quoteName($fieldname));
									$query->from($this->db->quoteName('#__users'));
									$query->where($this->db->quoteName('id') . ' = ' . (int) $record->virtuemart_user_id);
									$this->db->setQuery($query);
									$fieldvalue = $this->db->loadResult();
								}
								break;
							case 'order_tax':
							case 'order_total':
							case 'order_subtotal':
							case 'order_shipment':
							case 'order_shipment_tax':
							case 'order_payment':
							case 'order_payment_tax':
							case 'coupon_discount':
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
								if ($fieldvalue)
								{
									$fieldvalue = number_format(
										$fieldvalue,
										$this->template->get('export_price_format_decimal', 2, 'int'),
										$this->template->get('export_price_format_decsep'),
										$this->template->get('export_price_format_thousep')
									);
								}
								break;
							case 'product_attribute':
								$options = json_decode($fieldvalue);
								$values = array();

								if (is_object($options))
								{
									foreach ($options as $virtuemart_custom_id => $option)
									{
										if (is_object($option))
										{
											foreach ($option as $option_type => $value)
											{
												$title = '';

												if ($option_type)
												{
													// Get the option type name
													$query = $this->db->getQuery(true)
														->select($this->db->quoteName('custom_title'))
														->from($this->db->quoteName('#__virtuemart_product_customfields', 'cf'))
														->leftJoin(
															$this->db->quoteName('#__virtuemart_customs', 'c')
															. ' ON ' . $this->db->quoteName('c.virtuemart_custom_id') . ' = ' . $this->db->quoteName('cf.virtuemart_custom_id')
														)
														->where($this->db->quoteName('virtuemart_customfield_id') . ' = ' . (int) $option_type);
													$this->db->setQuery($query);

													$title = $this->db->loadResult() . ' ';
												}

												if (is_array($value))
												{
													foreach ($value as $option_field => $text)
													{
														// Check if we have a stockable product
														if ($option_type == 'stockable' && $option_field == 'child_id')
														{
															$query = $this->db->getQuery(true);
															$query->select($this->db->quoteName('product_name'));
															$query->from($this->db->quoteName('#__virtuemart_products_' . $language));
															$query->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $text);
															$this->db->setQuery($query);

															$values[] = $title . $this->db->loadResult();
														}
														else
														{
															$values[] = $title . $text;
														}
													}
												}
											}
										}
										else
										{
											// Get the option type name
											$query = $this->db->getQuery(true)
												->select($this->db->quoteName('customfield_value'))
												->from($this->db->quoteName('#__virtuemart_product_customfields', 'cf'))
												->where($this->db->quoteName('virtuemart_customfield_id') . ' = ' . (int) $option);
											$this->db->setQuery($query);

											$title = $this->db->loadResult();

											if ($title)
											{
												$option = $title;
											}

											$values[] = $option;
										}
									}

									$fieldvalue = implode('|', $values);
								}
								break;
							case 'order_weight':
							case 'shipment_name':
								// Set a default value
								$fieldvalue = '';

								// Get the shipping value
								if ($record->shipment_element)
								{
									$query = $this->db->getQuery(true)
										->select($this->db->quoteName($fieldname))
										->from($this->db->quoteName('#__virtuemart_shipment_plg_' . $record->shipment_element))
										->where($this->db->quoteName('virtuemart_order_id') . ' = ' . (int) $record->virtuemart_order_id);
									$this->db->setQuery($query);
									$fieldvalue = strip_tags($this->db->loadResult());
								}

								if ($fieldname == 'order_weight')
								{
									$fieldvalue = number_format(
										$fieldvalue,
										$this->template->get('export_price_format_decimal', 2, 'int'),
										$this->template->get('export_price_format_decsep'),
										$this->template->get('export_price_format_thousep')
									);
								}

								break;
							case 'custom':
								$fieldvalue = $field->default_value;
								break;
						}

						// Store the field value
						$this->fields->set($field->csvi_templatefield_id, $fieldvalue);
					}

					// Output the data
					$this->addExportFields();

					if ($splitLine)
					{
						// Keep track of the order ID
						if ($record->virtuemart_order_id != $orderId)
						{
							$orderId = $record->virtuemart_order_id;
							$emptyLine = true;
						}
					}

					// Output the contents
					$this->writeOutput($emptyLine);

					if ($splitLine)
					{
						$emptyLine = false;
					}
				}
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
	 * Create an SQL filter.
	 *
	 * @param   string  $filter            The type of filter to build.
	 * @param   string  $address           The type of address to use.
	 * @param   array   $user_info_fields  Array of user info fields.
	 *
	 * @return  string  The SQL part to add to the query.
	 *
	 * @since   3.0
	 */
	protected function getQueryFilter($filter, $address, $user_info_fields)
	{
		$fields = array();

		$exportfields = $this->fields->getFields();

		foreach ($exportfields as $field)
		{
			if ($field->enabled)
			{
				switch ($field->field_name)
				{
					case 'custom':
					case 'total_order_items':
					case 'discount_percentage':
					case 'product_price_total':
					case 'full_name':
					case 'order_weight':
					case 'shipping_full_name':
					case 'payment_element':
					case 'shipment_element':
					case 'shipment_name':
					case 'state_2_code':
					case 'state_3_code':
					case 'state_name':
					case 'country_2_code':
					case 'country_3_code':
					case 'country_name':
					case 'user_currency':
					case 'user_email':
					case 'virtuemart_country_id':
						break;
					case 'user_id':
						$fields[] = $this->db->quoteName('#__virtuemart_orders.virtuemart_user_id');
						break;
					case 'product_price':
						$fields[] = $this->db->quoteName('product_item_price');
						break;
					case 'ordering':
						$fields[] = $this->db->quoteName('#__virtuemart_orderstates.ordering');
						break;
					default:
						if ($address == 'BTST' && preg_match("/" . $field->field_name . "/i", join(",", array_keys($user_info_fields))))
						{
							$fields[] = $this->db->quoteName('user_info1.' . $field->field_name);
						}
						else
						{
							$fields[] = $this->db->quoteName($field->field_name);
						}
						break;
				}
			}
		}

		// Construct the SQL part
		if (!empty($fields))
		{
			switch ($filter)
			{
				case 'groupby':
					$groupby_fields = array_unique($fields);
					$q = implode(',', $groupby_fields);
					break;
				case 'sort':
					$sort_fields = array_unique($fields);
					$q = implode(', ', $sort_fields);
					break;
				default:
					$q = '';
					break;
			}
		}
		else
		{
			$q = '';
		}

		return $q;
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
