/**
 * Symbion EU Restriction - Admin Bar JavaScript
 */

jQuery(document).ready(function($) {
	'use strict';

	// Debug beim Laden
	console.log('Symbion EU: Admin Bar geladen');
	console.log('Symbion EU: Suche nach .symbion-eu-test-country Elementen...');
	console.log('Symbion EU: Gefundene Elemente:', $('.symbion-eu-test-country').length);

	// Funktion zum Testmodus setzen
	function setTestCountry(country) {
		console.log('Symbion EU: setTestCountry aufgerufen mit:', country);
		
		// AJAX request
		$.ajax({
			url: symbionEUAdminBar.ajaxUrl,
			type: 'POST',
			data: {
				action: 'symbion_eu_set_test_country',
				nonce: symbionEUAdminBar.nonce,
				country: country
			},
			success: function(response) {
				console.log('Symbion EU: AJAX Response:', response);
				if (response.success) {
					console.log('Symbion EU: Erfolg! Seite wird neu geladen...');
					setTimeout(function() {
						location.reload();
					}, 200);
				} else {
					console.error('Symbion EU: Fehler bei AJAX:', response);
					alert('Fehler: ' + (response.data ? response.data.message : 'Unbekannter Fehler'));
				}
			},
			error: function(xhr, status, error) {
				console.error('Symbion EU: AJAX-Fehler:', status, error, xhr.responseText);
				alert('Verbindungsfehler. Bitte versuchen Sie es erneut.');
			}
		});
	}

	// Event-Handler mit höchster Priorität
	$(document).on('click', '.symbion-eu-test-country', function(e) {
		e.preventDefault();
		e.stopPropagation();
		e.stopImmediatePropagation();
		
		console.log('Symbion EU: Click-Event ausgelöst (delegiert)!');
		
		// Country-Code aus der ID extrahieren
		var itemId = $(this).attr('id');
		var country = '';
		
		if (itemId) {
			var parts = itemId.split('-symbion-eu-test-');
			if (parts.length > 1) {
				country = parts[1];
				if (country === 'none') {
					country = '';
				}
			}
		}
		
		console.log('Symbion EU: Extrahierter Country-Code:', country);
		setTestCountry(country);
		
		return false;
	});

	// Direkter Handler als Backup (nach kurzem Delay wenn Elemente geladen sind)
	setTimeout(function() {
		console.log('Symbion EU: Binde direkte Handler...');
		$('.symbion-eu-test-country').each(function(index) {
			var $item = $(this);
			
			// Country-Code aus der ID extrahieren: "wp-admin-bar-symbion-eu-test-CH" -> "CH"
			var itemId = $item.attr('id');
			var country = '';
			
			if (itemId) {
				var parts = itemId.split('-symbion-eu-test-');
				if (parts.length > 1) {
					country = parts[1];
					if (country === 'none') {
						country = ''; // "none" = kein Testmodus
					}
				}
			}
			
			console.log('Symbion EU: Handler ' + index + ' gebunden für ID:', itemId, 'Country:', country);
			
			// Entferne alte Handler
			$item.off('click');
			
			// Neuer direkter Handler
			$item.on('click', function(e) {
				e.preventDefault();
				e.stopPropagation();
				e.stopImmediatePropagation();
				
				console.log('Symbion EU: Direkter Click-Handler ausgelöst für:', country);
				setTestCountry(country);
				
				return false;
			});
		});
		
		console.log('Symbion EU: Alle Handler gebunden');
	}, 200);

	// Admin Bar Item prüfen
	setTimeout(function() {
		console.log('Symbion EU: Final Check - Elemente:', $('.symbion-eu-test-country').length);
	}, 500);
});

