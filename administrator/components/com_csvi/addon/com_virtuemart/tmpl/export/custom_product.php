<?php
/**
 * @package     CSVI
 * @subpackage  VirtueMart
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

$form = $this->forms->custom_product;
?>
<div class="span6">
	<h3><?php echo JText::_('COM_CSVI_EXPORT_CUSTOM_PRODUCT'); ?></h3>
	<div class="control-group">
		<label class="control-label <?php echo $form->getField('language', 'jform')->labelClass; ?>" for="<?php echo $form->getField('language', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('language', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('language', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('language', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('exportsef', 'jform')->labelClass; ?>" for="<?php echo $form->getField('exportsef', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('exportsef', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('exportsef', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('exportsef', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('producturl_suffix', 'jform')->labelClass; ?>" for="<?php echo $form->getField('producturl_suffix', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('producturl_suffix', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('producturl_suffix', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('producturl_suffix', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('vm_itemid', 'jform')->labelClass; ?>" for="<?php echo $form->getField('vm_itemid', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('vm_itemid', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('vm_itemid', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('vm_itemid', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('picture_limit', 'jform')->labelClass; ?>" for="<?php echo $form->getField('picture_limit', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('picture_limit', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('picture_limit', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('picture_limit', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('featured', 'jform')->labelClass; ?>" for="<?php echo $form->getField('featured', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('featured', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('featured', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('featured', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('category_separator', 'jform')->labelClass; ?>" for="<?php echo $form->getField('category_separator', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('category_separator', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('category_separator', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('category_separator', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('product_categories', 'jform')->labelClass; ?>" for="<?php echo $form->getField('product_categories', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('product_categories', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('product_categories', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('product_categories', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('publish_state_categories', 'jform')->labelClass; ?>" for="<?php echo $form->getField('publish_state_categories', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('publish_state_categories', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('publish_state_categories', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('publish_state_categories', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('incl_subcategory', 'jform')->labelClass; ?>" for="<?php echo $form->getField('incl_subcategory', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('incl_subcategory', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('incl_subcategory', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('incl_subcategory', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('parent_only', 'jform')->labelClass; ?>" for="<?php echo $form->getField('parent_only', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('parent_only', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('parent_only', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('parent_only', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('child_only', 'jform')->labelClass; ?>" for="<?php echo $form->getField('child_only', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('child_only', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('child_only', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('child_only', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('custom_title', 'jform')->labelClass; ?>" for="<?php echo $form->getField('custom_title', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('custom_title', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('custom_title', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('custom_title', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('productskufilter', 'jform')->labelClass; ?>" for="<?php echo $form->getField('productskufilter', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('productskufilter', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('productskufilter', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('productskufilter', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('stocklevelstart', 'jform')->labelClass; ?>" for="<?php echo $form->getField('stocklevelstart', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('stocklevelstart', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('stocklevelstart', 'jform'); ?>
			<?php echo $form->getInput('stocklevelend', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('stocklevelstart', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('shopper_groups', 'jform')->labelClass; ?>" for="<?php echo $form->getField('shopper_groups', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('shopper_groups', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('shopper_groups', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('shopper_groups', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('manufacturers', 'jform')->labelClass; ?>" for="<?php echo $form->getField('manufacturers', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('manufacturers', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('manufacturers', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('manufacturers', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>
</div>
<div class="span6">
	<h3><?php echo JText::_('COM_CSVI_PRICE_OPTIONS'); ?></h3>
	<div class="control-group">
		<label class="control-label <?php echo $form->getField('shopper_group_price', 'jform')->labelClass; ?>" for="<?php echo $form->getField('shopper_group_price', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('shopper_group_price', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('shopper_group_price', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('shopper_group_price', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('pricefrom', 'jform')->labelClass; ?>" for="<?php echo $form->getField('pricefrom', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('pricefrom', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('priceoperator', 'jform'); ?>
			<?php echo $form->getInput('pricefrom', 'jform'); ?>
			<?php echo $form->getInput('priceto', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('pricefrom', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('price_quantity_start', 'jform')->labelClass; ?>" for="<?php echo $form->getField('price_quantity_start', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('price_quantity_start', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('price_quantity_start', 'jform'); ?>
			<?php echo $form->getInput('price_quantity_end', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('price_quantity_start', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>

	<div class="control-group">
		<label class="control-label <?php echo $form->getField('targetcurrency', 'jform')->labelClass; ?>" for="<?php echo $form->getField('targetcurrency', 'jform')->id; ?>">
			<?php echo JText::_('COM_CSVI_' . $form->getField('targetcurrency', 'jform')->id . '_LABEL'); ?>
		</label>
		<div class="controls">
			<?php echo $form->getInput('targetcurrency', 'jform'); ?>
			<span class="help-block">
				<?php echo JText::_('COM_CSVI_' . $form->getField('targetcurrency', 'jform')->id . '_DESC'); ?>
			</span>
		</div>
	</div>
	<div class="advancedUser">
		<div class="control-group">
			<label class="control-label <?php echo $form->getField('force_shopper_group_price', 'jform')->labelClass; ?>" for="<?php echo $form->getField('force_shopper_group_price', 'jform')->id; ?>">
				<?php echo JText::_('COM_CSVI_' . $form->getField('force_shopper_group_price', 'jform')->id . '_LABEL'); ?>
			</label>
			<div class="controls">
				<?php echo $form->getInput('force_shopper_group_price', 'jform'); ?>
				<span class="help-block">
					<?php echo JText::_('COM_CSVI_' . $form->getField('force_shopper_group_price', 'jform')->id . '_DESC'); ?>
				</span>
			</div>
		</div>
	</div>
</div>
