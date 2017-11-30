<?php
/**
 * @package     CSVI
 * @subpackage  Templatefields
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * The template fields model.
 *
 * @package     CSVI
 * @subpackage  Templatefields
 * @since       6.0
 */
class CsviModelTemplatefields extends FOFModel
{
	/**
	 * Holds the database driver
	 *
	 * @var    JDatabase
	 * @since  6.0
	 */
	protected $db = null;

	/**
	 * Construct the class.
	 *
	 * @since   6.0
	 */
	public function __construct()
	{
		parent::__construct();

		// Load the basics
		$this->db = $this->getDbo();
	}

	/**
	 * Get the filter values.
	 *
	 * @return  object  Filter values.
	 *
	 * @since   6.0
	 */
	private function getFilterValues()
	{
		return (object) array(
				'field_name'	=> $this->getState('field_name', '', 'string'),
				'csvi_template_id'	=> $this->getState('csvi_template_id', 0, 'int')
		);
	}

	/**
	 * Build an SQL query to load the list data.
	 *
	 * @param   bool  $overrideLimits  Sets whether or not to override the page limits
	 *
	 * @return  object the query to execute.
	 *
	 * @since   4.0
	 */
	public function buildQuery($overrideLimits = false)
	{
		// Get the parent query
		$query = parent::buildQuery($overrideLimits);

		// Clean the query builder so we can do our stuff
		$query->clear('select');
		$query->clear('from');
		$query->clear('where');

		// Load the tables
		$query->from($this->db->quoteName('#__csvi_templatefields', 'tbl'));
		$query->leftJoin(
				$this->db->quoteName('#__csvi_templates', 't')
				. ' ON ' . $this->db->quoteName('t.csvi_template_id') . ' = ' . $this->db->quoteName('tbl.csvi_template_id')
			);
		$query->leftJoin(
				$this->db->quoteName('#__users', 'u')
				. ' ON ' . $this->db->quoteName('tbl.locked_by') . ' = ' . $this->db->quoteName('u.id')
			);

		// Set the selects
		$query->select($this->db->quoteName('tbl') . '.*');
		$query->select($this->db->quoteName('t.template_name'));
		$query->select($this->db->quoteName('u.name', 'editor'));

		$state = $this->getFilterValues();

		if ($state->field_name)
		{
			$query->where($this->db->quoteName('tbl.field_name') . ' LIKE ' . $this->db->quote('%' . $state->field_name . '%'));
		}

		if ($state->csvi_template_id)
		{
			$query->where($this->db->quoteName('tbl.csvi_template_id') . ' = ' . (int) $state->csvi_template_id);
		}

		return $query;
	}

	/**
	 * This method can be overriden to automatically do something with the
	 * list results array. You are supposed to modify the list which was passed
	 * in the parameters; DO NOT return a new array!
	 *
	 * @param   array  &$resultArray  An array of objects, each row representing a record
	 *
	 * @return  void
	 */
	protected function onProcessList(&$resultArray)
	{
		foreach ($resultArray as $key => $result)
		{
			$result->rules = $this->loadRules($result->csvi_templatefield_id);
			$resultArray[$key] = $result;
		}
	}

	/**
	 * This method runs after an item has been gotten from the database in a read
	 * operation. You can modify it before it's returned to the MVC triad for
	 * further processing.
	 *
	 * @param   FOFTable  &$record  The table instance we fetched
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	protected function onAfterGetItem(&$record)
	{
		// Set the template ID
		$record->csvi_template_id = $this->getState('csvi_template_id', $record->get('csvi_template_id'));

		// Load the rule IDs
		$record->rules = $this->loadRules($record->csvi_templatefield_id);

		if (empty($record->rules))
		{
			$record->rules = '';
		}
	}

	/**
	 * Load the rules for a given field.
	 *
	 * @param   int  $csvi_templatefield_id  The ID of the field to get the rules for.
	 *
	 * @return  array  List of rules.
	 *
	 * @since   6.2.0
	 */
	private function loadRules($csvi_templatefield_id)
	{
		// Load the rule IDs
		$query = $this->db->getQuery(true)
			->select($this->db->quoteName('csvi_rule_id'))
			->from($this->db->quoteName('#__csvi_templatefields_rules'))
			->where($this->db->quoteName('csvi_templatefield_id') . ' = ' . (int) $csvi_templatefield_id);
		$this->db->setQuery($query);

		return $this->db->loadColumn();
	}

	/**
	 * This method runs before the $data is saved to the $table. Return false to
	 * stop saving.
	 *
	 * @param   array     &$data   The data to save
	 * @param   FOFTable  &$table  The table to save the data to
	 *
	 * @return  boolean  Return false to prevent saving, true to allow it
	 *
	 * @since   6.0
	 */
	protected function onBeforeSave(&$data, &$table)
	{
		if (parent::onBeforeSave($data, $table))
		{
			// Auto increment ordering if not set by user
			if ($data['ordering'] == 0)
			{
				// Get the highest ordering number from db
				$query = $this->db->getQuery(true)
					->select('MAX(' . $this->db->quoteName('ordering') . ')')
					->from($this->db->quoteName('#__csvi_templatefields'))
					->where($this->db->quoteName('csvi_template_id') . ' = ' . (int) $data['csvi_template_id']);
				$this->db->setQuery($query);
				$ordering = $this->db->loadResult();

				if (count($ordering) > 0)
				{
					$data['ordering'] = ++$ordering;
				}
			}

			if (isset($data['rules']))
			{
				// Remove all rule IDs
				$query = $this->db->getQuery(true)
					->delete($this->db->quoteName('#__csvi_templatefields_rules'))
					->where($this->db->quoteName('csvi_templatefield_id') . ' = ' . (int) $data['csvi_templatefield_id']);
				$this->db->setQuery($query);
				$this->db->execute();

				// Store rule IDs
				$rule_table = FOFTable::getAnInstance('templatefields_rules');

				foreach ($data['rules'] as $rule_id)
				{
					if (!empty($rule_id))
					{
						$rule_table->save(array('csvi_templatefield_id' => $data['csvi_templatefield_id'], 'csvi_rule_id' => $rule_id));
						$rule_table->set('csvi_templatefields_rule_id', null);
					}
				}
			}

			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Store a template field.
	 *
	 * @return  array  The field option objects.
	 *
	 * @throws  Exception
	 *
	 * @since   4.3
	 */
	public function storeTemplateField()
	{
		// Collect the data
		$data = array();
		$fieldnames = explode('~', $this->input->get('field_name', '', 'string'));
		$template_id = $this->input->getInt('template_id', 0);

		// Get the highest field number
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select('MAX(' . $this->db->quoteName('ordering') . ')')
			->from($this->db->quoteName('#__csvi_templatefields'))
			->where($this->db->quoteName('csvi_template_id') . ' = ' . (int) $template_id);
		$db->setQuery($query);
		$ordering = $db->loadResult();

		foreach ($fieldnames as $fieldname)
		{
			if (!empty($fieldname))
			{
				$table = $this->getTable('Templatefield');
				$data['csvi_template_id'] = $template_id;
				$data['ordering'] = ++$ordering;
				$data['field_name'] = $fieldname;
				$data['file_field_name'] = $this->input->get('file_field_name', '', 'string');
				$data['column_header'] = $this->input->get('column_header', '', 'string');
				$data['default_value'] = $this->input->get('default_value', '', 'string');
				$data['enabled'] = $this->input->get('enabled', 1, 'int');
				$data['sort'] = $this->input->get('sort', 0, 'int');
				$table->bind($data);

				if (!$table->store())
				{
					throw new Exception(JText::_('COM_CSVI_STORE_TEMPLATE_FIELD_FAILED'), 500);
				}
			}
		}

		return true;
	}

	/**
	 * Moves the current item up or down in the ordering list
	 *
	 * @param   string  $dirn  The direction and magnitude to use (2 means move up by 2 positions, -3 means move down three positions)
	 *
	 * @return  boolean  True on success
	 */
	public function move($dirn)
	{
		$table = $this->getTable($this->table);

		$id = $this->getId();
		$status = $table->load($id);

		if (!$status)
		{
			$this->setError($table->getError());
		}

		if (!$status)
		{
			return false;
		}

		if (!$this->onBeforeMove($table))
		{
			return false;
		}

		$status = $table->move($dirn, 'csvi_template_id = ' . (int) $table->csvi_template_id);

		if (!$status)
		{
			$this->setError($table->getError());
		}
		else
		{
			$this->onAfterMove($table);
		}

		return $status;
	}

	/**
	 * Creates the WHERE part of the reorder query
	 *
	 * @return  string
	 */
	public function getReorderWhere()
	{
		return 'csvi_template_id = ' . (int) $this->input->getInt('csvi_template_id', 0);
	}
}
