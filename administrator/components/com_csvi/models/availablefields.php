<?php
/**
 * @package     CSVI
 * @subpackage  AvailableFields
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Model for available fields.
 *
 * @package     CSVI
 * @subpackage  AvailableFields
 * @since       6.0
 */
class CsviModelAvailablefields extends CsviModelDefault
{
	/**
	 * Get the filters to apply to the query.
	 *
	 * @return  object  An object with filters.
	 *
	 * @since   6.0
	 */
	private function getFilterValues()
	{
		return (object) array(
				'action'		=> $this->getState('jform_action', $this->getState('action', '', 'string'), 'string'),
				'component'		=> $this->getState('jform_component', $this->getState('component', '', 'string'), 'string'),
				'operation'		=> $this->getState('jform_operation', $this->getState('operation', '', 'string'), 'string'),
				'template_table' => $this->getState('template_table', '', 'string'),
				'avfields'		=> $this->getState('avfields', '', 'string'),
				'idfields'		=> $this->input->get('idfields', $this->getState('idfields', false)),
		);
	}

	/**
	 * Builds the SELECT query
	 *
	 * @param   boolean  $overrideLimits  Are we requested to override the set limits?
	 *
	 * @return  JDatabaseQuery
	 */
	public function buildQuery($overrideLimits = false)
	{
		// Get the parent query
		$query = parent::buildQuery($overrideLimits);

		// Clean out some settings so we can reset them
		$query->clear('select');
		$query->clear('from');
		$query->clear('where');

		// Setup the new settings
		$query->select(
			array(
				$this->db->quoteName('csvi_name'),
				$this->db->quoteName('component_name'),
				$this->db->quoteName('component_table'),
				$this->db->quoteName('isprimary')
			)
		);

		// Join the template table
		$query->from($this->db->quoteName('#__csvi_availablefields', 'tbl'));
		$query->leftJoin(
			$this->db->quoteName('#__csvi_availabletables', 't')
			. ' ON ' . $this->db->quoteName('t.template_table') . ' = ' . $this->db->quoteName('tbl.component_table')
		);

		$state = $this->getFilterValues();

		if ($state->action)
		{
			$query->where($this->db->quoteName('tbl.action') . ' = ' . $this->db->quote($state->action));
			$query->where($this->db->quoteName('t.action') . ' = ' . $this->db->quote($state->action));
		}

		if ($state->component)
		{
			$query->where($this->db->quoteName('tbl.component') . ' = ' . $this->db->quote($state->component));
			$query->where($this->db->quoteName('t.component') . ' = ' . $this->db->quote($state->component));
		}

		if ($state->operation)
		{
			$query->where($this->db->quoteName('t.task_name') . ' = ' . $this->db->quote($state->operation));
		}

		if ($state->template_table)
		{
			$query->where($this->db->quoteName('t.template_table') . ' = ' . $this->db->quote($state->template_table));
		}

		if ($state->avfields)
		{
			$query->where(
				'('
				. $this->db->quoteName('csvi_name') . ' LIKE ' . $this->db->quote('%' . $state->avfields . '%')
				. ' OR ' . $this->db->quoteName('component_name') . ' LIKE ' . $this->db->quote('%' . $state->avfields . '%')
				. ' OR ' . $this->db->quoteName('csvi_name') . ' LIKE ' . $this->db->quote('%' . $state->avfields . '%')
				. ')'
			);
		}

		if (!$state->idfields)
		{
			$query->where(
				'(' . $this->db->quoteName('csvi_name') . ' NOT LIKE ' . $this->db->quote('%\_id') . ' AND ' . $this->db->quoteName('csvi_name')
				. ' NOT LIKE ' . $this->db->quote('id') . ')'
			);
		}

		// Group the value
		$query->group($this->db->quoteName(array('csvi_name', 'component_table')));

		return $query;
	}

	/**
	 * Get the fields belonging to a certain operation type.
	 *
	 * @param   string  $type       The task name.
	 * @param   string  $component  The name of the component to get the available fields for.
	 * @param   string  $action     The type of action the fields belong to.
	 * @param   string  $filter     The type of return value either array or object.
	 * @param   string  $tableName  The name of the table to filter on.
	 *
	 * @return  array  List of fields.
	 *
	 * @since   3.0
	 */
	public function getAvailableFields($type, $component, $action, $filter='array', $tableName=null)
	{
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('csvi_name', 'value') . ',' . $this->db->quoteName('csvi_name', 'text'))
			->from($this->db->quoteName('#__csvi_availablefields', 'a'))
			->leftJoin(
				$this->db->quoteName('#__csvi_availabletables', 't')
				. ' ON ' . $this->db->quoteName('t.template_table') . ' = ' . $this->db->quoteName('a.component_table')
			)
			->where($this->db->quoteName('t.task_name') . ' = ' . $this->db->quote($type))
			->where($this->db->quoteName('t.action') . ' = ' . $this->db->quote($action))
			->where($this->db->quoteName('t.component') . ' = ' . $this->db->quote($component))
			->where($this->db->quoteName('a.component') . ' = ' . $this->db->quote($component));

		if ($tableName)
		{
			$query->where($this->db->quoteName('t.template_table') . ' = ' . $this->db->quote($tableName));
		}

		$query->group($this->db->quoteName('csvi_name'));
		$this->db->setQuery($query);

		// Get the results
		$fields = array();

		if ($filter == 'array')
		{
			$fields = $this->db->loadColumn();
		}
		elseif ($filter == 'object')
		{
			$fields = $this->db->loadObjectList();
		}

		// Return the array of fields
		return $fields;
	}
}
