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
$item = @$this->item;
$formName = 'k2storeadminForm_'.$item->product_id;
require_once (JPATH_SITE.'/components/com_k2store/helpers/cart.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/select.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/inventory.php');

if(isset($this->product->item_cart_text) && (JString::strlen($this->product->item_cart_text) > 0 ) ) {
	$cart_text = $this->product->item_cart_text;
} else {
	$cart_text = JText::_('K2STORE_ADD_TO_CART');
}

$inventoryCheck = K2StoreInventory::isAllowed($item);
$action = JRoute::_('index.php?option=com_k2store&view=mycart');
?>
<div id="k2store-product-<?php echo $item->product_id; ?>" class="k2store k2store-product-info">
<?php if(count(JModuleHelper::getModules('k2store-addtocart-top')) > 0 ): ?>
	<div class="k2store_modules">
		<?php echo K2StoreHelperModules::loadposition('k2store-addtocart-top'); ?>
	</div>
<?php endif; ?>

<form action="<?php echo $action; ?>" method="post" class="k2storeCartForm1" id="<?php echo $formName; ?>" name="<?php echo $formName; ?>" enctype="multipart/form-data">

	<div class="warning"></div>

	<?php if($this->params->get('show_price_field', 1)):?>
	<!--base price-->
		<span id="product_price_<?php echo $item->product_id; ?>" class="product_price">
			<?php if($item->special_price > 0.000) echo '<strike>'; ?>
    			<?php  echo K2StoreHelperCart::dispayPriceWithTax($item->price, $item->tax, $this->params->get('price_display_options', 1)); ?>
    		<?php if($item->special_price > 0.000) echo '</strike>'; ?>
    	</span>

    	<!--special price-->
		 <?php if($item->special_price > 0.000) :?>
		    <span id="product_special_price_<?php echo $item->product_id; ?>" class="product_special_price">
		    	<?php  echo K2StoreHelperCart::dispayPriceWithTax($item->special_price, $item->sp_tax, $this->params->get('price_display_options', 1)); ?>
		    </span>
		 <?php endif;?>

   <?php endif; ?>


   <!-- sku -->

   <?php if($this->params->get('show_sku_field', 0)):?>
	 		<div id='product_sku_<?php echo $item->product_id; ?>' class="product_sku">
				<span><?php echo JText::_( "K2STORE_SKU" ); ?>:</span>
				<span><?php echo $this->product->product_sku; ?></span>
     		</div>
	<?php endif;?>

	<!-- stock -->
	<?php if($this->params->get('show_stock_field', 0) && K2STORE_PRO==1):?>
		<div id='product_stock_<?php echo $item->product_id; ?>' class="product_stock">
		<?php if($inventoryCheck->can_allow):?>
			<?php if($item->product_stock > 0): ?>
	 			<span><?php echo JText::_( "K2STORE_IN_STOCK" ); ?>:</span>
				<span><?php echo $item->product_stock; ?></span>
			<?php endif; ?>
		<?php elseif($inventoryCheck->backorder && $inventoryCheck->can_allow == 0):?>
			<?php echo JText::_('K2STORE_ADDTOCART_BACKORDER_ALERT'); ?>
		<?php else: ?>
				<span><?php echo JText::_( "K2STORE_OUT_OF_STOCK" ); ?></span>
		<?php endif; ?>
		</div>
	<?php endif;?>

	<!-- is catalogue mode enabled?-->
	<?php if(!$this->params->get('catalog_mode', 0)):?>

		<?php
		//registered users check
		$allow = true;
		$is_register = $this->params->get('isregister', 0);
		if($is_register && !JFactory::getUser()->id) {
			//user not logged in. set to false
			$allow = false;
		}
		?>

		<?php if($allow):?>

				   <!-- product options -->
				   <?php echo $this->loadTemplate('options'); ?>
				   <!--  trigger plugin events -->
				   	<?php if(isset($item->event->K2StoreBeforeCartDisplay)):?>
						<?php echo $item->event->K2StoreBeforeCartDisplay; ?>
					<?php endif; ?>

					<!-- Quantity field -->
						<?php if($this->params->get('show_qty_field', 1)):?>
					 		<div id='product_quantity_input_<?php echo $item->product_id; ?>' class="product_quantity_input">
								<span><?php echo JText::_( "K2STORE_ADDTOCART_QUANTITY" ); ?>:</span>
								<input type="text" name="product_qty" value="<?php echo $item->product_quantity; ?>" size="2" />
								<?php if(isset($item->item_minimum_notice)):?>
								<br />
								<small class="k2store-minmum-quantity muted"><?php echo $item->item_minimum_notice; ?></small>
								<?php endif; ?>
				     		</div>
						<?php else:?>
						<input type="hidden" name="product_qty" value="<?php echo $item->product_quantity; ?>" size="2" />
					    <?php endif; ?>

				     <!-- Add to cart button -->
					<?php if($inventoryCheck->can_allow || $inventoryCheck->backorder):?>
						<div id='add_to_cart_<?php echo $item->product_id; ?>' class="k2store_add_to_cart">
					        <input type="hidden" id="k2store_product_id" name="product_id" value="<?php echo $item->product_id; ?>" />

					        <?php echo JHTML::_( 'form.token' ); ?>
					        <input type="hidden" name="return" value="<?php echo base64_encode( JUri::getInstance()->toString() ); ?>" />
					        <input value="<?php echo $cart_text; ?>" type="submit" class="k2store_cart_button btn btn-primary" />
					    </div>
				     <?php else: ?>
				     <div class="k2store_no_stock">
				      <input value="<?php echo JText::_('K2STORE_OUT_OF_STOCK'); ?>" type="button" class="k2store_cart_button k2store_button_no_stock btn btn-warning" />
				     </div>
					<?php endif; ?>

		 <?php endif; //registerd users check ?>

	<?php endif; //catalogue mode check ?>


				<div class="k2store-notification" style="display: none;">
						<div class="message"></div>
						<div class="cart_link"><a class="btn btn-success" href="<?php echo $action; ?>"><?php echo JText::_('K2STORE_VIEW_CART')?></a></div>
						<div class="cart_dialogue_close" onclick="jQuery(this).parent().slideUp().hide();">x</div>
				</div>
				<div class="error_container">
					<div class="k2product"></div>
					<div class="k2stock"></div>
				</div>

<input type="hidden" name="option" value="com_k2store" />
<input type="hidden" name="view" value="mycart" />
<input type="hidden" id="task" name="task" value="add" />
</form>

	<?php if(count(JModuleHelper::getModules('k2store-addtocart-bottom')) > 0 ): ?>
	<div class="k2store_modules">
		<?php echo K2StoreHelperModules::loadposition('k2store-addtocart-bottom'); ?>
	</div>
	<?php endif; ?>
</div>