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
 * Shipping method language table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableShipmentmethodLang extends CsviTableDefault
{
	/**
	 * A dummy place to fake a primary key
	 *
	 * @var    int
	 * @since  6.0
	 */
	protected $fakePrimary = null;

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

		if ($this->template->get('operation') == 'shippingrate')
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

		parent::__construct('#__virtuemart_shipmentmethods_' . $lang, 'virtuemart_shipmentmethod_id', $db, $config);
	}

	/**
	 * Get the shipment method ID.
	 *
	 * @return  bool  True if shipment name exists | False if it doesn't exist.
	 *
	 * @since   3.0
	 */
	public function check()
	{
		if (!$this->virtuemart_shipmentmethod_id)
		{
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('virtuemart_shipmentmethod_id'))
				->from($this->db->quoteName('#__virtuemart_shipmentmethods_' . $this->template->get('language')))
				->where($this->db->quoteName('shipment_name') . ' = ' . $this->db->quote($this->shipment_name));
			$this->db->setQuery($query);
			$this->log->add('Check if the shipment method exists', true);
			$id = $this->db->loadResult();

			if ($id)
			{
				$this->virtuemart_shipmentmethod_id = $id;

				return true;
			}
			else
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the shipment method exists.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  bool  True if shipment method language entry exists | False if shipment method language entry does not exist.
	 *
	 * @since   4.0
	 */
	public function store($updateNulls = false)
	{
		if (!empty($this->virtuemart_shipmentmethod_id))
		{
			$query = $this->db->getQuery(true)
				->delete($this->db->quoteName($this->_tbl))
				->where($this->db->quoteName($this->_tbl_key) . ' = ' . (int) $this->virtuemart_shipmentmethod_id);
			$this->db->setQuery($query)->execute();
		}

		// We must change the primary key to something non-existent so the record is inserted
		$this->setKeyName('fakePrimary');

		if (empty($this->slug))
		{
			// Create the slug
			$this->validateSlug();
		}

		$result = parent::store($updateNulls);

		$primaryTable = '#__virtuemart_shipmentmethods_' . $this->template->get('language');

		if ($result && ($this->_tbl !== $primaryTable))
		{
			// Check if the main language has an entry
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('virtuemart_shipmentmethod_id'))
				->from($this->db->quoteName($primaryTable))
				->where($this->db->quoteName('virtuemart_shipmentmethod_id') . ' = ' . (int) $this->virtuemart_shipmentmethod_id);
			$this->db->setQuery($query);

			$id = $this->db->loadResult();

			if (!$id)
			{
				$this->_tbl = '#__virtuemart_shipmentmethods_' . $this->template->get('language');

				parent::store($updateNulls);
			}
		}

		return $result;
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
		$this->slug = $this->helper->createSlug($this->shipment_name);

		// Check if the slug exists
		$query = $this->db->getQuery(true)
			->select('COUNT(' . $this->db->quote($this->_tbl_key) . ')')
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('slug') . ' = ' . $this->db->quote($this->slug));
		$this->db->setQuery($query);
		$slugs = $this->db->loadResult();
		$this->log->add('Check shipment slug', true);

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
		$this->virtuemart_shipmentmethod_id = null;

		return true;
	}
}
