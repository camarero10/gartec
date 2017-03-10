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


//no direct access
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.modal');
$row = @$this->row;
$order = @$this->order;
$items = @$order->getItems();
$order_state_save_link = JRoute::_('index.php?option=com_k2store&view=orders&task=orderstatesave');
$uri = JURI::root(true);
require_once(JPATH_ADMINISTRATOR.'/components/com_k2store/library/k2item.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/popup.php');
require_once (JPATH_SITE.'/components/com_k2store/helpers/orders.php');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/library/selectable/base.php');
$selectableBase = new K2StoreSelectableBase();
?>
<div class="container-fluid k2store k2store_orders">

<div class='row-fluid'>
	<div class="span6 pull-left">
		<a class="btn" href="<?php echo JRoute::_("index.php?option=com_k2store&view=orders"); ?>"><?php echo JText::_( 'K2STORE_ORDER_RETURN_TO_LIST' ); ?></a>
	</div>
<div class="span6 pull-right">
	<?php
	$url = JRoute::_( "index.php?option=com_k2store&view=orders&task=printOrder&tmpl=component&id=".@$row->id);
	$text = JText::_( "K2STORE_PRINT_INVOICE" );
	echo '<span class="btn">';
	echo K2StorePopup::popup( $url, $text );
	echo '</span>';
	?>
	</div>


</div>

<div class='row-fluid'>
	<div class="span12">
		<h3><?php echo JText::_( "K2STORE_ORDER_DETAIL" ); ?></h3>
	</div>
</div>

<div class='row-fluid'>
	<div class="span6">
		<h3><?php echo JText::_("K2STORE_ORDER_INFORMATION"); ?></h3>
		<dl class="dl-horizontal">
			<!--
			<dt><?php echo JText::_("K2STORE_ORDER_ID"); ?> </dt>
			<dd><?php echo @$row->order_id; ?></dd>
			-->

			<dt><?php echo JText::_("K2STORE_INVOICE_NO"); ?></dt>
			<dd><?php echo @$row->id; ?></dd>

			<dt><?php echo JText::_("K2STORE_ORDER_PAYMENT_AMOUNT"); ?></dt>
			<dd><?php echo K2StorePrices::number( $row->order_total, $row->currency_code, $row->currency_value ); ?></dd>

			<dt><?php echo JText::_("K2STORE_ORDER_DATE"); ?></dt>
			<dd><?php echo JHTML::_('date', $row->created_date, $this->params->get('date_format', JText::_('DATE_FORMAT_LC1'))); ?></dd>

			<dt><?php echo JText::_("K2STORE_ORDER_STATUS"); ?></dt>
			<dd>
			<span class="label <?php echo $this->label_class;?> order-state-label">
				<?php
				if(JString::strlen($row->order_state) > 0) {
					echo JText::_($row->order_state);
				} else {
					echo JText::_('K2STORE_PAYSTATUS_INCOMPLETE');
				}
				?>
			</span>
			</dd>
			</dl>


		<div class="well">
		<dl class="dl-horizontal">
			<dt>
					<?php echo JText::_("K2STORE_CHANGE_ORDER_STATUS"); ?>
				</dt>
				<dd>
					<?php // echo JText::_((@$row->order_state=='')?'':@$row->order_state); ?>
					<form action="<?php echo $order_state_save_link; ?>" method="post"
						name="adminForm">
						<?php echo @$this->order_state; ?>
						<br />
						<label>
						<input type="checkbox" name="notify_customer" value="1" />
						<?php echo JText::_('K2STORE_NOTIFY_CUSTOMER');?>
						</label>
						<br />
						<input type="hidden" name="id" value="<?php echo $row->id; ?>" />
						<input class="btn btn-primary" type="submit"
							value="<?php echo JText::_('K2STORE_ORDER_STATUS_SAVE'); ?>" />
					</form>
				</dd>
		</dl>
		</div>

		<h3><?php echo JText::_("K2STORE_ORDER_PAYMENT_INFORMATION"); ?></h3>
		<dl class="dl-horizontal">
			<dt><?php echo JText::_('K2STORE_ORDER_PAYMENT_TYPE'); ?></dt>
			<dd><?php echo JText::_($row->orderpayment_type); ?></dd>

			<?php if ($row->orderpayment_type == 'payment_offline') : ?>
							<dt><?php echo JText::_('K2STORE_ORDER_PAYMENT_MODE'); ?></dt>
							<dd><?php echo JText::_($row->transaction_details); ?>
							</dd>
			<?php endif; ?>
			<?php if(!empty($row->transaction_id)): ?>
							<dt><?php echo JText::_('K2STORE_ORDER_TRANSACTION_ID'); ?></dt>
							<dd><?php echo $row->transaction_id; ?></dd>
			<?php endif; ?>

		<?php echo $selectableBase->getFormatedCustomFields($row, 'customfields', 'payment'); ?>

		<dt></dt>
		<dd>
		<br />
		<?php
				$log_url = "index.php?option=com_k2store&view=orders&task=viewtxnlog&tmpl=component&id=".@$row->id;
				echo '<span class="btn">';
				echo K2StorePopup::popup( $log_url, JText::_('K2STORE_ORDER_TRANSACTION_LOG') );
				echo '</span>';
			?>
			</dd>
		</dl>

		<?php if(isset($this->shipping_info->ordershipping_type)): ?>
					<h3><?php echo JText::_('K2STORE_ORDER_SHIPPING_INFORMATION') ?></h3>
					<dl class="dl-horizontal">
						<dt><?php echo JText::_('K2STORE_ORDER_SHIPPING_NAME') ?></dt>
						<dd><?php echo $this->shipping_info->ordershipping_name; ?></dd>
					</dl>
		<?php endif; ?>

	</div>
	<div class="span6">
		<h3><?php echo JText::_("K2STORE_ORDER_CUSTOMER_INFORMATION"); ?></h3>
		<dl class="dl-horizontal">
				<dt><?php echo JText::_("K2STORE_BILLING_ADDRESS"); ?></dt>
				<dd>
				<address>

							<?php

								echo '<strong>'.$row->billing_first_name." ".$row->billing_last_name."</strong><br/>";
								echo $row->billing_address_1.", ";
								echo $row->billing_address_2 ? $row->billing_address_2.", " : "<br/>";
								echo $row->billing_city.", ";
								echo $row->billing_zone_name ? $row->billing_zone_name." - " : "";
								echo $row->billing_zip." <br/>";
								echo $row->billing_country_name." <br/> ".JText::_('K2STORE_TELEPHONE').":";
								echo $row->billing_phone_1." , ";
								echo $row->billing_phone_2 ? $row->billing_phone_2.", " : "<br/> ";
								echo '<br/> ';
								echo $row->user_email;
								echo '<br/> ';
								echo $row->billing_company ? JText::_('K2STORE_COMPANY_NAME').':&nbsp;'.$row->billing_company."</br>" : "";
								echo $row->billing_tax_number ? JText::_('K2STORE_TAX_ID').':&nbsp;'.$row->billing_tax_number."</br>" : "";

							?>
					</address>
					<?php echo $selectableBase->getFormatedCustomFields($row, 'customfields', 'billing'); ?>
					</dd>
		 <?php if($this->params->get('show_shipping_address') ): ?>
						<dt><?php echo JText::_("K2STORE_SHIPPING_ADDRESS"); ?></dt>
							<dd>
							<address>
							<?php
								echo '<strong>'.$row->shipping_first_name." ".$row->shipping_last_name."</strong><br/>";
								echo $row->shipping_address_1.", ";
								echo $row->shipping_address_2 ? $row->shipping_address_2.", " : "<br/>";
								echo $row->shipping_city.", ";
								echo $row->shipping_zone_name ? $row->shipping_zone_name." - " : "";
								echo $row->shipping_zip." <br/>";
								echo $row->shipping_country_name;

								echo $row->shipping_phone_1." , ";
								echo $row->shipping_phone_2 ? $row->shipping_phone_2.", " : "<br/> ";
								echo '<br/> ';
								echo $row->shipping_company ? JText::_('K2STORE_COMPANY_NAME').':&nbsp;'.$row->shipping_company."</br>" : "";
								echo $row->shipping_tax_number ? JText::_('K2STORE_TAX_ID').':&nbsp;'.$row->shipping_tax_number."</br>" : "";

							?>
							</address>
							<?php echo $selectableBase->getFormatedCustomFields($row, 'customfields', 'shipping'); ?>
							</dd>
					<?php endif; ?>
		</dl>

	 <?php if(!empty($row->customer_note)): ?>
	 <dl class="dl-horizontal">
		 <dt><?php echo JText::_("K2STORE_ORDER_CUSTOMER_NOTE"); ?></dt>
		 <dd><?php echo $row->customer_note; ?></dd>
	</dl>
	<?php endif; ?>
</div>
</div>
<div class="row-fluid">
<div class="span12">
	<h3>
		<?php echo JText::_("K2STORE_ITEMS_IN_ORDER"); ?>
	</h3>

	<table class="cart_order table table-striped table-bordered" style="clear: both;">
		<thead>
			<tr>
			 <?php if($this->params->get('show_thumb_cart') && $this->params->get('show_thumb_invoice', 0)) : ?>
			 	<th style="text-align: left;"><?php echo JText::_("K2STORE_CART_ITEM_IMAGE"); ?></th>
			 <?php endif; ?>
				<th style="text-align: left;"><?php echo JText::_("K2STORE_CART_ITEM"); ?></th>
				<th style="width: 150px; text-align: center;"><?php echo JText::_("K2STORE_CART_ITEM_QUANTITY"); ?>
				</th>
				<th style="width: 150px; text-align: right;"><?php echo JText::_("K2STORE_ITEM_PRICE"); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php $i=0; $k=0; ?>
			<?php foreach (@$items as $item) : ?>
			<?php
				$colspan = 2;
				if($this->params->get('show_thumb_cart') && $this->params->get('show_thumb_invoice', 0)) {
					$image_path = K2StoreItem::getK2Image($item->product_id, $this->params);
					$colspan = 3;
				}
			?>
			<tr class='row<?php echo $k; ?>'>

				<?php if($this->params->get('show_thumb_cart') && $this->params->get('show_thumb_invoice', 0)) : ?>
                    <td style="text-align: center;">
                       <?php if(!empty($image_path)) : ?>
                        <img src="<?php echo $image_path; ?>" class="itemImg<?php echo $this->params->get('cartimage_size','small') ?>" />
                        <?php endif;?>
                    </td>
                 <?php endif; ?>

				<td><strong>
						<?php echo JText::_( $item->orderitem_name ); ?> </strong> <br />
						<!-- start of orderitem attributes -->

						<!-- backward compatibility -->
						<?php if(!K2StoreOrdersHelper::isJSON(stripslashes($item->orderitem_attribute_names))): ?>

							<?php if (!empty($item->orderitem_attribute_names)) : ?>
								<span><?php echo $item->orderitem_attribute_names; ?></span>
							<?php endif; ?>
						<br />
						<?php else: ?>
						<!-- since 3.1.0. Parse attributes that are saved in JSON format -->
						<?php if (!empty($item->orderitem_attribute_names)) : ?>
                            <?php
                            	//first convert from JSON to array
                            	$registry = new JRegistry;
                            	$registry->loadString(stripslashes($item->orderitem_attribute_names), 'JSON');
                            	$product_options = $registry->toObject();
                            ?>
                            	<?php foreach ($product_options as $option) : ?>
             				   - <small><?php echo $option->name; ?>: <?php echo $option->value; ?></small><br />
            				   <?php endforeach; ?>
                            <br/>
                        <?php endif; ?>
					<?php endif; ?>
					<!-- end of orderitem attributes -->


					<?php if (!empty($item->orderitem_sku)) : ?> <b><?php echo JText::_( "K2STORE_SKU" ); ?>:</b>
					<?php echo $item->orderitem_sku; ?> <br /> <?php endif; ?> <b><?php echo JText::_( "K2STORE_CART_ITEM_UNIT_PRICE" ); ?>:</b>
					<?php echo K2StorePrices::number( $item->orderitem_price, $row->currency_code, $row->currency_value); ?>
				</td>
				<td style="text-align: center;"><?php echo $item->orderitem_quantity; ?>
				</td>
				<td style="text-align: right;"><?php echo K2StorePrices::number( $item->orderitem_final_price, $row->currency_code, $row->currency_value ); ?>
				</td>
			</tr>
			<?php $i=$i+1; $k = (1 - $k); ?>
			<?php endforeach; ?>

			<?php if (empty($items)) : ?>
			<tr>
				<td colspan="10" align="center"><?php echo JText::_('K2STORE_NO_ITEMS'); ?>
				</td>
			</tr>
			<?php endif; ?>
		</tbody>
		<tfoot>
			<tr>
				<th colspan="<?php echo $colspan; ?>" style="text-align: right;"><?php echo JText::_( "K2STORE_CART_SUBTOTAL" ); ?>
				</th>
				<th style="text-align: right;"><?php echo K2StorePrices::number($row->order_subtotal, $row->currency_code, $row->currency_value); ?>
				</th>
			</tr>

			<tr>
				<th colspan="<?php echo $colspan; ?>" style="text-align: right;">
				<?php  echo "(+)";?>
				<?php echo JText::_( "K2STORE_SHIPPING" ); ?>
				</th>
				<th style="text-align: right;"><?php echo K2StorePrices::number($row->order_shipping, $row->currency_code, $row->currency_value); ?>
				</th>
			</tr>

			<?php if($row->order_shipping_tax > 0):?>
			<tr>
				<th colspan="<?php echo $colspan; ?>" style="text-align: right;">
				<?php echo "(+)";?>
				<?php echo JText::_( "K2STORE_CART_SHIPPING_TAX" ); ?>
				</th>
				<th style="text-align: right;"><?php echo K2StorePrices::number($row->order_shipping_tax, $row->currency_code, $row->currency_value); ?>
				</th>
			</tr>
			<?php endif; ?>

			<tr>
				<th colspan="<?php echo $colspan; ?>" style="text-align: right;">
				<?php
				if (!empty($row->order_discount ))
                    	{
                            echo "(-)";
                            echo JText::_("K2STORE_CART_DISCOUNT");
                    	}
                   ?>
				</th>

				<th style="text-align: right;">
				<?php
				if (!empty($row->order_discount )) {
					echo K2StorePrices::number($row->order_discount, $row->currency_code, $row->currency_value);
				}
				?>
				</th>
			</tr>

			<tr>
				<th colspan="<?php echo $colspan; ?>" style="text-align: right;"><?php
				if (!empty($this->show_tax)) {
					echo JText::_("K2STORE_CART_PRODUCT_TAX_INCLUDED");
				}
				else { echo JText::_("K2STORE_CART_PRODUCT_TAX");
				}
				?>
				</th>
				<th style="text-align: right;"><?php echo K2StorePrices::number($row->order_tax, $row->currency_code, $row->currency_value); ?>
				</th>
			</tr>

			<?php if($row->order_surcharge > 0):?>
				<tr>
				<th colspan="<?php echo $colspan; ?>" style="text-align: right;">
				<?php echo JText::_("K2STORE_CART_SURCHARGE"); ?>
				</th>
				<th style="text-align: right;"><?php echo K2StorePrices::number($row->order_surcharge, $row->currency_code, $row->currency_value); ?>
				</th>

			</tr>
			<?php endif; ?>

			<tr>
				<th colspan="<?php echo $colspan; ?>" style="font-size: 120%; text-align: right;"><?php echo JText::_( "K2STORE_CART_GRANDTOTAL" ); ?>
				</th>
				<th style="font-size: 120%; text-align: right;"><?php echo K2StorePrices::number($row->order_total, $row->currency_code, $row->currency_value); ?>
				</th>

			</tr>
		</tfoot>
	</table>
</div>
</div>
</div>
