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

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/views/default/view.html.php';

/**
 * Tasks view.
 *
 * @package     CSVI
 * @subpackage  Tasks
 * @since       6.0
 */
class CsviViewTasks extends CsviViewDefault
{
	/**
	 * The installed components
	 *
	 * @var    array
	 * @since  6.0
	 */
	protected $components = array();

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
		$model->setState('filter_order', $model->getState('filter_order', 'component', 'cmd'));
		$model->setState('filter_order_Dir', $model->getState('filter_order_Dir', 'ASC', 'cmd'));

		$helper = new CsviHelperCsvi;
		$this->components = $helper->getComponents();
		array_unshift($this->components, JHtml::_('select.option', '', JText::_('JALL')));

		// Back to the parent, we have set our override settings
		parent::onBrowse($tpl);
	}
}
