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
 * Manufacturer language table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableManufacturerLang extends CsviTableDefault
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
		if (isset($config['template']))
		{
			$this->template = $config['template'];
		}

		if ($this->template->get('operation') == 'manufacturer')
		{
			if ($this->template->get('language') == $this->template->get('target_language'))
			{
				$lang = $this->template->get('language');
			}
			else
			{
				$lang = $this->template->get('target_language');
			}
		}
		else
		{
			$lang = $this->template->get('language');
		}

		parent::__construct('#__virtuemart_manufacturers_' . $lang, 'virtuemart_manufacturer_id', $db, $config);
	}

	/**
	 * Check if the manufacturer exists.
	 *
	 * @param   bool  $create  Set to true if manufacturer should be created
	 *
	 * @return  bool  True if manufacturer exists | False if manufacturer does not exist.
	 *
	 * @since   4.0
	 */
	public function check($create = true)
	{
		if (!empty($this->virtuemart_manufacturer_id))
		{
			$query = $this->db->getQuery(true);
			$query->select($this->db->quoteName($this->_tbl_key))
				->from($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName($this->_tbl_key) . ' = ' . (int) $this->virtuemart_manufacturer_id);
			$this->db->setQuery($query);
			$id = $this->db->loadResult();

			if ($id > 0)
			{
				// Load the existing data so we have a slug
				$this->load();
				$this->log->add('Manufacturer already exists', true);

				// Check if the main entry exists
				$query = $this->db->getQuery(true);
				$query->select($this->db->quoteName('virtuemart_manufacturer_id'))
					->from($this->db->quoteName('#__virtuemart_manufacturers'))
					->where($this->db->quoteName('virtuemart_manufacturer_id') . ' = ' . (int) $this->virtuemart_manufacturer_id);
				$this->db->setQuery($query);
				$mf = $this->db->loadObject();

				if (empty($mf))
				{
					$this->log->add('Found a manufacturer language but no manufacturer !!!!!');

					// Not good, no main entry found so let's create one
					$query = $this->db->getQuery(true)
						->insert($this->db->quoteName('#__virtuemart_manufacturers'))
						->columns(array($this->db->quoteName('virtuemart_manufacturer_id')))
						->values($this->db->quote($this->virtuemart_manufacturer_id));
					$this->db->setQuery($query)->execute();
				}

				return true;
			}
			else
			{
				if ($create)
				{
					// Create a dummy entry for updating
					$query->insert($this->db->quoteName($this->_tbl))
						->columns(array($this->db->quoteName($this->_tbl_key)))
						->values($this->db->quote($this->virtuemart_manufacturer_id));
					$this->db->setQuery($query);

					if ($this->db->execute())
					{
						return true;
					}
					else
					{
						$this->log->add('Manufacturer does not exist', true);

						return false;
					}
				}
				else
				{
					$this->log->add('Manufacturer does not exist', true);

					return false;
				}
			}
		}
		else
		{
			// We have no manufacturer ID yet, try to find it
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName($this->_tbl_key))
				->from($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName('mf_name') . ' = ' . $this->db->quote($this->mf_name));
			$this->db->setQuery($query);
			$id = $this->db->loadResult();

			if ($id > 0)
			{
				$this->log->add('Manufacturer exists', true);
				$this->virtuemart_manufacturer_id = $id;
				$this->load();

				return true;
			}
			else
			{
				if (isset($this->mf_name_trans))
				{
					// Check if we can find it by the original name
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName($this->_tbl_key))
						->from($this->db->quoteName('#__virtuemart_manufacturers_' . $this->template->get('language')))
						->where($this->db->quoteName('mf_name') . ' = ' . $this->db->quote($this->mf_name_trans));
					$this->db->setQuery($query);
					$id = $this->db->loadResult();

					if ($id > 0)
					{
						$this->log->add('Manufacturer exists', true);
						$this->virtuemart_manufacturer_id = $id;

						// Create a dummy entry for updating
						$query = $this->db->getQuery(true)
							->insert($this->db->quoteName($this->_tbl))
							->columns(array($this->db->quoteName($this->_tbl_key)))
							->values($this->db->quote($id));
						$this->db->setQuery($query);

						if ($this->db->execute())
						{
							$this->virtuemart_manufacturer_id = $id;
						}

						return true;
					}
				}

				if ($create)
				{
					// Find the highest ID
					$query = $this->db->getQuery(true)
						->select('MAX(' . $this->db->quoteName('virtuemart_manufacturer_id') . ')')
						->from($this->db->quoteName($this->_tbl));
					$this->db->setQuery($query);
					$maxid = $this->db->loadResult();
					$maxid++;

					// Create a dummy entry for updating
					$query = $this->db->getQuery(true)
						->insert($this->db->quoteName($this->_tbl))
						->columns(array($this->db->quoteName($this->_tbl_key)))
						->values($this->db->quote($maxid));
					$this->db->setQuery($query);

					if ($this->db->execute())
					{
						$this->virtuemart_manufacturer_id = $maxid;

						return true;
					}
					else
					{
						$this->log->add('COM_CSVI_DEBUG_MANUFACTURER_NOT_EXISTS', true);

						return false;
					}
				}
				else
				{
					$this->log->add('COM_CSVI_DEBUG_MANUFACTURER_NOT_EXISTS', true);

					return false;
				}
			}
		}
	}

	/**
	 * Create a slug if needed and store the product.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  boolean  True on success | False on failure.
	 *
	 * @since   4.0
	 */
	public function store($updateNulls = false)
	{
		if (empty($this->slug))
		{
			// Create the slug
			$this->validateSlug();
		}

		// Remove the translation name
		unset($this->mf_name_trans);

		return parent::store($updateNulls);
	}

	/**
	 * Validate a slug.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	private function validateSlug()
	{
		// Create the slug
		$this->slug = $this->helper->createSlug($this->mf_name);

		// Check if the slug exists
		$query = $this->db->getQuery(true)
			->select('COUNT(' . $this->db->quoteName($this->_tbl_key) . ')')
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('slug') . ' = ' . $this->db->quote($this->slug));
		$this->db->setQuery($query);
		$slugs = $this->db->loadResult();
		$this->log->add('Check manufacturer slug', true);

		if ($slugs > 0)
		{
			$jdate = JFactory::getDate();
			$this->slug .= $jdate->format("Y-m-d-h-i-s") . mt_rand();
		}
	}

	/**
	 * Reset the primary key.
	 *
	 * @return  boolean  Always returns true.
	 *
	 * @since   6.0
	 */
	public function reset()
	{
		parent::reset();

		// Empty the primary key
		$this->set('virtuemart_manufacturer_id', null);

		return true;
	}
}
