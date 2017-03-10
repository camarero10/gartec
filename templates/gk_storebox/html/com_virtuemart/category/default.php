<?php
/**
*
* Show the products in a category
*
* @package	VirtueMart
* @subpackage
* @author RolandD
* @author Max Milbers
* @todo add pagination
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: default.php 5120 2011-12-18 18:29:26Z electrocity $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

/* javascript for list Slide
  Only here for the order list
  can be changed by the template maker */
$js = "jQuery(document).ready(function () {
	jQuery('.orderlistcontainer').hover(
		function() { jQuery(this).find('.orderlist').stop().show()},
		function() { jQuery(this).find('.orderlist').stop().hide()}
	)
});";

$document = JFactory::getDocument(); 
$document->addScriptDeclaration($js);
//var_dump($this->category);
?>

<?php
/* Show child categories */
if ( VmConfig::get('showCategory',1) and empty($this->keyword)) :
	if (!empty($this->category->haschildren)) :
		// Category and Columns Counter
		$iCol = 1;
		$iCategory = 1;
		// Calculating Categories Per Row
		$categories_per_row = VmConfig::get ( 'categories_per_row', 3 );
		$category_cellwidth = ' width'.floor ( 100 / $categories_per_row );
		$BrowseTotalProducts = count($this->products);
		// Separator
		$verticalseparator = " vertical-separator";
	?>
	<div class="category-view">
		<?php // Start the Output
		if(!empty($this->category->children)) :
			foreach ( $this->category->children as $category ) : ?>
				<?php if ($iCol == 1 && $iCategory > $categories_per_row) : ?>
				<div class="horizontal-separator"></div>
				<?php endif; ?>
				
				<?php if ($iCol == 1) : ?>
				<div class="row">
				<?php endif; ?>
				
				<?php
					// Show the vertical seperator
					if ($iCategory == $categories_per_row or $iCategory % $categories_per_row == 0) {
						$show_vertical_separator = ' ';
					} else {
						$show_vertical_separator = $verticalseparator;
					}
		
					// Category Link
					$caturl = JRoute::_ ( 'index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id );
		
						// Show Category ?>
						<div class="category floatleft<?php echo $category_cellwidth . $show_vertical_separator ?>">
							<div class="spacer">
								<a href="<?php echo $caturl ?>" title="<?php echo $category->category_name ?>"><?php echo $category->images[0]->displayMediaThumb("",false); ?></a>
								
								<h2 class="catSub"> <a href="<?php echo $caturl ?>" title="<?php echo $category->category_name ?>"> <?php echo $category->category_name ?> </a> </h2>
								
								<a href="<?php echo $caturl; ?>" class="category-overlay"><span><span><?php echo JText::_('TPL_GK_LANG_VM_VIEW'); ?></span></span></a>
							</div><!-- .spacer -->
						</div><!-- .category -->
						<?php
					$iCategory ++;
		
				// Do we need to close the current row now?
				if ($iCol == $categories_per_row) : ?>
					<div class="clear"></div>
				</div><!-- .row -->
				<?php
					$iCol = 1;
				else :
					$iCol ++;
				endif;
			endforeach;
		endif;
		// Do we need a final closing row tag?
		if ($iCol != 1) : ?>
			<div class="clear"></div>
		</div><!-- .row -->
		<?php endif; ?>
	</div><!-- .category-view -->
<?php 
	endif;
endif;
?>

<div class="browse-view">
	<?php
	// Show child categories
	if (!empty($this->products)) : ?>
			
		<?php if (!empty($this->keyword)) : ?>
		<h1><?php echo $this->keyword; ?></h1>
		<?php endif; ?>
		
		<?php // Category and Columns Counter
			$iBrowseCol = 1;
			$iBrowseProduct = 1;
			
			// Calculating Products Per Row
			$BrowseProducts_per_row = $this->perRow;
			$Browsecellwidth = ' width'.floor ( 100 / $BrowseProducts_per_row );
			
			// Separator
			$verticalseparator = " vertical-separator";
		?>
	
		<?php if(!empty($this->category->category_name)) : ?>
		<h1><?php echo $this->category->category_name; ?></h1>
		<?php endif; ?>
		
		<?php if (empty($this->keyword) && !empty($this->category) && !empty($this->category->category_description)) : ?>
		<p class="category_description">
			<?php echo $this->category->category_description; ?>
		</p>
		<?php endif; ?>
		
		<form action="<?php echo JRoute::_ ('index.php?option=com_virtuemart&view=category&limitstart=0&virtuemart_category_id=' . $this->category->virtuemart_category_id, FALSE); ?>" method="get">
				<?php if (!empty($this->products)) : ?>
				<div class="orderby-displaynumber"><?php echo $this->orderByList['orderby']; ?>
						<div class="display-number"><?php echo $this->vmPagination->getResultsCounter();?> <?php echo $this->vmPagination->getLimitBox ($this->category->limit_list_step); ?></div>
						
				</div>
				<?php endif ?>
		</form>
		<?php // Start the Output
		foreach ( $this->products as $product ) :
			// Show the horizontal seperator
			if ($iBrowseCol == 1 && $iBrowseProduct > $BrowseProducts_per_row) : ?>
			<div class="horizontal-separator"></div>
			<?php endif;
	
			// this is an indicator wether a row needs to be opened or not
			if ($iBrowseCol == 1) : ?>
			<div class="row">
			<?php endif;
	
				// Show the vertical seperator
				if ($iBrowseProduct == $BrowseProducts_per_row or $iBrowseProduct % $BrowseProducts_per_row == 0) {
					$show_vertical_separator = ' ';
				} else {
					$show_vertical_separator = $verticalseparator;
				}
				// Show Products ?>
				<div class="product floatleft<?php echo $Browsecellwidth . $show_vertical_separator ?>">
					<div class="spacer">
						<div>
							<a title="<?php echo $product->product_name ?>" href="<?php echo $product->link; ?>">
								<?php
									echo $product->images[0]->displayMediaThumb('class="browseProductImage"', false);
								?>
							 </a>
						</div>
						
						<div>
							<h3 class="catProductTitle"><?php echo JHTML::link($product->link, $product->product_name); ?></h3>
							
							<div class="catProductPrice" id="productPrice<?php echo $product->virtuemart_product_id ?>">
								<?php
								if ($this->show_prices == '1') :
									if ($product->prices['salesPrice']<=0 and VmConfig::get ('askprice', 1) and  !$product->images[0]->file_is_downloadable) :
										echo JText::_ ('COM_VIRTUEMART_PRODUCT_ASKPRICE');
									endif;
									echo $this->currency->createPriceDiv('salesPrice', '', $product->prices);
									echo $this->currency->createPriceDiv('taxAmount','TPL_GK_LANG_VM_INC_TAX', $product->prices);
								endif; ?>
							</div>
							
							<?php if ( VmConfig::get ('display_stock', 1)) : ?>
							<div class="stockLavel"> <span class="vmicon vm2-<?php echo $product->stock->stock_level ?>" title="<?php echo $product->stock->stock_tip ?>"></span> <span class="stock-level"><?php echo JText::_('COM_VIRTUEMART_STOCK_LEVEL_DISPLAY_TITLE_TIP') ?></span> </div>
							<?php endif; ?>
						</div>
						
						<a href="<?php echo $product->link; ?>" class="product-overlay"><span><span><?php echo JText::_('TPL_GK_LANG_VM_VIEW'); ?></span></span></a>
					</div><!-- .spacer -->
				</div><!-- .product -->
			<?php
		
			// Do we need to close the current row now?
			if ($iBrowseCol == $BrowseProducts_per_row || (isset($BrowseTotalProducts) && $iBrowseProduct == $BrowseTotalProducts)) : ?>
				</div><!-- .row -->
				<?php
				$iBrowseCol = 1;
			else :
				$iBrowseCol++;
			endif;
		
			$iBrowseProduct++;
		endforeach;
		// Do we need a final closing row tag?
		if ($iBrowseCol != 1) : ?>
			<div class="clear"></div>
		</div><!-- .row -->
		<?php endif; ?>
	
		<?php if($this->vmPagination->getPagesLinks() != '') : ?>
		<div class="pagination"> 
			<?php echo str_replace('</ul>', '<li class="counter">'.$this->vmPagination->getPagesCounter().'</li></ul>', $this->vmPagination->getPagesLinks()); ?> 
		</div><!-- .pagination -->
		<?php endif; ?>
	<?php
	elseif (!empty($this->keyword)) :
		echo JText::_ ('COM_VIRTUEMART_NO_RESULT') . ($this->keyword ? ' : (' . $this->keyword . ')' : '');
	endif;
	?>
</div><!-- .browse-view -->