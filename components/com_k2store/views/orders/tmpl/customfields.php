<?php
if($type=='billing') {
	$field = 'all_billing';
}elseif($type=='shipping') {
	$field = 'all_shipping';
}elseif($type=='payment') {
	$field = 'all_payment';
}
$fields = array();
if(!empty($row->$field) && JString::strlen($row->$field) > 0) {
	$custom_fields = json_decode(stripslashes($row->$field));
	if(isset($custom_fields) && count($custom_fields)) {
		foreach($custom_fields as $namekey=>$field) {
			if(!property_exists($row, $type.'_'.$namekey) && !property_exists($row, 'user_'.$namekey) && $namekey !='country_id' && $namekey != 'zone_id' && $namekey != 'option' && $namekey !='task' && $namekey != 'view' ) {
				$fields[$namekey] = $field;
			}
		}

	}
}
?>


<?php if(isset($fields) && count($fields)) :?>
<?php foreach($fields as $namekey=>$field) : ?>
	<?php if(is_object($field)): ?>
		<dt><?php echo JText::_($field->label); ?>:</dt>
		<dd>
		<?php
		if(is_array($field->value)) {
			echo '<br />';
			foreach($field->value as $value) {
				echo '- '.JText::_($value).'<br/>';
			}

		}elseif(K2StoreOrdersHelper::isJson(stripslashes($field->value))) {
			$json_values = json_decode(stripslashes($field->value));

		if(is_array($json_values)) {
			foreach($json_values as $value){
				echo '- '.JText::_($value).'<br/>';
			}
		} else {
				echo JText::_($field->value);
			}

		} else {
			echo JText::_($field->value);
		}
		?>
		</dd>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>