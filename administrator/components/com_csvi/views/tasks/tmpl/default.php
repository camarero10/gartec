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
<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=tasks'); ?>" method="post" id="adminForm" name="adminForm" class="form-horizontal">
	<input type="hidden" name="task" value="browse" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
	<div class="pull-right">
		<?php echo $this->pagination->getLimitBox(); ?>
	</div>
	<table class="adminlist table table-striped" id="itemsList">
		<thead>
			<tr>
				<th width="20">
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
				</th>
				<th class="title" width="400px"><?php echo JHtml::_('grid.sort', 'COM_CSVI_TASK_NAME_LABEL', 'task_name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?></th>
				<th class="title"><?php echo JText::_('COM_CSVI_TASKS_TASK_DESC'); ?></th>
				<th class="title"><?php echo JHtml::_('grid.sort', 'COM_CSVI_COMPONENT_LABEL', 'component', $this->lists->order_Dir, $this->lists->order, 'browse'); ?></th>
				<th class="title"><?php echo JHtml::_('grid.sort', 'COM_CSVI_ACTION_LABEL', 'template_type', $this->lists->order_Dir, $this->lists->order, 'browse'); ?></th>
				<th class="title">
					<?php echo JText::_('JSTATUS'); ?>
				</th>
			</tr>
			<tr>
				<th></th>
				<th>
					<input type="text" class="input-large" name="name" id="name" value="<?php echo $this->escape($this->getModel()->getState('name','')); ?>" onchange="this.form.submit();" />
					<div class="btn-group hidden-phone">
						<button class="btn" onclick="this.form.submit();" title="<?php echo JText::_('JSEARCH_FILTER'); ?>">
							<i class="icon-search"></i>
						</button>
						<button class="btn" onclick="document.adminForm.field_name.value=''; this.form.submit();" title="<?php echo JText::_('JSEARCH_RESET'); ?>">
							<i class="icon-remove"></i>
						</button>
					</div>
				</th>
				<th></th>
				<th>
					<?php echo JHtml::_(
						'select.genericlist',
						$this->components,
						'component',
						'onchange="this.form.submit()" class="advancedSelect"',
						'value',
						'text',
						$this->escape($this->getModel()->getState('component', '')),
						false,
						true
					); ?>
				</th>
				<th>
					<?php echo JHtml::_(
						'select.genericlist',
						array('*' => JText::_('JALL'), 'import' => JText::_('COM_CSVI_IMPORT'),'export' => JText::_('COM_CSVI_EXPORT')),
						'process',
						'onchange="this.form.submit()" class="advancedSelect"',
						'value',
						'text',
						$this->escape($this->getModel()->getState('process', '')),
						false,
						true
					); ?>
				</th>
				<th>
					<?php echo JHtml::_(
						'select.genericlist',
						array('1' => JText::_('JPUBLISHED'), '0' => JText::_('JUNPUBLISHED')),
						'published',
						'onchange="this.form.submit()" class="advancedSelect"',
						'value',
						'text',
						$this->escape($this->getModel()->getState('published', '')),
						false,
						true
					); ?>
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
							<?php echo JHtml::_('grid.id', $i, $item->csvi_task_id); ?>
						</td>
						<td><?php
							if ($item->locked_by)
							{
								echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->locked_on, 'tasks.');
								echo $this->escape(JText::_('COM_CSVI_' . $item->component . '_' . $item->task_name));
							}
							else { ?>
							  <a href="<?php echo JRoute::_('index.php?option=com_csvi&view=task&id='. (int) $item->csvi_task_id); ?>">
								<?php echo $this->escape(JText::_('COM_CSVI_' . $item->component . '_' . $item->task_name)); ?>
							  </a>
							<?php } ?>
							<?php
							if (!empty($item->url))
							{
								echo '[ ' . JHtml::_('link', JRoute::_($item->url), JText::_($item->component), 'target="_blank"') . ' ]';
							}
							?>
						</td>
						<td><?php echo JText::_('COM_CSVI_' . $item->component . '_' . $item->task_name . '_DESC'); ?></td>
						<td>
							<?php echo JHtml::_('link', JRoute::_('index.php?option=' . $item->component), JText::_('COM_CSVI_' . $item->component), 'target="_blank"'); ?>
						</td>
						<td><?php echo JText::_('COM_CSVI_' . strtoupper($item->action)); ?></td>
						<td class="center"><?php echo JHtml::_('jgrid.published', $item->enabled, $i); ?></td>
					</tr>
				<?php } ?>
			<?php } ?>
		</tbody>
	</table>
</form>

<?php
	$layout = new JLayoutFile('csvi.modal');
	echo $layout->render(
	array(
		'modal-header' => JText::_('COM_CSVI_CONFIRM_RESET_TEMPLATETYPES_TITLE'),
		'modal-body' => JText::_('COM_CSVI_CONFIRM_RESET_TEMPLATETYPES_TEXT'),
		'cancel-button' => true,
		'ok-button' => JText::_('COM_CSVI_RESET_TEMPLATETYPES'),
		'ok-btn-dismiss' => true
	));
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task == 'reload') {
			jQuery('#csviModal').modal('show');

			jQuery('.ok-btn').on('click', function(e) {
				e.preventDefault();
				jQuery('#csviModal').modal('hide');
				Joomla.submitform(task);
			});
		}
		else Joomla.submitform(task);
	}
</script>
