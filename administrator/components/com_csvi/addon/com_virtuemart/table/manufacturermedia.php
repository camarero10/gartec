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
 * Manufacturer media table.
 *
 * @package     CSVI
 * @subpackage  VirtueMart
 * @since       6.0
 */
class VirtueMartTableManufacturerMedia extends CsviTableDefault
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
		parent::__construct('#__virtuemart_manufacturer_medias', 'id', $db, $config);
	}

	/**
	 * Check if a media file reference already exists.
	 *
	 * @return  bool  Always return true.
	 *
	 * @since   4.0
	 */
	public function check()
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName($this->_tbl_key))
			->from($this->db->quoteName($this->_tbl))
			->where($this->db->quoteName('virtuemart_manufacturer_id') . ' = ' . (int) $this->virtuemart_manufacturer_id)
			->where($this->db->quoteName('virtuemart_media_id') . ' = ' . (int) $this->virtuemart_media_id);
		$this->db->setQuery($query);
		$id = $this->db->loadResult();

		if ($id > 0)
		{
			$this->id = $id;
		}

		return true;
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
		$this->id = null;

		return true;
	}
}
