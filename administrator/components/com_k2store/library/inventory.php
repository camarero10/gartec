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

//class to manage inventory

// no direct access
defined('_JEXEC') or die('Restricted access');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/version.php');
class K2StoreInventory {

public static function setInventory($orderpayment_id, $order_state_id) {

		//only reduce the inventory if the order is successful. 1==CONFIRMED.
		//do it only once.
		$dispatcher = JDispatcher::getInstance();
		JPluginHelper::importPlugin ('k2store');

		if($order_state_id == 1) {

			require_once(JPATH_SITE.'/components/com_k2store/models/orders.php');
			$model =  new K2StoreModelOrders();
			//lets set the id first
			$model->setId($orderpayment_id);

			$order = $model->getTable( 'orders' );
			$order->load( $model->getId() );

			//trigger the plugin
			$dispatcher->trigger( "onK2StoreBeforeInventory", array($order->id));

			//Do it once and set that the stock is adjusted
			if($order->stock_adjusted != 1) {
				$orderitems = $order->getItems();
				foreach($orderitems as $item) {
					K2StoreInventory::minusStock($item->product_id, $item->orderitem_quantity);
				}
				$order->stock_adjusted == 1;
				$order->store();

				//trigger the plugin
				$dispatcher->trigger( "onK2StoreAfterInventory", array($order->id) );

			}
		} else {
			return;
		}
		return;
	}

public static function minusStock($product_id, $quantity) {
		$db = JFactory::getDbo();

		//first get stock and then minus
		$stock = K2StoreInventory::getStock($product_id);
		if($stock->manage_stock > 0){
			JTable::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_k2store/tables');
			$row = JTable::getInstance('ProductQuantities', 'Table');
			$row->load(array('product_id'=>$product_id));

			if($row->product_id == $product_id) {

				if($row->quantity >= $quantity) {
					$adjusted_quantity = $row->quantity - (int) $quantity;
				} else {
					$adjusted_quantity = 0;
				}

				$row->quantity =  $adjusted_quantity;
				$row->store();
			}
		}
	}

	public static function validateStock($product_id, $qty=1) {

		$params = JComponentHelper::getParams('com_k2store');

		//if inventory is not enabled, return true
		if(!$params->get('enable_inventory', 0)) {
			return true;
		}

		//if backorder is allowed, dont check anything. just return true
		/* if($params->get('allow_backorder', 0)) {
			return true;
		}
 */
		if(K2STORE_PRO != 1) {
			return true;
		}

		$stock = K2StoreInventory::getStock($product_id);

		//if manage stock is set to no, then return true. we dont need to track inventory for this item.

		if((int)$stock->manage_stock < 1) {
			return true;
		}

		//if stock has reached the min out qty
		if($stock->quantity <= $stock->min_out_qty) {
			return false;
		}

		//if stock has reached the min out qty
		if($qty > $stock->quantity) {
			return false;
		}

		if($stock->quantity < 0) {
			return false;
		}

		return true;
	}

	public static function getStock($product_id) {

		$db = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select('*');
		$query->from('#__k2store_productquantities');
		$query->where('product_id='.$db->quote($product_id));
		$db->setQuery($query);
		$stock = $db->loadObject();

		if(!isset($stock) || K2STORE_PRO != 1 ) {
			$stock = JTable::getInstance('ProductQuantities', 'Table');
		}

		//prepare data. We may have some settings in the store global
		require_once(JPATH_SITE.'/components/com_k2store/helpers/cart.php');

		$store_config = K2StoreHelperCart::getStoreAddress();

		if($stock->use_store_config_min_out_qty > 0) {
			$stock->min_out_qty = (float) $store_config->store_min_out_qty;
		}

		if($stock->use_store_config_min_sale_qty > 0) {
			$stock->min_sale_qty = (float) $store_config->store_min_sale_qty;
		}

		if($stock->use_store_config_max_sale_qty > 0) {
			$stock->max_sale_qty = (float) $store_config->store_max_sale_qty;
		}

		if($stock->use_store_config_notify_qty > 0) {
			$stock->notify_qty = (float) $store_config->store_notify_qty;
		}

		return $stock;
	}

	public static function isAllowed($item) {


		$params = JComponentHelper::getParams('com_k2store');

		//set the result object
		$result = new JObject();
		$result->backorder = false;
		//we always want to allow users to buy. so initialise to 1.
		$result->can_allow = 1;

		//if basic version return true
		if(K2STORE_PRO != 1) {
			$result->can_allow = 1;
			return $result;
		}
		//first check if inventory is enabled.

		if(!$params->get('enable_inventory', 0)) {
			//if not enabled, allow adding and return here
			$result->can_allow = 1;
			return $result;
		}

		//now, inventory seems enabled. So check stock.
		if(self::validateStock($item->product_id)) { //if true, product is available
			$result->can_allow = 1;
		}elseif($item->product_stock == -1 || is_null($item->product_stock)) { //if -1, then this is disabled. If empty, assume it's disabled
			$result->can_allow = 1;
		} else {
			$result->can_allow = 0;
		}

		//if backorder is allowed, set it and override to allow adding
		if($params->get('enable_inventory', 0) && $params->get('allow_backorder', 0)) {
			$result->backorder = true;
		}

		return $result;
	}


	public static function validateQuantityRestrictions($products) {

		if(K2STORE_PRO != 1) {
			return true;
		}
		$error = '';
		foreach ($products as $product) {
			$product_total = 0;

			foreach ($products as $product_2) {
				if ($product_2['product_id'] == $product['product_id']) {
					$product_total += $product_2['quantity'];
				}
			}

				//validate only if it is set
				if(isset($product['stock']->min_sale_qty) && $product['stock']->min_sale_qty > 0) {
					if ($product['stock']->min_sale_qty > $product_total) {
						$error .= JText::sprintf('K2STORE_MINIMUM_QUANTITY_REQUIRED', $product['name'], (int) $product['stock']->min_sale_qty, $product_total );

					}
				}

				if(isset($product['stock']->max_sale_qty) && $product['stock']->max_sale_qty > 0) {
					if ($product_total > $product['stock']->max_sale_qty ) {
						$error .=  JText::sprintf('K2STORE_MAXIMUM_QUANTITY_WARNING', $product['name'], (int) $product['stock']->max_sale_qty, $product_total);
					}
				}
			}

		if(!empty($error)) {
			throw new Exception($error);
			return false;
		}

		return true;
	}

}