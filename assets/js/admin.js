/**
 * Symbion EU Restriction - Admin JavaScript
 */

jQuery(document).ready(function($) {
	'use strict';

	// Tab Switching
	$('.symbion-eu-tab').on('click', function() {
		var tab = $(this).data('tab');
		
		// Update active tab
		$('.symbion-eu-tab').removeClass('active');
		$(this).addClass('active');
		
		// Show corresponding content
		$('.symbion-eu-tab-content').removeClass('active');
		$('[data-tab-content="' + tab + '"]').addClass('active');
	});

	// Cache leeren
	$('#symbion-eu-clear-cache').on('click', function(e) {
		e.preventDefault();
		
		var $button = $(this);
		var originalText = $button.text();
		
		$button.prop('disabled', true).text('Leere Cache...');
		
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'symbion_eu_clear_cache',
				nonce: $('#_wpnonce').val()
			},
			success: function(response) {
				$button.text('✓ Cache geleert!');
				setTimeout(function() {
					$button.prop('disabled', false).text(originalText);
				}, 2000);
			},
			error: function() {
				$button.text('✗ Fehler');
				setTimeout(function() {
					$button.prop('disabled', false).text(originalText);
				}, 2000);
			}
		});
	});

	// Smooth scroll to top when switching tabs
	$('.symbion-eu-tab').on('click', function() {
		$('html, body').animate({
			scrollTop: $('.symbion-eu-tabs').offset().top - 32
		}, 300);
	});
});

