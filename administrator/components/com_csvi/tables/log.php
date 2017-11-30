<?php
/**
 * @package     CSVI
 * @subpackage  Table
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * CSVI Logs table.
 *
 * @package     CSVI
 * @subpackage  Table
 * @since       6.0
 */
class CsviTableLog extends FOFTable
{
	/**
	 * Reset the primary key
	 *
	 * @return  bool  True on success | False on failure
	 *
	 * @since   6.0
	 */
	protected function onAfterReset()
	{
		if (parent::onAfterReset())
		{
			$this->csvi_log_id = null;

			return true;
		}
		else
		{
			return false;
		}
	}
}
