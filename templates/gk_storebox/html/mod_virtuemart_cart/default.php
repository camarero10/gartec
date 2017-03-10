<?php // no direct access
defined('_JEXEC') or die('Restricted access');
$show_price = (bool)$params->get( 'show_price', 1 ); 
//dump ($cart,'mod cart');
// Ajax is displayed in vm_cart_products
// ALL THE DISPLAY IS Done by Ajax using "hiddencontainer" ?>

<!-- Virtuemart 2 Ajax Card -->

<div class="vmCartModule <?php echo $params->get('moduleclass_sfx'); ?>" id="vmCartModule">
	<?php
if ($show_product_list) {
	?>
	<h3><?php echo JText::_('COM_VIRTUEMART_CART_TITLE'); ?></h3>
	<dl>
		<?php foreach ($data->products as $product) : ?>
		<dt><span><?php echo  $product['quantity'] ?> x </span><?php echo  $product['product_name'] ?></dt>
		<?php if ($show_price) : ?>
		<dd><?php echo  $product['prices'] ?></dd>
		<?php endif; ?>
		<?php if ( !empty($product['product_attributes']) ) : ?>
		<dd><?php echo $product['product_attributes'] ?></dd>
		<?php endif; ?>
		<?php if (!empty($product['customProductData']) ) : ?>
		<dd class="customProductData"><?php echo $product['customProductData'] ?></dd>
		<?php endif; ?>
		<?php endforeach; ?>
	</dl>
	<?php } ?>
	<dl>
		<dt><?php echo  $data->totalProductTxt ?></dt>
		<?php if($show_price) : ?>
		<dd>
			<?php if ($data->totalProduct and $show_price) echo  $data->billTotal; ?>
		</dd>
		<?php endif; ?>
		<dd class="show_cart">
			<?php if ($data->totalProduct and $show_price) echo  $data->cart_show; ?>
		</dd>
	</dl>
	<noscript>
	<?php echo JText::_('MOD_VIRTUEMART_CART_AJAX_CART_PLZ_JAVASCRIPT') ?>
	</noscript>
</div>
<?php exit(); ?>