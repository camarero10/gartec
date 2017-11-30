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
 * User info import.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartModelImportUserinfo extends RantaiImportEngine
{
	/**
	 * User info table.
	 *
	 * @var    VirtueMartTableUserinfo
	 * @since  6.0
	 */
	private $userinfoTable = null;

	/**
	 * User shopper group table.
	 *
	 * @var    VirtueMartTableVmuserShoppergroup
	 * @since  6.0
	 */
	private $vmuserShoppergroupTable = null;

	/**
	 * User table.
	 *
	 * @var    VirtueMartTableUser
	 * @since  6.0
	 */
	private $userTable = null;

	/**
	 * VirtueMart user table.
	 *
	 * @var    VirtueMartTableVmuser
	 * @since  6.0
	 */
	private $vmuserTable = null;

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
					case 'address_type':
						switch (strtolower($value))
						{
							case 'shipping address':
							case 'st':
								$address_type = 'ST';
								break;
							case 'billing address':
							case 'bt':
							default:
								$address_type = 'BT';
								break;
						}

						$this->setState('address_type', $address_type);
						break;

					default:
						$this->setState($name, $value);
						break;
				}
			}
		}

		// Set defaults
		$this->setState('address_type', $this->getState('address_type', 'BT'));

		// Reset loaded state
		$this->loaded = true;

		// Required fields
		if ($this->getState('virtuemart_userinfo_id', false)
			|| ($this->getState('virtuemart_user_id', false) && $this->getState('email', false))
			|| ($this->getState('address_type', false) || $this->getState('address_type_name', false)))
		{
			// Check if we have a field ID
			if ($this->getState('virtuemart_userinfo_id', false))
			{
				$this->getVirtuemartUserId();
			}

			// See if we have a user_id or user_email
			if (!$this->getState('virtuemart_user_id', false) && $this->getState('email'))
			{
				// We have an e-mail address, find the user_id
				$query = $this->db->getQuery(true)
					->select($this->db->quoteName('id'))
					->from($this->db->quoteName('#__users'))
					->where($this->db->quoteName('email') . ' = ' . $this->db->quote($this->getState('email')));
				$this->db->setQuery($query);
				$this->log->add('Find the user ID from Joomla');
				$this->setState('virtuemart_user_id', $this->db->loadResult());
			}

			// Bind the values
			$this->userinfoTable->bind($this->state);

			if ($this->userinfoTable->check())
			{
				$this->setState('virtuemart_userinfo_id', $this->userinfoTable->virtuemart_userinfo_id);

				// Check if we have an existing item
				if ($this->getState('virtuemart_userinfo_id', 0) > 0 && !$this->template->get('overwrite_existing_data', true))
				{
					$this->log->add(JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('email')));
					$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_CONTENT', $this->getState('email')));
					$this->loaded = false;
				}
				else
				{
					// Load the current content data
					$this->userinfoTable->load();
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
			if (!$this->getState('virtuemart_userinfo_id', false) && $this->template->get('ignore_non_exist'))
			{
				// Do nothing for new rules when user chooses to ignore new rules
				$this->log->addStats('skipped', JText::sprintf('COM_CSVI_DATA_EXISTS_IGNORE_NEW', $this->getState('email')));

				return false;
			}
			else
			{
				$userdata = array();
				jimport('joomla.user.helper');

				// If it is a new Joomla user but no username is set, we must set one
				if ((!$this->getState('virtuemart_user_id', false) || !$this->getState('virtuemart_user_id')) && !$this->getState('username'))
				{
					$userdata['username'] = $this->getState('email');
				}
				// Set the username
				elseif ($this->getState('username', false))
				{
					$userdata['username'] = $this->getState('username');
				}

				// Check if we have an encrypted password
				if ($this->getState('password_crypt', false))
				{
					$userdata['password'] = $this->getState('password_crypt');
					$this->setState('password', true);
				}
				elseif ($this->getState('password', false))
				{
					// Check if we have an encrypted password
					$userdata['password'] = JUserHelper::hashPassword($this->getState('password'));
				}

				// No user id, need to create a user if possible
				if (!$this->getState('virtuemart_user_id', false)
					&& $this->getState('email', false)
					&& $this->getState('password', false))
				{
					// Set the creation date
					$userdata['registerDate'] = $this->getState('registerDate', $this->date->toSql());

					// Set the basic parameters
					$this->setState('params', $this->getState('params', '{"admin_style":"","admin_language":"","language":"","editor":"","helpsite":"","timezone":""}'));
				}
				elseif (!$this->getState('virtuemart_user_id', false)
					&& (!$this->getState('email', false)
					|| !$this->getState('password', false)))
				{
					$this->log->addStats('incorrect', 'COM_CSVI_NO_NEW_USER_PASSWORD_EMAIL');

					return false;
				}
				else
				{
					// Set the id
					$userdata['id'] = $this->getState('virtuemart_user_id');

					// Load the existing user
					$this->userTable->load($userdata['id']);
				}

				// Only store the Joomla user if there is an e-mail address supplied
				if ($this->getState('email', false))
				{
					// Set the name
					if ($this->getState('name', false))
					{
						$userdata['name'] = $this->getState('name');
					}
					else
					{
						$fullname = false;

						if ($this->getState('first_name', false))
						{
							$fullname .= $this->getState('first_name') . ' ';
						}

						if ($this->getState('last_name', false))
						{
							$fullname .= $this->getState('last_name');
						}

						if (!$fullname)
						{
							$fullname = $this->getState('user_email');
						}

						$userdata['name'] = trim($fullname);
					}

					// Set the email
					$userdata['email'] = $this->getState('email');

					// Set if the user is blocked
					if ($this->getState('block', false))
					{
						$userdata['block'] = $this->getState('block');
					}

					// Set the sendEmail
					if ($this->getState('sendemail', false))
					{
						$userdata['sendEmail'] = $this->getState('sendemail');
					}

					// Set the registerDate
					if ($this->getState('registerDate', false))
					{
						$userdata['registerDate'] = $this->getState('registerDate');
					}

					// Set the lastvisitDate
					if ($this->getState('lastvisitDate', false))
					{
						$userdata['lastvisitDate'] = $this->getState('lastvisitDate');
					}

					// Set the activation
					if ($this->getState('activation', false))
					{
						$userdata['activation'] = $this->getState('activation');
					}

					// Set the params
					if ($this->getState('params', false))
					{
						$userdata['params'] = $this->getState('params');
					}

					// Set the reset count
					if ($this->getState('lastResetTime', false))
					{
						$userdata['lastResetTime'] = $this->getState('lastResetTime');
					}

					// Set the reset count
					if ($this->getState('resetCount', false))
					{
						$userdata['resetCount'] = $this->getState('resetCount');
					}

					// Set the one time key
					if ($this->getState('otpKey', false))
					{
						$userdata['otpKey'] = $this->getState('otpKey');
					}

					// Set the one time emergency key
					if ($this->getState('otep', false))
					{
						$userdata['otep'] = $this->getState('otep');
					}

					// Set the require reset
					if ($this->getState('requireReset', false))
					{
						$userdata['requireReset'] = $this->getState('requireReset');
					}

					// Check if we have a group ID
					if (!$this->getState('group_id', false) && strlen($this->getState('usergroup_name', '')) == 0)
					{
						$this->log->addStats('incorrect', 'COM_CSVI_NO_USERGROUP_NAME_FOUND');

						return false;
					}
					elseif (!$this->getState('group_id', false))
					{
						$query = $this->db->getQuery(true)
							->select($this->db->quoteName('id'))
							->from($this->db->quoteName('#__usergroups'))
							->where($this->db->quoteName('title') . ' = ' . $this->db->quote($this->getState('usergroup_name')));
						$this->db->setQuery($query);
						$this->setState('group_id', $this->db->loadResult());

						if (!$this->getState('group_id', false))
						{
							$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_NO_USERGROUP_FOUND', $this->getState('usergroup_name')));

							return false;
						}
					}

					// Bind the data
					$this->userTable->bind($userdata);

					// Store/update the user
					if ($this->userTable->store())
					{
						$this->log->add('Joomla user stored', false);

						// Get the new user ID
						$this->setState('virtuemart_user_id', $this->userTable->id);

						// Empty the usergroup map table
						$query = $this->db->getQuery(true)
							->delete($this->db->quoteName('#__user_usergroup_map'))
							->where($this->db->quoteName('user_id') . ' = ' . (int) $this->getState('virtuemart_user_id'));
						$this->db->setQuery($query)->execute();
						$this->log->add('Delete old user groups');

						// Store the user in the usergroup map table
						$query = $this->db->getQuery(true)
							->insert($this->db->quoteName('#__user_usergroup_map'))
							->values((int) $this->getState('virtuemart_user_id') . ', ' . (int) $this->getState('group_id'));
						$this->db->setQuery($query);

						// Store the map
						if ($this->db->execute())
						{
							$this->log->add('Joomla user map stored');
						}
						else
						{
							$this->log->add('Joomla user map not stored');
						}
					}
					else
					{
						$this->log->add('Joomla user not found');
					}
				}
				else
				{
					$this->log->add('Joomla user skipped because no email was found');
				}

				// Set the modified date as we are modifying the user
				if (!$this->getState('modified_on', false))
				{
					$this->userinfoTable->modified_on = $this->date->toSql();
					$this->userinfoTable->modified_by = $this->userId;
				}

				// Check for country ID
				if ($this->getState('country', false))
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_country_id'))
						->from($this->db->quoteName('#__virtuemart_countries'))
						->where($this->db->quoteName('country_name') . ' = ' . $this->db->quote($this->getState('country')));
					$this->db->setQuery($query);
					$this->setState('virtuemart_country_id', $this->db->loadResult());
				}

				// Check for state ID
				if ($this->getState('state', false))
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_state_id'))
						->from($this->db->quoteName('#__virtuemart_states'))
						->where($this->db->quoteName('state_name') . ' = ' . $this->db->quote($this->getState('state')));
					$this->db->setQuery($query);
					$this->setState('virtuemart_state_id', $this->db->loadResult());
				}

				// Bind the VirtueMart user data
				$this->userinfoTable->bind($this->state);

				// Store the VirtueMart user info
				if (!$this->userinfoTable->store())
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_USERINFO_NOT_ADDED', $this->userinfoTable->getError()));
				}

				/**
				 * See if there is any shopper group information to be stored
				 * user_id, vendor_id, shopper_group_id, customer number
				 * Get the user_id
				 */
				if (!$this->getState('virtuemart_user_id', false) && $this->userinfoTable->virtuemart_userinfo_id)
				{
					$this->setState('virtuemart_user_id', $this->userinfoTable->virtuemart_user_id);
				}

				$this->log->add('VirtueMart user id: ' . $this->getState('virtuemart_user_id'), false);
				$this->log->add('VirtueMart user info id: ' . $this->userinfoTable->virtuemart_userinfo_id, false);

				// Get the vendor_id
				if (!$this->getState('virtuemart_vendor_id', false) && $this->getState('vendor_name'))
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_vendor_id'))
						->from($this->db->quoteName('#__virtuemart_vendors'))
						->where($this->db->quoteName('vendor_name') . ' = ' . $this->db->quote($this->getState('vendor_name')));
					$this->db->setQuery($query);
					$this->setState('virtuemart_vendor_id', $this->db->loadResult());

					if (!$this->getState('vendor_id', false))
					{
						$this->setState('virtuemart_vendor_id', $this->helper->getVendorId());
					}
				}
				else
				{
					$this->setState('virtuemart_vendor_id', $this->helper->getVendorId());
				}

				// Get the shopper_group_id
				if (!$this->getState('virtuemart_shoppergroup_id', false) && $this->getState('shopper_group_name'))
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('virtuemart_shoppergroup_id'))
						->from($this->db->quoteName('#__virtuemart_shoppergroups'))
						->where($this->db->quoteName('shopper_group_name') . ' = ' . $this->db->quote($this->getState('shopper_group_name')));
					$this->db->setQuery($query);
					$this->setState('virtuemart_shoppergroup_id', $this->db->loadResult());

					if (!$this->getState('virtuemart_shoppergroup_id', false))
					{
						$this->setState('virtuemart_shoppergroup_id', $this->helper->getDefaultShopperGroupID());
					}
				}
				elseif (!$this->getState('virtuemart_shoppergroup_id', false) && !$this->getState('shopper_group_name', false))
				{
					$this->setState('virtuemart_shoppergroup_id', $this->helper->getDefaultShopperGroupID());
				}

				// Bind the shopper group data
				$this->vmuserShoppergroupTable->bind($this->state);
				$this->vmuserShoppergroupTable->check();

				if (!$this->vmuserShoppergroupTable->store())
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_SHOPPER_GROUP_NOT_ADDED', $this->vmuserShoppergroupTable->getError()));
				}

				// See if there is any vmusers entry
				$this->vmuserTable->load($this->getState('virtuemart_user_id'));

				if (empty($this->vmuserTable->virtuemart_user_id))
				{
					if (!$this->getState('user_is_vendor', false))
					{
						$this->setState('user_is_vendor', 0);
					}

					if (!$this->getState('customer_number', false))
					{
						$this->setState('customer_number', md5($userdata['username']));
					}

					if (!$this->getState('perms', false))
					{
						$this->setState('perms', 'shopper');
					}

					if (!$this->getState('virtuemart_paymentmethod_id', false))
					{
						$this->setState('virtuemart_paymentmethod_id', 0);
					}

					if (!$this->getState('virtuemart_shipmentmethod_id', false))
					{
						$this->setState('virtuemart_shipmentmethod_id', 0);
					}

					if (!$this->getState('agreed', false))
					{
						$this->setState('agreed', 0);
					}
				}

				// Bind the data
				$this->vmuserTable->bind($this->state);

				// Check the vmusers table
				if ($this->vmuserTable->check())
				{
					// Update the dates
					if (!$this->modified_on)
					{
						$this->vmuserTable->modified_on = $this->date->toSql();
						$this->vmuserTable->modified_by = $this->userId;
					}
				}
				else
				{
					$this->vmuserTable->created_on = $this->date->toSql();
					$this->vmuserTable->created_by = $this->userId;
				}

				// Store the vmusers data
				if (!$this->vmuserTable->store())
				{
					$this->log->addStats('incorrect', JText::sprintf('COM_CSVI_VMUSERS_NOT_ADDED', $this->vmuserTable->getError()));
				}

				return true;
			}
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
		$this->userinfoTable = $this->getTable('Userinfo');
		$this->vmuserTable = $this->getTable('Vmuser');
		$this->vmuserShoppergroupTable = $this->getTable('VmuserShoppergroup');
		$this->userTable = $this->getTable('User');
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
		$this->userinfoTable->reset();
		$this->vmuserTable->reset();
		$this->vmuserShoppergroupTable->reset();
		$this->userTable->reset();
	}

	/**
	 * Get user ID.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	private function getVirtuemartUserId()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_user_id'))
			->from($this->db->quoteName('#__virtuemart_userinfos'))
			->where($this->db->quoteName('virtuemart_userinfo_id') . ' = ' . (int) $this->getState('virtuemart_userinfo_id'));
		$this->db->setQuery($query);
		$this->log->add('COM_CSVI_DEBUG_FIND_USER_ID_FROM_VM', true);
		$this->setState('virtuemart_user_id', $this->db->loadResult());
	}
}
