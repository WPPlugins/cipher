<?php 
/**
 * Plugin Name: Cipher
 * Plugin URI: http://cavalieri.io/cipher/
 * Description: Cipher is all about publishing code.
 * Version: 1.1
 * Author: Luigi Cavalieri
 * Author URI: http://cavalieri.io/
 * License: GPL v3.0
 * License URI: license.txt
 * 
 * 
 * Copyright (c) 2013 Luigi Cavalieri, http://cavalieri.io
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * ------------------------------------------------------------------------ */


/**
 * @package Cipher
 * @version 1.1
 *
 * @since 1.0
 */
final class Cipher {
	/**
	 * @since 1.0
	 */
	const VERSION = '1.1';
	
	/**
	 * @since 1.1
	 */
	const MIN_WP_VERSION = '3.4';
	
	/**
	 * @since 1.0
	 * @var object
	 */
	private static $plugin;
	
	/**
	 * @since 1.1
	 * @var string
	 */
	private $dirPath;
	
	/**
	 * @since 1.0
	 *
	 * @param string $loader_path
	 * @return void|int
	 */
	public static function launch( $loader_path ) {
		if ( self::$plugin )
			wp_die( __( 'Cheatin&#8217; huh?' ) );
			
		self::$plugin = new self( $loader_path );
		
		if (! self::$plugin->verifyEnvironmentCompatibility() )
			return -1;
			
		global $pagenow;
		
		switch ( $pagenow ) {
			case 'wp-comments-post.php':
				include( self::$plugin->dirPath . '/includes/comment-processor.class.php' );

				add_filter( 'preprocess_comment', array( new CipherCommentProcessor(), 'run' ) );
				break;
			case 'post.php':
			case 'post-new.php':
			case 'comment.php':
			case 'edit-comments.php':
				include( self::$plugin->dirPath . '/includes/admin-delegate.class.php' );
				
				$adminDelegate = new CipherAdminDelegate( self::$plugin );
				
				add_action( 'admin_print_styles', array( $adminDelegate, 'wpWillPrintStyles' ) );
				add_action( 'admin_footer', array( $adminDelegate, 'wpWillRendedFooter' ) );
				add_action( 'admin_print_footer_scripts', array( $adminDelegate, 'wpWillPrintScriptsInFooter' ) );
				add_action( 'admin_enqueue_scripts', array( $adminDelegate, 'wpWillLoadScripts' ) );
				//add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
				break;
		}
	}
	
	/**
	 * @since 1.0
	 * @param string $loader_path
	 */
	private function __construct( $loader_path ) {
		$this->file    = $loader_path;
		$this->dirPath = dirname( $loader_path );
		$this->url     = plugins_url( '/', $loader_path );
	}
	
	/**
	 * @since 1.1
	 * @return bool
	 */
	private function verifyEnvironmentCompatibility() {
		if ( version_compare( phpversion(), '5.3', '<' ) ) {
			$this->registerFatalError(sprintf(
				__( 'Your setup is quite outdated! %s needs at least PHP %s to spread its bits. '
				  . 'Consider upgrading your PHP installation to a more recent and secure version.', 'cipher' ), 
				'Cipher ' . self::VERSION,
				'5.3'
			));
		}
		elseif ( version_compare( get_bloginfo( 'version' ), self::MIN_WP_VERSION, '<' ) ) {
			$this->registerFatalError(sprintf(
				__( 'To run %s you need at least WordPress %s. Please, update your WordPress installation to '
				  . 'the %slatest version%s available.', 'cipher' ), 
				'Cipher ' . self::VERSION, self::MIN_WP_VERSION,
				'<a href="http://wordpress.org/download/" target="_blank">',
				'</a>'
			));
		}
		else { return true; }
		
		return false;
	}
	
	/**
	 * @since 1.1
	 *
	 * @param string $msg
	 * @return void|bool
	 */
	private function registerFatalError( $msg ) {
		if (! is_admin() )
			return false;
		
		add_action( 'admin_notices', array( $this, 'triggerFatalError' ) );
		
		if (! get_transient( 'cipher_fatal_error' ) )
			set_transient( 'cipher_fatal_error' , $msg, 30 );
	}
	
	/**
	 * @since 1.1
	 */
	public function triggerFatalError() {
		$allowed_tags = array(
			'a'  => array( 'href' => true, 'target' => true ),
			'em' => array()
		);
		
		if ( $msg = get_transient( 'cipher_fatal_error' ) ) {
			delete_transient( 'cipher_fatal_error' );
			
			echo '<div class="error"><p>' . wp_kses( $msg,  $allowed_tags ) . '</p></div>';
			
			// Hack: it forces WordPress to not show the "Plugin Activated" message 
			// if the fatal error is triggered during activation.
			unset( $_GET['activate'] );
		}
		
		deactivate_plugins( $this->file, false, is_network_admin() );
	}
	
	/**
	 * @since 1.0
	 */
	public function loadTextdomain() {
		load_plugin_textdomain( 'cipher', false, $this->dirPath . '/languages/' );
	}
	
	/**
	 * @since 1.1
	 *
	 * @param string $relative_path
	 * @return string
	 */
	public function url( $relative_path = '' ) { return $this->url . $relative_path; }
}



if ( defined( 'ABSPATH' ) ) Cipher::launch( __FILE__ );
?>