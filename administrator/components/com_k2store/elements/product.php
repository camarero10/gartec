<?php
/*------------------------------------------------------------------------
# com_k2store - K2Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org
-------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die;

// import the list field type
jimport('joomla.form.helper');

class JElementProduct extends JElement
{
	/**
	 * The field type.
	 *
	 * @var		string
	 */
	var	$_name = 'Product';

	function fetchElement($name, $value, &$node, $control_name){
		$app = JFactory::getApplication();
		$product_id = $app->input->get('cid');
		$html = '';
		$html .='<table class="adminlist table table-striped table-bordered">';
		if(isset($product_id)){
			$html .= '<tr><td>';
			$html .= '<label class="k2store_product_id">';
			$html .= '<strong>'.JText::_('PLG_K2STORE_PRODUCT_ID_LABEL');
			$html .= '</strong>:&nbsp;&nbsp;';
			$html .= $product_id;
			$html .= '</label>';
			$html .= '</td></tr><tr><td>';

			$html .= "<strong>".JText::_('PLG_K2STORE_PRODUCT_SHORT_TAG')."</strong>: {k2storecart $product_id}";
			$html .= '&nbsp;&nbsp;';
			$html .= JHtml::tooltip(JText::_('PLG_K2STORE_PRODUCT_SHORT_TAG_HELP'), JText::_('PLG_K2STORE_PRODUCT_SHORT_TAG'),'tooltip.png', '', '', false);
			$html .= '</td></tr>';
		} else {
			$html .= '<div class="alert alert-info">';
			$html .= JText::_('PLG_K2STORE_PRODUCT_ID_DESC');
			$html .= '</div>';
		}
		$html .= '</table>';
		return $html;
	}
}