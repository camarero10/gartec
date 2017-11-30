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

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/views/default/view.html.php';

/**
 * Logdetails view.
 *
 * @package     CSVI
 * @subpackage  Logdetails
 * @since       6.0
 */
class CsviViewLogdetails extends CsviViewDefault
{
	/**
	 * Executes before rendering a generic page, default to actions necessary
	 * for the Browse task.
	 *
	 * @param   string  $tpl  Subtemplate to use
	 *
	 * @return  boolean  Return true to allow rendering of the page
	 */
	protected function onDisplay($tpl = null)
	{
		// Load the model
		$model = $this->getModel();

		// Set a default ordering
		$model->setState('filter_order', $model->getState('filter_order', 'line', 'cmd'));
		$model->setState('filter_order_Dir', $model->getState('filter_order_Dir', 'ASC', 'cmd'));

		return parent::onDisplay($tpl);
	}
}
