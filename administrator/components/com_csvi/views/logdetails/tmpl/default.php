<?php
/**
 * @package     CSVI
 * @subpackage  Logdetails
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

JHtml::_('behavior.modal');
JHtml::_('formbehavior.chosen');
?>
<div class="row-fluid">
	<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=logdetails&run_id=' . $this->input->getInt('run_id')); ?>" method="post" id="adminForm" name="adminForm">
		<div class="row-fluid">
			<div class="span2">
				<div>
					<span class="badge badge-info"><?php echo JText::_('COM_CSVI_RECORDS_PROCESSED'); ?></span>
				</div>
				<?php echo $this->logresult->records; ?>
			</div>
			<div class="span3">
				<div>
					<span class="badge badge-info"><?php echo JText::_('COM_CSVI_FILENAME'); ?></span>
				</div>
				<?php echo $this->logresult->file_name; ?>
			</div>
			<div class="span2">
				<div>
					<span class="badge badge-info"><?php echo JText::_('COM_CSVI_DEBUG_LOG'); ?></span>
				</div>
				<?php echo $this->logresult->debug; ?>
			</div>
		</div>
		<?php if (!empty($this->logresult->resultstats)) : ?>
			<table class="table table-condensed table-striped">
				<caption>
					<h3>
						<?php echo JText::_('COM_CSVI_LOG_STATISTICS'); ?>
					</h3>
				</caption>
				<thead>
				</thead>
				<tfoot>
				</tfoot>
				<tbody>
				<?php foreach ($this->logresult->resultstats as $result) : ?>
					<tr>
						<td class="span2"><?php echo $result->area; ?></td>
						<td class="span2"><?php echo $result->status; ?></td>
						<td class="span2"><?php echo $result->total; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>

		<?php if (!empty($this->logresult->result)) : ?>
			<h3 class="center">
				<?php echo JText::_('COM_CSVI_LOG_DETAILS'); ?>
			</h3>
			<table id="loglines" class="adminlist table table-condensed table-striped">
				<thead>
				</thead>
				<tfoot>
				</tfoot>
				<tbody>
					<!--  Add some filters -->
					<div id="filterbox" class="form-horizontal">
						<?php echo JText::_('COM_CSVI_LOGDETAILS_FILTER'); ?>
						<?php
						echo JHtml::_(
							'select.genericlist',
							$this->actions,
							'action',
							'class="input-medium advancedSelect"',
							'value',
							'text',
							$this->escape($this->getModel()->getState('action', '')),
							false,
							true
						);
						?>
						<?php
						echo JHtml::_(
							'select.genericlist',
							$this->results,
							'result',
							'class="input-medium advancedSelect"',
							'value',
							'text',
							$this->escape($this->getModel()->getState('result', '')),
							false,
							true
						);
						?>
						<input type="submit" class="btn" onclick="this.form.submit();" value="<?php echo JText::_('COM_CSVI_LOGDETAILS_GO'); ?>" />
						<input type="submit" class="btn" onclick="jQuery('#action, #result').val('');" value="<?php echo JText::_('COM_CSVI_LOGDETAILS_RESET'); ?>" />
						<div class="resultscounter"><?php echo $this->pagination->getResultsCounter(); ?></div>
					</div>
					<table class="table table-condensed table-striped">
						<thead>
						<tr>
							<th class="title">
								<?php echo JHtml::_('grid.sort', 'COM_CSVI_LOG_LINE', 'line',  $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
							</th>
							<th class="title">
								<?php echo JHtml::_('grid.sort', 'COM_CSVI_LOG_ACTION', 'status',  $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
							</th>
							<th class="title">
								<?php echo JHtml::_('grid.sort', 'COM_CSVI_LOG_RESULT', 'result',  $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
							</th>
							<th class="title">
								<?php echo JText::_('COM_CSVI_LOG_MESSAGE'); ?>
							</th>
						</tr>
						</thead>
						<tfoot>
						<tr>
							<td colspan="4"><?php echo $this->pagination->getListFooter(); ?></td>
						</tr>
						</tfoot>
						<tbody>
						<?php
						if ($this->items)
						{
							foreach ($this->items as $key => $log)
							{ ?>
								<tr>
									<td>
										<?php echo $log->line; ?>
									</td>
									<td>
										<?php echo $log->status; ?>
									</td>
									<td>
										<?php echo JText::_($log->result); ?>
									</td>
									<td>
										<?php echo nl2br($log->description); ?>
									</td>
								</tr>
							<?php
							}
						}
						?>
						</tbody>
					</table>
				</tbody>
			</table>
		<?php endif; ?>
		<input type="hidden" id="task" name="task" value="browse" />
		<input type="hidden" name="run_id" value="<?php echo $this->runId; ?>" />
		<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
		<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
		<?php echo JHtml::_('form.token'); ?>
	</form>
</div>
