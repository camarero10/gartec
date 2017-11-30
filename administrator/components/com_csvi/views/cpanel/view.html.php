<?php
/**
 * @package     CSVI
 * @subpackage  Dashboard
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

/**
 * Dashboard list.
 *
 * @package     CSVI
 * @subpackage  Dashboard
 * @since       6.0
 */
class CsviViewCpanels extends FOFViewHtml
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
		$helper = new CsviHelperCsvi;
		$helper->setDownloadId();

		// Load the items
		$this->items = FOFModel::getTmpInstance('Logs', 'CsviModel')
			->limitstart(0)
			->limit(10)
			->filter_order('start')
			->filter_order_Dir('DESC')
			->getItemList();

		return parent::onDisplay($tpl);
	}
}
