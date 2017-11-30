<?php
/**
 * @package     CSVI
 * @subpackage  Logdetails
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Log details model.
 *
 * @package     CSVI
 * @subpackage  Logdetails
 * @since       6.0
 */
class CsviModelLogdetails extends CsviModelDefault
{
	/**
	 * Filters to apply to the query.
	 *
	 * @return  object  List of filters.
	 *
	 * @since   6.0
	 */
	private function getFilterValues()
	{
		return (object) array(
				'action'	=> $this->getState('action', '', 'string'),
				'result'	=> $this->getState('result', '', 'string')
		);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   boolean  $overrideLimits  Are we requested to override the set limits?
	 *
	 * @return  object  The query to execute.
	 *
	 * @since   4.0
	 */
	public function buildQuery($overrideLimits = false)
	{
		// Get the parent query
		$query = parent::buildQuery($overrideLimits);

		// Get the Run ID
		$run_id = $this->input->getInt('run_id', 0);

		// Select the required fields from the table.
		$query->clear('from');
		$query->from($this->db->quoteName('#__csvi_logdetails'));

		if ($run_id)
		{
			$query->leftJoin(
				$this->db->quoteName('#__csvi_logs', 'l')
				. ' ON ' .
				$this->db->quoteName('l.csvi_log_id') . ' = ' . $this->db->quoteName('#__csvi_logdetails.csvi_log_id')
			);
			$query->where($this->db->quoteName('l.csvi_log_id') . ' = ' . (int) $run_id);
		}

		// Get the filters
		$state = $this->getFilterValues();

		if ($state->action)
		{
			$query->where($this->db->quoteName('status') . ' = ' . $this->db->quote($state->action));
		}

		if ($state->result)
		{
			$query->where($this->db->quoteName('result') . ' = ' . $this->db->quote($state->result));
		}

		return $query;
	}

	/**
	 * Get the actions available for the current log.
	 *
	 * @param   int  $runId  The ID of the run to get the actions for
	 *
	 * @return  array  List of available actions.
	 *
	 * @since   3.0
	 */
	public function getActions($runId)
	{
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('status', 'text'));
		$query->select($this->db->quoteName('status', 'value'))
			->from($this->db->quoteName('#__csvi_logdetails', 'd'))
			->leftJoin(
				$this->db->quoteName('#__csvi_logs', 'l')
				. ' ON ' .
				$this->db->quoteName('d.csvi_log_id') . ' = ' . $this->db->quoteName('l.csvi_log_id')
			)
			->where($this->db->quoteName('l.csvi_log_id') . ' = ' . (int) $runId)
			->group($this->db->quoteName('value'));
		$this->db->setQuery($query);
		$actions = $this->db->loadObjectList();
		$showall = JHtml::_('select.option', '', JText::_('COM_CSVI_SELECT_ACTION'), 'value', 'text');
		array_unshift($actions, $showall);

		return $actions;
	}

	/**
	 * Get the results available for the current log.
	 *
	 * @param   int  $runId  The ID of the run to get the actions for
	 *
	 * @return    array  List of results.
	 *
	 * @since   3.0
	 */
	public function getResults($runId)
	{
		$query = $this->db->getQuery(true);
		$query->select($this->db->quoteName('result', 'text'));
		$query->select($this->db->quoteName('result', 'value'))
			->from($this->db->quoteName('#__csvi_logdetails', 'd'))
			->leftjoin(
				$this->db->quoteName('#__csvi_logs', 'l')
				. ' ON ' .
				$this->db->quoteName('d.csvi_log_id') . ' = ' . $this->db->quoteName('l.csvi_log_id')
			)
			->where($this->db->quoteName('l.csvi_log_id') . ' = ' . (int) $runId)
			->group($this->db->quoteName('result'));
		$this->db->setQuery($query);
		$results = $this->db->loadObjectList();
		$showall = JHtml::_('select.option', '', JText::_('COM_CSVI_SELECT_RESULT'), 'value', 'text');
		array_unshift($results, $showall);

		return $results;
	}

	/**
	 * Load the statistics for displaying.
	 *
	 * @param   int     $runId  The log ID.
	 * @param   string  $type   The type of details to get.
	 *
	 * @return  object  Object of result
	 *
	 * @since   3.0
	 */
	public function getStats($runId, $type='default')
	{
		$details = new stdClass;

		if ($runId)
		{
			jimport('joomla.filesystem.file');

			// Add the run ID
			$details->run_id = $runId;

			// Get the total number of records
			$query = $this->db->getQuery(true)
				->select(
					array(
						$this->db->quoteName('start'),
						$this->db->quoteName('end'),
						$this->db->quoteName('addon'),
						$this->db->quoteName('action'),
						$this->db->quoteName('action_type'),
						$this->db->quoteName('template_name'),
						$this->db->quoteName('records'),
						$this->db->quoteName('file_name'),
						$this->db->quoteName('run_cancelled')
					)
				)
				->from($this->db->quoteName('#__csvi_logs'))
				->where($this->db->quoteName('csvi_log_id') . ' = ' . (int) $runId);
			$this->db->setQuery($query);
			$details = $this->db->loadObject();

			// Load the addon language
			$this->csvihelper->loadLanguage($details->addon);

			// Get the status area results
			$query->clear()
				->select('COUNT(' . $this->db->quoteName('status') . ') AS ' . $this->db->quoteName('total'))
				->select($this->db->quoteName('status'))
				->select($this->db->quoteName('area'))
				->from($this->db->quoteName('#__csvi_logdetails'))
				->where($this->db->quoteName('csvi_log_id') . ' = ' . (int) $runId)
				->group($this->db->quoteName(array('status', 'area')))
				->order($this->db->quoteName('area'));
			$this->db->setQuery($query);
			$details->resultstats = $this->db->loadObjectList();

			// Get some status results
			$query->clear()
				->select('COUNT(' . $this->db->quoteName('status') . ') AS ' . $this->db->quoteName('total'))
				->select($this->db->quoteName('status'))
				->select($this->db->quoteName('result'))
				->from($this->db->quoteName('#__csvi_logdetails'))
				->where($this->db->quoteName('csvi_log_id') . ' = ' . (int) $runId)
				->order($this->db->quoteName('csvi_logdetail_id'));
			$this->db->setQuery($query);
			$results = $this->db->loadObjectList('status');
			$details->result = array();

			foreach ($results as $status => $result)
			{
				if (!empty($status))
				{
					$details->result[$status] = $result;
				}
			}

			// Check if there is a debug log file
			$logfile = JPATH_SITE . '/logs/com_csvi.log.' . $runId . '.php';

			if (JFile::exists($logfile))
			{
				$attribs = 'class="modal" onclick="" rel="{handler: \'iframe\', size: {x: 950, y: 500}}"';
				$details->debug = JHtml::_(
					'link',
					JRoute::_('index.php?option=com_csvi&view=logs&task=logreader&tmpl=component&run_id=' . $runId),
					JText::_('COM_CSVI_SHOW_LOG'),
					$attribs
				);
				$details->debug .= ' | ';
				$details->debug .= JHtml::_(
					'link',
					JRoute::_('index.php?option=com_csvi&view=logs&task=logreader&tmpl=component&run_id=' . $runId),
					JText::_('COM_CSVI_OPEN_LOG'),
					'target="_new"'
				);
				$details->debug .= ' | ';
				$details->debug .= JHtml::_(
					'link',
					JRoute::_('index.php?option=com_csvi&view=logs&task=downloaddebug&run_id=' . $runId),
					JText::_('COM_CSVI_DOWNLOAD_LOG')
				);
			}
			else
			{
				$details->debug = JText::_('COM_CSVI_NO_DEBUG_LOG_FOUND');
			}
		}

		return $details;
	}
}
