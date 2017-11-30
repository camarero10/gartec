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
 *  VirtueMart product language table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       4.0
 */
class VirtueMartTableProductLang extends CsviTableDefault
{
	/**
	 * The VirtueMart helper
	 *
	 * @var    Com_VirtuemartHelperCom_Virtuemart
	 * @since  6.0
	 */
	protected $helper = null;

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
		parent::__construct('#__virtuemart_products_' . $config['template']->get('language'), 'virtuemart_product_id', $db, $config);
	}

	/**
	 * Check if the product ID exists.
	 *
	 * @return  bool  True if product ID exists | False if product ID does not exist.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName($this->_tbl_key))
			->select($this->db->quoteName('slug'))
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName($this->_tbl_key) . ' = ' . (int) $this->virtuemart_product_id);
		$this->db->setQuery($query);
		$entry = $this->db->loadObject();
		$this->log->add('Check product language entry');

		if (empty($entry->virtuemart_product_id))
		{
			if (empty($this->slug))
			{
				$this->createSlug();
			}

			if (!empty($this->slug))
			{
				// Check if the slug exists
				$this->verifySlug();

				// Create a dummy entry for updating
				$query = $this->db->getQuery(true)
					->insert($this->db->quoteName($this->_tbl))
					->columns(array($this->db->quoteName($this->_tbl_key), $this->db->quoteName('slug')))
					->values((int) $this->get('virtuemart_product_id') . ', ' . $this->db->quote($this->slug));
				$this->db->setQuery($query);
				$this->log->add('Insert the dummy entry for the product language');

				if ($this->db->execute())
				{
					// Get the last inserted ID
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName($this->_tbl_key))
						->from($this->db->quoteName($this->_tbl))
						->where($this->db->quoteName('slug') . ' = ' . $this->db->quote($this->slug));
					$this->db->setQuery($query);
					$id = $this->db->loadResult();
					$this->virtuemart_product_id = $id;
				}
				else
				{
					return false;
				}
			}
			else
			{
				return false;
			}
		}
		else
		{
			// Keep the existing slug if we have one
			$slug = $this->get('slug', false);

			// Load the existing data
			$this->load($this->get('virtuemart_product_id'));

			// Keep the slug user is importing
			if ($slug)
			{
				$this->set('slug', $slug);
			}

			// Verify the slug
			$this->verifySlug();
		}

		return true;
	}

	/**
	 * Override of the store method to log queries.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  bool  True on success | False on failure.
	 *
	 * @since   6.0
	 */
	public function store($updateNulls = false)
	{
		// Sanitize the product name
		$this->set('product_name', filter_var($this->get('product_name'), FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_ENCODE_LOW));

		return parent::store();
	}

	/**
	 * Create a slug.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	public function createSlug()
	{
		// Is there a product name
		if (empty($this->product_name))
		{
			$jdate = JFactory::getDate();
			$this->slug = $jdate->format("Y-m-d-h-i-s") . mt_rand();
		}
		else
		{
			// Create the slug
			$this->slug = $this->helper->createSlug($this->product_name);

			// Check if the slug exists
			$this->verifySlug();
		}
	}

	/**
	 * Check if a slug exists.
	 *
	 * @return  void.
	 *
	 * @since   5.9
	 */
	private function verifySlug()
	{
		// Check if the slug exists
		$query = $this->db->getQuery(true)
			->select('COUNT(' . $this->db->quoteName($this->_tbl_key) . ')')
			->from($this->_tbl)
			->where($this->db->quoteName('slug') . ' = ' . $this->db->quote($this->slug))
			->where($this->db->quoteName($this->_tbl_key) . ' != ' . (int) $this->virtuemart_product_id);
		$this->db->setQuery($query);
		$slugs = $this->db->loadResult();
		$this->log->add('Check if the product slug exists');

		if ($slugs > 0)
		{
			if ($this->get('product_name'))
			{
				// See how many products we have with the same name
				$query->clear()
					->select(
						'ABS(REPLACE(' . $this->db->quoteName('slug') . ', ' . $this->db->quote($this->slug) . ', ' . $this->db->quote('') . ')) + 1'
					)
					->from($this->_tbl)
					->where(
						'('
						. $this->db->quoteName('slug') . ' = ' . $this->db->quote($this->slug)
						. ' OR '
						. $this->db->quoteName('slug') . ' REGEXP ' . $this->db->quote('^' . $this->slug . '-[[:digit:]]+$')
						. ' AND RIGHT(' . $this->db->quoteName('slug') . ', 1) IN (0,1,2,3,4,5,6,7,8,9)'
						. ')'
					)
					->where($this->db->quoteName($this->_tbl_key) . ' != ' . (int) $this->get('virtuemart_product_id'))
					->order('LENGTH(' . $this->db->quoteName('slug') . '), ' . $this->db->quoteName('slug') . ' ASC');
				$this->db->setQuery($query);
				$items = $this->db->loadColumn();
				$this->log->add('Check how many other products exist with the same slug');

				if ($items)
				{
					$count = array_pop($items);

					$this->slug .= '-' . $count;
				}
			}
			else
			{
				$jdate = JFactory::getDate();
				$this->slug .= $jdate->format("Y-m-d-h-i-s") . mt_rand();
			}
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
		$this->virtuemart_product_id = null;

		return true;
	}
}
