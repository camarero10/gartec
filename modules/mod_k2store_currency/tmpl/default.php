<?php
/*------------------------------------------------------------------------
# mod_k2store_cart - K2Store Cart
# ------------------------------------------------------------------------
# author    Sasi varna kumar - Weblogicx India http://www.weblogicxindia.com
# copyright Copyright (C) 2012 Weblogicxindia.com. All Rights Reserved.
# @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
# Websites: http://k2store.org
# Technical Support:  Forum - http://k2store.org/forum/index.html
-------------------------------------------------------------------------*/


// no direct access
defined('_JEXEC') or die('Restricted access');
require_once (JPATH_ADMINISTRATOR.'/components/com_k2store/helpers/strapper.php');
K2StoreStrapper::addJS();
$action = JRoute::_('index.php');

?>
<script type="text/javascript">
<!--
if(typeof(k2store) == 'undefined') {
	var k2store = {};
}
if(typeof(k2store.jQuery) == 'undefined') {
	k2store.jQuery = jQuery.noConflict();
}

//-->
</script>

<style type="text/css">
#k2store_currency {
background: <?php echo $background_color; ?>;
color: <?php echo $text_color; ?>;
}

#k2store_currency a {
color: <?php echo $link_color; ?>;
}

#k2store_currency a.active {
color: <?php echo $active_link_color; ?>;
}


#k2store_currency a:hover {
color: <?php echo $link_hover_color; ?>;
}

</style>

<?php if (count($currencies) > 1) : ?>
<div class="k2store <?php echo $moduleclass_sfx ?>" >
<form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data">
  <div id="k2store_currency">
    <?php foreach ($currencies as $currency) : ?>

	    <?php if ($currency->currency_code == $currency_code) : ?>
		    <a class="active" title="<?php echo $currency->currency_title; ?>"><b><?php echo $currency->currency_symbol; ?></b></a>
	    <?php else: ?>
	    	<a title="<?php echo $currency->currency_title; ?>" onclick="k2store.jQuery('input[name=\'currency_code\']').attr('value', '<?php echo $currency->currency_code; ?>'); k2store.jQuery(this).parent().parent().submit();"><?php echo $currency->currency_symbol; ?></a>
	    <?php endif; ?>

    <?php endforeach; ?>
    <input type="hidden" name="currency_code" value="" />
    <input type="hidden" name="option" value="com_k2store" />
    <input type="hidden" name="view" value="mycart" />
    <input type="hidden" name="task" value="setcurrency" />
    <input type="hidden" name="redirect" value="<?php echo base64_encode( JUri::getInstance()->toString()); ?>" />
  </div>
</form>
</div>
<?php endif; ?>
