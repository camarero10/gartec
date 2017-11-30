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

$form = $this->forms->custom_orderitem;
?>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('orderitemnostart', 'jform')->labelClass; ?>" for="<?php echo $form->getField('orderitemnostart', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemnostart', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('orderitemnostart', 'jform'); ?>
		<?php echo $form->getInput('orderitemnoend', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemnostart', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('orderitemlist', 'jform')->labelClass; ?>" for="<?php echo $form->getField('orderitemlist', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemlist', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('orderitemlist', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemlist', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('orderitemdaterange', 'jform')->labelClass; ?>" for="<?php echo $form->getField('orderitemdaterange', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemdaterange', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('orderitemdaterange', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemdaterange', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('orderitemdatestart', 'jform')->labelClass; ?>" for="<?php echo $form->getField('orderitemdatestart', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemdatestart', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('orderitemdatestart', 'jform'); ?>
		<?php echo $form->getInput('orderitemdateend', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemdatestart', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('orderitemmdatestart', 'jform')->labelClass; ?>" for="<?php echo $form->getField('orderitemmdatestart', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemmdatestart', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('orderitemmdatestart', 'jform'); ?>
		<?php echo $form->getInput('orderitemmdateend', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemmdatestart', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('orderitemstatus', 'jform')->labelClass; ?>" for="<?php echo $form->getField('orderitemstatus', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemstatus', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('orderitemstatus', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemstatus', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('orderitemcurrency', 'jform')->labelClass; ?>" for="<?php echo $form->getField('orderitemcurrency', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemcurrency', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('orderitemcurrency', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('orderitemcurrency', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>


<div class="control-group">
	<label class="control-label <?php echo $form->getField('orderitempricestart', 'jform')->labelClass; ?>" for="<?php echo $form->getField('orderitempricestart', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('orderitempricestart', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('orderitempricestart', 'jform'); ?>
		<?php echo $form->getInput('orderitempriceend', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('orderitempricestart', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('orderproduct', 'jform')->labelClass; ?>" for="<?php echo $form->getField('orderproduct', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('orderproduct', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<div class="pull-left">
			<?php echo $form->getInput('orderproduct', 'jform'); ?>
		</div>
		<div class="pull-left ordersearch">
			<div id="searchproduct"><input type="text" name="searchproductbox" id="searchproductbox" placeholder="<?php echo JText::_('COM_CSVI_SEARCH'); ?>" /></div>
			<div class="clr"></div>

			<div>
				<table id="selectproductsku" class="table table-striped">
					<thead>
					<tr>
						<th class="dialog-hide">
							<?php echo JText::_('COM_CSVI_EXPORT_PRODUCT_ID'); ?>
						</th>
						<th>
							<?php echo JText::_('COM_CSVI_EXPORT_PRODUCT_SKU'); ?>
						</th>
						<th>
							<?php echo JText::_('COM_CSVI_EXPORT_PRODUCT_NAME');?>
						</th>
					</tr>
					</thead>
				</table>
			</div>
		</div>
		<div class="clr"></div>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('orderproduct', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>
