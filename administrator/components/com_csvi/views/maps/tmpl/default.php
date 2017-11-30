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

?>
<div class="row-fluid">
	<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=maps'); ?>" method="post" name="adminForm" id="adminForm">
		<input type="hidden" name="task" value="browse" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="mapid" id="mapid" value="0" />
		<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
		<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
		<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
		<div id="availablefieldslist" style="text-align: left;">
			<table class="table table-condensed table-striped">
				<thead>
				<tr>
					<th width="20">
						<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
					</th>
					<th class="title">
						<?php echo JHtml::_('grid.sort', 'COM_CSVI_MAP_TEMPLATE_NAME_LABEL', 'name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
					</th>
					<th class="title">
						<?php echo JText::_('COM_CSVI_MAP_OPTIONS'); ?>
					</th>
				</tr>
				</thead>
				<tfoot>
					<tr>
						<td colspan="10"><?php echo $this->pagination->getListFooter(); ?></td>
					</tr>
				</tfoot>
				<tbody>
				<?php
				if ($this->items) {
					foreach ($this->items as $i => $item) {
						?>
						<tr>
							<td class="center">
								<?php
				                if ($item->locked_by) {
				                  echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->locked_on, 'maps.');
				                }
				                else { ?>
									<?php echo JHtml::_('grid.id', $i, $item->csvi_map_id); ?>
								<?php } ?>
							</td>
							<td>
								<?php
				                if ($item->locked_by) {
				                	echo $this->escape($item->title);
				                }
				                else {
				                  echo JHtml::_('link', 'index.php?option=com_csvi&view=map&id='.$item->csvi_map_id, $item->title);
				                } ?>
							</td>
							<td>
								<?php echo JHtml::_('link', 'index.php?option=com_csvi&view=maps', JText::_('COM_CSVI_MAP_CREATE_TEMPLATE'), 'onclick="showForm(' . $item->csvi_map_id . '); return false;"'); ?>
						</tr>
						<?php
					}
				}
				?>
				</tbody>
			</table>
		</div>
	</form>
</div>

<?php

	$template_name = '<div class="control-group">
						<label class="control-label" for="template_name">
						' . JText::_('COM_CSVI_MAP_TEMPLATE_NAME_LABEL') . '
						</label>
						<div class="controls">
							<input type="text" id="template_name" name="template_name" value="" />
						</div>
					</div>';

	$layout = new JLayoutFile('csvi.modal');
	echo $layout->render(
		array(
			'modal-id' => 'mapsModal',
			'modal-header' => JText::_('COM_CSVI_MAP_TEMPLATE_NAME_LABEL'),
			'modal-body' => $template_name,
			'cancel-button' => true,
			'ok-button' => JText::_('COM_CSVI_MAP_CREATE_TEMPLATE')
		));

	echo $layout->render(
		array(
			'modal-id' => 'createdModal',
			'modal-header' => JText::_('COM_CSVI_INFORMATION'),
			'modal-body' => JText::_('COM_CSVI_TEMPLATE_CREATED'),
			'ok-btn-dismiss' => true,
			'ok-button' => JText::_('COM_CSVI_CLOSE_DIALOG')
		));

	echo $layout->render(
		array(
			'modal-id' => 'notcreatedModal',
			'modal-header' => JText::_('COM_CSVI_INFORMATION'),
			'modal-body' => JText::_('COM_CSVI_TEMPLATE_NOT_CREATED'),
			'ok-btn-dismiss' => true,
			'ok-button' => JText::_('COM_CSVI_CLOSE_DIALOG')
		));
?>
<script type="text/javascript">
	function showForm(mapid)
	{
		jQuery('#mapid').val(mapid);
		jQuery('#mapsModal').modal('show');
		jQuery('#template_name').focus();
	}

	jQuery('#mapsModal .ok-btn').on('click', function(e) {
		e.preventDefault();
		jQuery('#mapsModal').modal('hide');
		var mapid = jQuery('#mapid').val();

		if (mapid > 0)
		{
			jQuery.ajax({
				async: false,
				url: 'index.php',
				dataType: 'json',
				type: 'get',
				data: 'option=com_csvi&view=maps&task=createtemplate&format=json&id='+mapid+'&templateName='+jQuery('#template_name').val(),
				success: function(data)
				{
					jQuery('#template_name').val('');

					if (data)
					{
						jQuery('#createdModal').modal('show');
					}
					else
					{
						jQuery('notcreatedModal').modal('show');
					}

				},
				error: function(data, status, statusText)
				{
					showMsg(
						'<?php echo JText::_('COM_CSVI_ERROR'); ?>',
						statusText + '<br /><br />' + data.responseText,
						'<?php echo JText::_('COM_CSVI_CLOSE_DIALOG'); ?>'
					);
				}
			});
		}
		else
		{
			showMsg(
				'<?php echo JText::_('COM_CSVI_INFORMATION'); ?>',
				'<?php echo JText::_('COM_CSVI_NO_MAP_ID_FOUND'); ?>',
				'<?php echo JText::_('COM_CSVI_CLOSE_DIALOG'); ?>'
			);
		}
	});
</script>
