<?php
/**
 * @package     CSVI
 * @subpackage  Addons
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

?>
<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=addons'); ?>" method="post" id="adminForm" name="adminForm" class="form-horizontal" enctype="multipart/form-data">
	<div>
		<div class="lead">
			<?php echo JText::_('COM_CSVI_ADDON_FILE_LABEL'); ?>
		</div>
		<div>
			<input type="file" name="addon" id="file" size="120" />
			<input type="button" onclick="Joomla.submitbutton('install')" value="Upload &amp; Install" class="btn">
		</div>
	</div>
	<br />
	<table class="adminlist table table-striped" id="itemsList">
		<thead>
			<tr>
				<th width="20">
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
				</th>
				<th class="title">
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_ADDON_NAME_LABEL', 'name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th class="title">
					<?php echo JText::_('COM_CSVI_ADDON_VERSION_LABEL'); ?>
				</th>
				<th class="title">
					<?php echo JText::_('JSTATUS'); ?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="20">
					<?php if($this->pagination->total > 0)
					{
						echo $this->pagination->getListFooter();
					} ?>
				</td>
			</tr>
		</tfoot>
		<tbody>
			<?php
			if (!empty($this->items))
			{
				foreach ($this->items as $i => $item)
				{
					$ordering = $this->lists->order == 'ordering';
					?>
					<tr>
						<td class="center">
							<?php
								if ($item->csvi_addon_id > 1)
								{
									echo JHtml::_('grid.id', $i, $item->csvi_addon_id);
								}
							?>
						</td>
						<td>
							<?php echo JText::_('COM_CSVI_' . $item->name); ?>
						</td>
						<td>
							<?php echo $item->version; ?>
						</td>
						<td class="center">
							<?php
							if ($item->csvi_addon_id > 1)
							{
								echo JHtml::_('jgrid.published', $item->enabled, $i);
							} ?>
						</td>
					</tr>
				<?php } ?>
			<?php } ?>
		</tbody>
	</table>
	<input type="hidden" name="task" value="browse" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
</form>

<script type="text/javascript">
	Joomla.submitbutton = function(pressbutton)
	{
		var form = document.getElementById('adminForm');

		if (pressbutton == 'install')
		{
			// Do field validation
			if (form.addon.value == "")
			{
				alert('<?php echo JText::_('COM_CSVI_SELECT_INSTALL_PACKAGE'); ?>');
			}
			else
			{
				form.task.value = 'install';
				form.submit();
			}
		}
		else
		{
			Joomla.submitform(pressbutton);
		}
	}
</script>
