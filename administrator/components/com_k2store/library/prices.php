<?php
/*------------------------------------------------------------------------
 # com_k2store - K2 Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/

/** ensure this file is being included by a parent file */
defined('_JEXEC') or die('Restricted access');

JLoader::register('K2Parameter', JPATH_ADMINISTRATOR.'/components/com_k2/lib/k2parameter.php');
require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/library/base.php');

class K2StorePrices
{

	public static function getPrice( $id, $quantity = '1')
	{
		// $sets[$id][$quantity][$group_id][$date]
		static $sets;

		if ( !is_array( $sets ) )
		{
			$sets = array( );
		}

		$price = null;
		if ( empty( $id ) )
		{
			return $price;
		}

		if ( !isset( $sets[$id][$quantity] ) )
		{

		( int ) $quantity;
			if ( $quantity <= '0' )
			{
				$quantity = '1';
			}

			$price = K2StorePrices::getItemPrice( $id );
			$item = new JObject;
			//1. base price
			$item->product_price = $price;

			//2. special/offer price if any
			$special_price = K2StorePrices::getSpecialPrice( $id );
			if( $special_price > 0.000){
				$item->product_price = $special_price;
			}

			//3. price range based on the date and the quantity
			$price_range = K2StorePrices::getPriceRange($id,$quantity);
			if( $price_range > 0.000){
				$item->product_price = $price_range;
			}

			$sets[$id][$quantity] = $item;
		}

		return $sets[$id][$quantity];
	}


	/**
	 *
	 * @return unknown_type
	 */
	public static function getItemPrice($id)
	{
		$k2store_vars = K2StorePrices::_getK2StoreVars($id);
		return $k2store_vars->item_price;
	}

	public static function getSpecialPrice($id)
	{
		$k2store_vars = K2StorePrices::_getK2StoreVars($id);
		if(!empty($k2store_vars) && isset($k2store_vars->special_price))
			return $k2store_vars->special_price;
		else
			return null;
	}


	public static function getPriceRange($product_id,$quantity)
	{
		$db		= JFactory::getDbo();
		$query	= $db->getQuery(true);

	//	$date= $db->Quote(JFactory::getDate()->toSql());

		$query->select('pr.price');
		$query->from('#__k2store_productprices as pr');
		$query->where('pr.product_id='.$product_id);
		$query->where('pr.quantity_start <='.$quantity);
		$query->order('pr.quantity_start DESC');
		//echo $query;
		$db->setQuery($query);
		$price_range=$db->loadResult();
		return $price_range;
	}



	public static function getItemTax(&$id) {

		$k2store_vars = K2StorePrices::_getK2StoreVars($id);

		 if ($k2store_vars->item_tax_id) {
			 $taxrate = K2StorePrices::_getTaxRate($k2store_vars->item_tax_id);
		  }	else {
			  $taxrate = 0;
		  }
		return $taxrate;
		}


		/**
	 *
	 * @return unknown_type
	 */
	public static function getItemSku(&$id)
	{
		$k2store_vars = K2StorePrices::_getK2StoreVars($id);
		return $k2store_vars->item_sku;
	}

	/**
	 *
	 * @return unknown_type
	 */
	public static function getItemEnabled(&$id)
	{
		$k2store_vars = K2StorePrices::_getK2StoreVars($id);

		if($k2store_vars->item_enabled) {
			return true;
		} else {
			return false;
		}
	}

	public static function isShippingEnabled($id) {
		$k2store_vars = K2StorePrices::_getK2StoreVars($id);
		if($k2store_vars->item_shipping) {
			return true;
		}
		return false;
	}

	public static function _getTaxRate($taxid) {

		$db		=JFactory::getDBO();
		$query= "SELECT tax_percent FROM #__k2store_taxprofiles WHERE id=".$taxid;
		$db->setQuery($query);
		return $db->loadResult();
	}

	public static function _getK2StoreVars($id) {

		$item = K2StorePrices::_getK2Item($id);

		$pluginName = 'k2store';

		//get the item price and tax profile id
		//$plugins = new JParameter($item->plugins, '', $pluginName);
		$plugin = JPluginHelper::getPlugin('k2', $pluginName);
//		$pluginParams = new JParameter($plugin->params);

		if(JVERSION==1.7) {
			// Get the output of the K2 plugin fields (the data entered by your site maintainers)
			$plugins = new JParameter($item->plugins, '', $pluginName);
		} else {
			$plugins = new K2Parameter($item->plugins, '', $pluginName);
		}

		//check due to the mess up happened due to j1.7 to j2.5 updates
	//	if(empty($plugins->get('item_price'))) {
	//		$plugins = new JParameter($item->plugins, '', $pluginName);
	//	}
		$product = self::getProduct($id);
		if(!$product) {
			//legacy compatibility
			$product = new JObject();
			$product->item_enabled = $plugins->get('item_enabled');
			$product->item_sku = $plugins->get('item_sku');
			$product->item_price = $plugins->get('item_price');
			$product->special_price = $plugins->get('special_price');
			$product->item_tax_id = $plugins->get('item_tax');
			$product->item_shipping = $plugins->get('item_shipping');
			$product->item_qty = $plugins->get('item_qty');
			$product->item_cart_text = $plugins->get('item_cart_text');
		} else {
			$product->item_tax_id = $product->item_tax;
			$product->item_cart_text = $plugins->get('item_cart_text');
		}
		return $product;
	}

	public static function getProduct($product_id) {

		$db = JFactory::getDbo();
		$query = $db->getQuery(true)->select('*')->from('#__k2store_products')->where('product_id='.$db->q($product_id));
		$db->setQuery($query);
		$row = $db->loadObject();
		return $row;
	}

/* 	public static function number($amount, $options='')
    {
        // default to whatever is in config
		$config = JComponentHelper::getParams('com_k2store');
        $options = (array) $options;
        $post = '';
        $pre = '';

        $default_currency = $config->get('currency_code', 'USD');
        $num_decimals = isset($options['num_decimals']) ? $options['num_decimals'] : $config->get('currency_num_decimals', '2');
        $thousands = isset($options['thousands']) ? $options['thousands'] : $config->get('currency_thousands', ',');
        $decimal = isset($options['decimal']) ? $options['decimal'] : $config->get('currency_decimal', '.');
        $currency_symbol = isset($options['currency']) ? $options['currency'] : $config->get('currency', '$');
        $currency_position = isset($options['currency_position']) ? $options['currency_position'] : $config->get('currency_position', 'pre');
        if($currency_position == 'post') {
			$post = $currency_symbol;
		} else {
			$pre = $currency_symbol;
		}

		$html = '';
		if($pre) {
			$html .= '<span class="currency_symbol">'.$pre.'</span>';
		}

		$html .=number_format($amount, $num_decimals, $decimal, $thousands);

		if($post) {
			$html .= '<span class="currency_symbol">'.$post.'</span>';
		}

        return $html;
    } */

	public static function number($amount, $currency = '', $value = '', $format = true) {
		if($value == 0) {
			$value = '';
		}
		//backward compatibility
		if(is_array($currency)) {
			$currency = '';
		}

		$currencyobject = K2StoreFactory::getCurrencyObject();
		return $currencyobject->format($amount, $currency, $value, $format);
	}

	public static function _getK2Item($id) {

		JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2/tables');
		$item =  JTable::getInstance('K2Item', 'Table');
		$id = intval($id);
		$item->load($id);
		return $item;
	}

	public static function getStock($product_id) {

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__k2store_productquantities');
		$query->where('product_id='.$db->quote($product_id));
		$db->setQuery($query);
		$result = $db->loadObject();
		if($result) {
		//	echo $result->quantity;
			return $result->quantity;
		} else {
			return null;
		}
	}

	public static function getTaxProfileId($product_id){
		$row = K2StorePrices:: _getK2StoreVars($product_id);
		return $row->item_tax_id;
	}

}
