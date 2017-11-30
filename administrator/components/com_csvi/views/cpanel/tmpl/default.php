<?php
/**
 * @package     CSVI
 * @subpackage  Dashboard
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;
JHtml::_('behavior.modal');
?>
<table class="table table-condensed table-striped table-hover">
	<thead>
		<tr>
			<th>
				<?php echo JText::_('COM_CSVI_ACTION'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_CSVI_ACTION_TYPE'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_CSVI_TEMPLATE_NAME_TITLE'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_CSVI_START'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_CSVI_END'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_CSVI_USER'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_CSVI_RECORDS'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_CSVI_RUN_CANCELLED'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_CSVI_FILENAME'); ?>
			</th>
			<th>
				<?php echo JText::_('COM_CSVI_DEBUG_LOG'); ?>
			</th>
		</tr>
	</thead>
	<tfoot>
		<tr>
			<td colspan="11"></td>
		</tr>
	</tfoot>
	<tbody>
		<?php
		if (!empty($this->items))
		{
			foreach ($this->items as $i => $item)
			{
				$link 	= 'index.php?option=com_csvi&view=logdetails&run_id=' . $item->csvi_log_id;
				?>
				<tr>
					<td>
						<a href="<?php echo $link; ?>"><?php echo JText::_('COM_CSVI_' . $item->action); ?></a>
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
