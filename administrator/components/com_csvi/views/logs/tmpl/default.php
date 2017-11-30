<?php
/**
 * @package     CSVI
 * @subpackage  Logs
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

JHtml::_('behavior.modal');
JHtml::_('formbehavior.chosen');
JHtml::_('formbehavior.chosen', '.inputbox');
?>

<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=logs'); ?>" method="post" name="adminForm" id="adminForm">
	<div class="btn-toolbar" id="filter-bar">
		<div class="btn-group pull-right hidden-phone">
			<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?></label>
			<?php echo $this->pagination->getLimitBox(); ?>
		</div>
	</div>
	<div id="availablefieldslist">
		<table class="table table-condensed table-striped table-hover">
			<thead>
			<tr>
				<th width="20">
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_ACTION', 'action', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_ACTION_TYPE', 'action_type', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_TEMPLATE_NAME_TITLE', 'template_name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_START', 'start', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_END', 'end', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_USER', 'userid', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_RECORDS', 'records', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_RUN_CANCELLED', 'run_cancelled', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_FILENAME', 'file_name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JText::_('COM_CSVI_DEBUG_LOG'); ?>
				</th>
			</tr>
			<tr>
				<th></th>
				<th>
					<?php echo JHtml::_(
						'select.genericlist',
						$this->get('ActionTypes'),
						'actiontype',
						'class="input-medium advancedSelect" onchange="this.form.submit()"',
						'value',
						'text',
						$this->escape($this->getModel()->getState('actiontype', ''))
					); ?>
				</th>
				<th colspan="11"></th>
			</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="11"><?php echo $this->pagination->getListFooter(); ?></td>
				</tr>
			</tfoot>
			<tbody>
			<?php
				if (!empty($this->items))
				{
					foreach ($this->items as $i => $item)
					{
						$checkedOut = false;
						$ordering = $this->lists->order == 'ordering';
						$link 	= 'index.php?option=com_csvi&view=logdetails&run_id=' . $item->csvi_log_id;
						?>
						<tr>
							<td align="center">
								<?php echo JHtml::_('grid.id', $i, $item->csvi_log_id, $checkedOut); ?>
							</td>
							<td>
								<a href="<?php echo $link; ?>">
									<?php echo JText::_('COM_CSVI_' . $item->action); ?>
								</a>
							</td>
							<td>
								<?php echo JText::_('COM_CSVI_' . $item->addon . '_' . $item->action_type); ?>
							</td>
							<td>
								<?php echo $item->template_name; ?>
							</td>
							<td>
								<?php echo JHtml::_('date', $item->start, 'Y-m-d H:i:s'); ?>
							</td>
							<td>
								<?php
									if ($item->end != '0000-00-00 00:00:00')
									{
										echo JHtml::_('date', $item->end, 'Y-m-d H:i:s');
									}
									else
									{
										echo JText::_('COM_CSVI_LOG_ENDDATE_UNKNOWN');
									}
								?>
							</td>
							<td>
								<?php echo $item->runuser; ?>
							</td>
							<td>
								<?php echo $item->records; ?>
							</td>
							<td>
								<?php $run_cancelled = ($item->run_cancelled) ? JText::_('COM_CSVI_YES') : JText::_('COM_CSVI_NO');
								echo $run_cancelled;?>
							</td>
							<td>
								<?php echo $item->file_name; ?>
							</td>
							<td>
								<?php
								if ($item->action == 'import' || $item->action == 'export')
								{
									if (file_exists(JPATH_SITE . '/logs/com_csvi.log.' . $item->csvi_log_id . '.php'))
									{
										$attribs = 'class="modal" onclick="" rel="{handler: \'iframe\', size: {x: 950, y: 500}}"';
										echo JHtml::_(
											'link',
											JRoute::_('index.php?option=com_csvi&view=logs&task=logreader&tmpl=component&run_id=' . $item->csvi_log_id),
											JText::_('COM_CSVI_SHOW_LOG'),
											$attribs
										);
										echo ' | ';
										echo JHtml::_(
											'link',
											JRoute::_('index.php?option=com_csvi&view=logs&task=logreader&tmpl=component&run_id=' . $item->csvi_log_id),
											JText::_('COM_CSVI_OPEN_LOG'),
											'target="_new"'
										);
										echo ' | ';
										echo JHtml::_(
											'link',
											JRoute::_('index.php?option=com_csvi&view=logs&task=downloaddebug&run_id=' . $item->csvi_log_id),
											JText::_('COM_CSVI_DOWNLOAD_LOG')
										);
									}
									else
									{
										echo JText::_('COM_CSVI_NO_DEBUG_LOG');
									}
								}
								else
								{
									echo JText::_('COM_CSVI_NO_DEBUG_LOG');
								}
								?>
							</td>
						</tr>
					<?php
					}
				}
				else
				{
					echo '<tr><td colspan="11" class="center">' . JText::_('COM_CSVI_NO_LOG_ENTRIES_FOUND') . '</td></tr>';
				}
				?>
			</tbody>
		</table>
	</div>
	<input type="hidden" id="task" name="task" value="browse" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>

<!-- Load the modal skeleton -->
<?php
$layout = new JLayoutFile('csvi.modal');
echo $layout->render(array('cancel-button' => true));
?>

<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task != 'logdetails') {
			// Set the task
			document.adminForm.task.value = task;
			if (task == 'remove')
			{
				showMsg(
					'<?php echo JText::_('COM_CSVI_DELETE'); ?>',
					'<?php echo JText::_('COM_CSVI_LOG_ARE_YOU_SURE_REMOVE'); ?>',
					'<?php echo JText::_('COM_CSVI_OK'); ?>',
					''
				);
			}
			else if (task == 'remove_all')
			{
				showMsg(
					'<?php echo JText::_('COM_CSVI_DELETE_ALL'); ?>',
					'<?php echo JText::_('COM_CSVI_LOG_ARE_YOU_SURE_REMOVE_ALL'); ?>',
					'<?php echo JText::_('COM_CSVI_OK'); ?>',
					''
				);
			}

			jQuery('.ok-btn').on('click', function(e) {
				e.preventDefault();
				Joomla.submitform(task);
			});

			jQuery('.cancel-btn').on('click', function(e) {
				e.preventDefault();
				document.adminForm.task.value = '';
				jQuery('#csviModal').modal('hide');
			});
		}
		else {
			if (task == 'logdetails')
			{
				// Set the task
				document.adminForm.task.value = '';
				var task = 'browse';
				jQuery('form').attr('action', '<?php echo html_entity_decode(JRoute::_('index.php?option=com_csvi&view=logdetails')); ?>');
			}
			Joomla.submitform(task);
		}
	}
</script>
