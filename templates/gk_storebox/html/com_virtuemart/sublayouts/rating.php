<?php 

defined('_JEXEC') or die('Restricted access');
$product = $viewData['product'];

if ($viewData['showRating']) {
	$maxrating = VmConfig::get('vm_maximum_rating_scale', 5);
	if (empty($product->rating)) {
	?>
		<span class="ratingbox dummy">
			<?php echo vmText::_('COM_VIRTUEMART_UNRATED'); ?>
		</span>
	<?php
		} else {
  	?>
	<span class="ratingbox" >
	  <?php echo (vmText::_("COM_VIRTUEMART_RATING_TITLE") . ' ' . round($product->rating) . '/' . $maxrating) ?>
	</span>
	<?php
	}
}