jQuery(function()
{
	function log( name, value )
	{
		jQuery("#jform_categoryList").append(jQuery('<option></option>').val(value).text(name));
		jQuery('#jform_categoryList option').prop('selected', true);
	}

	jQuery("#jform_category").autocomplete
	({
		source: 'index.php?option=com_csvi&view=templates&task=jsonData&addon=com_mijoshop&method=findCategory&format=json&language_id=' + jQuery('#jform_language').val(),
		minLength: 2,
		select: function( event, ui )
		{
			if (ui.item)
			{
				log(ui.item.value, ui.item.id);
				jQuery(this).val('');
				return false;
			}
		}
	});
});