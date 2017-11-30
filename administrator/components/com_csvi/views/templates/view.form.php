<?php
/**
 * @package     CSVI
 * @subpackage  Templates
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Templates view.
 *
 * @package     CSVI
 * @subpackage  Templates
 * @since       6.0
 */
class CsviViewTemplates extends FOFViewForm
{
	/**
	 * Executes before rendering the page for the Browse task.
	 *
	 * @param   string  $tpl  Subtemplate to use
	 *
	 * @return  boolean  Return true to allow rendering of the page
	 */
	protected function onBrowse($tpl = null)
	{
		// Load the model
		$model = $this->getModel();

		// Set a default ordering
		$model->setState('filter_order', $model->getState('filter_order', 'ordering', 'cmd'));
		$model->setState('filter_order_Dir', $model->getState('filter_order_Dir', 'ASC', 'cmd'));

		// Back to the parent, we have set our override settings
		parent::onBrowse($tpl);
	}
}
