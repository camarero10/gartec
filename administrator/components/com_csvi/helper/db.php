<?php
/**
 * @package     CSVI
 * @subpackage  Database
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * CSVI database helper .
 *
 * @package     CSVI
 * @subpackage  Database
 * @since       6.0
 */
class CsviHelperDb
{
	/**
	 * A JDatabase instance
	 *
	 * @var    JDatabase
	 * @since  6.0
	 */
	private $db = null;

	/**
	 * The database connection cursor from the last query.
	 *
	 * @var    resource
	 * @since  6.0
	 */
	private $cursor = null;

	/**
	 * Construct the helper.
	 *
	 * @since   6.0
	 */
	public function __construct()
	{
		$conf = JFactory::getConfig();

		$host = $conf->get('host');
		$user = $conf->get('user');
		$password = $conf->get('password');
		$database = $conf->get('db');
		$prefix = $conf->get('dbprefix');
		$driver = $conf->get('dbtype');
		$key = 'csvi';

		$options = array(
			'driver' => $driver,
			'host' => $host,
			'user' => $user,
			'password' => $password,
			'database' => $database,
			'prefix' => $prefix,
			'key' => $key
		);

		$this->db = JDatabase::getInstance($options);
	}

	/**
	 * Set the query and execute it.
	 *
	 * @param   string  $sql     The query to execute.
	 * @param   int     $offset  The position to get the records from.
	 * @param   int     $limit   The number of records to get.
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 *
	 * @throws  CsviException
	 */
	public function setQuery($sql, $offset = 0, $limit = 0)
	{
		$this->db->setQuery($sql, $offset, $limit);

		if (!$this->cursor = $this->db->execute())
		{
			throw new CsviException($this->db->getErrorMsg(), $this->db->getErrorNum());
		}
	}

	/**
	 * Get a single row.
	 *
	 * @return  mixed  Array if row is found | False if no result is found.
	 *
	 * @since   6.0
	 */
	public function getRow()
	{
		if (!is_object($this->cursor))
		{
			$array = mysqli_fetch_object($this->cursor);
		}
		else
		{
			$array = $this->cursor->fetch_object();
		}

		if ($array)
		{
			return $array;
		}
		else
		{
			if (!is_object($this->cursor))
			{
				mysqli_free_result($this->cursor);
			}
			else
			{
				$this->cursor->free_result();
			}

			return false;
		}
	}

	/**
	 * Get the number of rows found.
	 *
	 * @return  int  The number of rows.
	 *
	 * @since   6.0
	 */
	public function getNumRows()
	{
		return $this->db->getNumRows($this->cursor);
	}

	/**
	 * Get the query.
	 *
	 * @return  string The query.
	 *
	 * @since   6.0
	 */
	public function getQuery()
	{
		return $this->db->getQuery();
	}
}
