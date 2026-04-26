/*--------------------- Copyright (c) 2020 -----------------------
[Master Javascript]

Project: Documents
Version: 1.
------------------------------------------------------------------


------------------------------------------------------------------*/

(function ($) {
	"use strict";
	var documnets = {
		initialised: false,
		version: 1.0,
		mobile: false,
		init: function () {

			if(!this.initialised) {
				this.initialised = true;
			} else {
				return;
			}

			/*-------------- CV documnets Functions Calling ---------------------------------------------------
			------------------------------------------------------------------------------------------------*/
			// this.header_sticky();
			this.toggle_menu();
			this.page_scroll();
			this.window_scroll();
			this.custom_scrollbar();
		},
		
		/*-------------- CV documnets Functions Calling ---------------------------------------------------
		---------------------------------------------------------------------------------------------------*/
		// open menu click toggle
		toggle_menu: function() {
			if($('.toggle_btn').length > 0){
				$('.toggle_btn').on('click', function(){
					$('body').toggleClass('menu_open');
				});
			}
		},
		/*---------------------------------------------------------------------------------------------------*/
		
		//Header sticky js
		// header_sticky: function() {
		// 	const body = document.body;
		// 	const nav = document.querySelector(".doc_header_wrapper");
		// 	const scrollUp = "scroll-up";
		// 	const scrollDown = "scroll-down";
		// 	let lastScroll = 0;

		// 	window.addEventListener("scroll", () => {
		// 	const currentScroll = window.pageYOffset;
		// 	if (currentScroll == 0) {
		// 		body.classList.remove(scrollUp);
		// 		return;
		// 	}
			
		// 	if (currentScroll > lastScroll && !body.classList.contains(scrollDown)) {
		// 		//  //down
		// 		body.classList.remove(scrollUp);
		// 		body.classList.add(scrollDown);
		// 	} else if (currentScroll < lastScroll && body.classList.contains(scrollDown)) {
		// 		//  //up
		// 		body.classList.remove(scrollDown);
		// 		body.classList.add(scrollUp);
		// 	}
		// 	lastScroll = currentScroll;
		// 	});
		// },
		/*---------------------------------------------------------------------------------------------------*/
		//Single page scroll js
		page_scroll: function() {
			if($('.menu_inner > ul > li.menu_item').length > 0){	
				$('.menu_inner > ul > li.menu_item').on('click' , function(e){
					$('.menu_inner > ul > li').removeClass('active');
					$(this).addClass('active');
					var target = $('[data-scroll='+$(this).attr('data-number')+']');
					e.preventDefault();
					var targetHeight = target.offset().top-50;
					$('html, body').animate({
						scrollTop: targetHeight
					}, 1000);
				});
			}
		},
		/*---------------------------------------------------------------------------------------------------*/	
		//scroll active class js
		window_scroll: function() {
			if($('.menu_inner').length > 0){	
				$(window).scroll(function() {
					var windscroll = $(window).scrollTop();
					var target = $('.menu_inner > ul > li.menu_item');
					if (windscroll >= 0) {
						$('[data-scroll]').each(function(i) {
							if ($(this).position().top <= windscroll + 78) {
								target.removeClass('active');
								target.eq(i).addClass('active');
							}
						});
					}else{
						target.removeClass('active');
						$('.menu_inner > ul > li.menu_item:first').addClass('active');
					}
				});
			}
		},
		/*------------------------------------------------------------------*/ 
		/*---------------------------------------------------------------------------------------------------*/
		//start custom scroll bar
		custom_scrollbar: function() {
			if($('.custom_scroll').length > 0){
				$('.custom_scroll').mCustomScrollbar({
				moveDragger:true,
				scrollEasing:"easeOut"
				});
			}
		},
		/*---------------------------------------------------------------------------------------------------*/
	
	};
	documnets.init();
	

		
}(jQuery));	
