<?php
/**
 * @package     CSVI
 * @subpackage  AvailableFields
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;

JHtml::_('formbehavior.chosen', 'select');
$helper = new CsviHelperCsvi;
?>
<form action="index.php?option=com_csvi&view=availablefields" method="post" id="adminForm" name="adminForm" class="form-horizontal">
	<div id="filterbox">
		<?php echo JText::_('COM_CSVI_AV_FILTER'); ?>:
		<?php
		// Get the list of actions
		$options = array();
		$options[] = JHtml::_('select.option', 'import', JText::_('COM_CSVI_IMPORT'));
		$options[] = JHtml::_('select.option', 'export', JText::_('COM_CSVI_EXPORT'));
		echo JHtml::_(
			'select.genericlist',
			$options,
			'jform_action',
			'onchange="Csvi.loadTasks();"',
			'value',
			'text',
			$this->escape($this->getModel()->getState('jform_action', ''))
		);
		$components = $helper->getComponents();
		echo JHtml::_(
			'select.genericlist',
			$components,
			'jform_component',
			'onchange="Csvi.loadTasks();"',
			'value',
			'text',
			$this->escape($this->getModel()->getState('jform_component', '')),
			false,
			true
		);

		// Get the first available component
		if (is_array($components) && !empty($components))
		{
			$component = $components[0]->value;
		}
		else
		{
			$component = '';
		}

		$operations = FOFModel::getTmpInstance('Tasks', 'CsviModel')
						->getOperations(
							$this->escape($this->getModel()->getState('jform_action', 'import')),
							$this->escape($this->getModel()->getState('jform_component', $component))
						);

		// Create the operations list
		echo JHtml::_(
				'select.genericlist',
				$operations,
				'jform_operation',
				'',
				'value',
				'name',
				$this->escape($this->getModel()->getState('jform_operation', '')),
				false,
				true
			);
		?>
		<input type="text" value="<?php echo $this->escape($this->getModel()->getState('avfields', '')); ?>" name="avfields" id="avfields" size="25" />
		<input type="submit" class="btn" onclick="this.form.submit();" value="<?php echo JText::_('COM_CSVI_AV_GO'); ?>" />
		<input
			type="submit"
			class="btn"
			onclick="document.adminForm.avfields.value = ''; document.adminForm.idfields.checked=false;"
			value="<?php echo JText::_('COM_CSVI_AV_RESET'); ?>"
		/>
		<?php
			if ($this->input->get('idfields', 0, 'int'))
			{
				$checked = 'checked="checked"';
			}
			else
			{
				$checked = '';
			}
		?>

		<input type="checkbox" value="1" <?php echo $checked; ?> name="idfields" id="idfields" />
		<?php echo JText::_('COM_CSVI_SHOW_IDFIELDS'); ?>
		<div class="limit pull-right"><?php echo $this->pagination->getLimitBox(); ?></div>
	</div>
	<div id="availablefieldslist" style="text-align: left;">
		<table id="available_fields" class="table table-condensed table-striped">
			<thead>
			<tr>
				<th class="title">
				<?php echo JHtml::_('grid.sort', 'COM_CSVI_AV_CSVI_NAME', 'csvi_name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th class="title">
				<?php echo JText::_('COM_CSVI_AV_COMPONENT_NAME'); ?>
				</th>
				<th class="title">
				<?php echo JHtml::_('grid.sort', 'COM_CSVI_AV_TABLE', 'component_table', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
			</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="7">
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
				?>
						<tr>
							<td>
								<?php
									echo $item->csvi_name;

									if ($item->isprimary)
									{
										echo '<span class="isprimary">' . JText::_('COM_CSVI_IS_PRIMARY') . '</span>';
									}
								?>
							</td>
							<td>
								<?php echo $item->component_name; ?>
							</td>
							<td>
								<?php echo $item->component_table; ?>
							</td>
						</tr>
				<?php
					}
				}
				?>
			</tbody>
		</table>
		<input type="hidden" id="task" name="task" value="browse" />
		<input type="hidden" name="boxchecked" value="0" />
		<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
		<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
		<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
	</div>
</form>
