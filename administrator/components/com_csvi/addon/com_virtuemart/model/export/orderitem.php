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
 * Export VirtueMart order items.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelExportOrderitem extends CsviModelExports
{
	/**
	 * Export the data.
	 *
	 * @return  bool  True if body is exported | False if body is not exported.
	 *
	 * @since   6.0
	 */
	protected function exportBody()
	{
		if (parent::exportBody())
		{
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
					case 'created_on':
					case 'delivery_date':
					case 'modified_on':
					case 'locked_on':
					case 'created_by':
					case 'modified_by':
					case 'locked_by':
					case 'virtuemart_order_id':
					case 'order_status':
					case 'virtuemart_vendor_id':
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
					case 'product_sku':
						$userfields[] = $this->db->quoteName('#__virtuemart_order_items.order_item_sku', 'product_sku');

						if (array_key_exists($field->field_name, $groupbyfields))
						{
							$groupby[] = $this->db->quoteName('#__virtuemart_order_items.order_item_sku');
						}

						if (array_key_exists($field->field_name, $sortbyfields))
						{
							$sortby[] = $this->db->quoteName('#__virtuemart_order_items.order_item_sku');
						}
						break;
					case 'full_name':
						$userfields[] = $this->db->quoteName('user_info1.first_name');
						$userfields[] = $this->db->quoteName('user_info1.middle_name');
						$userfields[] = $this->db->quoteName('user_info1.last_name');

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

			// Construct the query
			$userfields = array_unique($userfields);
			$query = $this->db->getQuery(true);
			$query->select(implode(",\n", $userfields));
			$query->from($this->db->quoteName('#__virtuemart_order_items'));
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_orders')
					. ' ON ' . $this->db->quoteName('#__virtuemart_orders.virtuemart_order_id') . ' = ' . $this->db->quoteName('#__virtuemart_order_items.virtuemart_order_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_order_userinfos', 'user_info1')
					. ' ON ' . $this->db->quoteName('user_info1.virtuemart_order_id') . ' = ' . $this->db->quoteName('#__virtuemart_order_items.virtuemart_order_id')
			);
			$query->leftJoin(
					$this->db->quoteName('#__virtuemart_orderstates')
					. ' ON ' . $this->db->quoteName('#__virtuemart_orderstates.order_status_code') . ' = ' . $this->db->quoteName('#__virtuemart_order_items.order_status')
			);

			// Filter by order number start
			$ordernostart = $this->template->get('orderitemnostart', 0, 'int');

			if ($ordernostart > 0)
			{
				$query->where($this->db->quoteName('#__virtuemart_order_items.virtuemart_order_id') . ' >= ' . (int) $ordernostart);
			}

			// Filter by order number end
			$ordernoend = $this->template->get('orderitemnoend', 0, 'int');

			if ($ordernoend > 0)
			{
				$query->where($this->db->quoteName('#__virtuemart_order_items.virtuemart_order_id') . ' <= ' . (int) $ordernoend);
			}

			// Filter by list of order numbers
			$orderlist = $this->template->get('orderitemlist');

			if ($orderlist)
			{
				$query->where($this->db->quoteName('#__virtuemart_order_items.virtuemart_order_id') . ' IN (' . $orderlist . ')');
			}

			// Check for a pre-defined date
			$daterange = $this->template->get('orderitemdaterange', '');

			if ($daterange != '')
			{
				$jdate = JFactory::getDate();

				switch ($daterange)
				{
					case 'lastrun':
						if (substr($this->template->getLastrun(), 0, 4) != '0000')
						{
							$query->where($this->db->quoteName('#__virtuemart_order_items.created_on') . ' > ' . $this->db->quote($this->template->getLastrun()));
						}
						break;
					case 'yesterday':
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') = DATE_SUB(CURDATE(), INTERVAL 1 DAY)');
						break;
					case 'thisweek':
						// Get the current day of the week
						$dayofweek = $jdate->__get('dayofweek');
						$offset = $dayofweek - 1;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= DATE_SUB(CURDATE(), INTERVAL ' . $offset . ' DAY)');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') <= CURDATE()');
						break;
					case 'lastweek':
						// Get the current day of the week
						$dayofweek = $jdate->__get('dayofweek');
						$offset = $dayofweek + 6;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= DATE_SUB(CURDATE(), INTERVAL ' . $offset . ' DAY)');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') <= DATE_SUB(CURDATE(), INTERVAL ' . $dayofweek . ' DAY)');
						break;
					case 'thismonth':
						// Get the current day of the week
						$dayofmonth = $jdate->__get('day');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= DATE_SUB(CURDATE(), INTERVAL ' . $dayofmonth . ' DAY)');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') <= CURDATE()');
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

						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= DATE_SUB(CURDATE(), INTERVAL ' . $offset . ' DAY)');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') <= DATE_SUB(CURDATE(), INTERVAL ' . $dayofmonth . ' DAY)');
						break;
					case 'thisquarter':
						// Find out which quarter we are in
						$month = $jdate->__get('month');
						$year = date('Y');
						$quarter = ceil($month / 3);

						switch ($quarter)
						{
							case '1':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-01-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year . '-04-01'));
								break;
							case '2':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-04-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year . '-07-01'));
								break;
							case '3':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-07-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year . '-10-01'));
								break;
							case '4':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-10-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year++ . '-01-01'));
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
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-01-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year . '-04-01'));
								break;
							case '2':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-04-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year . '-07-01'));
								break;
							case '3':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-07-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year . '-10-01'));
								break;
							case '4':
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-10-01'));
								$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year++ . '-01-01'));
								break;
						}
						break;
					case 'thisyear':
						$year = date('Y');
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-01-01'));
						$year++;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year . '-01-01'));
						break;
					case 'lastyear':
						$year = date('Y');
						$year--;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') >= ' . $this->db->quote($year . '-01-01'));
						$year++;
						$query->where('DATE(' . $this->db->quoteName('#__virtuemart_order_items.created_on') . ') < ' . $this->db->quote($year . '-01-01'));
						break;
				}
			}
			else
			{
				// Filter by order date start
				$orderdatestart = $this->template->get('orderitemdatestart', false);

				if ($orderdatestart)
				{
					$orderdate = JFactory::getDate($orderdatestart);
					$query->where($this->db->quoteName('#__virtuemart_order_items.created_on') . ' >= ' . $this->db->quote($orderdate->toSql()));
				}

				// Filter by order date end
				$orderdateend = $this->template->get('orderitemdateend', false);

				if ($orderdateend)
				{
					$orderdate = JFactory::getDate($orderdateend);
					$query->where($this->db->quoteName('#__virtuemart_order_items.created_on') . ' <= ' . $this->db->quote($orderdate->toSql()));
				}

				// Filter by order modified date start
				$ordermdatestart = $this->template->get('orderitemmdatestart', false);

				if ($ordermdatestart)
				{
					$ordermdate = JFactory::getDate($ordermdatestart);
					$query->where($this->db->quoteName('#__virtuemart_order_items.modified_on') . ' >= ' . $this->db->quote($ordermdate->toSql()));
				}

				// Filter by order modified date end
				$ordermdateend = $this->template->get('orderitemmdateend', false);

				if ($ordermdateend)
				{
					$ordermdate = JFactory::getDate($ordermdateend);
					$query->where($this->db->quoteName('#__virtuemart_order_items.modified_on') . ' <= ' . $this->db->quote($ordermdate->toSql()));
				}
			}

			// Filter by order status
			$orderstatus = $this->template->get('orderitemstatus', false);

			if ($orderstatus && $orderstatus[0] != '')
			{
				$query->where($this->db->quoteName('#__virtuemart_order_items.order_status') . ' IN (\'' . implode("','", $orderstatus) . '\')');
			}

			// Filter by order price start
			$pricestart = $this->template->get('orderitempricestart', false, 'float');

			if ($pricestart)
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.order_total') . ' >= ' . $pricestart);
			}

			// Filter by order price end
			$priceend = $this->template->get('orderitempriceend', false, 'float');

			if ($priceend)
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.order_total') . ' <= ' . $priceend);
			}

			// Filter by order product
			$orderproduct = $this->template->get('orderitemproduct', false);

			if ($orderproduct && $orderproduct[0] != '')
			{
				$query->where($this->db->quoteName('#__virtuemart_order_items.order_item_sku') . ' IN (\'' . implode("','", $orderproduct) . '\')');
			}

			// Filter by order currency
			$ordercurrency = $this->template->get('orderitemcurrency', false);

			if ($ordercurrency && $ordercurrency[0] != '')
			{
				$query->where($this->db->quoteName('#__virtuemart_orders.order_currency') . ' IN (\'' . implode("','", $ordercurrency) . '\')');
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
				while ($record = $this->csvidb->getRow())
				{
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
							case 'created_on':
							case 'modified_on':
							case 'locked_on':
								$date = JFactory::getDate($record->$fieldname);
								$fieldvalue = date($this->template->get('export_date_format'), $date->toUnix());
								break;
							case 'product_item_price':
							case 'product_final_price':
							case 'product_price':
							case 'product_basePriceWithTax':
							case 'product_discountedPriceWithoutTax':
							case 'product_priceWithoutTax':
							case 'product_subtotal_discount':
							case 'product_subtotal_with_tax':
							case 'product_tax':
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
							case 'full_name':
								$fieldvalue = str_replace('  ', ' ', $record->first_name . ' ' . $record->middle_name . ' ' . $record->last_name);
								break;
						}

						// Store the field value
						$this->fields->set($field->csvi_templatefield_id, $fieldvalue);
					}

					// Output the data
					$this->addExportFields();

					// Output the contents
					$this->writeOutput();
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
}
