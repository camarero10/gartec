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
 * The VirtueMart helper class.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartHelperCom_Virtuemart_Category
{
	/**
	 * Database connector
	 *
	 * @var    JDatabaseDriver
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * Category separator
	 *
	 * @var    string
	 * @since  6.0
	 */
	private $categorySeparator = null;

	/**
	 * Constructor.
	 *
	 * @param   JDatabase $db Database connector.
	 *
	 * @since   4.0
	 */
	public function __construct(JDatabaseDriver $db)
	{
		$this->db = $db;
	}

	/**
	 * Construct the category path.
	 *
	 * @param   array   $catids    The IDs to build a category for.
	 * @param   string  $language  The name of the template language field to use.
	 *
	 * @return  array  An array of category paths.
	 *
	 * @since   4.0
	 */
	private function constructCategoryPath($catids, $language = 'language')
	{
		$catpaths = array();

		if (is_array($catids))
		{
			// Load the category separator
			if (is_null($this->categorySeparator))
			{
				$this->categorySeparator = $this->template->get('category_separator', 'general', '/');
			}

			// Get the paths
			foreach ($catids as $category_id)
			{
				// Create the path
				$paths = array();

				while ($category_id > 0)
				{
					$query = $this->db->getQuery(true)
						->select($this->db->quoteName('category_parent_id', ' l.category_name'))
						->from($this->db->quoteName('#__virtuemart_category_categories', ' x'))
						->leftJoin(
								$this->db->quoteName('#__virtuemart_categories', 'c')
								. ' ON ' . $this->db->quoteName('x.category_child_id') . ' = ' . $this->db->quoteName('c.virtuemart_category_id')
							)
						->leftJoin(
								$this->db->quoteName('#__virtuemart_categories_' . $this->template->get($language), 'l')
								. ' ON ' . $this->db->quoteName('x.category_child_id') . ' = ' . $this->db->quoteName('l.virtuemart_category_id')
							)
						->where($this->db->quoteName('category_child_id') . ' = ' . (int) $category_id);
					$this->db->setQuery($query);
					$path = $this->db->loadObject();
					$this->log->add('Get cat ID' . $category_id);

					if (is_object($path))
					{
						$paths[] = $path->category_name;
						$category_id = $path->category_parent_id;
					}
					else
					{
						$this->log->add('COM_CSVI_CANNOT_GET_CATEGORY_ID');

						return '';
					}
				}

				// Create the path
				$paths = array_reverse($paths);
				$catpaths[] = implode($this->categorySeparator, $paths);
			}
		}

		return $catpaths;
	}

	/**
	 * Create a category path or array of category paths based on the product ID.
	 *
	 * @param   int   $product_id  The ID to create the paths for.
	 * @param   bool  $id          Set if the category IDs should be returned.
	 *
	 * @return  array  An array of category paths.
	 *
	 * @since   3.0
	 */
	public function createCategoryPath($product_id, $id = false)
	{
		$paths = array();

		// Get the category paths
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_category_id'))
			->from($this->db->quoteName('#__virtuemart_product_categories'))
			->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int) $product_id);
		$this->db->setQuery($query);
		$catids = $this->db->loadColumn();

		if (!empty($catids))
		{
			// Return the paths
			if ($id)
			{
				$result = $this->db->loadColumn();

				if (is_array($result))
				{
					$paths = implode('|', $result);
				}
			}
			else
			{
				$catpaths = $this->constructCategoryPath($catids);

				if (is_array($catpaths))
				{
					$paths = implode('|', $catpaths);
				}
			}
		}

		return $paths;
	}

	/**
	 * Create a category path based on ID.
	 *
	 * @param   array  $catids    List of IDs to generate category path for.
	 * @param   string $language  The name of the language selector.
	 *
	 * @return  int  The category ID the product is linked to limited to 1.
	 *
	 * @since   3.0
	 */
	public function createCategoryPathById($catids, $language = 'language')
	{
		if (!is_array($catids))
		{
			$catids = (array)$catids;
		}

		$paths = $this->constructCategoryPath($catids, $language);

		if (is_array($paths))
		{
			return implode('|', $paths);
		}
		else
		{
			return '';
		}
	}

	/**
	 * Get the category ID for a product.
	 *
	 * @param   int  $product_id The product ID to get the category for
	 *
	 * @return  int The category ID the product is linked to limited to 1.
	 *
	 * @since   3.0
	 */
	public function getCategoryId($product_id)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('virtuemart_category_id'))
			->from($this->db->quoteName('#__virtuemart_product_categories'))
			->where($this->db->quoteName('virtuemart_product_id') . ' = ' . (int)$product_id);
		$this->db->setQuery($query, 0, 1);

		return $this->db->loadResult();
	}
}
