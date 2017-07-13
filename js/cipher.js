/* Copyright (c) 2013 Luigi Cavalieri, http://cavalieri.io - GPL v3.0 */

 
var Cipher = (function($) {
	var _self, _config, _encRegex;
	var _encSelection = true;
	var _dialog = { isOpened: false }, _editor = {};
	var _tags   = {
		code: { open: '<code>',      close: '</code>',       isOpened: false },
		pre:  { open: '<pre><code>', close: '</code></pre>', isOpened: false }
	};
	var _decDictionary = { 'quot': '"' };
	var _encDictionary = { '<': 'lt', '>': 'gt', '&': 'amp' };
	
	/**
	 * @param {object} ed
	 */
	var _editorInit = function _editorInit( ed ) {
		_editor = ed;
		
		// Internet Explorer
		if ( document.selection ) {
			_editor.focus();
			_editor.range = document.selection.createRange();
			_editor.sel = _editor.range.text;
		}
		// FF, WebKit, Opera
		else { _editor.sel = ed.value.substring( ed.selectionStart, ed.selectionEnd ); }
		
		_editor.selActive = ( _editor.sel != '' );
	};
	
	/**
	 * @param {string} snippet
	 * @param {bool} wrap
	 * @param {string} wrapper
	 * @return {string}
	 */
	var _prepareSnippet = function _prepareSnippet( snippet, wrap, wrapper ) {
		if ( _dialog.isOpened || _encSelection )
			snippet = snippet.replace( _encRegex, function(char){ return '&' + _encDictionary[char] + ';'; } );
		
		if ( wrap )
			snippet = _tags[wrapper].open + snippet + _tags[wrapper].close;
			
		return snippet;
	};
	
	/**
	 * @param {object} target
	 * @param {string} content
	 */
	var _insertContent = function _insertContent( target, content ) {
		if ( target.range ) {
			target.focus();
			
			target.range.text = content;
			
			target.range.moveToBookmark( target.range.getBookmark() );
			target.range.select();
		}
		else {
			var scroll_top = target.scrollTop;
			var cursor_pos = target.selectionStart + content.length;
			
			target.value = target.value.substring(0, target.selectionStart) + content + target.value.substring(target.selectionEnd);
			
			target.selectionStart = target.selectionEnd = cursor_pos;
			target.scrollTop = scroll_top;
		}
	};
	
	return {
		/**
		 * @param {object} config
		 */
		init: function( config ) {
			var char_list = '';
			
			_self             = this;
			_dialog.view	  = $('#cipher');
			_dialog.textarea  = document.getElementById('cipher-code-area');
			_dialog.submit	  = document.getElementById('cipher-submit');
			_dialog.wrapCheck = document.getElementById('cipher-wrap-checkbox');
			_dialog.preRadio  = document.getElementById('cipher-pre-wrap');
			_dialog.codeRadio = document.getElementById('cipher-code-wrap');
			
			$( document ).bind( 'keydown keyup', _self.toggleEnconding );
			$( '#cipher-cancel' ).bind( 'click', _self.close );
			$( _dialog.textarea ).bind( 'keydown', _self.keyDown );
			
			_dialog.view.bind( 'submit', _self.onSubmit );
			_dialog.view.bind( 'keyup', _self.keyUp );
			_dialog.view.bind( 'wpdialogbeforeopen', _self.beforeOpen );
			_dialog.view.bind( 'wpdialogclose', _self.onClose );
			
			// Hack: removes the "code" quicktag.
			edButtons.splice( 110, 1 );
			
			QTags.addButton( 'pre', 'pre+code', _self.tag, '', '', '', 109 );
			QTags.addButton( 'code', 'code', _self.tag, '', 'c', '', 110 );
			QTags.addButton( 'snippet', 'snippet', _self.open, '', '', config.qTitle, 111 );
			
			// If we are in a comment related page, we update the encoding dictionary 
			// with a few additional characters that will prevent WordPress from hyperlinking URI(s)
			// and e-mail addresses in code snippets.
			if ( pagenow && ( pagenow == 'edit-comments' || pagenow == 'comment' ) ) {
				_encDictionary['.']   = '#46';
				_encDictionary['/']   = '#47';
				_encDictionary['@']   = '#64';
				_decDictionary['#39'] = "'";
			}
			
			// Dinamically init the decoding dictionary and the encoding regex
			for ( var char in _encDictionary ) {
				char_list += char;
				_decDictionary[ _encDictionary[char] ] = char
			}

			_config   = config;
			_encRegex = new RegExp( '[' + char_list + ']', 'g' );
		},
		
		/**
		 * @param {object} button
		 * @param {object} target
		 */
		open: function( button, target ) {
			_editorInit( target );
			
			if (! _dialog.view.data('wpdialog') ) {
				_dialog.view.wpdialog({
					title: _config.dialogTitle,
					width: 600,
					height: 'auto',
					modal: true,
					dialogClass: 'wp-dialog',
					zIndex: 300000
				});
			}
			
			_dialog.view.wpdialog('open');
		},
		
		beforeOpen: function() {
			_dialog.isOpened = true;
			_dialog.submit.value = _config.submitTitles[ Number(_editor.selActive) ];
			
			if (! _editor.selActive )
				return ( _dialog.wrapCheck.checked = _dialog.preRadio.checked = true );
			
			// We unwrap selected text from <pre><code> tags and we setup the state of Dialog Controls
			_dialog.wrapCheck.checked = false;
			
			var sel = _editor.sel.replace( /^(<pre[^>]*>)?<code[^>]*>([\s\S]*)<\/code>(<\/pre>)?$/, function( match, pre_open, snippet, pre_close ){
				if ( ( pre_open && !pre_close ) || ( !pre_open && pre_close ) )
					return match;
				
				_dialog.wrapCheck.checked = true;
				
				if ( pre_open )
					_dialog.preRadio.checked = true;
				else
					_dialog.codeRadio.checked = true;
				
				return snippet;
			});
			
			// Then, we decode html entities and we initialise the textarea
			_dialog.textarea.value = sel.replace(/&[0-9a-z#]{2,4};/g, function( entity ){
				entity = entity.replace( '0', '' );
				
				var char = _decDictionary[ entity.substr( 1, entity.length - 2 ) ];
				
				return ( char ? char : entity );
			});
		},
		
		/**
		 * @return {bool}
		 */
		close: function() {
			_dialog.view.wpdialog('close');
			
			return false;
		},
		
		onClose: function() {
			_dialog.isOpened = false;
			_dialog.textarea.value = '';
			
			_editor.focus();
		},
		
		/**
		 * @return {bool}
		 */
		onSubmit: function() {
		 	if ( _dialog.textarea.value ) {
		 		var wrapper = _dialog.preRadio.checked ? 'pre' : 'code';
		 		
				_insertContent( _editor, _prepareSnippet( _dialog.textarea.value, _dialog.wrapCheck.checked, wrapper ) );
				
				return _self.close();
			}
			
			_dialog.textarea.focus();
			
			return false;
		},
		
		/**
		 * @param {object} event
		 * @return {bool}
		 */
		keyDown: function ( event ) {
			if ( event.which != $.ui.keyCode.TAB )
				return true;
			
			if ( document.selection ) {
				_dialog.textarea.focus();
				_dialog.textarea.range = document.selection.createRange();
			}
			
			_insertContent( _dialog.textarea, "\t" );
			
			return false;
		},
		
		/**
		 * @param {object} event
		 * @return {bool}
		 */
		keyUp: function( event ) {
			if ( event.which != $.ui.keyCode.ESCAPE )
				return true;
				
			event.stopImmediatePropagation();
			
			return _self.close();
		},
		
		/**
		 * @param {object} event
		 * @return {bool} Always true
		 */
		toggleEnconding: function( event ) {
			// keyCode 18 = Alt key
			if ( event.which == 18 )
				_encSelection = !_encSelection;
				
			return true;
		},
		
		/**
		 * @param {object} button
		 * @param {object} target
		 * @param {object} qt
		 */
		tag: function( button, target, qt ) {
			var snippet, is_open;
			var btn_id = button.id.substr( qt.name.length + 1 );
			
			_editorInit( target );
			
			if ( _editor.selActive ) {
				snippet = _prepareSnippet( _editor.sel, true, btn_id );
			}
			else {
				if ( is_open = _tags[btn_id].isOpened ) {
					snippet = _tags[btn_id].close;
					button.value = button.value.substring(1);
				}
				else {
					snippet = _tags[btn_id].open;
					button.value = '/' + button.value;
				}
				
				_tags[btn_id].isOpened = !is_open;
			}
			
			_insertContent( _editor, snippet );
		}
	};
})(jQuery);