/**
 * Setup (required for Joomla! 3)
 */
if(typeof(k2store) == 'undefined') {
	var k2store = {};
}

if(typeof(jQuery) != 'undefined') {
	jQuery.noConflict();

if(typeof(k2store.jQuery) == 'undefined') {
	k2store.jQuery = jQuery.noConflict();
}

}
if(typeof(k2storeURL) == 'undefined') {
	var k2storeURL = '';
}