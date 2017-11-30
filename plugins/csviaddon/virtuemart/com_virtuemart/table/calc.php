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
 * Calculation rule table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableCalc extends CsviTableDefault
{
	/**
	 * Category IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $categoryIds = array();

	/**
	 * Country IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $countryIds = array();

	/**
	 * Manufacturer IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $manufacturerIds = array();

	/**
	 * Shopper group IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $shoppergroupIds = array();

	/**
	 * State IDs
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $stateIds = array();

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
		parent::__construct('#__virtuemart_calcs', 'virtuemart_calc_id', $db, $config);
	}

	/**
	 * Check if a discount already exists. If so, retrieve the discount ID.
	 *
	 * @return  bool  Returns true if rule ID has been found | False if no rule ID has been found.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		if ($this->calc_value)
		{
			// Define the limits
			if (strstr($this->calc_value, '.'))
			{
				list($main, $decimal) = explode('.', $this->calc_value);

				switch (strlen($decimal))
				{
					case '1':
						$modify = 0.1;
						break;
					case '2':
						$modify = 0.01;
						break;
					case '3':
						$modify = 0.001;
						break;
					case '4':
						$modify = 0.0001;
						break;
					case '5':
						$modify = 0.00001;
						break;
					default:
						$modify = 0;
				}
			}
			else
			{
				$modify = 0;
			}

			// Check if the amount exists in the database
			$query = $this->db->getQuery(true)
				->select($this->db->quoteName('c.' . $this->_tbl_key))
				->from($this->db->quoteName($this->_tbl, 'c'))
				->where($this->db->quoteName('calc_kind') . ' = ' . $this->db->quote($this->calc_kind))
				->where($this->db->quoteName('calc_value_mathop') . ' = ' . $this->db->quote($this->calc_value_mathop))
				->where(
					$this->db->quoteName('calc_value')
					. ' BETWEEN ' . $this->db->quote(($this->calc_value - $modify)) . ' AND ' . $this->db->quote(($this->calc_value + $modify))
			);

			if (!empty($this->publish_up))
			{
				if ($this->db->getNullDate() == $this->publish_up)
				{
					$query->where(
						'(' . $this->db->quoteName('publish_up') . ' = ' . $this->db->quote($this->publish_up) .
						' OR ' . $this->db->quoteName('publish_up') . ' = ' . $this->db->quote('1970-01-01 00:00:00') . ')');
				}
				else
				{
					$query->where($this->db->quoteName('publish_up') . ' = ' . $this->db->quote($this->publish_up));
				}
			}

			if (!empty($this->publish_down))
			{
				if ($this->db->getNullDate() == $this->publish_down)
				{
					$query->where(
						'(' . $this->db->quoteName('publish_down') . ' = ' . $this->db->quote($this->publish_down) .
						' OR ' . $this->db->quoteName('publish_down') . ' = ' . $this->db->quote('1970-01-01 00:00:00') . ')');
				}
				else
				{
					$query->where($this->db->quoteName('publish_down') . ' = ' . $this->db->quote($this->publish_down));
				}
			}

			if (!empty($this->categoryIds))
			{
				$query->leftJoin(
						$this->db->quoteName('#__virtuemart_calc_categories', 'cc')
						. ' ON ' . $this->db->quoteName('cc.virtuemart_calc_id') . ' = ' . $this->db->quoteName('c.virtuemart_calc_id')
					)
					->where($this->db->quoteName('cc.virtuemart_category_id') . ' IN (' . implode(',', $this->categoryIds) . ')');
			}

			if (!empty($this->countryIds))
			{
				$query->leftJoin(
					$this->db->quoteName('#__virtuemart_calc_countries', 'cl')
					. ' ON ' . $this->db->quoteName('cl.virtuemart_calc_id') . ' = ' . $this->db->quoteName('c.virtuemart_calc_id')
				)
					->where($this->db->quoteName('cl.virtuemart_category_id') . ' IN (' . implode(',', $this->countryIds) . ')');
			}

			if (!empty($this->manufacturerIds))
			{
				$query->leftJoin(
					$this->db->quoteName('#__virtuemart_calc_manufacturers', 'cm')
					. ' ON ' . $this->db->quoteName('cm.virtuemart_calc_id') . ' = ' . $this->db->quoteName('c.virtuemart_calc_id')
				)
					->where($this->db->quoteName('cs.virtuemart_manufacturer_id') . ' IN (' . implode(',', $this->manufacturerIds) . ')');
			}

			if (!empty($this->shoppergroupIds))
			{
				$query->leftJoin(
					$this->db->quoteName('#__virtuemart_calc_shoppergroups', 'cs')
					. ' ON ' . $this->db->quoteName('cs.virtuemart_calc_id') . ' = ' . $this->db->quoteName('c.virtuemart_calc_id')
				)
					->where($this->db->quoteName('cs.virtuemart_shoppergroup_id') . ' IN (' . implode(',', $this->shoppergroupIds) . ')');
			}

			if (!empty($this->stateIds))
			{
				$query->leftJoin(
					$this->db->quoteName('#__virtuemart_calc_states', 'cst')
					. ' ON ' . $this->db->quoteName('cst.virtuemart_calc_id') . ' = ' . $this->db->quoteName('c.virtuemart_calc_id')
				)
					->where($this->db->quoteName('cst.virtuemart_state_id') . ' IN (' . implode(',', $this->stateIds) . ')');
			}

			$query->group($this->db->quoteName('c.virtuemart_calc_id'));

			$this->db->setQuery($query);
			$ids = $this->db->loadColumn();
			$this->log->add('Check if calculation rule exists');

			// There are multiple discount ids, we take the first one
			if (count($ids) > 0)
			{
				$this->log->add('Found multiple calculation rules, using the first one. This is ' . $ids[0], false);
				$this->virtuemart_calc_id = $ids[0];

				return true;
			}
			else
			{
				$this->virtuemart_calc_id = null;

				return false;
			}
		}

		return false;
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
		$this->virtuemart_calc_id = null;

		return true;
	}
}
