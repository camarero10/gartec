<?php
/**
 * @package     CSVI
 * @subpackage  Controller
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * A default controller. This can be used for views that do not have a direct link to a database table.
 *
 * @package     CSVI
 * @subpackage  Controller
 * @since       6.0
 */
class CsviControllerDefault extends FOFController
{
	/**
	 * Set if we should override the task
	 *
	 * @var    bool
	 * @since  6.0
	 */
	protected $override = false;

	/**
	 * Execute a given task.
	 *
	 * @param   string  $task  The task to execute
	 *
	 * @return  void.
	 *
	 * @since   6.0
	 */
	public function execute($task)
	{
		if (!$this->override)
		{
			if (in_array($task, array('browse', 'read', 'add', 'edit', 'apply', 'copy', 'save', 'savenew')))
			{
				$task = 'detail';
			}
		}

		parent::execute($task);
	}

	/**
	 * A task that does not require any linked database table.
	 *
	 * @return  boolean  Always returns true.
	 *
	 * @since   6.0
	 */
	public function detail()
	{
		// Set the layout to item, if it's not set in the URL
		if (is_null($this->layout))
		{
			$this->layout = 'default';
		}

		// Display
		$this->display(in_array('detail', $this->cacheableTasks));

		return true;
	}
}
