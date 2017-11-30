<?php
/**
 * @package     CSVI
 * @subpackage  Log
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/views/default/view.html.php';

/**
 * Log list.
 *
 * @package     CSVI
 * @subpackage  Log
 * @since       6.0
 */
class CsviViewLogs extends CsviViewDefault
{
	/**
	 * Executes before rendering a generic page, default to actions necessary
	 * for the Browse task.
	 *
	 * @param   string  $tpl  Subtemplate to use
	 *
	 * @return  boolean  Return true to allow rendering of the page
	 *
	 * @since   6.0
	 */
	public function onDisplay($tpl = null)
	{
		// Load the model
		$model = $this->getModel();

		// Set a default ordering
		$model->setState('filter_order', $model->getState('filter_order', 'start', 'cmd'));
		$model->setState('filter_order_Dir', $model->getState('filter_order_Dir', 'DESC', 'cmd'));

		return parent::onDisplay($tpl);
	}

	/**
	 * Read the log details.
	 *
	 * @return  bool  Returns true.
	 *
	 * @since   6.0
	 */
	public function onLogReader()
	{
		$this->logdetails = $this->get('Logfile');
		$this->logfile = JPATH_SITE . '/logs/com_csvi.log.' . $this->input->get('run_id', 0, 'int') . '.php';

		return true;
	}
}
