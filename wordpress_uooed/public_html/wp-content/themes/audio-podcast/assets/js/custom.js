function audio_podcast_menu_open_nav() {
	window.audio_podcast_responsiveMenu=true;
	jQuery(".sidenav").addClass('show');
}
function audio_podcast_menu_close_nav() {
	window.audio_podcast_responsiveMenu=false;
 	jQuery(".sidenav").removeClass('show');
}

jQuery(function($){
 	"use strict";
 	jQuery('.main-menu > ul').superfish({
		delay: 500,
		animation: {opacity:'show',height:'show'},  
		speed: 'fast'
 	});

	new WOW().init();

	var leftmenu_height = $( '.left-menu' ).height();
  if(leftmenu_height > 540){
    $( '.left-menu' ).css({  
	    "overflow-y": "scroll",
	    "height": "540px"
	  });
  }
});

jQuery(document).ready(function () {
	window.audio_podcast_currentfocus=null;
  	audio_podcast_checkfocusdElement();
	var audio_podcast_body = document.querySelector('body');
	audio_podcast_body.addEventListener('keyup', audio_podcast_check_tab_press);
	var audio_podcast_gotoHome = false;
	var audio_podcast_gotoClose = false;
	window.audio_podcast_responsiveMenu=false;
 	function audio_podcast_checkfocusdElement(){
	 	if(window.audio_podcast_currentfocus=document.activeElement.className){
		 	window.audio_podcast_currentfocus=document.activeElement.className;
	 	}
 	}
 	function audio_podcast_check_tab_press(e) {
		"use strict";
		// pick passed event or global event object if passed one is empty
		e = e || event;
		var activeElement;

		if(window.innerWidth < 999){
		if (e.keyCode == 9) {
			if(window.audio_podcast_responsiveMenu){
			if (!e.shiftKey) {
				if(audio_podcast_gotoHome) {
					jQuery( ".main-menu ul:first li:first a:first-child" ).focus();
				}
			}
			if (jQuery("a.closebtn.mobile-menu").is(":focus")) {
				audio_podcast_gotoHome = true;
			} else {
				audio_podcast_gotoHome = false;
			}

		}else{

			if(window.audio_podcast_currentfocus=="responsivetoggle"){
				jQuery( "" ).focus();
			}}}
		}
		if (e.shiftKey && e.keyCode == 9) {
		if(window.innerWidth < 999){
			if(window.audio_podcast_currentfocus=="header-search"){
				jQuery(".responsivetoggle").focus();
			}else{
				if(window.audio_podcast_responsiveMenu){
				if(audio_podcast_gotoClose){
					jQuery("a.closebtn.mobile-menu").focus();
				}
				if (jQuery( ".main-menu ul:first li:first a:first-child" ).is(":focus")) {
					audio_podcast_gotoClose = true;
				} else {
					audio_podcast_gotoClose = false;
				}
			
			}else{

			if(window.audio_podcast_responsiveMenu){
			}}}}
		}
	 	audio_podcast_checkfocusdElement();
	}
});

jQuery('document').ready(function($){
  $(window).scroll(function(){
	var sticky = $('.header-sticky'),
		scroll = $(window).scrollTop();

	if (scroll >= 100) sticky.addClass('header-fixed');
	else sticky.removeClass('header-fixed');
	});

	/*sticky sidebar*/
window.addEventListener('scroll', function() {
  var sticky = document.querySelector('.sidebar-sticky');
  if (!sticky) return;

  var scrollTop = window.scrollY || document.documentElement.scrollTop;
  var windowHeight = window.innerHeight;
  var documentHeight = document.documentElement.scrollHeight;

  var isBottom = scrollTop + windowHeight >= documentHeight-100;

  if (scrollTop >= 100 && !isBottom) {
    sticky.classList.add('sidebar-fixed');
  } else {
    sticky.classList.remove('sidebar-fixed');
  }
});

	/*sticky copyright*/
	window.addEventListener('scroll', function() {
	  var sticky = document.querySelector('.copyright-sticky');
	  if (!sticky) return;

	  var scrollTop = window.scrollY || document.documentElement.scrollTop;
	  var windowHeight = window.innerHeight;
	  var documentHeight = document.documentElement.scrollHeight;

	  var isBottom = scrollTop + windowHeight >= documentHeight-100;

	  if (scrollTop >= 100 && !isBottom) {
	    sticky.classList.add('copyright-fixed');
	  } else {
	    sticky.classList.remove('copyright-fixed');
	  }
	});
  setTimeout(function () {
		jQuery("#preloader").fadeOut("slow");
  },1000);
});

jQuery(document).ready(function () {
	jQuery(window).scroll(function () {
    if (jQuery(this).scrollTop() > 100) {
      jQuery('.scrollup i').fadeIn();
    } else {
      jQuery('.scrollup i').fadeOut();
    }
	});
	jQuery('.scrollup i').click(function () {
    jQuery("html, body").animate({
      scrollTop: 0
    }, 600);
    return false;
	});
});

function audio_podcast_leftmenu_open_nav() {
	window.audio_podcast_responsiveMenu=true;
	jQuery(".left-menu").addClass('show');
}
function audio_podcast_leftmenu_close_nav() {
	window.audio_podcast_responsiveMenu=false;
 	jQuery(".left-menu").removeClass('show');
}

jQuery(document).ready(function () {
	window.audio_podcast_currentfocus=null;
  	audio_podcast_checkfocusdElement();
	var audio_podcast_body = document.querySelector('body');
	audio_podcast_body.addEventListener('keyup', audio_podcast_check_tab_press);
	var audio_podcast_gotoHome = false;
	var audio_podcast_gotoClose = false;
	window.audio_podcast_responsiveMenu=false;
 	function audio_podcast_checkfocusdElement(){
	 	if(window.audio_podcast_currentfocus=document.activeElement.className){
		 	window.audio_podcast_currentfocus=document.activeElement.className;
	 	}
 	}
 	function audio_podcast_check_tab_press(e) {
		"use strict";
		// pick passed event or global event object if passed one is empty
		e = e || event;
		var activeElement;

		if(window.innerWidth < 999){
		if (e.keyCode == 9) {
			if(window.audio_podcast_responsiveMenu){
			if (!e.shiftKey) {
				if(audio_podcast_gotoHome) {
					jQuery( ".left-menu ul:first li:first a:first-child" ).focus();
				}
			}
			if (jQuery("a.closebtn.mobile-leftmenu").is(":focus")) {
				audio_podcast_gotoHome = true;
			} else {
				audio_podcast_gotoHome = false;
			}

		}else{

			if(window.audio_podcast_currentfocus=="responsive-lefttoggle"){
				jQuery( "" ).focus();
			}}}
		}
		if (e.shiftKey && e.keyCode == 9) {
		if(window.innerWidth < 999){
			if(window.audio_podcast_currentfocus=="header-search"){
				jQuery(".responsive-lefttoggle").focus();
			}else{
				if(window.audio_podcast_responsiveMenu){
				if(audio_podcast_gotoClose){
					jQuery("a.closebtn.mobile-leftmenu").focus();
				}
				if (jQuery( ".left-menu ul:first li:first a:first-child" ).is(":focus")) {
					audio_podcast_gotoClose = true;
				} else {
					audio_podcast_gotoClose = false;
				}
			
			}else{

			if(window.audio_podcast_responsiveMenu){
			}}}}
		}
	 	audio_podcast_checkfocusdElement();
	}
});

/* Progress Bar */
document.addEventListener("DOMContentLoaded", function () {
    const audio_podcast_progressBar =
        document.getElementById("audio_podcast_elemento_progress_bar");
    if (!audio_podcast_progressBar) return;
    window.addEventListener("scroll", function () {
        const audio_podcast_scrollTop =
            document.documentElement.scrollTop || document.body.scrollTop;
        const audio_podcast_height =
            document.documentElement.scrollHeight -
            document.documentElement.clientHeight;
        const audio_podcast_scrolled =
            (audio_podcast_scrollTop / audio_podcast_height) * 100;
        audio_podcast_progressBar.style.width =
            audio_podcast_scrolled + "%";
    });
});