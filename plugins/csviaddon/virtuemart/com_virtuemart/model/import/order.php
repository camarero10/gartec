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
 * Order import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportOrder extends RantaiImportEngine
{
	/**
	 * Order table.
	 *
	 * @var    VirtueMartTableOrder
	 * @since  6.0
	 */
	private $orderTable = null;

	/**
	 * Order user info table.
	 *
	 * @var    VirtueMartTableOrderuserinfo
	 * @since  6.0
	 */
	private $orderUserinfoTable = null;

	/**
	 * Order history.
	 *
	 * @var    VirtueMartTableOrderhistory
	 * @since  6.0
	 */
	private $orderHistoryTable = null;

	/**
	 * Start the product import process.
	 *
	 * @return  bool  True on success | false on failure.
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
					case 'customer_notified':
						$notified = (strtoupper($value) == 'N') ? 0 : 1;
						$this->setState($name, $notified);
						break;
					case 'order_status_name':
						$order_status_code = $this->helper->getOrderStatus($value);
						$this->setState('order_status_code', $order_status_code);
						$this->setState('order_status', $order_status_code);
						break;
					case 'order_total':
					case 'order_subtotal':
					case 'order_tax':
					case 'order_shipment':
					case 'order_shipment_tax':
					case 'order_payment':
					case 'order_payment_tax':
					case 'coupon_discount':
					case 'order_discount':
						$this->setState($name, $this->cleanPrice($value));
						break;
					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Set defaults
		$this->setState('address_type', $this->getState('address_type', 'BT'));

		// All is good
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
		// Load the order user details
		$virtuemart_user_id = $this->getState('virtuemart_user_id', false);
		$email = $this->getState('email', false);

		if (!$virtuemart_user_id && $email)
		{
			$query = $this->db->getQuery(true);
			$query->select($this->db->quoteName('id'));
			$query->from($this->db->quoteName('#__users'));
			$query->where($this->db->quoteName('email') . ' = ' . $this->db->quote($email));
			$this->db->setQuery($query);
			$virtuemart_user_id = $this->db->loadResult();
			$this->setState('virtuemart_user_id', $virtuemart_user_id);
			$this->log->add('COM_CSVI_DEBUG_RETRIEVE_USER_ID', true);
		}

		if ($virtuemart_user_id)
		{
			$query = $this->db->getQuery(true);
			$query->select('*');
			$query->from($this->db->quoteName('#__virtuemart_userinfos'));
			$query->where($this->db->quoteName('address_type') . ' = ' . $this->db->quote('BT'));
			$query->where($this->db->quoteName('virtuemart_user_id') . ' = ' . (int) $virtuemart_user_id);
			$this->db->setQuery($query);
			$userdetails = $this->db->loadObject();
			$this->log->add('COM_CSVI_DEBUG_LOAD_USER_DETAILS', true);
		}
		else
		{
			$this->log->add('COM_CSVI_NOT_PROCESS_USER');
			$this->log->AddStats('incorrect', 'COM_CSVI_NOT_PROCESS_USER');

			return false;
		}

		// Check if we have an order ID
		$virtuemart_order_id = $this->getState('virtuemart_order_id', false);
		$order_number = $this->getState('order_number', false);

		if (!$virtuemart_order_id && $order_number)
		{
			$query = $this->db->getQuery(true);
			$query->select($this->db->quoteName('virtuemart_order_id'));
			$query->from($this->db->quoteName('#__virtuemart_orders'));
			$query->where($this->db->quoteName('order_number') . ' = ' . $this->db->quote($order_number));
			$this->db->setQuery($query);
			$virtuemart_order_id = $this->db->loadResult();
			$this->setState('virtuemart_order_id', $virtuemart_order_id);
			$this->log->add('COM_CSVI_DEBUG_LOAD_ORDER_ID', true);
		}

		// Load the order if there is an order_id
		if (!$virtuemart_order_id || $this->template->get('keepid', false))
		{
			// Add a creating date if there is no order id
			$this->orderTable->created_on = $this->date->toSql();
			$this->orderTable->created_by = $this->userId;

			// Create an order number if it is empty
			if (!$this->getState('order_number', false))
			{
				$this->log->add('COM_CSVI_DEBUG_CREATE_ORDER_NUMBER');
				$this->setState('order_number', substr(md5(session_id() . (string) time() . (string) $virtuemart_user_id), 0, 8));
			}
			else
			{
				$this->log->add('COM_CSVI_DEBUG_NOT_CREATE_ORDER_NUMBER');
			}

			// Create an order pass
			if (!$this->getState('order_pass', false))
			{
				$this->log->add('COM_CSVI_DEBUG_CREATE_ORDER_PASS');
				$this->setState('order_pass', 'p_' . substr(md5(session_id() . (string) time() . (string) $this->getState('order_number')), 0, 6));
			}

			// Check the user currency
			if (!$this->getState('user_currency_id', false) && $this->getState('user_currency', false))
			{
				$query = $this->db->getQuery(true);
				$query->select($this->db->quoteName('virtuemart_currency_id'));
				$query->from($this->db->quoteName('#__virtuemart_currencies'));
				$query->where($this->db->quoteName('currency_code_3') . ' = ' . $this->db->quote($this->getState('user_currency')));
				$this->db->setQuery($query);
				$this->setState('user_currency_id', $this->db->loadResult());
			}

			// Check the currency rate
			if (!$this->getState('user_currency_rate', false))
			{
				$this->setState('user_currency_rate', 1);
			}

			// Check the order currency
			if (!$this->getState('order_currency'))
			{
				$this->setState('order_currency', $this->user_currency_id);
			}

			// Check the user info id
			//if (empty($this->virtuemart_order_id)) $this->virtuemart_order_userinfo_id = $userdetails->virtuemart_userinfo_id;

			// Check the pyament method ID
			if (!$this->getState('virtuemart_paymentmethod_id', false))
			{
				// Handle the payment method ID
				if ($this->getState('payment_element', false))
				{
					$query = $this->db->getQuery(true);
					$query->select($this->db->quoteName('virtuemart_paymentmethod_id'));
					$query->from($this->db->quoteName('#__virtuemart_paymentmethods'));
					$query->where($this->db->quoteName('payment_element') . ' = ' . $this->db->quote($this->getState('payment_element')));
					$this->db->setQuery($query);
					$this->setState('virtuemart_paymentmethod_id', $this->db->loadResult());
					$this->log->add('COM_CSVI_DEBUG_FIND_PAYMENT_METHOD', true);
				}
				else
				{
					$this->setState('virtuemart_paymentmethod_id', 0);
				}
			}

			// Check order payment
			if (!$this->getState('order_payment', false))
			{
				$this->orderTable->order_payment = 0;
			}

			// Check order payment tax
			if (!$this->getState('order_payment_tax', false))
			{
				$this->orderTable->order_payment_tax = 0;
			}

			// Check the order_shipping
			if (!$this->getState('order_shipment', false))
			{
				$this->setState('order_shipment', 0);
			}

			// Check the order_shipping_tax
			if (!$this->getState('order_shipment_tax', false))
			{
				$this->setState('order_shipment_tax', 0);
			}

			// Check the coupon_code
			if (!$this->getState('coupon_code', false))
			{
				$this->setState('coupon_code', '');
			}

			// Check the customer number
			if (!$this->getState('customer_number', false))
			{
				// Retrieve the customer number
				$query = $this->db->getQuery(true)
					->select($this->db->quoteName('customer_number'))
					->from($this->db->quoteName('#__virtuemart_vmusers'))
					->where($this->db->quoteName('virtuemart_user_id') . ' = ' . (int) $this->getState('virtuemart_user_id'))
					->where($this->db->quoteName('virtuemart_vendor_id') . '= ' . (int) $this->getState('virtuemart_vendor_id'));
				$this->db->setQuery($query);

				$this->setState('customer_number', $this->db->loadResult());
			}

			// Check the customer note
			if (!$this->getState('customer_note', false))
			{
				$this->setState('customer_note', '');
			}

			// Check the IP address
			if (!$this->getState('ip_address', false))
			{
				$this->setState('ip_address', $_SERVER['SERVER_ADDR']);
			}

			// Check the ship_method_id
			if (!$this->getState('virtuemart_shipmentmethod_id', false))
			{
				if ($this->getState('shipment_element', false))
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_shipmentmethod_id'))
						->from($this->db->quoteName('#__virtuemart_shipmentmethods'))
						->where($this->db->quoteName('shipment_element') . ' = ' . $this->db->quote($this->getState('shipment_element')));
					$this->db->setQuery($query);
					$this->setState('virtuemart_shipmentmethod_id', $this->db->loadResult());
				}
				else
				{
					$this->setState('virtuemart_shipmentmethod_id', '');
				}
			}

			// Check the delivery date
			if (!$this->getState('delivery_date', false))
			{
				$this->setState('delivery_date', '');
			}

			// Check the order language
			if (!$this->getState('order_language', false))
			{
				$languages = $this->csvihelper->getLanguages();
				$default = 'en-GB';

				if (isset($languages[0]))
				{
					$default = $languages[0]->lang_code;
				}

				$this->setState('order_language', $default);
			}
		}

		// Add the modification date
		if (!$this->getState('modified_on', false))
		{
			$this->orderTable->modified_on = $this->date->toSql();
			$this->orderTable->modified_by = $this->userId;
		}

		// Bind order data
		$this->orderTable->bind($this->state);

		// Check if we use a given order id
		if ($this->template->get('keepid'))
		{
			$this->orderTable->check();
		}

		// Store the order
		if ($this->orderTable->store())
		{
			$this->setState('virtuemart_order_id', $this->orderTable->virtuemart_order_id);
		}
		else
		{
			$this->log->add('COM_CSVI_ORDER_QUERY', true);
			$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_ORDER_NOT_ADDED', $this->orderTable->getError()));

			return false;
		}

		// Store the user info
		if (!$this->getState('virtuemart_order_userinfo_id', false))
		{
			// Check if there is the requested address in the database
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('virtuemart_order_userinfo_id'))
				->from($this->db->quoteName('#__virtuemart_order_userinfos'))
				->where($this->db->quoteName('address_type') . ' = ' . $this->db->quote($this->getState('address_type', 'BT')))
				->where($this->db->quoteName('virtuemart_order_id') . ' = ' . (int) $this->getState('virtuemart_order_id'));
			$this->db->setQuery($query);
			$this->setState('virtuemart_order_userinfo_id', $this->db->loadResult());
			$this->log->add('COM_CSVI_DEBUG_LOAD_ORDER_INFO_ID', true);
		}

		// Load the order info
		if ($this->getState('virtuemart_order_userinfo_id', false))
		{
			$this->orderUserinfoTable->load($this->getState('virtuemart_order_userinfo_id'));
			$this->log->add('COM_CSVI_DEBUG_LOAD_ORDER_INFO', true);

			if (!$this->getState('modified_on', false))
			{
				$this->orderUserinfoTable->modified_on = $this->date->toSql();
				$this->orderUserinfoTable->modified_by = $this->userId;
			}
		}

		if (!$this->getState('virtuemart_order_userinfo_id') || $this->orderUserinfoTable->virtuemart_user_id != $this->getState('virtuemart_user_id'))
		{
			$this->log->add('COM_CSVI_DEBUG_LOAD_USER_ORDER_INFO');

			// Address type name
			if (!$this->getState('address_type_name', false))
			{
				$this->setState('address_type_name', $userdetails->address_type_name);
			}

			// Company
			if (!$this->getState('company', false))
			{
				$this->setState('company', $userdetails->company);
			}

			// Title
			if (!$this->getState('title', false))
			{
				$this->setState('title', $userdetails->title);
			}

			// Last name
			if (!$this->getState('last_name', false))
			{
				$this->setState('last_name', $userdetails->last_name);
			}

			// First name
			if (!$this->getState('first_name', false))
			{
				$this->setState('first_name', $userdetails->first_name);
			}

			// Middle name
			if (!$this->getState('middle_name', false))
			{
				$this->setState('middle_name', $userdetails->middle_name);
			}

			// Phone 1
			if (!$this->getState('phone_1', false))
			{
				$this->setState('phone_1', $userdetails->phone_1);
			}

			// Phone 2
			if (!$this->getState('phone_2', false))
			{
				$this->setState('phone_2', $userdetails->phone_2);
			}

			// Fax
			if (!$this->getState('fax', false))
			{
				$this->setState('fax', $userdetails->fax);
			}

			// Address 1
			if (!$this->getState('address_1', false))
			{
				$this->setState('address_1', $userdetails->address_1);
			}

			// Address 2
			if (!$this->getState('address_2', false))
			{
				$this->setState('address_2', $userdetails->address_2);
			}

			// City
			if (!$this->getState('city', false))
			{
				$this->setState('city', $userdetails->city);
			}

			// State
			if (!$this->getState('virtuemart_state_id', false))
			{
				$state_name = $this->getState('state_name', false);
				$state_2_code = $this->getState('state_2_code', false);
				$state_3_code = $this->getState('state_3_code', false);

				if ($state_name || $state_2_code || $state_3_code)
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_state_id'))
						->from($this->db->quoteName('#__virtuemart_states'));

					if ($state_name)
					{
						$query->where($this->db->quoteName('state_name') . ' = ' . $this->db->quote($state_name));
					}
					elseif ($state_2_code)
					{
						$query->where($this->db->quoteName('state_2_code') . ' = ' . $this->db->quote($state_2_code));
					}
					elseif ($state_3_code)
					{
						$query->where($this->db->quoteName('state_3_code') . ' = ' . $this->db->quote($state_3_code));
					}

					$this->db->setQuery($query);
					$this->setState('virtuemart_state_id', $this->db->loadResult());
				}
				else
				{
					$this->setState('virtuemart_state_id', $userdetails->virtuemart_state_id);
				}
			}

			// Country
			if (!$this->getState('virtuemart_country_id', false))
			{
				$country_name = $this->getState('country_name', false);
				$country_2_code = $this->getState('country_2_code', false);
				$country_3_code = $this->getState('country_3_code', false);

				if ($country_name || $country_2_code || $country_3_code)
				{
					$this->setState('virtuemart_country_id', $this->helper->getCountryId($country_name, $country_2_code, $country_3_code));
				}
				else
				{
					$this->setState('virtuemart_country_id', $userdetails->virtuemart_country_id);
				}
			}

			// Zip
			if (!$this->getState('zip', false))
			{
				$this->setState('zip', $userdetails->zip);
			}

			// Agreed
			if (!$this->getState('agreed', false))
			{
				$this->setState('agreed', 0);
			}

			// Modified date
			if (!$this->getState('modified_on', false))
			{
				$this->orderUserinfoTable->modified_on = $this->date->toSql();
				$this->orderUserinfoTable->modified_by = $this->userId;
			}

			// Created date
			if (!$this->getState('created_on', false))
			{
				$this->orderUserinfoTable->created_on = $this->date->toSql();
				$this->orderUserinfoTable->created_by = $this->userId;
			}
		}

		// Bind the user uploaded data
		$this->orderUserinfoTable->bind($this->state);

		// Store the order user info
		if (!$this->orderUserinfoTable->store())
		{
			$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_ORDERUSER_NOT_ADDED', $this->orderUserinfoTable->getError()));
		}

		// Check if the order has at least a billing address
		if ($this->getStart('address_type', 'BT') == 'ST')
		{
			// Check if there is the requested address in the database
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('virtuemart_order_userinfo_id'))
				->from($this->db->quoteName('#__virtuemart_order_userinfos'))
				->where($this->db->quoteName('address_type') . ' = ' . $this->db->quote('BT'))
				->where($this->db->quoteName('virtuemart_order_id') . ' = ' . (int) $this->virtuemart_order_id);
			$this->db->setQuery($query);
			$bt_order_info_id = $this->db->loadResult();

			// There is no BT address let's add one
			if (!$bt_order_info_id)
			{
				// Get all the fields from the user info table
				$q = "SHOW COLUMNS FROM " . $this->db->quoteName('#__virtuemart_userinfos');
				$this->db->setQuery($q);
				$user_fields_raw = $this->db->loadAssocList();

				$user_fields = array();

				foreach ($user_fields_raw as $user_field)
				{
					$user_fields[] = $user_field['Field'];
				}

				$q = "SHOW COLUMNS FROM " . $this->db->quoteName('#__virtuemart_order_userinfos');
				$this->db->setQuery($q);
				$order_user_fields_raw = $this->db->loadAssocList();

				$order_user_fields = array();

				foreach ($order_user_fields_raw as $user_field)
				{
					$order_user_fields[] = $user_field['Field'];
				}

				$copy_fields = array_intersect($order_user_fields, $user_fields);

				// Create the billing address entry
				$q = "INSERT INTO " . $this->db->quoteName('#__virtuemart_order_userinfos')
					. " (" . implode(',', $copy_fields) . ", " . $this->db->quoteName('virtuemart_order_id') . ")
						(SELECT " . implode(',', $copy_fields) . ", " . $this->getState('virtuemart_order_id') . " AS order_id"
							. " FROM " . $this->db->quoteName('#__virtuemart_userinfos')
							. " WHERE " . $this->db->quoteName('virtuemart_user_id') . " = " . (int) $this->getState('virtuemart_user_id')
							. " AND " . $this->db->quoteName('address_type') . " = " . $this->db->quote('BT') . ")";
				$this->db->setQuery($q)->execute();
				$this->log->add('COM_CSVI_CREATE_BILLING_QUERY', true);
			}
		}

		// Create an order history entry
		// Load the payment info
		if ($this->getState('virtuemart_order_history_id', false))
		{
			$this->orderHistoryTable->load($this->getState('virtuemart_order_history_id'));

			if (!$this->getState('modified_on', false))
			{
				$this->orderHistoryTable->modified_on = $this->date->toSql();
				$this->orderHistoryTable->modified_by = $this->userId;
			}
		}
		else
		{
			if (!$this->getState('modified_on', false))
			{
				$this->orderHistoryTable->modified_on = $this->date->toSql();
				$this->orderHistoryTable->modified_by = $this->userId;
			}

			// Add a creating date if there is no product_id
			$this->orderHistoryTable->created_on = $this->date->toSql();
			$this->orderHistoryTable->created_by = $this->userId;

			if (!$this->getState('customer_notified', false))
			{
				$this->setState('customer_notified', 0);
			}

			// Comments
			$this->orderHistoryTable->comments = '';
		}

		// Bind the payment data
		$this->orderHistoryTable->bind($this->state);

		// Store the order history info
		if (!$this->orderHistoryTable->store())
		{
			$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_ORDER_PAYMNET_NOT_ADDED', $this->orderHistoryTable->getError()));
		}

		return true;
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
		$this->orderTable = $this->getTable('Order');
		$this->orderUserinfoTable = $this->getTable('OrderUserinfo');
		$this->orderHistoryTable = $this->getTable('OrderHistory');
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
		$this->orderTable->reset();
		$this->orderUserinfoTable->reset();
		$this->orderHistoryTable->reset();
	}
}
