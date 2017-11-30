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

$hasAjaxOrderingSupport = $this->hasAjaxOrderingSupport();

JHtml::_('formbehavior.chosen', 'select');
?>
<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=rules'); ?>" method="post" id="adminForm" name="adminForm" class="form-horizontal">
	<div class="pagination pagination-toolbar clearfix" style="text-align: center;">
		<div class="limit pull-right"><?php echo $this->pagination->getLimitBox(); ?></div>
	</div>
	<table class="adminlist table table-striped" id="itemsList">
		<thead>
			<tr>
				<th>
					<?php echo JHtml::_(
						'grid.sort',
						'<i class="icon-menu-2"></i>',
						'ordering',
						$this->lists->order_Dir,
						$this->lists->order,
						null,
						'asc',
						'JGRID_HEADING_ORDERING'
					); ?>
				</th>
				<th width="20">
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
				</th>
				<th class="title">
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_RULE_NAME_LABEL', 'name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th class="title">
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_RULE_PLUGIN_LABEL', 'plugin', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th class="title">
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_RULE_ACTION_LABEL', 'action', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
			</tr>
			<tr>
				<th></th>
				<th></th>
				<th>
					<input type="text" class="input-large" name="tbl_name" id="name" placeholder="<?php echo JText::_('COM_CSVI_RULE_NAME_LABEL'); ?>" value="<?php echo $this->escape($this->getModel()->getState('tbl_name','')); ?>" onchange="this.form.submit();" />
					<div class="btn-group hidden-phone">
						<button class="btn" onclick="this.form.submit();" title="<?php echo JText::_('JSEARCH_FILTER'); ?>">
							<i class="icon-search"></i>
						</button>
						<button class="btn" onclick="document.adminForm.tbl_name.value=''; this.form.submit();" title="<?php echo JText::_('JSEARCH_RESET'); ?>">
							<i class="icon-remove"></i>
						</button>
					</div>
				</th>
				<th>
				</th>
				<th>
				</th>
			</tr>
		</thead>
		<tfoot>
			<tr>
				<td colspan="20">
					<div class="pull-left">
					<?php
						if ($this->pagination->total > 0)
						{
							echo $this->pagination->getListFooter();
						}
					?>
					</div>
					<div class="pull-right"><?php echo $this->pagination->getResultsCounter(); ?></div>
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
						<td class="order nowrap center hidden-phone">
							<?php
							if ($this->perms->editstate)
							{
								$disableClassName = '';
								$disabledLabel	  = '';

								if (!$hasAjaxOrderingSupport['saveOrder'])
								{
									$disabledLabel    = JText::_('JORDERINGDISABLED');
									$disableClassName = 'inactive tip-top';
								}
								?>
								<span class="sortable-handler <?php echo $disableClassName; ?>" title="<?php echo $disabledLabel; ?>" rel="tooltip">
									<i class="icon-menu"></i>
								</span>
								<input type="text" style="display:none"  name="order[]" size="5" value="<?php echo $item->ordering;?>" class="input-mini text-area-order " />
							<?php
							}
							else
							{
								?>
								<span class="sortable-handler inactive">
									<i class="icon-menu"></i>
								</span>
							<?php
							}
							?>
						</td>
						<td class="center">
							<?php echo JHtml::_('grid.id', $i, $item->csvi_rule_id); ?>
						</td>
						<td>
							  <a href="<?php echo JRoute::_('index.php?option=com_csvi&view=rule&id=' . (int) $item->csvi_rule_id); ?>">
								<?php echo $this->escape($item->name); ?>
							  </a>
						</td>
						<td><?php echo $item->plugin; ?></td>
						<td>
							<?php echo JText::_('COM_CSVI_' . strtoupper($item->action)); ?>
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
