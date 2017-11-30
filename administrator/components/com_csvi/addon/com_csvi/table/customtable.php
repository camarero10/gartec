<?php
/**
 * @package     CSVI
 * @subpackage  CSVI
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Custom table.
 *
 * @package     CSVI
 * @subpackage  CSVI
 * @since       6.0
 */
class CsviTableCustomtable extends CsviTableDefault
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
			// Find which table we are importing
			$tbl = $config['template']->get('custom_table');

			// Find the primary key for this table
			$helper = new CsviHelperCsvi;
			$pk = $helper->getPrimaryKey($tbl);

			parent::__construct('#__' . $tbl, $pk, $db, $config);
		}
		else
		{
			throw new CsviException(JText::_('COM_CSVI_TEMPLATE_NOT_AVAIlABLE'), 515);
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
		$key = $this->_tbl_key;
		$this->$key = null;

		return true;
	}
}
