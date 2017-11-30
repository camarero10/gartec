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
 * Category list helper.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class Com_VirtuemartHelperCategoryList
{
	/**
	 * A database connector.
	 *
	 * @var    JDatabase
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * Holds the HTML select list options
	 *
	 * @var    array
	 * @since  6.0
	 */
	private $selectOptions = array();

	/**
	 * Method to instantiate the form field object.
	 *
	 * @param   object  $form  The form to attach to the form field object.
	 *
	 * @since   6.0
	 */
	public function __construct($form = null)
	{
		$this->db = JFactory::getDbo();
	}

	/**
	 * Get category tree.
	 *
	 * @param   string  $language  The language code for the category names.
	 *
	 * @return  array  The list of select options.
	 *
	 * @since   4.0
	 */
	public function getCategoryTree($language)
	{
		// Clean up the language if needed
		$language = strtolower(str_replace('-', '_', $language));

		$query = $this->db->getQuery(true);

		// 1. Get all categories
		$query->select(
			array(
				$this->db->quoteName('x.category_parent_id', 'parent_id'),
				$this->db->quoteName('x.category_child_id', 'id'),
				$this->db->quoteName('l.category_name', 'catname')
			)
		);
		$query->from($this->db->quoteName('#__virtuemart_categories', 'c'));
		$query->leftJoin(
			$this->db->quoteName('#__virtuemart_category_categories', 'x')
			. ' ON ' . $this->db->quoteName('c.virtuemart_category_id') . ' = ' . $this->db->quoteName('x.category_child_id')
		);
		$query->leftJoin(
			$this->db->quoteName('#__virtuemart_categories_' . $language, 'l')
			. ' ON ' . $this->db->quoteName('l.virtuemart_category_id') . ' = ' . $this->db->quoteName('c.virtuemart_category_id')
		);
		$this->db->setQuery($query);
		$rawcats = $this->db->loadObjectList();

		if ($rawcats)
		{
			// 2. Group categories based on their parent_id
			$categories = array();

			foreach ($rawcats as $rawcat)
			{
				$categories[$rawcat->parent_id][$rawcat->id]['pid'] = $rawcat->parent_id;
				$categories[$rawcat->parent_id][$rawcat->id]['cid'] = $rawcat->id;
				$categories[$rawcat->parent_id][$rawcat->id]['catname'] = $rawcat->catname;
			}
		}

		// Free up some memory
		unset($rawcats);

		// Clean the array
		$this->selectOptions = array();

		// Add a don't use option
		$this->selectOptions[] = JHtml::_('select.option', '', JText::_('COM_CSVI_DONT_USE'));

		if (isset($categories))
		{
			if (count($categories) > 0)
			{
				// Take the toplevels first
				foreach ($categories[0] as $category)
				{
					$this->selectOptions[] = JHtml::_('select.option', $category['cid'], $category['catname']);

					// Write the subcategories
					$this->buildCategory($categories, $category['cid'], array());
				}
			}
		}

		// Free up some memory
		unset($categories);

		// Return the select options
		return $this->selectOptions;
	}

	/**
	 * Create the subcategory layout.
	 *
	 * @param   array   $cattree    The list of categories.
	 * @param   string  $catfilter  The category ID to filter on.
	 * @param   array   $subcats    The list of subcategories.
	 * @param   int     $loop       Keeps track of the number of levels.
	 *
	 * @return  array  List of categories.
	 *
	 * @since   3.0
	 */
	private function buildCategory($cattree, $catfilter, $subcats, $loop=1)
	{
		if (isset($cattree[$catfilter]))
		{
			foreach ($cattree[$catfilter] as $subcatid => $category)
			{
				$this->selectOptions[] = JHtml::_('select.option', $category['cid'], str_repeat('>', $loop) . ' ' . $category['catname']);
				$subcats = $this->buildCategory($cattree, $subcatid, $subcats, $loop + 1);
			}
		}
	}
}
