<?php
/**
 * @package     CSVI
 * @subpackage  Maintenance
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

?>
<form method="post" action="index.php?option=com_csvi&view=maintenance" id="adminForm" name="adminForm">
	<table class="adminlist table table-condensed table-striped" id="progresstable" style="width: 45%;">
		<thead>
			<tr>
				<th colspan="2">
					<?php echo JText::_('COM_CSVI_' . $this->input->get('operation') . '_LABEL'); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
		</tfoot>
		<tbody>
			<tr>
				<td>
					<?php echo JText::_('COM_CSVI_MAINTENANCE_NOTICES'); ?>
					<div>
						<?php echo JHtml::_('image', JURI::root() . '/administrator/components/com_csvi/assets/images/csvi_ajax-loading.gif', 'id="spinner"')?>
					</div>
				</td>
				<td>
					<div id="status"></div>
				</td>
			</tr>
		</tbody>
	</table>
	<input type="hidden" name="task" value="browse" />
	<input type="hidden" id="run_id" name="run_id" value="" />
</form>
<script type="text/javascript">
jQuery(function()
{
	loadIndex(0);
});

// Start the import
function loadIndex(key) {
	var run_id = jQuery('#run_id').val();
	jQuery.ajax({
		async: true,
		url: 'index.php',
		dataType: 'json',
		cache: false,
		data: 'option=com_csvi&view=maintenance&task=runoperation&operation=<?php echo $this->input->get('operation'); ?>&component=<?php echo $this->input->get('component'); ?>&format=json&run_id='+run_id+'&key='+key+'&<?php echo JSession::getFormToken(); ?>=1',
		success: function(data) {
			if (data)
			{
				// Set the run ID
				jQuery('#run_id').val(data.run_id);
				if (data.process == true) {
					jQuery('#status').prepend(data.info+'<br />');
					loadIndex(data.key);
				}
				else {
					window.location = data.url;
				}
			}
		},
		failure: function(data)
		{
			jQuery('#spinner').remove();
			jQuery('#status').html(Joomla.JText._('COM_CSVI_ERROR_PROCESSING_RECORDS')+data.responseText);
		},
		error: function(data)
		{
			jQuery('#spinner').remove();
			jQuery('#status').html(Joomla.JText._('COM_CSVI_ERROR_PROCESSING_RECORDS')+data.responseText);
		}
	});
}
</script>
