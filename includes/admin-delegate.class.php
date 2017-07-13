<?php
/**
 * @package Cipher
 * @copyright Copyright (c) 2013 Luigi Cavalieri
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.1
 */
final class CipherAdminDelegate {
	/**
	 * @since 1.1
	 * @var object
	 */
	private $plugin;
	
	/**
	 * @since 1.1
	 * @param object $plugin
	 */
	public function __construct( $plugin ) { $this->plugin = $plugin; }
	
	/**
	 * @since 1.1
	 */
	public function wpWillPrintStyles() {
		echo '<style>';
		echo str_replace( array( "\n", "\t" ), '',
<<<CSS
#cipher {
	display: none;
	height: 100%;
	padding: 5px 10px;
	}
	#cipher p.howto { margin: 10px 0 8px; }
	#cipher label {
		font-size: 11px;
		margin-right: 8px;
	}
	#cipher div.submitbox {
		height: 29px;
		margin-top: 20px;
		overflow: hidden;
		}
	
#cipher-code-area {
	border: #DFDFDF 1px solid;
	border-radius: 3px;
	font-family: monospace;
	font-size: 13px;
	height: 77.6%;
	outline: none;
	padding: 4px 6px;
	resize: none;
	white-space: pre;
	word-wrap: normal;
	width: 99.6%;
}
#cipher-code-area:focus { border-color: #BBB; }

#cipher-cancel {
	font-size: 11px;
	line-height: 25px;
	}

#cipher-submit { float: right; }
CSS
		);

		echo "</style>\n";
	}
	
	/**
	 * Renders the main view of the auxiliary editor.
	 *
	 * @since 1.1
	 */
	public function wpWillRendedFooter() {
		echo '<form id="cipher"><p class="howto">', __( 'Enter/Edit the code snippet', 'cipher' ), '</p>',
		     '<textarea id="cipher-code-area" cols="50" rows="20"></textarea>',
		     '<label><input type="checkbox" checked="checked" id="cipher-wrap-checkbox" value="1"> ',
		     __( 'Enclose snippet in: ', 'cipher' ), '</label>',
		     '<label><input type="radio" checked="checked" id="cipher-pre-wrap" name="wrapper"> <code>pre+code</code></label> ',
		     '<label><input type="radio" id="cipher-code-wrap" name="wrapper"> <code>code</code></label></label>',
		     '<div class="submitbox"><a class="submitdelete" id="cipher-cancel" href="#">' . __( 'Cancel', 'cipher' ) . '</a>',
		     '<input type="submit" id="cipher-submit" class="button-primary"></div></form>';
	}
	
	/**
	 * Echos the initialization script.
	 *
	 * @since 1.1
	 */
	public function wpWillPrintScriptsInFooter() {
		echo '<script>jQuery(document).ready(Cipher.init({',
		     'qTitle:"', __( 'Insert/Edit a code snippet', 'cipher' ), '",',
		     'dialogTitle:"', __( 'Insert/Edit Snippet', 'cipher' ), '",',
		     'submitTitles:["', __( 'Insert Snippet', 'cipher' ), '", "',
		     __( 'Update Snippet', 'cipher' ), '"]', '}));</script>';
	}
	
	/**
	 * Enqueues the js module.
	 *
	 * @since 1.1
	 */
	public function wpWillLoadScripts() {
		$suffix =  ( defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ) ? '' : '.min';
		
		wp_enqueue_script( 'cipher', $this->plugin->url( 'js/cipher' . $suffix . '.js' ), null, Cipher::VERSION );
	}	
}
?>