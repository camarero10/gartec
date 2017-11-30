<?php
/**
 * @package     CSVI
 * @subpackage  Maps
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

// Load some needed behaviors
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('formbehavior.chosen', 'select');

$helper = new CsviHelperCsvi;
?>
<form
	action="<?php echo JRoute::_('index.php?option=com_csvi&view=map&id=' . $this->item->csvi_map_id); ?>"
	method="post"
	name="adminForm"
	id="adminForm"
	class="form-validate form-horizontal"
	enctype="multipart/form-data">
	<input type="hidden" name="task" value="save" />
	<input type="hidden" name="csvi_map_id" value="<?php echo $this->item->csvi_map_id; ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
	<div class="row-fluid">
		<div class="span6">
			<div class="control-group">
				<?php echo $this->form->getLabel('title', 'jform'); ?>
				<div class="controls">
					<?php echo $this->form->getInput('title', 'jform'); ?>
					<span class="help-block"><?php echo JText::_($this->form->getFieldAttribute('title', 'description', '', 'jform')); ?></span>
				</div>
			</div>
			<div class="control-group">
				<?php echo $this->form->getLabel('mapfile', 'jform'); ?>
				<div class="controls">
					<?php echo $this->form->getInput('mapfile', 'jform'); ?>
					<span class="help-block"><?php echo JText::_($this->form->getFieldAttribute('mapfile', 'description', '', 'jform')); ?></span>
				</div>
			</div>
			<div class="control-group">
				<?php echo $this->form->getLabel('auto_detect_delimiters', 'jform'); ?>
				<div class="controls">
					<?php echo $this->form->getInput('auto_detect_delimiters', 'jform'); ?>
					<span class="help-block"><?php echo JText::_($this->form->getFieldAttribute('auto_detect_delimiters', 'description', '', 'jform')); ?></span>
				</div>
			</div>
			<div class="control-group">
				<?php echo $this->form->getLabel('field_delimiter', 'jform'); ?>
				<div class="controls">
					<?php echo $this->form->getInput('field_delimiter', 'jform'); ?>
					<span class="help-block"><?php echo JText::_($this->form->getFieldAttribute('field_delimiter', 'description', '', 'jform')); ?></span>
				</div>
			</div>
			<div class="control-group">
				<?php echo $this->form->getLabel('text_enclosure', 'jform'); ?>
				<div class="controls">
					<?php echo $this->form->getInput('text_enclosure', 'jform'); ?>
					<span class="help-block"><?php echo JText::_($this->form->getFieldAttribute('text_enclosure', 'description', '', 'jform')); ?></span>
				</div>
			</div>
			<div class="control-group">
				<?php echo $this->form->getLabel('action', 'jform'); ?>
				<div class="controls">
					<?php echo $this->form->getInput('action', 'jform'); ?>
					<span class="help-block"><?php echo JText::_($this->form->getFieldAttribute('action', 'description', '', 'jform')); ?></span>
				</div>
			</div>
			<div class="control-group">
				<?php echo $this->form->getLabel('component', 'jform'); ?>
				<div class="controls">
					<?php echo $this->form->getInput('component', 'jform'); ?>
					<span class="help-block"><?php echo JText::_($this->form->getFieldAttribute('component', 'description', '', 'jform')); ?></span>
				</div>
			</div>
			<div class="control-group">
				<?php echo $this->form->getLabel('operation', 'jform'); ?>
				<div class="controls">
					<?php echo $this->form->getInput('operation', 'jform'); ?>
					<span class="help-block"><?php echo JText::_($this->form->getFieldAttribute('operation', 'description', '', 'jform')); ?></span>
				</div>
			</div>
		</div>
		<div id="fieldchange" class="dialog-hide save_template"><?php echo JText::_('COM_CSVI_SAVE_MAP_FIRST'); ?></div>
		<div class="span6">
			<table id="fieldmap" class="table table-condensed table-striped">
				<thead>
					<tr><th><?php echo JText::_('COM_CSVI_FILEHEADER'); ?></th><th><?php echo JText::_('COM_CSVI_TEMPLATEHEADER')?></th></tr>
				</thead>
				<tbody></tbody>
				<tbody>
					<?php if (!empty($this->item->headers)) :
						// Load the fields
						$availablefields = FOFModel::getTmpInstance('AvailableFields', 'CsviModel')
							->getAvailableFields($this->item->operation, $this->item->component, $this->item->action, 'object');

						// Render the select boxes
						foreach ($this->item->headers as $header) :
					?>
							<tr>
								<td>
									<?php echo $header->csvheader; ?>
								</td>
								<td>
									<?php
										$filter = JFilterInput::getInstance();
										echo JHtml::_(
										'select.genericlist',
										$availablefields,
										'templateheader[' . $filter->clean($header->csvheader, 'cmd') . ']',
										null,
										'value',
										'text', $header->templateheader
									); ?>
								</td>
							</tr>
						<?php
							endforeach;
						?>
					<?php
						endif;
					?>
				</tbody>
			</table>
		</div>
	</div>
</form>
<script type="text/javascript">
	jQuery(document).ready(function ()
	{
		// Turn off the help texts
		jQuery('.help-block').hide();
	});

	Joomla.submitbutton = function(task) {
		if (task == 'hidetips')
		{
			jQuery('.help-block').toggle();
			return false;
		}
		else {
			if (task == 'cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
				Joomla.submitform(task, document.getElementById('adminForm'));
			} else {
				alert('<?php echo $this->escape(JText::_('JGLOBAL_VALIDATION_FORM_FAILED'));?>');
			}
		}
	}
</script>
