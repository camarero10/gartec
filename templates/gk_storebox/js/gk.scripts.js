
/**
 * jQuery Cookie plugin
 *
 * Copyright (c) 2010 Klaus Hartl (stilbuero.de)
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 */
jQuery.noConflict();
jQuery.cookie = function (key, value, options) {

    // key and at least value given, set cookie...
    if (arguments.length > 1 && String(value) !== "[object Object]") {
        options = jQuery.extend({}, options);

        if (value === null || value === undefined) {
            options.expires = -1;
        }

        if (typeof options.expires === 'number') {
            var days = options.expires, t = options.expires = new Date();
            t.setDate(t.getDate() + days);
        }

        value = String(value);

        return (document.cookie = [
            encodeURIComponent(key), '=',
            options.raw ? value : encodeURIComponent(value),
            options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
            options.path ? '; path=' + options.path : '',
            options.domain ? '; domain=' + options.domain : '',
            options.secure ? '; secure' : ''
        ].join(''));
    }

    // key and possibly options given, get cookie...
    options = value || {};
    var result, decode = options.raw ? function (s) { return s; } : decodeURIComponent;
    return (result = new RegExp('(?:^|; )' + encodeURIComponent(key) + '=([^;]*)').exec(document.cookie)) ? decode(result[1]) : null;
};
//
var page_loaded = false;
//
jQuery(document).ready(function() {	
	//
	page_loaded = true;

	if(jQuery(document.body).attr('data-smoothscroll') == '1') {
		// smooth anchor scrolling
		    if(
        !(
            jQuery('#gkMainbody').find('.subpage').length && 
            jQuery('#gkMainbody').find('.subpage').hasClass('edit')
        ) && !(
            jQuery('#modules-form').length
        )
    ) {
        jQuery('a[href*="#"]').on('click', function (e) {
            e.preventDefault();
            if(
                this.hash !== '' && 
                this.hash.indexOf('carousel') === -1 &&
                this.hash.indexOf('advancedSearch') === -1
            ) {
                var target = jQuery(this.hash);

                if(this.hash !== '' && this.href.replace(this.hash, '') == window.location.href.replace(window.location.hash, '')) {    
                    if(target.length && this.hash !== '#') {
                        jQuery('html, body').stop().animate({
                            'scrollTop': target.offset().top
                        }, 1000, 'swing', function () {
                            if(this.hash !== '#') {
                                window.location.hash = target.selector;
                            }
                        });
                    } else if(this.hash !== '' && this.href.replace(this.hash, '') !== '') {
                        window.location.href = this.href;
                    }
                } else if(this.hash !== '' && this.href.replace(this.hash, '') !== '') {
                    window.location.href = this.href;
                }
            }
        });
    }
	}
	// style area
	if(jQuery('#gkStyleArea')){
		jQuery('#gkStyleArea').find('a').each(function(i,element){
			jQuery(element).click(function(e){
	            e.preventDefault();
	            e.stopPropagation();
				changeStyle(i+1);
			});
		});
	}
	
	// small fix for pagination in pagebreak
	if(jQuery('div.pager').length > 0) {
		if(jQuery('div.pager > ul li').first().find('a').length == 0)
		{
			jQuery('div.pager > ul li').first().html('<span>'+jQuery('div.pager > ul li').first().html()+'</span>');
		}
	}
	
	// font-size switcher
	if(jQuery('#gkTools') && jQuery('#gkMainbody')) {
		var current_fs = 100;
		
		jQuery('#gkMainbody').css('font-size', current_fs+"%");
		
		jQuery('#gkToolsInc').click(function(e){ 
			e.stopPropagation();
			e.preventDefault(); 
			if(current_fs < 150) {  
				jQuery('#gkMainbody').animate({ 'font-size': (current_fs + 10) + "%"}, 200); 
				current_fs += 10; 
			} 
		});
		jQuery('#gkToolsReset').click(function(e){ 
			e.stopPropagation();
			e.preventDefault(); 
			jQuery('#gkMainbody').animate({ 'font-size' : "100%"}, 200); 
			current_fs = 100; 
		});
		jQuery('#gkToolsDec').click(function(e){ 
			e.stopPropagation();
			e.preventDefault(); 
			if(current_fs > 70) { 
				jQuery('#gkMainbody').animate({ 'font-size': (current_fs - 10) + "%"}, 200); 
				current_fs -= 10; 
			} 
		});
	}
	// K2 font-size switcher fix
	if(jQuery('#fontIncrease') && jQuery('.itemIntroText')) {
		jQuery('#fontIncrease').click(function() {
			jQuery('.itemIntroText').attr('class', 'itemIntroText largerFontSize');
		});
		
		jQuery('#fontDecrease').click( function() {
			jQuery('.itemIntroText').attr('class', 'itemIntroText smallerFontSize');
		});
	}
	
	if(jQuery('#system-message-container a.close')){
		  jQuery('#system-message-container').find('a.close').each(function(i, element){
		  		jQuery('#system-message-container').css({'display' : 'block'});	
	           jQuery(element).click(function(e){
	           		e.preventDefault();
	           		e.stopPropagation();
	                jQuery(element).parents().eq(2).fadeOut();
	                (function() {
	                     jQuery(element).parents().eq(2).css({'display': 'none'});
	                }).delay(500);
	           });
	      });
	} 
	// change the login
	if(jQuery('a[title="login"]')) {
		jQuery('a[title="login"]').attr('id', 'btnLogin');
	}
	// login popup
	if(jQuery('#gkPopupLogin').length > 0 || jQuery('#gkPopupCart').length > 0) {
		var popup_overlay = jQuery('#gkPopupOverlay');
		popup_overlay.css({'display': 'none', 'opacity' : 0});
		popup_overlay.fadeOut();
		
		jQuery('#gkPopupLogin').css({'display': 'block', 'opacity': 0, 'height' : 0});
		var opened_popup = null;
		var popup_login = null;
		var popup_login_h = null;
		var popup_login_fx = null;
		
		if(jQuery('#gkPopupLogin')) {

			popup_login = jQuery('#gkPopupLogin');
			popup_login.css('display', 'block');
			popup_login_h = popup_login.find('.gkPopupWrap').outerHeight();
			 
			jQuery('#btnLogin').click( function(e) {
				e.preventDefault();
				e.stopPropagation();
				popup_overlay.css({'opacity' : 0.6});
				popup_overlay.fadeIn('slow');
				
				popup_login.animate({'opacity':1, 'height': popup_login_h},200, 'swing');
				opened_popup = 'login';
				
				(function() {
					if(jQuery('#modlgn-username')) {
						jQuery('#modlgn-username').focus();
					}
				}).delay(600);
			});
		}
		
		if(jQuery('#gkPopupCart').length > 0) {
			var btn = jQuery('#btnCart');
			popup_cart = jQuery('#gkPopupCart');
			
			popup_cart_h = popup_cart.find('.gkPopupWrap').outerHeight(); 
			var wait_for_results = true;
			var wait = false;
			
			jQuery(window).scroll(function() {
				var scroll = jQuery(window).scrollTop();
				var max = jQuery('#gkMainWrap').height();
				var final = 0;
				if(scroll > 70) {
					if(scroll < max - 122) {
						final = scroll - 50;
					} else {
						final = max - 172;
					}
				} else {
					final = 20;
				}
				btn.css('top', final + "px");
			});
			
			btn.click(function(e) {
		        e.preventDefault();
		        e.stopPropagation();
		        popup_overlay.css('height', jQuery('body').outerHeight());
		               
		        popup_overlay.css({'opacity' : 0.45});
		        popup_overlay.fadeIn('fast');
		        
		        opened_popup = 'cart';
		        
		        if(!wait) {
	                jQuery.ajax({
	                        url: $GK_URL + 'index.php?tmpl=cart&lang=' + jQuery('html').attr('lang').split('-')[0],
	                        cache: false,
	                        dataType: 'text',
	                        beforeSend: function() {
	                                btn.addClass('loading');
	                                wait = true;
	                        },
	                        complete: function() {
	                                var timer = (function() {
	                                        if(!wait_for_results) {
	                                                
	                                                
	                                                wait_for_results = true;
	                                                wait = false;
	                                                clearInterval(timer);
	                                                
	                                        }
	                                });
	                                popup_cart.css('top', btn.offset().top + 50 + "px");
	                        },
	                        success: function(data) {
	                                jQuery('#gkAjaxCart').html(data);
	                                popup_cart.css('display', 'block');
	                                popup_cart.css('display', 'block');
	                                				                                                      
	                                popup_cart.animate({'opacity':1, 'margin-top':0},200, 'swing');
	                                popup_cart.addClass('gk3Danim');
	                                btn.removeClass('loading');
	                                //popup_cart.css('opacity', 0).css('margin-top', '-50px');
	                                wait_for_results = false;
	                                wait = false;
	                        }
	                });
		        }
		});
			
			
		}
		
		
		popup_overlay.click( function() {
			if(opened_popup == 'login')	{
				popup_overlay.fadeOut('slow');
				popup_login.css({
					'opacity' : 0,
					'height' : 0
				});
			}
			if(opened_popup == 'cart') {
			    popup_overlay.fadeOut('medium');
			    popup_overlay.css('opacity', 0.01);
			    popup_cart.removeClass('gk3Danim');
			    setTimeout(function() {
			    	popup_cart.animate({
			    		'opacity' : 0
			    	},350, 'swing');
			    }, 100);
			}  
		});
	}
});

// Function to change styles
function changeStyle(style){
	var file1 = $GK_TMPL_URL+'/css/style'+style+'.css';
	var file2 = $GK_TMPL_URL+'/css/typography/typography.style'+style+'.css';
	var file3 = $GK_TMPL_URL+'/css/typography/typography.iconset.style'+style+'.css';
	jQuery('head').append('<link rel="stylesheet" href="'+file1+'" type="text/css" />');
	jQuery('head').append('<link rel="stylesheet" href="'+file2+'" type="text/css" />');
	jQuery('head').append('<link rel="stylesheet" href="'+file3+'" type="text/css" />');
	jQuery.cookie('gk_storebox_j30_style', style, { expires: 365, path: '/' });
}


jQuery(window).ready(function() {
	
	// hack modal boxes ;)
	setTimeout(function() {
		jQuery('a.modal').unbind();
	}, 2000);
	
	jQuery('a.modal').each(function(i,link) {
		// register start event
		var lasttouch = [];
		// here
		jQuery(link).bind('touchstart', function(e) {
			lasttouch = [link, new Date().getTime()];
		});
		// and then
		jQuery(link).bind('touchend', function(e) {
			// compare if the touch was short ;)
			if(lasttouch[0] == link && Math.abs(lasttouch[1] - new Date().getTime()) < 500) {
				window.location = jQuery(link).attr('href');
			}
		});
	});

	var products = jQuery('.browse-view .product');
	var categories = jQuery('.category-view .category');
	var f_products = jQuery('.featured-view .product');
	var l_products = jQuery('.latest-view .product');
	var t_products = jQuery('.topten-view .product'); 
	var r_products = jQuery('.recent-view .product');
	
	jQuery([products, categories, f_products, l_products, t_products, r_products]).each(function(i, p) {
		if(p.length > 0) {
			p.each(function(i, item) {
				item = jQuery(item);
				item.mouseenter(function() {
					item.addClass('active');
				});
				item.mouseleave(function() {
					item.removeClass('active');
				});
			}); 
		}
	});
});

jQuery(window).load(function() {
	if(jQuery('body').attr('data-mobile') === 'true') {
		setTimeout(function() {
			jQuery('.modal').off();
			jQuery('.ask-a-question').offsetHeight();
		}, 2000);
	}
});
