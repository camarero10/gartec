<?php // no direct access
defined('_JEXEC') or die('Restricted access');

$document = JFactory::getDocument();

$uri = JURI::getInstance();
$app = JFactory::getApplication();
$template_name = $app->getTemplate();

$css_path = $uri->root().'templates/'.$template_name.'/html/mod_virtuemart_manufacturer/assets/style.css';
$js_path = $uri->root().'templates/'.$template_name.'/html/mod_virtuemart_manufacturer/assets/gallery.js';

$document->addScript($js_path);
$document->addStyleSheet($css_path);

$i = 0;
?>
<div class="vmgroup<?php echo $params->get( 'moduleclass_sfx' ) ?>">

<?php if ($headerText) : ?>
	<div class="vmheader"><?php echo $headerText ?></div>
<?php endif;
if ($display_style =="div") { ?>
	<div class="vmmanufacturer<?php echo $params->get('moduleclass_sfx'); ?>">
		<div class="gkNspPM gkNspPM-ManufacturerGallery gkAutoAnimation gkArrows" data-cols="<?php echo $manufacturers_per_row; ?>" data-autoanim-time="5000">
			<div class="gkImagesWrapper gkImagesCols<?php echo $manufacturers_per_row; ?>">
	<?php foreach ($manufacturers as $manufacturer) {
		$link = JROUTE::_('index.php?option=com_virtuemart&view=manufacturer&virtuemart_manufacturer_id=' . $manufacturer->virtuemart_manufacturer_id);

		?>

				<a href="<?php echo $link; ?>"  class="gkImage show<?php echo ($i < $manufacturers_per_row) ? ' active' : ''?>">
				<?php
					if ($manufacturer->images && ($show == 'image' or $show == 'all' )) { ?>
							<?php echo $manufacturer->images[0]->displayMediaThumb('',false);?>
					<?php
					}
					if ($show == 'text' or $show == 'all' ) { ?>
		 				<div><?php echo $manufacturer->mf_name; ?></div>
					<?php
					} ?>
				</a>
		<?php
			$i++;
		} ?>
			</div>
			<a href="#prev" class="gkPrevBtn">&laquo;</a>
			<a href="#next" class="gkNextBtn">&raquo;</a>
		</div>
	
	</div>

<?php
} else {
?>

<ul class="vmmanufacturer<?php echo $params->get('moduleclass_sfx'); ?>">
	<li>
		<div class="gkNspPM gkNspPM-ManufacturerGallery gkAutoAnimation gkArrows" data-cols="$manufacturers_per_row" data-autoanim-time="5000">
			<div class="gkImagesWrapper gkImagesCols6">
	<?php foreach ($manufacturers as $manufacturer) {
		$link = JROUTE::_('index.php?option=com_virtuemart&view=manufacturer&virtuemart_manufacturer_id=' . $manufacturer->virtuemart_manufacturer_id);

		?>

				<a href="<?php echo $link; ?>"  class="gkImage show<?php echo ($i < $manufacturers_per_row) ? ' active' : ''?>">
				<?php
					if ($manufacturer->images && ($show == 'image' or $show == 'all' )) { ?>
							<?php echo $manufacturer->images[0]->displayMediaThumb('',false);?>
					<?php
					}
					if ($show == 'text' or $show == 'all' ) { ?>
		 				<div><?php echo $manufacturer->mf_name; ?></div>
					<?php
					} ?>
				</a>
		<?php
		} ?>
			</div>
			<a href="#prev" class="gkPrevBtn">&laquo;</a>
			<a href="#next" class="gkNextBtn">&raquo;</a>
		</div>
	</li>
</ul>

<?php }
	if ($footerText) : ?>
	<div class="vmfooter<?php echo $params->get( 'moduleclass_sfx' ) ?>">
		 <?php echo $footerText ?>
	</div>
<?php endif; ?>
</div>
