<?php
/*------------------------------------------------------------------------
 # com_k2store - K2 Store
# ------------------------------------------------------------------------
# author    Ramesh Elamathi - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/


//no direct access
defined('_JEXEC') or die('Restricted access');
JHtml::_('behavior.modal');
?>
<script type="text/javascript">
function k2storeOpenModal(url) {
	<?php if(JBrowser::getInstance()->getBrowser() =='msie') :?>
	var options = {size:{x:document.documentElement.­clientWidth-80, y: document.documentElement.­clientHeight-80}};
	<?php else: ?>
	var options = {size:{x: window.innerWidth-80, y: window.innerHeight-80}};
	<?php endif; ?>
	SqueezeBox.initialize();
	SqueezeBox.setOptions(options);
	SqueezeBox.setContent('iframe',url);
}
</script>
<div class="container-fluid k2store">

<div class='row-fluid'>
<?php if(!isset($this->guest)): ?>
	<div class="span6 pull-left">
		<a class="btn" href="<?php echo JRoute::_("index.php?option=com_k2store&view=orders"); ?>"><?php echo JText::_( 'K2STORE_ORDER_RETURN_TO_LIST' ); ?></a>
	</div>

<?php endif; ?>

<div class="span6 pull-right">
	<?php
	$url = JRoute::_( "index.php?option=com_k2store&view=orders&task=printOrder&tmpl=component&id=".@$this->row->id);
	?>
	<input type="button" class="btn btn-primary" onclick="k2storeOpenModal('<?php echo $url; ?>')" value="<?php echo JText::_( "K2STORE_PRINT_INVOICE" ); ?>" />
	</div>

</div>
<?php
// get the template and default paths for the layout
$templatePath = JPATH_SITE.'/templates/'.JFactory::getApplication()->getTemplate().'/html/com_k2store/orders/view_item.php';
$defaultPath = JPATH_SITE.'/components/com_k2store/views/orders/tmpl/view_item.php';

// if the site template has a layout override, use it
jimport('joomla.filesystem.file');
if (JFile::exists( $templatePath ))
{
	$path = $templatePath;
}
else
{
	$path = $defaultPath;
}
include_once($path);
?>
</div>
