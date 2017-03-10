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
/**
 * Metics Form Field class for the K2Store component
 */
class JElementMetrics extends JElement
{
	/**
	 * The field type.
	 *
	 * @var		string
	 */
	var	$_name = 'Metrics';

	function fetchElement($name, $value, &$node, $control_name){

		$app = JFactory::getApplication();
		$fieldName = $control_name.'['.$name.']';

 		//get libraries
 		$html ='';

 		$html .='<table id="attribute_options_table" class="adminlist table table-striped table-bordered k2store_metrics"><tr><td>';
 		$cid = $app->input->get('cid', 0);
		if($cid) {
 			$row = $this->getData($cid);
			//dimentions
 			$html .='<label>'.JText::_('K2STORE_METRICS_DIMENTIONS').'</label><br /><br />';
 			$html .="<input class='' name='plugins[k2storeitem_metrics][item_length]' value='{$row->item_length}' />";
 			$html .="<input class='' name='plugins[k2storeitem_metrics][item_width]' value='{$row->item_width}' />";
 			$html .="<input class='' name='plugins[k2storeitem_metrics][item_height]' value='{$row->item_height}' />";
 			//length class
 			$html .='</tr><tr><td>';
 			$html .='<label>'.JText::_('K2STORE_METRICS_LENGTH_CLASS').'</label>';
 			$html .= $this->getLengthClass($cid);
 			$html .='</tr><tr><td>';

 			//weight
 			$html .='<label>'.JText::_('K2STORE_METRICS_WEIGHT').'</label>';
 			$html .="<input class='' name='plugins[k2storeitem_metrics][item_weight]' value='{$row->item_weight}' />";
 			$html .='</tr><tr><td>';

 			//weight class
 			$html .='<label>'.JText::_('K2STORE_METRICS_WEIGHT_CLASS').'</label>';
 			$html .= $this->getWeightClass($cid);

		} else {
			$html .= JText::_('K2STORE_METRICS_SAVE_TO_ADD');
		}

		$html .= '</td></tr></table>';

 		return $html;

	 }

	 protected function getData($product_id) {
		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2store/tables');
	 	$row = JTable::getInstance('Products', 'Table');
	 	if($product_id) {
	 		$row->load(array('product_id'=>$product_id));
	 	}
	 	return $row;
	 }

	 protected function getLengthClass($product_id) {
	 	$product = $this->getData($product_id);

	 	require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/models/lengths.php');
		$model = new K2StoreModelLengths;
		$lengths = $model->getLengths();
		//generate country filter list
		$length_options = array();
			$length_options[] = JHTML::_('select.option', 0, JText::_('K2STORE_METRICS_SELECT_LENGTH_CLASS'));
		foreach($lengths as $row) {
			$length_options[] =  JHTML::_('select.option', $row->length_class_id, $row->length_title);
		}

		return JHTML::_('select.genericlist', $length_options, 'plugins[k2storeitem_metrics][item_length_class_id]', 'onchange=', 'value', 'text', $product->item_length_class_id);
	 }

	 protected function getWeightClass($product_id) {
	 	$product = $this->getData($product_id);
	 	require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/models/weights.php');
	 	$model = new K2StoreModelWeights;
	 	$weights = $model->getWeights();
	 	//generate country filter list
	 	$weight_options = array();
	 	$weight_options[] = JHTML::_('select.option', 0, JText::_('K2STORE_METRICS_SELECT_WEIGHT_CLASS'));
	 	foreach($weights as $row) {
	 		$weight_options[] =  JHTML::_('select.option', $row->weight_class_id, $row->weight_title);
	 	}

	 	return JHTML::_('select.genericlist', $weight_options, 'plugins[k2storeitem_metrics][item_weight_class_id]', 'onchange=', 'value', 'text', $product->item_weight_class_id);

	 }
}