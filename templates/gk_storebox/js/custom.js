

jQuery(document).ready(function(){
    var div_ancho = jQuery("#gkMainbody a.imgProdListado").width();
    var div_alto = jQuery("#gkMainbody a.imgProdListado").height();

    jQuery("#gkMainbody a.imgProdListado").height(div_ancho);
    jQuery("#gkMainbody a.imgProdListado img").height(div_ancho);

});

jQuery(document).ready(function(){

    jQuery(window).on('resize', function(){
        if (jQuery(this).height() >= div_ancho){
            jQuery('#gkMainbody a.imgProdListado img').css('max-height', div_ancho); //set max height
        } else{
            jQuery('#gkMainbody a.imgProdListado img').css('max-height', '');
        }
    }).resize()

});



