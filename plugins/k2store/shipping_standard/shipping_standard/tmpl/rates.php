<?php defined('_JEXEC') or die('Restricted access'); ?>
<?php
$rates = array();
foreach($vars->rates as $rate){
	$r = new JObject;
	$r->value = $rate->shipping_rate_id;
	$r->text = K2StorePrices::number($rate->shipping_rate_price);
	$rates[] = &$r;
}
?>
<div class="shipping_rates">
<?php
echo JHTML::_( 'select.radiolist', $rates, 'shipping_rate', array() );
?>
</div>