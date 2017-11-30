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
 * Category language table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableCategoryLang extends CsviTableDefault
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
	public function __construct($table, $key, &$db, $config)
	{
		if (isset($config['template']))
		{
			$this->template = $config['template'];
		}

		if ($this->template->get('operation') == 'category')
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

		parent::__construct('#__virtuemart_categories_' . $lang, 'virtuemart_category_id', $db, $config);
	}

	/**
	 * Check if the category ID exists.
	 *
	 * @return  bool  True if ID exists | False if ID is not found.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName($this->_tbl_key))
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName($this->_tbl_key) . ' = ' . (int) $this->virtuemart_category_id);
		$this->db->setQuery($query);
		$id = $this->db->loadResult();

		$this->log->add('Check the category language');

		if (!$id)
		{
			if (empty($this->slug))
			{
				$this->validateSlug();
			}

			if (!empty($this->slug))
			{
				// Create a dummy entry for updating
				$query = "INSERT INTO "
					. $this->_tbl
					. " (" . $this->db->quoteName($this->_tbl_key) . ", " . $this->db->quoteName('slug') . ") "
					. "VALUES (" . (int) $this->virtuemart_category_id . ", " . $this->db->quote($this->slug) . ")";
				$this->db->setQuery($query);
				$this->log->add('Add the category language entry');

				if ($this->db->execute())
				{
					return true;
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
			return true;
		}
	}

	/**
	 * Create a slug if needed and store the product.
	 *
	 * @param   boolean  $updateNulls  True to update fields even if they are null.
	 *
	 * @return  bool  True on success | Fales on failure.
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
		if (empty($this->category_name))
		{
			$jdate = JFactory::getDate();
			$this->slug = $jdate->format("Y-m-d-h-i-s") . mt_rand();
		}
		else
		{
			// Create the slug
			$this->slug = $this->helper->createSlug($this->category_name);

			// Check if the slug exists
			$this->validateSlug();
		}
	}

	/**
	 * Validate slug.
	 *
	 * @return  void.
	 *
	 * @since   4.0
	 */
	private function validateSlug()
	{
		// Create the slug
		$this->slug = $this->helper->createSlug($this->category_name);

		// Check if the slug exists
		$query = $this->db->getQuery(true)
			->select('COUNT(' . $this->db->quoteName($this->_tbl_key) . ')')
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('slug') . ' = ' . $this->db->quote($this->slug))
			->where($this->db->quoteName('virtuemart_category_id') . ' != ' . (int) $this->virtuemart_category_id);
		$this->db->setQuery($query);
		$slugs = $this->db->loadResult();
		$this->log->add('Check the category slug');

		if ($slugs > 0)
		{
			$jdate = JFactory::getDate();
			$this->slug .= $jdate->format("Y-m-d-h-i-s") . mt_rand();
		}
	}

	/**
	 * Reset the primary key.
	 *
	 * @return  array  The field option objects.
	 *
	 * @since   6.0
	 */
	public function reset()
	{
		parent::reset();

		// Empty the primary key
		$this->virtuemart_category_id = null;

		return true;
	}
}
