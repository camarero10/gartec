<?php
/**
 * Import page
 *
 * @author 		Roland Dalmulder
 * @link 		http://www.csvimproved.com
 * @copyright 	Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license 	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @version 	$Id: default.php 2436 2013-05-25 13:14:20Z Roland $
 */

defined('_JEXEC') or die;

// Add chosen
JHtml::_('formbehavior.chosen', 'select');
$templates = FOFModel::getTmpInstance('Templates', 'CsviModel')->action('import')->enabled(1)->filter_order('ordering')->getList();
$this->step = 1;
?>
<div class="row-fluid">
	<?php if (count($templates) > 0) : ?>
		<div class="span2">
			<?php echo $this->loadAnyTemplate('admin:com_csvi/imports/steps'); ?>
		</div>
		<div class="span10">
			<form  action="index.php?option=com_csvi&view=imports" id="adminForm" name="adminForm" method="post" class="form-horizontal">
				<?php
					$select = JHtml::_('select.option', '', JText::_('COM_CSVI_MAKE_A_CHOICE'), 'csvi_template_id', 'template_name');
					array_unshift($templates, $select);
					echo JHtml::_('select.genericlist', $templates, 'csvi_template_id', 'class="input-xxlarge"', 'csvi_template_id', 'template_name');
				?>
				<input type="hidden" name="task" value="" />
				<?php echo JHtml::_('form.token'); ?>
			</form>
		</div>
	<?php else : ?>
		<?php echo JText::_('COM_CSVI_NO_TEMPLATES_CREATE_THEM'); ?>
	<?php endif; ?>
</div>
