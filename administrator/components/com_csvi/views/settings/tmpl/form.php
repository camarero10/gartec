<?php
/**
 * Log settings page
 *
 * @author 		Roland Dalmulder
 * @link 		http://www.csvimproved.com
 * @copyright 	Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license 	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @version 	$Id: default.php 2390 2013-03-23 16:54:46Z RolandD $
 */

defined('_JEXEC') or die;

// Load some behaviour
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('behavior.tooltip');

?>
<form action="index.php?option=com_csvi&view=settings" method="post" name="adminForm" id="adminForm" class="form-horizontal form-validate">
	<div class="row-fluid">
		<div class="span12">
			<!-- Tab headers -->
			<ul class="nav nav-pills">
				<li class="active"><a data-toggle="tab" href="#site_settings"><?php echo JText::_('COM_CSVI_SETTINGS_SITE_SETTINGS'); ?></a></li>
				<li><a data-toggle="tab" href="#google_base_settings"><?php echo JText::_('COM_CSVI_SETTINGS_GOOGLE_BASE_SETTINGS'); ?></a></li>
				<li><a data-toggle="tab" href="#yandex_settings"><?php echo JText::_('COM_CSVI_SETTINGS_YANDEX_SETTINGS'); ?></a></li>
				<li><a data-toggle="tab" href="#icecat_settings"><?php echo JText::_('COM_CSVI_SETTINGS_ICECAT_SETTINGS'); ?></a></li>
				<li><a data-toggle="tab" href="#log_settings"><?php echo JText::_('COM_CSVI_SETTINGS_LOG_SETTINGS'); ?></a></li>
			</ul>

			<!-- Tab content -->
			<div class="tab-content">
				<div id="site_settings" class="tab-pane active">
					<?php echo $this->forms['site']; ?>
				</div>
				<div id="google_base_settings" class="tab-pane">
					<?php echo $this->forms['google']; ?>
				</div>
				<div id="yandex_settings" class="tab-pane">
					<?php echo $this->forms['yandex']; ?>
				</div>
				<div id="icecat_settings" class="tab-pane">
					<?php echo $this->loadAnyTemplate('admin:com_csvi/settings/form_icecat'); ?>
				</div>
				<div id="log_settings" class="tab-pane">
					<?php echo $this->forms['log']; ?>
				</div>
			</div>
		</div>
	</div>
	<input type="hidden" name="task" value="save" />
	<input type="hidden" name="csvi_setting_id" value="1" />
	<?php echo JHtml::_('form.token'); ?>
</form>

<?php
	$layout = new JLayoutFile('csvi.modal');
	echo $layout->render(
	array(
		'modal-header' => JText::_('COM_CSVI_CONFIRM_RESET_SETTINGS_TITLE'),
		'modal-body' => JText::_('COM_CSVI_CONFIRM_RESET_SETTINGS_TEXT'),
		'cancel-button' => JText::_('COM_CSVI_CANCEL_DIALOG'),
		'ok-button' => JText::_('COM_CSVI_RESET_SETTINGS')
	));

	echo $layout->render(
	array(
		'modal-id' => 'errorModal',
		'modal-header' => JText::_('COM_CSVI_ERROR'),
		'modal-body' => JText::_('COM_CSVI_INCOMPLETE_FORM'),
		'ok-button' => JText::_('COM_CSVI_CLOSE_DIALOG'),
		'ok-btn-dismiss' => true
	));
?>
<script type="text/javascript">
	// Turn off the help texts
	jQuery('.help-block').toggle();

	Joomla.submitbutton = function(task)
	{
		if (task == 'hidetips') {
			jQuery('.help-block').toggle();
			return false;
		}
		else if (task == 'reset') {
			jQuery('#csviModal').modal('show');

			jQuery('.ok-btn').on('click', function(e) {
				e.preventDefault();
				jQuery('#csviModal').modal('hide');
				Joomla.submitform(task);
			});

		}
		else if (document.formvalidator.isValid(document.id('adminForm')))
		{
			Joomla.submitform(task);
		}
		else
		{
			jQuery('#errorModal').modal('show');
		}
	}
</script>
