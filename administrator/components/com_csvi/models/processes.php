<?php
/**
 * @package     CSVI
 * @subpackage  Tasks
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Processes model.
 *
 * @package     CSVI
 * @subpackage  Processes
 * @since       6.0
 */
class CsviModelProcesses extends CsviModelDefault
{
	/**
	 * Builds the SELECT query
	 *
	 * @param   boolean  $overrideLimits  Are we requested to override the set limits?
	 *
	 * @return  JDatabaseQuery
	 *
	 * @since   6.0
	 */
	public function buildQuery($overrideLimits = false)
	{
		$query = parent::buildQuery($overrideLimits)
			->select($this->db->quoteName('template_name'))
			->leftJoin(
				$this->db->quoteName('#__csvi_templates', 't')
				. ' ON ' . $this->db->quoteName('t.csvi_template_id') . ' = ' . $this->db->quoteName('#__csvi_processes.csvi_template_id')
			);

		return $query;
	}
}
