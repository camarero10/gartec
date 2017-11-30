<?php
/**
 * @package     CSVI
 * @subpackage  Tasks
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');
?>
<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=processes'); ?>" method="post" id="adminForm" name="adminForm" class="form-horizontal">
	<input type="hidden" name="task" value="browse" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
	<div class="pull-right">
		<?php echo $this->pagination->getLimitBox(); ?>
	</div>
	<div class="clearfix"></div>
	<table class="adminlist table table-striped">
		<thead>
			<tr>
				<th>
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
				</th>
				<th>
					<?php
						echo JHtml::_('grid.sort', 'COM_CSVI_PROCESSES_TEMPLATE_LABEL', 'csvi_template_id', $this->lists->order_Dir, $this->lists->order, 'browse');
					?>
				</th>
				<th>
					<?php
						echo JHtml::_('grid.sort', 'COM_CSVI_PROCESSES_USERID_LABEL', 'userId', $this->lists->order_Dir, $this->lists->order, 'browse');
					?>
				</th>
				<th>
					<?php
						echo JHtml::_('grid.sort', 'COM_CSVI_PROCESSES_PROCESSFILE_LABEL', 'processfile', $this->lists->order_Dir, $this->lists->order, 'browse');
					?>
				</th>
				<th>
					<?php
						echo JHtml::_('grid.sort', 'COM_CSVI_PROCESSES_PROCESSFOLDER_LABEL', 'processfolder', $this->lists->order_Dir, $this->lists->order, 'browse');
					?>
				</th>
				<th>
					<?php
						echo JHtml::_('grid.sort', 'COM_CSVI_PROCESSES_POSITION_LABEL', 'position', $this->lists->order_Dir, $this->lists->order, 'browse');
					?>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="20">
					<?php
						if ($this->pagination->total > 0)
						{
							echo $this->pagination->getListFooter();
						}
					?>
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
							<?php echo JHtml::_('grid.id', $i, $item->csvi_process_id); ?>
						</td>
						<td>
							<?php echo $item->template_name; ?>
						</td>
						<td>
							<?php echo $item->userId; ?>
						</td>
						<td>
							<?php echo $item->processfile; ?>
						</td>
						<td>
							<?php echo $item->processfolder; ?>
						</td>
						<td>
							<?php echo $item->position; ?>
						</td>
					</tr>
				<?php } ?>
			<?php } ?>
		</tbody>
	</table>
</form>
