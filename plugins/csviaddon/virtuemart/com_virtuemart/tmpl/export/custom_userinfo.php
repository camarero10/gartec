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

$form = $this->forms->custom_userinfo;

require_once JPATH_ADMINISTRATOR . '/components/com_csvi/addon/com_virtuemart/helper/com_virtuemart_config.php';

$helperconfig = new Com_VirtuemartHelperCom_Virtuemart_Config;
?>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('userinfo_address', 'jform')->labelClass; ?>" for="<?php echo $form->getField('userinfo_address', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('userinfo_address', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('userinfo_address', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('userinfo_address', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('vendors', 'jform')->labelClass; ?>" for="<?php echo $form->getField('vendors', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('vendors', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('vendors', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('vendors', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('userinfomdatestart', 'jform')->labelClass; ?>" for="<?php echo $form->getField('userinfomdatestart', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('userinfomdatestart', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('userinfomdatestart', 'jform'); ?>
		<?php echo $form->getInput('userinfomdateend', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('userinfomdatestart', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('blocked', 'jform')->labelClass; ?>" for="<?php echo $form->getField('blocked', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('blocked', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('blocked', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('blocked', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>

<div class="control-group">
	<label class="control-label <?php echo $form->getField('activated', 'jform')->labelClass; ?>" for="<?php echo $form->getField('activated', 'jform')->id; ?>">
		<?php echo JText::_('COM_CSVI_' . $form->getField('activated', 'jform')->id . '_LABEL'); ?>
	</label>
	<div class="controls">
		<?php echo $form->getInput('activated', 'jform'); ?>
		<span class="help-block">
			<?php echo JText::_('COM_CSVI_' . $form->getField('activated', 'jform')->id . '_DESC'); ?>
		</span>
	</div>
</div>
