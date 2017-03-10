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

// no direct access
defined( '_JEXEC' ) or die( 'Restricted access' );
jimport( 'joomla.filter.filterinput' );
jimport('joomla.application.component.model');

JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR.DS.'tables');
JLoader::register('K2StoreModel',  JPATH_ADMINISTRATOR.'/components/com_k2store/models/model.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/inventory.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/tax.php');
class K2StoreModelMyCart extends K2StoreModel {

	private $_product_id;
	private $_data = array();
	private $tax;


	function __construct($config = array())
	{
		parent::__construct($config);
		$this->tax = new K2StoreTax();
	}

	function getDataNew()
	{
		require_once (JPATH_SITE.'/components/com_k2store/helpers/cart.php');

		$session = JFactory::getSession();

		// Lets load the content if it doesn't already exist
		if (empty($this->_data) && count($session->get('k2store_cart')))
		{

			foreach ($session->get('k2store_cart') as $key => $quantity) {

				$product = explode(':', $key);
				$product_id = $product[0];
				$stock = true;

				// Options
				if (isset($product[1])) {
					$options = unserialize(base64_decode($product[1]));
				} else {
					$options = array();
				}

				//now get product details
				$product_info = K2StoreHelperCart::getItemInfo($product_id);

				//now get product options
				if($product_info) {
					$option_price = 0;
					$option_weight = 0;
					$option_data = array();

					foreach ($options as $product_option_id => $option_value) {

						$product_option = $this->getCartProductOptions($product_option_id , $product_id);

						if ($product_option) {
							if ($product_option->type == 'select' || $product_option->type == 'radio') {

								//ok now get product option values
								$product_option_value = $this->getCartProductOptionValues($product_option->product_option_id, $option_value );

								if ($product_option_value) {

									//price
									if ($product_option_value->product_optionvalue_prefix == '+') {
										$option_price += $product_option_value->product_optionvalue_price;
									} elseif ($product_option_value->product_optionvalue_prefix == '-') {
										$option_price -= $product_option_value->product_optionvalue_price;
									}

									//options weight
									if ($product_option_value->product_optionvalue_weight_prefix == '+') {
										$option_weight += $product_option_value->product_optionvalue_weight;
									} elseif ($product_option_value->product_optionvalue_weight_prefix == '-') {
										$option_weight -= $product_option_value->product_optionvalue_weight;
									}

									$option_data[] = array(
											'product_option_id'       => $product_option_id,
											'product_optionvalue_id' => $option_value,
											'option_id'               => $product_option->option_id,
											'optionvalue_id'         => $product_option_value->optionvalue_id,
											'name'                    => $product_option->option_name,
											'option_value'            => $product_option_value->optionvalue_name,
											'type'                    => $product_option->type,
											'price'                   => $product_option_value->product_optionvalue_price,
											'price_prefix'            => $product_option_value->product_optionvalue_prefix,
											'weight'                   => $product_option_value->product_optionvalue_weight,
											'weight_prefix'            => $product_option_value->product_optionvalue_weight_prefix

									);
								}
							}
						}
					}

					//get the product price

					//base price
					$price = $product_info->price;

					//we may have special price or discounts. so check
					$price_override = K2StorePrices::getPrice($product_info->product_id, $quantity);

					if(isset($price_override) && !empty($price_override)) {
						$price = $price_override->product_price;
					}

					$this->_data[$key] = array(
							'key'             => $key,
							'product_id'      =>  $product_info->product_id,
							'name'            =>  $product_info->product_name,
							'model'           =>  $product_info->product_sku,
							'option'          => $option_data,
							'option_price'    => $option_price,
							'quantity'        => $quantity,
							'stock'			  => $product_info->stock,
							'tax_profile_id'  => $product_info->tax_profile_id,
							'shipping' 		  => $product_info->item_shipping,
							'price'           => ($price + $option_price),
							'total'           => ($price + $option_price) * $quantity,
							'weight'          => ($product_info->item_weight + $option_weight),
							'weight_total'    => ($product_info->item_weight + $option_weight) * $quantity,
							'option_weight'   => ($option_weight * $quantity),
							'weight_class_id' => $product_info->item_weight_class_id,
							'length'          => $product_info->item_length,
							'width'           => $product_info->item_width,
							'height'          => $product_info->item_height,
							'length_class_id' => $product_info->item_length_class_id

					);

				} // end of product info if
				else {
					$this->remove($key);
				}
			}
		}
		return $this->_data;
	}

	 public function getShippingIsEnabled()
    {
	   	$model = JModelLegacy::getInstance( 'MyCart', 'K2StoreModel');
		$list = $model->getDataNew();

    	// If no item in the list, return false
        if ( empty( $list ) )
        {
          	return false;
        }

        require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/library/k2item.php');
        $product_helper = new K2StoreItem();
        foreach ($list as $item)
        {
           	$shipping = $product_helper->isShippingEnabled($item['product_id']);
        	if ($shipping)
        	{
        	    return true;
        	}
        }

        return false;
    }

    function getProductOptions($product_id) {

    	//first get the product options
    	$db = JFactory::getDbo();
    	$product_option_data = array();
    	$query = $db->getQuery(true);
    	$query->select('po.*');
    	$query->from('#__k2store_product_options AS po');
    	$query->where('po.product_id='.$product_id);

    	//join the options table to get the name
    	$query->select('o.option_name, o.type');
    	$query->join('LEFT', '#__k2store_options AS o ON po.option_id=o.option_id');
    	$query->where('o.state=1');
    	$query->order('po.product_option_id ASC');

    	$db->setQuery($query);
    	$product_options = $db->loadObjectList();
		//now prepare to get the product option values
    	foreach($product_options as $product_option) {

    		//if multiple choices available, then we got to get them
    		if ($product_option->type == 'select' || $product_option->type == 'radio' || $product_option->type == 'checkbox') {

    			$product_option_value_data = array();

    			$product_option_values = $this->getProductOptionValues($product_option->product_option_id, $product_option->product_id);

    			foreach ($product_option_values as $product_option_value) {
    				$product_option_value_data[] = array(
    						'product_optionvalue_id' 		=> $product_option_value->product_optionvalue_id,
    						'optionvalue_id'         		=> $product_option_value->optionvalue_id,
    						'optionvalue_name'       		=> $product_option_value->optionvalue_name,
    						'product_optionvalue_price' 	=> $product_option_value->product_optionvalue_price,
    						'product_optionvalue_prefix'	=> $product_option_value->product_optionvalue_prefix,
    						'product_optionvalue_weight' 	=> $product_option_value->product_optionvalue_weight,
    						'product_optionvalue_weight_prefix'	=> $product_option_value->product_optionvalue_weight_prefix
    				);
    			}

    			$product_option_data[] = array(
    					'product_option_id' => $product_option->product_option_id,
    					'option_id'         => $product_option->option_id,
    					'option_name'		=> $product_option->option_name,
    					'type'              => $product_option->type,
    					'optionvalue'       => $product_option_value_data,
    					'required'          => $product_option->required
    			);

    		} else {

    			//if no option values are present, then
    			$product_option_data[] = array(
    					'product_option_id' => $product_option->product_option_id,
    					'option_id'         => $product_option->option_id,
    					'option_name'		=> $product_option->option_name,
    					'type'              => $product_option->type,
    					'optionvalue'       => '',
    					'required'          => $product_option->required
    			);
    		} //endif
    	} //end product option foreach

    	return $product_option_data;
    }

    function getProductOptionValues($product_option_id, $product_id) {

    	//first get the product options
    	$db = JFactory::getDbo();
    	$query = $db->getQuery(true);
    	$query->select('pov.*');
    	$query->from('#__k2store_product_optionvalues AS pov');
    	$query->where('pov.product_id='.$product_id);
    	$query->where('pov.product_option_id='.$product_option_id);

    	//join the optionvalues table to get the name
    	$query->select('ov.optionvalue_id, ov.optionvalue_name');
    	$query->join('LEFT', '#__k2store_optionvalues AS ov ON pov.optionvalue_id=ov.optionvalue_id');
    	$query->order('pov.ordering ASC');

    	$db->setQuery($query);
    	$product_option_values = $db->loadObjectList();
    	return $product_option_values;
    }

    function getCartProductOptions($product_option_id , $product_id) {

    	$db = JFactory::getDbo();
    	$query = $db->getQuery(true);
    	$query->select('po.*');
    	$query->from('#__k2store_product_options AS po');
    	$query->where('po.product_option_id='.$product_option_id);
    	$query->where('po.product_id='.$product_id);

    	//join the options table to get the name
    	$query->select('o.option_name, o.type');
    	$query->join('LEFT', '#__k2store_options AS o ON po.option_id=o.option_id');
    	$query->order('o.ordering ASC');
    	$db->setQuery($query);

    	$product_option = $db->loadObject();
    	return $product_option;
    }

    function getCartProductOptionValues($product_option_id, $option_value ) {

    	//first get the product options
    	$db = JFactory::getDbo();
    	$query = $db->getQuery(true);
    	$query->select('pov.*');
    	$query->from('#__k2store_product_optionvalues AS pov');
    	$query->where('pov.product_optionvalue_id='.$option_value);
    	$query->where('pov.product_option_id='.$product_option_id);

    	//join the optionvalues table to get the name
    	$query->select('ov.optionvalue_id, ov.optionvalue_name');
    	$query->join('LEFT', '#__k2store_optionvalues AS ov ON pov.optionvalue_id=ov.optionvalue_id');
    	$query->order('pov.ordering ASC');

    	$db->setQuery($query);
    	$product_option_value = $db->loadObject();
    	return $product_option_value;
    }


    public function update($key, $qty) {
    	$cart = JFactory::getSession()->get('k2store_cart');
    	if ((int)$qty && ((int)$qty > 0)) {
    		$cart[$key] = (int)$qty;
    	} else {
    		$this->remove($key);
    	}
    	JFactory::getSession()->set('k2store_cart', $cart);
    	$this->_data = array();
    }


    public function remove($key) {
    	$cart = JFactory::getSession()->get('k2store_cart');

    	if (isset($cart[$key])) {
    		unset($cart[$key]);
    	}
    	JFactory::getSession()->set('k2store_cart', $cart);
    	$this->_data = array();
    }

    public function clear() {
    	JFactory::getSession()->set('k2store_cart', array());
    	$this->_data = array();
    }


    public function removeCoupon() {
    	$session = JFactory::getSession();
    	if($session->has('coupon', 'k2store')) {
    		$session->set('coupon', '', 'k2store');
    	}
    }
    
    /**
     *
     * Method to check config, user group and product state (if recurs).
     * Then get right values accordingly
     * @param array $items - cart items
     * @param boolean - config to show tax or not
     * @return object
     */
    function checkItems( &$items, $show_tax=false)
    {
    	if (empty($items)) {
    		return array();
    	}
    	$params = JComponentHelper::getParams('com_k2store');

    	$this->_data['products'] = array();

    	foreach ($items as $product) {
    		$product_total = 0;

    		foreach ($items as $product_2) {
    			if ($product_2['product_id'] == $product['product_id']) {
    				$product_total += $product_2['quantity'];
    			}
    		}

    		//options
    		$option_data = array();

    		foreach ($product['option'] as $option) {

    			$value = $option['option_value'];
    			$option_data[] = array(
    					'name'  => $option['name'],
    					'value' => (utf8_strlen($value) > 20 ? utf8_substr($value, 0, 20) . '..' : $value)
    			);
    		}

    		// Display prices
    		$price = $this->tax->calculate($product['price'], $product['tax_profile_id'], $params->get('show_tax_total'));

    		$total = $this->tax->calculate($product['price'], $product['tax_profile_id'], $params->get('show_tax_total')) * $product['quantity'];

    		$tax_amount = '';
    		$this->_data['products'][] = array(
    				'key'      => $product['key'],
    				'product_id'     => $product['product_id'],
    				'product_name'     => $product['name'],
    				'product_model'    => $product['model'],
    				'product_total'    => $product_total,
    				'product_options'   => $option_data,
    				'quantity' => $product['quantity'],
    				'stock'    => $product['stock'],
    				'tax_amount'    => $tax_amount,
    				'price'    => $price,
    				'total'    => $total
    		);

    	}
    	$cartObj = JArrayHelper::toObject($this->_data['products']);
    	return $cartObj;
    }


    function getTotals() {
    	$app = JFactory::getApplication();
    	$session = JFactory::getSession();
    	$products =$this->getDataNew();
    	$total_data = array();
    	$total = 0;

    	//products
    	$total_data['products'] = $products;

    	//sub total
    	$total_data['subtotal'] = K2StoreHelperCart::getSubtotal();
    	$total +=$total_data['subtotal'];
    	//taxes
    	$tax_data = array();
    	$taxes = K2StoreHelperCart::getTaxes();

    	//coupon
    	if($session->has('coupon', 'k2store')) {
    		$coupon_info = K2StoreHelperCart::getCoupon($session->get('coupon', '', 'k2store'));

    		if ($coupon_info) {
    			$discount_total = 0;

    			if (!$coupon_info->product) {
    				$sub_total =K2StoreHelperCart::getSubTotal();
    			} else {
    				$sub_total = 0;
    				foreach ($products as $product) {
    					if (in_array($product['product_id'], $coupon_info->product)) {
    						$sub_total += $product['total'];
    					}
    				}
    			}

    			if ($coupon_info->value_type == 'F') {
    				$coupon_info->value = min($coupon_info->value, $sub_total);
    			}

    			foreach ($products as $product) {
    				$discount = 0;

    				if (!$coupon_info->product) {
    					$status = true;
    				} else {
    					if (in_array($product['product_id'], $coupon_info->product)) {
    						$status = true;
    					} else {
    						$status = false;
    					}
    				}

    				if ($status) {
    					if ($coupon_info->value_type == 'F') {
    						$discount = $coupon_info->value * ($product['total'] / $sub_total);
    					} elseif ($coupon_info->value_type == 'P') {
    						$discount = $product['total'] / 100 * $coupon_info->value;
    					}

    					if ($product['tax_profile_id']) {

    						$tax_rates = $this->tax->getRateArray($product['total'] - ($product['total'] - $discount), $product['tax_profile_id']);
    						foreach ($tax_rates as $tax_rate) {
    							//	if ($tax_rate['value_type'] == 'P') {
    							$taxes[$tax_rate['taxrate_id']] -= $tax_rate['amount'];
    							//	}
    						}
    					}
    				}

    				$discount_total += $discount;
    			}

    			$total_data['coupon'] = array(
    					'title'      => JText::sprintf('K2STORE_COUPON_TITLE', $session->get('coupon', '', 'k2store')),
    					'value'      => -$discount_total
    			);

    			//$total_data['coupon'] = $coupon_data;
    			//less the coupon discount in the total
    			$total -= $discount_total;
    		}

    	}

    	$total_data['total_without_tax'] = $total;

    	//taxes
    	foreach ($taxes as $key => $value) {
    		if ($value > 0) {
    			$tax_data[]= array(
    					'title'      => $this->tax->getRateName($key),
    					'value'      => $value
    			);
    			$total += $value;
    		}
    	}
    	$total_data['taxes'] = $tax_data;

    	$total_data['total'] = $total;

    	return $total_data;

    }

}
