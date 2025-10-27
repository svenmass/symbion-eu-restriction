/**
 * Symbion EU Restriction - Bulk Edit JavaScript
 */

jQuery(document).ready(function($) {
	'use strict';

	// Quick Edit: Wert aus Inline-Daten laden
	var $inline_editor = inlineEditPost.edit;
	inlineEditPost.edit = function(id) {
		$inline_editor.apply(this, arguments);

		var post_id = 0;
		if (typeof(id) === 'object') {
			post_id = parseInt(this.getId(id));
		}

		if (post_id > 0) {
			var $row = $('#post-' + post_id);
			var $edit_row = $('#edit-' + post_id);
			
			// Wert aus der versteckten Spalte lesen wäre ideal,
			// aber da wir ein Icon haben, nutzen wir data-attribute
			// (wird in der Spalte gesetzt werden müssen)
		}
	};
});

