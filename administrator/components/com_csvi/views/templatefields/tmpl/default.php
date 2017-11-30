<?php
/**
 * Templates list page
 *
 * @author 		Roland Dalmulder
 * @link 		http://www.csvimproved.com
 * @copyright 	Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license 	GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @version 	$Id: default.php 1924 2012-03-02 11:32:38Z RolandD $
 */

defined('_JEXEC') or die;

$saveOrderingUrl = 'index.php?option=com_csvi&view=templatefields&task=saveorder&format=json';
JHtml::_('sortablelist.sortable', 'itemsList', 'adminForm', strtolower($this->lists->order_Dir), $saveOrderingUrl);

JHtml::_('formbehavior.chosen', 'select');
?>
<form action="<?php echo JRoute::_('index.php?option=com_csvi&view=templatefields'); ?>" method="post" name="adminForm" id="adminForm" class="form-horizontal">
	<div class="pull-right">
		<?php echo $this->pagination->getLimitBox(); ?>
	</div>
	<div class="clearfix" />
	<table class="table table-striped" id="itemsList">
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
				<th>
					<input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" />
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_TEMPLATE_NAME', 'template_id', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<th>
					<?php echo JHtml::_('grid.sort', 'COM_CSVI_FIELD_NAME', 'field_name', $this->lists->order_Dir, $this->lists->order, 'browse'); ?>
				</th>
				<?php
					if ($this->action == 'export')
					{
						?>
						<th>
							<?php echo JText::_('COM_CSVI_COLUMN_HEADER_LABEL'); ?>
						</th>
						<?php
					}

					if ($this->action == 'import')
					{
						?>
						<th>
							<?php echo JText::_('COM_CSVI_XML_NODE_LABEL'); ?>
						</th>
						<?php
					}
				?>
				<th>
					<?php echo JText::_('COM_CSVI_DEFAULT_VALUE_LABEL'); ?>
				</th>
				<?php
				if ($this->action == 'export')
				{
				?>
					<th>
						<?php echo JText::_('COM_CSVI_COLUMN_PUBLISHED_LABEL'); ?>
					</th>
				<?php
				}
				?>
			</tr>
			<tr>
				<th></th>
				<th></th>
				<th>
					<?php echo JHtml::_(
							'select.groupedlist',
							$this->groupedtemplates,
							'csvi_template_id',
							array(
								'list.attr' => 'class="input-xlarge" onchange="this.form.submit()"',
								'id' => 'csvi_template_id',
								'list.select' => $this->csvi_template_id
							)
					); ?>
				</th>
				<th>
					<input type="text" value="<?php echo $this->escape($this->getModel()->getState('field_name','')); ?>" name="field_name" id="field_name" class="input-medium"/>
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
				<th></th>
				<?php
				if ($this->action == 'export')
				{
					?>
					<th>
					</th>
				<?php
				}
				?>
			</tr>
		<thead>
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
					<td class="order nowrap center hidden-phone">
						<span class="sortable-handler">
							<i class="icon-menu"></i>
						</span>
						<input type="text" style="display:none"  name="order[]" size="5"
							value="<?php echo $item->ordering;?>" class="input-mini text-area-order " />
					</td>
					<td>
						<?php echo JHtml::_('grid.id', $i, $item->csvi_templatefield_id); ?>
					</td>
					<td><?php echo $item->template_name; ?></td>
					<td>
						<a href="<?php echo JRoute::_('index.php?option=com_csvi&view=templatefield&id=' . (int) $item->csvi_templatefield_id); ?>">
							<?php echo $this->escape($item->field_name); ?>
						</a>
						<?php
						if (!empty($item->rules))
						{
							echo '<span class="icon-fire"></span>';
						}
						?>
					</td>
					<?php
						if ($this->action == 'export')
						{
							?>
							<td><?php echo $item->column_header; ?></td>
							<?php
						}
						?>

					<?php
						if ($this->action == 'import')
						{
							?>
							<td><?php echo $item->xml_node; ?></td>
							<?php
						}
						?>
					<td>
						<?php echo $item->default_value; ?>
					</td>
					<?php
					if ($this->action == 'export')
					{
						?>
						<td>
							<?php echo JHtml::_('jgrid.published', $item->enabled, $i, '', true); ?>
						</td>
					<?php
					}
					?>
				</tr>
			<?php
			}
			?>
		<?php
		}
		?>
		</tbody>
	</table>
	<input type="hidden" name="task" value="browse" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="filter_order" id="filter_order" value="<?php echo $this->lists->order ?>" />
	<input type="hidden" name="filter_order_Dir" id="filter_order_Dir" value="<?php echo $this->lists->order_Dir ?>" />
	<?php echo JHtml::_('form.token'); ?>
</form>

<!-- The Quick Add form -->
<div class="modal hide fade" id="availablefieldsModal" style="width: 30%; left: 70%;">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal">&#215;</button>
		<h3><?php echo JText::_('COM_CSVI_QUICK_ADD_FIELDS'); ?></h3>
	</div>
	<div class="modal-body modal-batch" style="overflow-y: scroll; max-height: 400px;">
		<input type="search" class="light-table-filter" data-table="order-table" placeholder="<?php echo JText::_('JSEARCH_FILTER'); ?>">
		<table class="order-table table table-striped table-hover">
			<tbody>
			<?php
				foreach ($this->availablefields as $fieldname)
				{
					?>
					<tr>
						<td>
							<input type="checkbox" name="quickfields"  class="addfield" value="<?php echo $fieldname->csvi_name; ?>" />
						</td>
						<td class="addfield">
							<?php echo $fieldname->csvi_name; ?>
						</td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
	</div>
	<div class="modal-footer">
		<button class="btn btn-primary show-button" style="display: none;" type="button" onclick="selectFields('uncheck');">
			<?php echo JText::_('COM_CSVI_UNCHECK_ALL_FIELDS'); ?>
		</button>
		<button class="btn btn-primary show-button" style="display: none;" type="submit" onclick="addFields();">
			<?php echo JText::_('COM_CSVI_ADD_FIELDS'); ?>
		</button>
		<button class="btn" type="button" data-dismiss="modal">
			<?php echo JText::_('JCANCEL'); ?>
		</button>
	</div>
</div>

<?php
	$layout = new JLayoutFile('csvi.modal');
	echo $layout->render(array('ok-btn-dismiss' => true));
?>

<script type="text/javascript">
Joomla.submitbutton = function(task)
{
	if (task == 'quickadd')
	{
		jQuery('#availablefieldsModal').modal('show');
	}
	else
	{
		Joomla.submitform(task);
	}
};

// Selects a field in the quick add list when user clicks on the name only
jQuery(".addfield").click(function()
{
	if (jQuery(this).is(':radio'))
	{
		var selectbox = jQuery(this);
	}
	else
	{
		var selectbox = jQuery(this).parent().children('td').children('input');

		if (jQuery(selectbox).attr('checked'))
		{
			jQuery(selectbox).attr('checked', false);
		}
		else
		{
			jQuery(selectbox).attr('checked', true);
			jQuery('.show-button').show();
		}
	}

	// Check if there are any checked boxes left
	var checked = 0;

	jQuery("input[name='quickfields'][type='checkbox']").each(function()
	{
		if (jQuery(this).is(':checked'))
		{
			checked = checked + 1;
		}
	});

	if (checked == 0)
	{
		jQuery('.show-button').hide();
	}
});

function selectFields(task)
{
	jQuery("input[name='quickfields'][type='checkbox']").each(function()
	{
		if (task == 'check')
		{
			jQuery(this).attr('checked', 'true');
		}
		else if (task == 'uncheck')
		{
			jQuery(this).removeAttr('checked');

			// Check if there are any checked boxes left
			var checked = 0;

			jQuery("input[name='quickfields'][type='checkbox']").each(function()
			{
				if (jQuery(this).is(':checked'))
				{
					checked = checked + 1;
				}
			});

			if (checked == 0)
			{
				jQuery('.show-button').hide();
			}
		}
	});
}

function addFields()
{
	var fieldnames = [];
	jQuery("input[name='quickfields'][type='checkbox']").each(function()
	{
		if (jQuery(this).is(':checked'))
		{
			fieldnames.push(jQuery(this).val());
		}
	});
	// Send the data to the database
	var template_id = <?php echo $this->csvi_template_id; ?>;

	if (template_id > 0)
	{
		jQuery.ajax({
			async: false,
			type: 'post',
			url: 'index.php',
			dataType: 'json',
			data: 'option=com_csvi&view=templatefield&task=storetemplatefield&format=json&template_id='+template_id+'&field_name='+fieldnames.join('~'),
			success: function(data)
			{
				window.location = "index.php?option=com_csvi&view=templatefields&template_id=<?php echo $this->getModel()->getState('template_id'); ?>";
			},
			error:function (request, status, error)
			{
				jQuery('#availablefieldsModal').modal('hide');

				showMsg(
					'<?php echo JText::_('COM_CSVI_ERROR'); ?>',
					'<span class="error">' + jQuery.trim(request.responseText).substring(0, 2500) + '</span>',
					'<?php echo JText::_('COM_CSVI_CLOSE_DIALOG'); ?>'
				);
			}
		});
	}
	jQuery('#availablefieldsModal').modal('hide');
}

// Source: http://codepen.io/chriscoyier/pen/tIuBL
(function(document) {
	'use strict';

	var LightTableFilter = (function(Arr) {

		var _input;

		function _onInputEvent(e) {
			_input = e.target;
			var tables = document.getElementsByClassName(_input.getAttribute('data-table'));
			Arr.forEach.call(tables, function(table) {
				Arr.forEach.call(table.tBodies, function(tbody) {
					Arr.forEach.call(tbody.rows, _filter);
				});
			});
		}

		function _filter(row) {
			var text = row.textContent.toLowerCase(), val = _input.value.toLowerCase();
			row.style.display = text.indexOf(val) === -1 ? 'none' : 'table-row';
		}

		return {
			init: function() {
				var inputs = document.getElementsByClassName('light-table-filter');
				Arr.forEach.call(inputs, function(input) {
					input.oninput = _onInputEvent;
				});
			}
		};
	})(Array.prototype);

	document.addEventListener('readystatechange', function() {
		if (document.readyState === 'complete') {
			LightTableFilter.init();
		}
	});

})(document);
 </script>
