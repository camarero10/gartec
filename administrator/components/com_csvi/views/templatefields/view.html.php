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

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/views/default/view.html.php';

/**
 * Template fields view.
 *
 * @package     CSVI
 * @subpackage  Templatefields
 * @since       6.0
 */
class CsviViewTemplatefields extends CsviViewDefault
{
	/**
	 * Determines if the current Joomla! version and your current table support
	 * AJAX-powered drag and drop reordering. If they do, it will set up the
	 * drag & drop reordering feature.
	 *
	 * CSVI has to override this because the jQuery UI version of Joomla! is too old
	 * and doesn't support the modal option.
	 *
	 * @return  boolean|array  False if not suported, a table with necessary
	 *                         information (saveOrder: should you enabled DnD
	 *                         reordering; orderingColumn: which column has the
	 *                         ordering information).
	 */
	public function hasAjaxOrderingSupport()
	{
		$model = $this->getModel();

		if (!method_exists($model, 'getTable'))
		{
			return false;
		}

		$table = $this->getModel()->getTable();

		if (!method_exists($table, 'getColumnAlias') || !method_exists($table, 'getTableFields'))
		{
			return false;
		}

		$orderingColumn = $table->getColumnAlias('ordering');
		$fields = $table->getTableFields();

		if (!array_key_exists($orderingColumn, $fields))
		{
			return false;
		}

		$listOrder = $this->escape($model->getState('filter_order', null, 'cmd'));
		$listDirn = $this->escape($model->getState('filter_order_Dir', 'ASC', 'cmd'));
		$saveOrder = $listOrder == $orderingColumn;

		if ($saveOrder)
		{
			$saveOrderingUrl = 'index.php?option=' . $this->config['option'] . '&view=' . $this->config['view'] . '&task=saveorder&format=json';

			JHtml::_('script', 'jui/sortablelist.js', false, true);
			JHtml::_('stylesheet', 'jui/sortablelist.css', false, true, false);

			// Attach sortable to document
			JFactory::getDocument()->addScriptDeclaration("
			(function ($){
				$(document).ready(function (){
					var sortableList = new $.JSortableList('#itemsList tbody','adminForm','" . strtolower($listDirn) . "' , '" . $saveOrderingUrl . "','','false');
				});
			})(jQuery);
			"
			);

			JFactory::getDocument()->addScriptDeclaration(
				"(function ($){
					$(document).ready(function (){
						var saveOrderButton = $('.saveorder');
						saveOrderButton.css({'opacity':'0.2', 'cursor':'default'}).attr('onclick','return false;');
						var oldOrderingValue = '';
						$('.text-area-order').focus(function ()
						{
							oldOrderingValue = $(this).attr('value');
						})
						.keyup(function (){
							var newOrderingValue = $(this).attr('value');
							if (oldOrderingValue != newOrderingValue)
							{
								saveOrderButton.css({'opacity':'1', 'cursor':'pointer'}).removeAttr('onclick')
							}
						});
					});
				})(jQuery);"
			);

		}

		return array(
			'saveOrder'		 => $saveOrder,
			'orderingColumn' => $orderingColumn
		);
	}
}
