/**
 * Symbion EU Restriction - Frontend JavaScript
 */

(function() {
	'use strict';

	// Zusätzlicher JavaScript-Fallback für .nur-eu Klasse
	// (CSS sollte bereits funktionieren, dies ist nur ein Backup)
	
	if (typeof symbionEU !== 'undefined' && symbionEU.isNonEU) {
		// Alle Elemente mit .nur-eu verstecken
		document.addEventListener('DOMContentLoaded', function() {
			var nurEuElements = document.querySelectorAll('.nur-eu');
			nurEuElements.forEach(function(element) {
				element.style.display = 'none';
			});
		});
	}

	// Debug-Modus: Log im Testmodus
	if (typeof symbionEU !== 'undefined' && symbionEU.testMode) {
		console.log('🧪 Symbion EU Restriction Testmodus aktiv');
		console.log('Land:', symbionEU.testCountry);
		console.log('Ist EU:', symbionEU.isEU);
	}
})();

