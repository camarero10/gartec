<?php
/**
 * @package     CSVI
 * @subpackage  Replacement
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

JHtml::_('behavior.formvalidation');
JHtml::_('formbehavior.chosen');
$db = JFactory::getDbo();
?>
<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=rule&id=' . $this->item->csvi_rule_id); ?>" method="post" name="adminForm"  id="adminForm" class="form-horizontal form-validate">
	<div class="row-fluid">
		<?php echo $this->form; ?>

		<div id="pluginfields">
			<?php
				// Load the plugin helper
				$dispatcher = new RantaiPluginDispatcher;
				$dispatcher->importPlugins('csvirules', $db);
				$output = $dispatcher->trigger('getForm', array('id' => $this->item->plugin, $this->item->pluginform));

				// Output the form
				if (isset($output[0]))
				{
					echo $output[0];
				}
			?>
		</div>
	</div>
	<input type="hidden" name="task" value="save" />
	<?php echo JHtml::_('form.token'); ?>
</form>

<script type="text/javascript">
	jQuery(document).ready(function ()
	{
		// Turn off the help texts
		jQuery('.help-block').hide();
	});

	Joomla.submitbutton = function(task)
	{
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
