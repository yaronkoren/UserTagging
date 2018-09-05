
$('textarea').keypress(function( event ) {
	var self = $(this);
	if ( event.which == 64 ) {
		// '@' pressed.

		var apiURL = mw.util.wikiScript( 'api' ) +
			'?format=json&action=usertaggingautocomplete';

		// Set pixel position for autocomplete dropdown.
		var cursorXAndY = self.textareaHelper('caretPos');
		var cursorX = cursorXAndY.left;
		var cursorY = cursorXAndY.top;
		var posString = "left+" + cursorX + "px top+" + ( cursorY + 20 ) + "px";

		$('textarea').autocomplete({
			source: function(req, responseFn) {
				var origTerm = req.term;
				var cursorPos = self.prop('selectionStart');
				var lastAtSign = origTerm.lastIndexOf('@', cursorPos);
				if ( cursorPos - lastAtSign == 1 ) {
					// Nothing yet after the '@'.
					return;
				}
				var realTerm = origTerm.substring( lastAtSign + 1, cursorPos );
				// Make sure it's still a valid username.
				var invalidChars = mw.config.get('wgInvalidUsernameCharacters');
				for ( var i = 0; i < invalidChars.length; i++ ) {
					var invalidChar = invalidChars.charAt(i);
					if ( realTerm.indexOf( invalidChar ) > -1 ) {
						self.autocomplete('destroy');
						self.removeClass('ui-autocomplete-loading');
						return;
					}
				}
				self.data('ui-autocomplete').term = realTerm;
				$.get( apiURL, {
					substr: realTerm
				}, function( data ) {
					var matchingUsernames = $.map(data.usertaggingautocomplete, function(item) {
						return { label: item.username, value: item.wikitext };
						//return { value: item.username };
					});
					self.removeClass('ui-autocomplete-loading');
					responseFn(matchingUsernames);
					if ( matchingUsernames.length == 0 ) {
						self.autocomplete('destroy');
					}
				});
			},
			focus: function( event, ui ) {
				// Disable any action if user scrolls through values.
				return false;
			},
			select: function( event, ui ) {
				var fullText = self.val();
				var cursorPos = self.prop('selectionStart');
				var lastAtSign = fullText.lastIndexOf('@', cursorPos);
				var userLink = ui.item.value;
				this.value = fullText.substring( 0, lastAtSign + 1 ) +
					userLink + fullText.substring( cursorPos );
				self.autocomplete('destroy');
				self.prop('selectionStart', lastAtSign + userLink.length + 1);
				self.prop('selectionEnd', lastAtSign + userLink.length + 1);
				return false;
			},
			position: { my : "left top", at: posString }
		});//.focusout( function() { alert('focusout'); });
	}
});

// Override some functions for jQuery UI Autocomplete.
$.ui.autocomplete.prototype._renderItem = function( ul, item) {
	// HTML-encode the value's label.
	var itemLabel = $('<div/>').text(item.label).html();
	var t = '<strong>' + itemLabel.substr(0, this.term.length) + '</strong>' +
		itemLabel.substr(this.term.length);
	return $( "<li></li>" )
		.data( "item.autocomplete", item )
		.append( " <a>" + t + "</a>" )
		.appendTo( ul );
};

$.ui.autocomplete.prototype._move = function( direction, event ) {
	if ( !this.menu.element.is( ":visible" ) ) {
		this.search( null, event );
		return;
	}
	if ( this.menu.isFirstItem() && /^previous/.test( direction ) ||
			this.menu.isLastItem() && /^next/.test( direction ) ) {
		// We override so that we can comment this one line out.
		//this._value( this.term );
		this.menu.blur();
		return;
	}
	this.menu[ direction ]( event );
};
