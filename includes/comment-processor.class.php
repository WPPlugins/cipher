<?php
/**
 * @package Cipher
 * @copyright Copyright (c) 2013 Luigi Cavalieri
 * @license http://opensource.org/licenses/GPL-3.0 GPL v3.0
 *
 * @since 1.1
 */
final class CipherCommentProcessor {
	/**
	 * @since 1.1
	 * @var string
	 */
	private $comment;
	
	/**
	 * The tag that is currently being parsed: opening/closing <code> or backtick
	 *
	 * @since 1.1
	 * @var array
	 */
	private $tag;
	
	/**
	 * The absolute position, in the unprocessed comment, where a 
	 * code block starts. The first element is the position where the whole
	 * block starts (code + wrapping tags), the second element specifies where
	 * the content of the code block (block - wrapping tags) starts.
	 *
	 * @see replaceCodeBlock()
	 * @since 1.1
	 *
	 * @var array
	 */
	private $anchorPoint;
	
	/**
	 * Offset applied to the anchor point: each time a code block is
	 * replaced the length of the comment increases.
	 *
	 * @since 1.1
	 * @var int
	 */
	private $offset = 0;
	
	/**
	 * @since 1.1
	 *
	 * @param array $data Comment data
	 * @return array
	 */
	public function &run( $data ) {
		$this->comment = $data['comment_content'];
		
		if ( $this->parseBlocks() ) {	
			// Try to retrieve commenter capabilities
			$user = null;
			
			if ( isset( $data['user_ID'] ) )
				$user = get_userdata( (int) $data['user_ID'] );
			
			elseif ( isset( $data['user_id'] ) )
				$user = get_userdata( (int) $data['user_id'] );
			
			// If the commenter has not editing priviledges, we remove all the <pre> tags 
			// ( except the ones wrapping code blocks )
			if ( !( $user && $user->has_cap( 'moderate_comments' ) ) ) {
				// Hack to enable <pre> tags in comments.
				global $allowedtags;
				$allowedtags['pre'] = array();

				$this->comment = preg_replace( '#<pre[^>]*>(?!<code[^>]*>)|(?<!</code>)</pre>#i', '', $this->comment );
			}
		}
		
		$data['comment_content'] = $this->comment;
		
		return $data;
	}
	
	/**
	 * @since 1.1
	 * @return bool
	 */
	private function parseBlocks() {
		$matches = array();
		
		if (! preg_match_all( '#<code[^>]*>|</code>|`#i', $this->comment, $matches, PREG_OFFSET_CAPTURE ) )
			return false;
		
		// Parsing & Encoding
		$open_tag_count    = 0;
		$last_open_tag_pos = -1;
		$last_close_tag    = array();
		$tag_is_open	   = $backtick_is_open = false;
		
		foreach ( $matches[0] as $this->tag ) {
			switch ( $this->tag[0] ) {
				case '`':
					if ( $tag_is_open ) break;
					
					if ( $backtick_is_open )
						$this->replaceCodeBlock();
					else
						$this->setAnchorPoint();
					
					$backtick_is_open = !$backtick_is_open;
					break;
					
				case '</code>':
				case '</CODE>':
					if ( $open_tag_count == 0 ) break; 
						
					$last_close_tag = $this->tag;
					
					if ( --$open_tag_count == 0 ) {
						$tag_is_open = false;
						$this->replaceCodeBlock();
					}
					break;
					
				default:
					if ( $backtick_is_open ) break;
				
					$last_open_tag_pos = $this->tag[1];
					
					if ( $open_tag_count++ > 0 ) break;
					
					$tag_is_open = true;
					$this->setAnchorPoint();
			}
		}
		
		// If no block has been parsed, no need to go any further.
		if (! $this->anchorPoint )
			return false;
		
		// Malformed markups?
		if ( $tag_is_open && $last_close_tag && ( $last_open_tag_pos < $last_close_tag[1] ) ) {
			$this->tag = $last_close_tag;
			$this->replaceCodeBlock();
			
			// Processes code blocks eventually left outside the last block.
			$this->comment = preg_replace_callback( '/`(.+?)`/s', array( $this, 'prepareCodeBlock' ), $this->comment );
		}
		// No closing tag found, so, we process what is left of the comment.
		elseif ( $tag_is_open || $backtick_is_open ) {
			$this->tag = array( '', strlen( $this->comment ) );
			$this->replaceCodeBlock();
		}
		
		return true;
	}
	
	/**
	 * Setter method, @see $anchorPoint
	 *
	 * @since 1.1
	 */
	private function setAnchorPoint() {
		$this->anchorPoint = array(
			'pos'	   => $this->tag[1],
			'body_pos' => $this->tag[1] + strlen( $this->tag[0] )
		);
	}
	
	/**
	 * Replaces a code block with its processed version.
	 *
	 * @since 1.1
	 */
	private function replaceCodeBlock() {
		$length		 = $this->tag[1] - $this->anchorPoint['pos'] + strlen( $this->tag[0] );
		$code_length = $this->tag[1] - $this->anchorPoint['body_pos'];
		
		$code = substr( $this->comment, $this->anchorPoint['body_pos'] + $this->offset, $code_length );
		$code = $this->prepareCodeBlock( $code, $code_length );
		
		$this->comment = substr_replace( $this->comment, $code, $this->anchorPoint['pos'] + $this->offset, $length );
		$this->offset += strlen( $code ) - $length;
	}
	
	/**
	 * @since 1.1
	 *
	 * @param string|array $string
	 * @param int $length
	 * @return string
	 */
	private function prepareCodeBlock( $string, $length = 0 ) {
		static $charset;

		if (! $charset )
			$charset = get_bloginfo( 'charset' );

		if ( is_array( $string ) ) {
			$string = $string[1];
			$length = strlen( $string[1] );
		}
			
		$string = htmlspecialchars( $string, ENT_NOQUOTES, $charset );
		
		// dot(s), slashes and @ are replaced to prevent the WordPress 
		// function 'make_clickable()' from hyperlinking URI(s) and e-mail adresses.
		$string = str_replace(
			array( '`', '.', '/', '@' ),
			array( '&#96;', '&#46;', '&#47;', '&#64;' ),
			$string
		);
		
		// Marking up
		$string = '<code>' . trim( $string ) . '</code>';
		
		if ( ( $length > 70 ) || preg_match( '/\n|\r|\t| {3,}/', $string ) )
			return '<pre>' . $string . '</pre>';
			
		return $string;
	}
}
?>