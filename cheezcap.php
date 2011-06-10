<?php
/**
 * CheezCap - Cheezburger Custom Administration Panel
 * (c) 2008 - 2011 Cheezburger Network (Pet Holdings, Inc.)
 * LOL: http://cheezburger.com
 * Source: http://github.com/cheezburger/cheezcap
 * Authors: Kyall Barrows, Toby McKes, Stefan Rusek, Scott Porad
 * License: GNU General Public License, version 2 (GPL), http://www.gnu.org/licenses/gpl-2.0.html
 */

require_once( dirname( __FILE__ ) . '/library.php' );

if( defined( 'CHEEZCAP_DEBUG' ) && CHEEZCAP_DEBUG )
	require_once( dirname( __FILE__ ) . '/config-sample.php' );

/**
 * This class is the handy short cut for accessing config options
 *
 * $cap->post_ratings is the same as get_bool_option("cap_post_ratings", false)
 */
class CheezCap {
	private $data = false;
	private $cache = array();
	private $settings = array();
	private $options = array();
	
	private $messages = array();

	function __construct( $options, $settings = array() ) {
		$settings = wp_parse_args( $settings, array( 
			'themename' => 'CheezCap',
			'req_cap_to_edit' => 'manage_options',
			'cap_menu_position' => 99,
			'cap_icon_url' => '',
		) );
		
		$settings['themeslug'] = sanitize_key( $settings['themename'] );
		
		// Let's prevent accidentally allowing low-level users access to cap
		if( ! in_array( $settings['req_cap_to_edit'], apply_filters( 'cheezcap_req_cap_to_edit_whitelist', array( 'manage_network', 'manage_options', 'edit_others_posts', 'publish_posts' ) ) ) )
			$settings['req_cap_to_edit'] = 'manage_options';
		
		$this->settings = $settings;
		$this->options = $options;
		$this->messages = $this->get_default_messages();
		
		add_action( 'admin_menu', array( $this, 'add_admin_page' ) );
    	add_action( 'admin_init', array( $this, 'handle_admin_actions' ) );
	}

	function init() {
		if ( $this->data )
			return;

		$this->data = array();
		$options = cap_get_options();

		foreach ( $options as $group ) {
			foreach( $group->options as $option ) {
				$this->data[$option->_key] = $option;
			}
		}
	}

	public function __get( $name ) {
		$this->init();

		if ( array_key_exists( $name, $this->cache ) )
			return $this->cache[$name];

		$option = $this->data[$name];
		if ( empty( $option ) )
			throw new Exception( "Unknown key: $name" );

		$value = $this->cache[$name] = $option->get();
		return $value;
	}
	
	public function get_options() {
		return $this->options;
	}
	
	public function get_settings() {
		return $this->settings;
	}
	
	public function get_setting( $setting, $default = '' ) {
		if( isset( $this->settings[$setting] ) )
			return $this->settings[$setting];
		return $default;
	}
	
	// UI-related functions
	function add_admin_page() {
		$page_name = sprintf( __( '%s Settings' ), esc_html( $this->get_setting( 'themename' ) ) );
		$page_hook = add_menu_page( $page_name, $page_name, $this->get_setting( 'req_cap_to_edit' ), $this->get_setting( 'themeslug' ), array( $this, 'display_admin_page' ), $this->get_setting( 'cap_icon_url' ), $this->get_setting( 'cap_menu_position' ) );
		
		add_action( "admin_print_scripts-$page_hook", array( $this, 'admin_js_libs' ) );
		add_action( "admin_footer-$page_hook", array( $this, 'admin_js_footer' ) );
		add_action( "admin_print_styles-$page_hook", array( $this, 'admin_css' ) );
	}
	
	function handle_admin_actions() {
		global $plugin_page;
		
		if ( $plugin_page == $this->get_setting( 'themeslug' ) ) {
			
			if ( ! current_user_can ( $this->get_setting( 'req_cap_to_edit' ) ) )
				return;
			
			$options = $this->get_options();
			$action = isset( $_POST['action'] ) ? strtolower( $_POST['action'] ) : '';
			$method = false;
			$done = false;
			$redirect = false;
			$data = new CheezCapImportData();
			
			switch ( $action ) {
				case 'save':
					$method = 'update';
					$redirect = array( 'success' => $method );
					break;
				
				case 'reset':
					$method = 'reset';
					$redirect = array( 'success' => $method );
					break;
				
				case 'export':
					$method = 'export';
					$done = array( $this, 'serialize_export' );
					break;
				
				case 'import':
					
					$data = @ unserialize( file_get_contents( $_FILES['file']['tmp_name'] ) ); // We're using @ to suppress the E_NOTICE
					
					if( $data && is_a( $data, 'CheezCapImportData' ) ) {
						$method = 'import';
						$redirect = array( 'success' => $method );
					} else {
						$redirect = array( 'error' => 'import' );
					}
					
					break;
			}
	
			if ( $method ) {
				foreach ( $options as $group ) {
					foreach ( $group->options as $option ) {
						call_user_func( array( $option, $method ), $data );
					}
		    	}
				
				if ( $done )
					call_user_func( $done, $data );
			}
			
			if( ! empty( $redirect ) )
				wp_redirect( add_query_arg( $redirect, menu_page_url( $plugin_page, false ) ) );
				
		}
	}
	
	function display_message( $type ) {
		$theme_name = $this->get_setting( 'themename' );
		$message_key = sanitize_key( $_GET[ $type ] );
		$message = isset( $this->messages[$type][$message_key] ) ? $this->messages[$type][$message_key] : '';
		
		$message_class = ( $type != 'error' ) ? 'updated' : $type;
		
		if( $message )
			echo sprintf( '<div id="message" class="%2$s fade"><p><strong>%1$s</strong></p></div>', sprintf( $message, $theme_name ), $message_class );
	}
	
	function display_admin_page() {
		$themename = $this->get_setting( 'themename' );
		
		if ( isset( $_GET['success'] ) )
			$this->display_message( 'success' );
		elseif ( isset( $_GET['error'] ) )
			$this->display_message( 'error' );
		
		?>
	
		<div class="wrap">
			<h2><b><?php echo esc_html( $themename . ' Theme Options.' ); ?></b></h2>
	
			<form method="post">
	
				<div id="config-tabs">
					<ul>
		<?php
		$groups = $this->get_options();
		foreach( $groups as $group ) :
		?>
						<li><a href='<?php echo esc_attr( '#' . $group->id ); ?>'><?php echo esc_html( $group->name ); ?></a></li>
		<?php
		endforeach;
		?>
					</ul>
		<?php
		foreach( $groups as $group ) :
		?>
					<div id='<?php echo esc_attr( $group->id ); ?>'>
		<?php
						$group->WriteHtml();
		?>
					</div>
		<?php
		endforeach;
		?>
				</div>
				<p class="submit alignleft">
					<input type="hidden" name="action" value="save" />
					<input name="save" type="submit" value="Save changes" />
				</p>
			</form>
			<form enctype="multipart/form-data" method="post">
				<p class="submit alignleft">
					<input name="action" type="submit" value="Reset" />
				</p>
				<p class="submit alignleft" style='margin-left:20px'>
					<input name="action" type="submit" value="Export" />
				</p>
				<p class="submit alignleft">
					<input name="action" type="submit" value="Import" />
					<input type="file" name="file" />
				</p>
			</form>
			<div class="clear"></div>
			<h2>Preview (updated when options are saved)</h2>
			<iframe src="<?php echo esc_url( home_url( '?preview=true' ) ); ?>" width="100%" height="600" ></iframe>
		<?php
	}
	
	function admin_css() {
		wp_enqueue_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.3/themes/base/jquery-ui.css', false, '1.7.3' );
	}
	
	function admin_js_libs() {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-tabs' );
	}
	
	function admin_js_footer() {
		?>
		<script type="text/javascript">
		/* <![CDATA[ */
			jQuery(document).ready(function($) {
				$("#config-tabs").tabs();
			});
		/* ]]> */
		</script>
		<?php
	}
	
	function get_default_messages() {
		return array( 
			'success' => array(
				'update' => __( 'Sweet! The settings for %s were saved!' ),
				'reset' => __( 'Yay! The settings for %s were reset!' ),
				'import' => __( 'Woo! The settings for %s were imported!' )
			),
			'error' => array(
				'import' => 'That doesn\'t look like a CheezCap Export file. Homie don\'t play that!',
			)
		);
	}
	
	function serialize_export( $data ) {
		$filename = sprintf( '%s-%s-theme-export.txt', date( 'Y.m.d' ), sanitize_key( get_bloginfo( 'name' ) ) );
		header( 'Content-disposition: attachment; filename=' . $filename );
		echo serialize( $data );
		exit();
	}
}
