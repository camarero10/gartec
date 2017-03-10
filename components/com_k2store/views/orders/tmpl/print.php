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

?>
<?php if (JFactory::getApplication()->input->getString('task') == 'printOrder') : ?>
    <script type="text/javascript">
           window.print();
    </script>
<?php endif; ?>

<div class="container-fluid k2store">
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