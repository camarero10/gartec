<?php
/**
 * Template field editing page
 *
 * @author 		Roland Dalmulder
 * @link 		http://www.csvimproved.com
 * @copyright 	Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license 	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @version 	$Id: default.php 1924 2012-03-02 11:32:38Z RolandD $
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die;

// Load some needed behaviors
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');
JHtml::_('formbehavior.chosen');
?>
<h4><?php echo $this->template->getName(); ?></h4>
<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=templatefield&id='.$this->item->csvi_templatefield_id); ?>" method="post" name="adminForm"  id="adminForm" class="form-validate form-horizontal">
	<?php echo $this->form; ?>
	<div id="pluginfields"></div>
	<input type="hidden" name="task" value="save" />
	<input type="hidden" name="csvi_templatefield_id" value="<?php echo $this->item->csvi_templatefield_id; ?>" />
	<input type="hidden" name="csvi_template_id" value="<?php echo $this->item->csvi_template_id; ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>

<?php
	$layout = new JLayoutFile('csvi.modal');
	echo $layout->render(array('ok-btn-dismiss' => true));
?>

<script type="text/javascript">
	//Turn off the help texts
	jQuery('.help-block').toggle();

	Joomla.submitbutton = function(task)
	{
		if (task == 'hidetips') {
			jQuery('.help-block').toggle();
			return false;
		}
		else if (task == 'cancel' || document.formvalidator.isValid(document.id('adminForm'))) {
			Joomla.submitform(task);
		}
		else {
			showMsg(
				'<?php echo JText::_('COM_CSVI_ERROR'); ?>',
				'<?php echo JText::_('COM_CSVI_INCOMPLETE_FORM'); ?>',
				'<?php echo JText::_('COM_CSVI_CLOSE_DIALOG'); ?>'
			);

			return false;
		}
	}
</script>
