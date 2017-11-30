<?php
/**
 * @package     CSVI
 * @subpackage  About
 *
 * @author      Roland Dalmulder <contact@csvimproved.com>
 * @copyright   Copyright (C) 2006 - 2015 RolandD Cyber Produksi. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 * @link        http://www.csvimproved.com
 */

defined('_JEXEC') or die;
?>
<div class="row-fluid">
	<table class="table table-condensed table-striped">
		<thead>
			<tr>
				<th width="650"><?php echo JText::_('COM_CSVI_FOLDER'); ?></th>
				<th><?php echo JText::_('COM_CSVI_FOLDER_STATUS'); ?></th>
				<th><?php echo JText::_('COM_CSVI_FOLDER_OPTIONS'); ?></th>
			</tr>
		</thead>
		<tfoot>
		</tfoot>
		<tbody>
			<?php
			$i = 1;
				foreach ($this->folders as $name => $access) { ?>
			<tr>
				<td><?php echo $name; ?></td>
				<td><?php if ($access) {
					echo '<span class="writable">'.JText::_('COM_CSVI_WRITABLE').'</span>';
				} else { echo '<span class="not_writable">'.JText::_('COM_CSVI_NOT_WRITABLE').'</span>';
	} ?>

				<td><?php if (!$access) { ?>
					<form action="index.php?option=com_csvi&view=about">
						<input type="button" class="button"
							onclick="Csvi.createFolder('<?php echo $name; ?>', 'createfolder<?php echo $i; ?>'); return false;"
							name="createfolder"
							value="<?php echo JText::_('COM_CSVI_FOLDER_CREATE'); ?>" />
					</form>
					<div id="createfolder<?php echo $i;?>"></div> <?php } ?>
				</td>
			</tr>
			<?php $i++;
				} ?>
		</tbody>
	</table>
	<div class="clr"></div>
	<table class="adminlist table table-condensed table-striped">
		<thead>
			<tr>
				<th><?php echo JText::_('COM_CSVI_ABOUT_SETTING'); ?></th>
				<th><?php echo JText::_('COM_CSVI_ABOUT_VALUE'); ?></th>
			</tr>
		</thead>
		<tfoot></tfoot>
		<tbody>
			<tr>
				<td style="width: 10%"><?php echo JText::_('COM_CSVI_ABOUT_DISPLAY_ERRORS'); ?></td>
				<td><?php echo (ini_get('display_errors')) ? JText::_('COM_CSVI_YES') : JText::_('COM_CSVI_NO'); ?></td>
			</tr>
			<tr>
				<td><?php echo JText::_('COM_CSVI_ABOUT_MAGIC_QUOTES_RUNTIME'); ?></td>
				<td><?php echo (ini_get('magic_quotes')) ? JText::_('COM_CSVI_YES') : JText::_('COM_CSVI_NO'); ?></td>
			</tr>
			<tr>
				<td><?php echo JText::_('COM_CSVI_ABOUT_MAGIC_QUOTES_GPC'); ?></td>
				<td><?php echo (get_magic_quotes_gpc()) ? JText::_('COM_CSVI_YES') : JText::_('COM_CSVI_NO'); ?></td>
			</tr>
			<tr>
				<td><?php echo JText::_('COM_CSVI_ABOUT_PHP'); ?></td>
				<td><?php echo PHP_VERSION; ?></td>
			</tr>
			<tr>
				<td><?php echo JText::_('COM_CSVI_ABOUT_JOOMLA'); ?></td>
				<td><?php echo JVERSION; ?></td>
			</tr>
			<tr>
				<td><?php echo JText::_('COM_CSVI_ABOUT_DATABASE_SCHEMA_VERSION'); ?></td>
				<td><?php echo $this->schemaVersion; ?></td>
			</tr>
			<?php
				$messages = array();
				foreach($this->errors as $line => $error) :
					$key = 'COM_CSVI_MSG_DATABASE_' . $error->queryType;
					$msgs = $error->msgElements;
					$file = basename($error->file);
					$msg0 = (isset($msgs[0])) ? $msgs[0] : ' ';
					$msg1 = (isset($msgs[1])) ? $msgs[1] : ' ';
					$msg2 = (isset($msgs[2])) ? $msgs[2] : ' ';
					$messages[] = JText::sprintf($key, $file, $msg0, $msg1, $msg2); ?>
			<?php endforeach; ?>
			<?php if (count($messages) > 0) :?>
				<tr>
					<td></td>
					<td>
						<div>
							<div class="error"><?php echo JText::_('COM_CSVI_MSG_DATABASE_ERRORS'); ?></div>
							<ul class="adminformlist">
								<?php foreach ($messages as $message) : ?>
									<li><?php echo $message; ?></li>
								<?php endforeach; ?>
							</ul>
						</div>
					</td>
				</tr>
			<?php endif; ?>
		</tbody>
	</table>
	<form name="adminForm" id="adminForm" action="<?php echo JRoute::_('index.php?option=com_csvi&view=about'); ?>" method="post">
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="<?php echo JFactory::getSession()->getFormToken();?>" value="1" />
	</form>
</div>
