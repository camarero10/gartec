<?php
/**
 *
 * Show the product details page
 *
 * @package	VirtueMart
 * @subpackage
 * @author Max Milbers, Eugen Stranz, Max Galt
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2014 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: default.php 8610 2014-12-02 18:53:19Z Milbo $
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/* Let's see if we found the product */
if (empty($this->product)) {
	echo vmText::_('COM_VIRTUEMART_PRODUCT_NOT_FOUND');
	echo '<br /><br />  ' . $this->continue_link_html;
	return;
}

echo shopFunctionsF::renderVmSubLayout('askrecomjs',array('product'=>$this->product));

if(vRequest::getInt('print',false)){ ?>
<body onload="javascript:print();">
<?php } ?>

<div class="product-container productdetails-view productdetails">
	<?php if (false && VmConfig::get('product_navigation', 1)) : ?>
        
        <?php // Back To Category Button
        if ($this->product->virtuemart_category_id) {
        	$catURL =  JRoute::_('index.php?option=com_virtuemart&view=category&virtuemart_category_id='.$this->product->virtuemart_category_id, FALSE);
        	$categoryName = $this->product->category_name ;
        } else {
        	$catURL =  JRoute::_('index.php?option=com_virtuemart');
        	$categoryName = vmText::_('COM_VIRTUEMART_SHOP_HOME') ;
        }
        ?>
        <div class="back-to-category">
        	<a href="<?php echo $catURL ?>" class="product-details" title="<?php echo $categoryName ?>"><?php echo vmText::sprintf('COM_VIRTUEMART_CATEGORY_BACK_TO',$categoryName) ?></a>
        </div>
    <?php endif; // Product Navigation END ?>
	<div class="productDetails">
	    <div class="img-productDetails">
	    	<?php 
	    		echo $this->loadTemplate('images');
	    	
	    		$count_images = count ($this->product->images);
	    		if ($count_images > 1) {
	    			echo $this->loadTemplate('images_additional');
	    		}
	    	?>
	   	</div>
	   	<div class="text-productDetails">
		    <h1 itemprop="name"><?php echo $this->product->product_name ?></h1>
	    	<?php echo $this->product->event->afterDisplayTitle ?>
			<div class="ref">Ref: <?php echo $this->product->product_sku;?></div>
			<?php if (!empty($this->product->product_desc)) :
			    ?>
		        <div class="product-description">
					<?php if (!empty($this->product->product_s_desc)) : ?>
					<div class="product-short-description">
					    <?php echo nl2br($this->product->product_s_desc); ?>
					</div>
					<?php endif; ?>
					<?php echo $this->product->product_desc; ?>
					<p>
					<?php
			        	if (file_exists(JPATH_ROOT.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'fichas'.DIRECTORY_SEPARATOR.$this->product->product_sku.'.pdf'))
			        	{
			        		echo '<a href="'.JUri::root().'images/fichas/'.$this->product->product_sku.'.pdf" target="_blank">Ver ficha del producto</a>';
			        	}
			        	else if (file_exists(JPATH_ROOT.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'fichas'.DIRECTORY_SEPARATOR.strtolower($this->product->product_sku).'pdf'))
			        	{
			        		echo '<a href="'.JUri::root().'images/fichas/'.strtolower($this->product->product_sku).'.pdf" target="_blank">Ver ficha del producto</a>';
			        	}
			        	else if (file_exists(JPATH_ROOT.DIRECTORY_SEPARATOR.'images'.DIRECTORY_SEPARATOR.'fichas'.DIRECTORY_SEPARATOR.strtoupper($this->product->product_sku).'pdf'))
			        	{
			        		echo '<a href="'.JUri::root().'images/fichas/'.strtoupper($this->product->product_sku).'.pdf" target="_blank">Ver ficha del producto</a>';
			        	}
		        	?>
					</p>
		        </div>
				<?php
				$product = $this->product;
				$position = 'normal';
				$class = 'product-fields';
			
				if (!empty($product->customfieldsSorted[$position])) {
				?>
					<?php foreach ($product->customfieldsSorted[$position] as $field) {
						if ( $field->is_hidden ) //OSP http://forum.virtuemart.net/index.php?topic=99320.0
						continue;
						?><dl class="product-field product-field-type-<?php echo $field->field_type ?>">
							<?php if ($field->custom_title != $custom_title and $field->show_title) : ?>
							<dt><?php echo vmText::_ ($field->custom_title) ?></dt>
							<?php endif; ?>
							
							<?php if (!empty($field->display)) : ?>
							<dd class="product-field-display"><?php echo $field->display ?></dd>
							<?php endif; ?>
							
							<?php if (!empty($field->custom_desc)) : ?>
							<dd class="product-field-desc"><?php echo vmText::_($field->custom_desc) ?></dd>
							<?php endif; ?>
						</dl>
					<?php
						$custom_title = $field->custom_title;
					} ?>
			<?php
			}

		    // Product Packaging
		    $product_packaging = '';
		    if ($this->product->product_box) :
			?>
		        <div class="product-box">
			    	<?php echo vmText::_('COM_VIRTUEMART_PRODUCT_UNITS_IN_BOX') .$this->product->product_box; ?>
		        </div>
		    <?php endif; // Product Packaging END ?>
			<?php
		    endif; // Product Description END
			?>
	    	
	        <div class="product-additional-info">
			    <?php
				    // Rating
				    echo shopFunctionsF::renderVmSubLayout('rating',array('showRating'=>$this->showRating,'product'=>$this->product));
				    // Manufacturer of the Product
				    if (VmConfig::get('show_manufacturers', 1) && !empty($this->product->virtuemart_manufacturer_id)) {
				        echo $this->loadTemplate('manufacturer');
				    }
			    ?>
			    
			    <?php if (VmConfig::get('show_emailfriend') || VmConfig::get('show_printicon') || VmConfig::get('pdf_icon')) : ?>
				    <?php
					    $link = 'index.php?tmpl=component&option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->virtuemart_product_id;
				
						echo $this->linkIcon($link . '&format=pdf', 'COM_VIRTUEMART_PDF', 'pdf_button', 'pdf_icon', false);
						echo $this->linkIcon($link . '&print=1', 'COM_VIRTUEMART_PRINT', 'printButton', 'show_printicon',false,true,false,'class="printModal"');
						$MailLink = 'index.php?option=com_virtuemart&view=productdetails&task=recommend&virtuemart_product_id=' . $this->product->virtuemart_product_id . '&virtuemart_category_id=' . $this->product->virtuemart_category_id . '&tmpl=component';
					    echo $this->linkIcon($MailLink, 'COM_VIRTUEMART_EMAIL', 'emailButton', 'show_emailfriend', false,true,false,'class="recommened-to-friend"');
				    ?>
				    <?php echo $this->edit_link; ?>
			    <?php endif; // PDF - Print - Email Icon END ?>
	        </div>
			
			<?php echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$this->product,'position'=>'ontop')); ?>

			<div class="vm-product-details-container">
			    <div class="spacer-buy-area">
				<?php
		
				if (is_array($this->productDisplayShipments)) {
				    foreach ($this->productDisplayShipments as $productDisplayShipment) {
					echo $productDisplayShipment . '<br />';
				    }
				}
				if (is_array($this->productDisplayPayments)) {
				    foreach ($this->productDisplayPayments as $productDisplayPayment) {
					echo $productDisplayPayment . '<br />';
				    }
				}
		
				//In case you are not happy using everywhere the same price display fromat, just create your own layout
				//in override /html/fields and use as first parameter the name of your file
				?>
				<div class="product-price" id="productPrice<?php echo $this->product->virtuemart_product_id ?>">
					<?php				
					if ($this->product->prices['salesPrice']<=0 and VmConfig::get ('askprice', 1) and isset($this->product->images[0]) and !$this->product->images[0]->file_is_downloadable) {
						$askquestion_url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&task=askquestion&virtuemart_product_id=' . $this->product->virtuemart_product_id . '&virtuemart_category_id=' . $this->product->virtuemart_category_id . '&tmpl=component', FALSE);
						?>
						<a class="ask-a-question bold" href="<?php echo $askquestion_url ?>" rel="nofollow" ><?php echo vmText::_ ('COM_VIRTUEMART_PRODUCT_ASKPRICE') ?></a>
						<?php
					} else {
						echo $this->currency->createPriceDiv ('salesPrice', 'COM_VIRTUEMART_PRODUCT_SALESPRICE', $this->product->prices);
						if ($this->product->prices['discountedPriceWithoutTax'] != $this->product->prices['priceWithoutTax']) {
							echo $this->currency->createPriceDiv ('discountedPriceWithoutTax', 'COM_VIRTUEMART_PRODUCT_SALESPRICE_WITHOUT_TAX', $this->product->prices);
						} else {
							echo $this->currency->createPriceDiv ('priceWithoutTax', 'COM_VIRTUEMART_PRODUCT_SALESPRICE_WITHOUT_TAX', $this->product->prices);
						}
						
						echo $this->currency->createPriceDiv ('basePrice', 'COM_VIRTUEMART_PRODUCT_BASEPRICE', $this->product->prices);
						echo $this->currency->createPriceDiv ('basePriceVariant', 'COM_VIRTUEMART_PRODUCT_BASEPRICE_VARIANT', $this->product->prices);
						echo $this->currency->createPriceDiv ('variantModification', 'COM_VIRTUEMART_PRODUCT_VARIANT_MOD', $this->product->prices);
						
						if (round($this->product->prices['basePriceWithTax'],$this->currency->_priceConfig['salesPrice'][1]) != $this->product->prices['salesPrice']) {
							echo '<span class="price-crossed" >' . $this->currency->createPriceDiv ('basePriceWithTax', 'COM_VIRTUEMART_PRODUCT_BASEPRICE_WITHTAX', $this->product->prices) . "</span>";
						}
						if (round($this->product->prices['salesPriceWithDiscount'],$this->currency->_priceConfig['salesPrice'][1]) != $this->product->prices['salesPrice']) {
							echo $this->currency->createPriceDiv ('salesPriceWithDiscount', 'COM_VIRTUEMART_PRODUCT_SALESPRICE_WITH_DISCOUNT', $this->product->prices);
						}
						echo $this->currency->createPriceDiv ('discountAmount', 'COM_VIRTUEMART_PRODUCT_DISCOUNT_AMOUNT', $this->product->prices);
						echo $this->currency->createPriceDiv ('taxAmount', 'COM_VIRTUEMART_PRODUCT_TAX_AMOUNT', $this->product->prices);
						$unitPriceDescription = vmText::sprintf ('COM_VIRTUEMART_PRODUCT_UNITPRICE', vmText::_('COM_VIRTUEMART_UNIT_SYMBOL_'.$this->product->product_unit));
						echo $this->currency->createPriceDiv ('unitPrice', $unitPriceDescription, $this->product->prices);
					}
					?>
				</div>
					
				<?php  
				// Ask a question about this product
				if (VmConfig::get('ask_question', 0) == 1) :
					$askquestion_url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&task=askquestion&virtuemart_product_id=' . $this->product->virtuemart_product_id . '&virtuemart_category_id=' . $this->product->virtuemart_category_id . '&tmpl=component', FALSE);
					?>
					<div class="ask-a-question">
						<a class="ask-a-question" href="<?php echo $askquestion_url ?>" rel="nofollow" ><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_ENQUIRY_LBL') ?></a>
					</div>
				<?php endif; ?>
				
				<?php if ( VmConfig::get ('display_stock', 1) || $this->product->product_box) : ?>
				<dl class="productDetailInfo">
					<?php if ( VmConfig::get ('display_stock', 1)) : ?>
					<dt>
						<?php echo JText::_('COM_VIRTUEMART_STOCK_LEVEL_DISPLAY_TITLE_TIP'); ?>:
					</dt>
					<dd>
						<?php echo $this->product->product_in_stock; ?>
					</dd>
					<?php endif; ?>
					
					<?php if ($this->product->product_box) : ?>
					<dt>
						<?php echo JText::_('COM_VIRTUEMART_PRODUCT_UNITS_IN_BOX'); ?>
					</dt>
					<dd>
						<?php echo $this->product->product_box; ?>
					</dd>
					<?php endif; ?>
				</dl>
				<?php endif; ?>
				
				<?php
					$product = $this->product;
					
					$addtoCartButton = '';
					if(!VmConfig::get('use_as_catalog', 0)){
						if($product->addToCartButton){
							$addtoCartButton = $product->addToCartButton;
						} else {
							$addtoCartButton = shopFunctionsF::getAddToCartButton ($product->orderable);
						}
					
					}
					$position = 'addtocart';
					if (isset($product->step_order_level))
						$step=$product->step_order_level;
					else
						$step=1;
					if($step==0)
						$step=1;
					$alert=JText::sprintf ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED', $step);
					
					$init = 1;
					if(isset($product->init)){
						$init = $product->init;
					}
					
					if(!empty($product->min_order_level) and $init<$product->min_order_level){
						$init = $product->min_order_level;
					}
					
					$step=1;
					if (!empty($product->step_order_level)){
						$step=$product->step_order_level;
						if(empty($product->min_order_level) and !isset($product->init)){
							$init = $step;
						}
					}
					
					$maxOrder= '';
					if (!empty($product->max_order_level)){
						$maxOrder = ' max="'.$product->max_order_level.'" ';
					}
				?>
				<div class="addtocart-area">
					<form method="post" class="product js-recalculate" action="<?php echo JRoute::_('index.php?option=com_virtuemart',false); ?>">
						
						<?php

						echo shopFunctionsF::renderVmSubLayout('customfields',array('product'=>$product,'position'=>'addtocart'));
			
						if (!VmConfig::get('use_as_catalog', 0)  ) { ?>
			
							<div class="addtocart-bar">
							<script type="text/javascript">
									function check(obj) {
							 		// use the modulus operator '%' to see if there is a remainder
									remainder=obj.value % <?php echo $step?>;
									quantity=obj.value;
							 		if (remainder  != 0) {
							 			alert('<?php echo $alert?>!');
							 			obj.value = quantity-remainder;
							 			return false;
							 			}
							 		return true;
							 		}
							</script> 
							
							<?php
							// Display the quantity box
							$stockhandle = VmConfig::get ('stockhandle', 'none');
							if (($stockhandle == 'disableit' or $stockhandle == 'disableadd') and ($product->product_in_stock - $product->product_ordered) < 1) { ?>
								<a href="<?php echo JRoute::_ ('index.php?option=com_virtuemart&view=productdetails&layout=notify&virtuemart_product_id=' . $product->virtuemart_product_id); ?>" class="notify"><?php echo vmText::_ ('COM_VIRTUEMART_CART_NOTIFY') ?></a><?php
							} else {
								$tmpPrice = (float) $product->prices['costPrice'];
								if (!( VmConfig::get('askprice', true) and empty($tmpPrice) ) ) { ?>
									<?php if ($product->orderable) : ?>
									<label for="quantity<?php echo $product->virtuemart_product_id; ?>" class="quantity_box"><?php echo vmText::_ ('COM_VIRTUEMART_CART_QUANTITY'); ?>: </label>
									<span class="quantity-box">
										<input type="text" class="quantity-input js-recalculate" name="quantity[]"
											   onblur="Virtuemart.checkQuantity(this,<?php echo $step?>,'<?php echo vmText::_ ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED')?>');"
											   onclick="Virtuemart.checkQuantity(this,<?php echo $step?>,'<?php echo vmText::_ ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED')?>');"
											   onchange="Virtuemart.checkQuantity(this,<?php echo $step?>,'<?php echo vmText::_ ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED')?>');"
											   onsubmit="Virtuemart.checkQuantity(this,<?php echo $step?>,'<?php echo vmText::_ ('COM_VIRTUEMART_WRONG_AMOUNT_ADDED')?>');"
											   value="<?php echo $init; ?>" init="<?php echo $init; ?>" step="<?php echo $step; ?>" <?php echo $maxOrder; ?> />
									</span>
										<span class="quantity-controls js-recalculate">
										<input type="button" value="+" class="quantity-controls quantity-plus"/>
										<input type="button" value="-" class="quantity-controls quantity-minus"/>
									</span>
									<?php endif; ?>
			
									<span class="addtocart-button">
										<?php echo $addtoCartButton ?>
									</span>
									<noscript><input type="hidden" name="task" value="add"/></noscript> <?php
								}
							} ?>
			
							</div><?php
						} ?>
						<input type="hidden" name="option" value="com_virtuemart"/>
						<input type="hidden" name="view" value="cart"/>
						<input type="hidden" name="virtuemart_product_id[]" value="<?php echo $product->virtuemart_product_id ?>"/>
						<input type="hidden" name="pid" value="<?php echo $product->virtuemart_product_id ?>"/>
						<input type="hidden" name="pname" value="<?php echo $product->product_name ?>"/>
						<?php
						$itemId=vRequest::getInt('Itemid',false);
						if($itemId){
							echo '<input type="hidden" name="Itemid" value="'.$itemId.'"/>';
						} ?>
					</form>
				</div>
				<?php echo shopFunctionsF::renderVmSubLayout('stockhandle',array('product'=>$this->product)); ?>
			    </div>
			</div>
		</div>
    </div>
	<?php echo $this->product->event->beforeDisplayContent; ?>


	<?php if(false && !empty($this->product->product_desc) && ($this->allowRating || $this->allowReview || $this->showRating || $this->showReview)) : ?>
	<ul id="product-tabs">
		<li data-toggle="product-description" class="active"><?php echo vmText::_('COM_VIRTUEMART_PRODUCT_DESC_TITLE') ?></li>
		<li data-toggle="customer-reviews"><?php echo vmText::_ ('COM_VIRTUEMART_REVIEWS') ?></li>
	</ul>
	<?php endif; ?>

	
		
		<?php if($this->allowRating || $this->allowReview || $this->showRating || $this->showReview) : ?>
			<div id="product-tabs-content">
				<div class="customer-reviews gk-product-tab active">
					<?php echo $this->loadTemplate('reviews'); ?>
				</div>
			</div>
		<?php endif; ?>
	
	
	
	
	    <?php
		    // RELATED PRODUCTS
		    $product = $this->product;
		    $position = 'related_products';
		    $customTitle = true;
		    $class = 'product-related-products';
		    
		    if (!empty($product->customfieldsSorted[$position])) :
		    ?>
		    <div class="<?php echo $class?>">
		    	<?php
		    	if($customTitle and isset($product->customfieldsSorted[$position][0])) :
		    		$field = $product->customfieldsSorted[$position][0]; ?>
		    	<h4><?php echo vmText::_ ($field->custom_title) ?></h4>
		    		
		    	<?php endif; ?>
		    	<?php foreach ($product->customfieldsSorted[$position] as $field) : ?>
		    		<?php if ( $field->is_hidden ) continue; //OSP http://forum.virtuemart.net/index.php?topic=99320.0 ?>
		    		<div class="product-field product-field-type-<?php echo $field->field_type ?>"> 
		    			<?php if (!empty($field->display)) : ?>
		    			<span class="product-field-display"><?php echo $field->display ?></span>
		    			<?php endif; ?>
		    		</div>
		    	<?php $custom_title = $field->custom_title; ?>
		    	<?php endforeach ?>
		    </div>
		<?php endif ?>
		
		<?php
		    // RELATED CATEGORIES
		    $product = $this->product;
		    $position = 'related_categories';
		    $customTitle = true;
		    $class = 'product-related-categories';
		    
		    if (!empty($product->customfieldsSorted[$position])) :
			?>
			<div class="<?php echo $class?>">
				<?php
				if($customTitle and isset($product->customfieldsSorted[$position][0])) :
					$field = $product->customfieldsSorted[$position][0]; ?>
				<h4><?php echo vmText::_ ($field->custom_title) ?></h4>
					
				<?php endif; ?>
				<?php foreach ($product->customfieldsSorted[$position] as $field) : ?>
					<?php if ( $field->is_hidden ) continue; //OSP http://forum.virtuemart.net/index.php?topic=99320.0 ?>
					<div class="product-field product-field-type-<?php echo $field->field_type ?>"> 
						<?php if (!empty($field->display)) : ?>
						<span class="product-field-display"><?php echo $field->display ?></span>
						<?php endif; ?>
					</div>
				<?php $custom_title = $field->custom_title; ?>
				<?php endforeach ?>
			</div>
		<?php endif ?>
<?php echo $this->product->event->afterDisplayContent; ?>

<?php // Show child categories
    if (VmConfig::get('showCategory', 1)) {
		echo $this->loadTemplate('showcategory');
    }?>

<?php
$j = 'jQuery(document).ready(function($) {
	Virtuemart.product(jQuery("form.product"));

	$("form.js-recalculate").each(function(){
		if ($(this).find(".product-fields").length && !$(this).find(".no-vm-bind").length) {
			var id= $(this).find(\'input[name="virtuemart_product_id[]"]\').val();
			Virtuemart.setproducttype($(this),id);

		}
	});
});';
//vmJsApi::addJScript('recalcReady',$j);

/** GALT
	 * Notice for Template Developers!
	 * Templates must set a Virtuemart.container variable as it takes part in
	 * dynamic content update.
	 * This variable points to a topmost element that holds other content.
	 */
$j = "Virtuemart.container = jQuery('.productdetails-view');
Virtuemart.containerSelector = '.productdetails-view';";

vmJsApi::addJScript('ajaxContent',$j);

/* VirtueMart addons */
$j = "initGKVMFeatures();
jQuery('body').on('updateVirtueMartProductDetail', function() { initGKVMFeatures(); });
function initGKVMFeatures() {
	var tabs = jQuery('#product-tabs');
	// if tabs exists
	if(tabs.length && tabs.find('li').length > 1) {
	    // initialization
	    tabs.find('li').first().addClass('active');
	    var contents = jQuery('#product-tabs-content');
	    contents.children('div').css('display', 'none');
	    contents.children('div').first().addClass('active');
	    // add events to the tabs
	    tabs.find('li').each(function(i, tab) {
	    	var tab = jQuery(tab);
	        tab.click(function() {
	            var toggle = tab.attr('data-toggle');
	            contents.children('div').removeClass('active');
	            jQuery(contents.children().get(i)).addClass('active');
	            tabs.find('li').removeClass('active');
	            tab.addClass('active');                
	        });
	    });
	}
	
	var products = jQuery('.browse-view .product');
	var categories = jQuery('.category-view .category');
	var f_products = jQuery('.featured-view .product');
	var l_products = jQuery('.latest-view .product');
	var t_products = jQuery('.topten-view .product'); 
	var r_products = jQuery('.recent-view .product');
	
	jQuery([products, categories, f_products, l_products, t_products, r_products]).each(function(i, p) {
		if(p.length > 0) {
			p.each(function(i, item) {
				item = jQuery(item);
				item.mouseenter(function() {
					item.addClass('active');
				});
				item.mouseleave(function() {
					item.removeClass('active');
				});
			}); 
		}
	});
	
	var productZoom = jQuery('.productDetails .main-image');
	if(productZoom.length > 0) {
		productZoom.each(function(i, item) {
			item = jQuery(item);
			var overlay = item.find('.product-overlay');
			var link = item.find('a');
			if(overlay) {
				overlay.appendTo(link);
			}
			item.mouseenter(function() {
				item.addClass('active');
			});
			item.mouseleave(function() {
				item.removeClass('active');
			});
		}); 
	} 
}";

vmJsApi::addJScript('gk-vm-addons',$j);

echo vmJsApi::writeJS();

if ($this->product->prices['salesPrice'] > 0) {
   echo shopFunctionsF::renderVmSubLayout('snippets',array('product'=>$this->product, 'currency'=>$this->currency, 'showRating'=>$this->showRating));
}
?>
<?php if (VmConfig::get('product_navigation', 1)) : ?>
        <div class="product-neighbours">
		    <?php
			    if (!empty($this->product->neighbours ['previous'][0])) {
				$prev_link = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->neighbours ['previous'][0] ['virtuemart_product_id'] . '&virtuemart_category_id=' . $this->product->virtuemart_category_id, FALSE);
					echo JHtml::_('link', $prev_link, '&laquo; ' . $this->product->neighbours['previous'][0]
					['product_name'], array('rel'=>'prev', 'class' => 'previous-page','data-dynamic-update' => '1'));
			    }
			    if (!empty($this->product->neighbours ['next'][0])) {
				$next_link = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $this->product->neighbours ['next'][0] ['virtuemart_product_id'] . '&virtuemart_category_id=' . $this->product->virtuemart_category_id, FALSE);
					echo JHtml::_('link', $next_link, $this->product->neighbours['next'][0]['product_name'] . ' &raquo;', array('rel'=>'next','class' => 'next-page','data-dynamic-update' => '1'));
			    }
		    ?>
        </div>
    <?php endif; // Product Navigation END ?>
</div><!-- .productDetails -->
<?php

// EOF