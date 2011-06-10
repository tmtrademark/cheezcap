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
require_once( dirname( __FILE__ ) . '/config.php' );

$cap = new CheezCap();

/**
 * This class is the handy short cut for accessing config options
 *
 * $cap->post_ratings is the same as get_bool_option("cap_post_ratings", false)
 */
class CheezCap {
	private $data = false;
	private $cache = array();

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
}

if ( ! defined( 'LOADED_CONFIG' ) ) {
    add_action( 'admin_menu', 'cap_add_admin_page' );
    add_action( 'admin_init', 'cap_handle_admin_actions' );
    define( 'LOADED_CONFIG', 1 );
}

function cap_add_admin_page() {
	global $themename, $req_cap_to_edit, $cap_menu_position, $cap_icon_url;
	
	$pgName = sprintf( __( '%s Settings' ), esc_html( $themename ) );
	$hook = add_menu_page( $pgName, $pgName, isset( $req_cap_to_edit ) ? $req_cap_to_edit : 'manage_options', 'cheezcap', 'cap_admin_page_display', isset( $cap_icon_url ) ? $cap_icon_url : $default, isset( $cap_menu_position ) ? $cap_menu_position : $default );
	add_action( "admin_print_scripts-$hook", 'cap_admin_js_libs' );
	add_action( "admin_footer-$hook", 'cap_admin_js_footer' );
	add_action( "admin_print_styles-$hook", 'cap_admin_css' );
}

function cap_handle_admin_actions() {
	global $plugin_page, $req_cap_to_edit;
	
	if ( $plugin_page == 'cheezcap' ) {
		
		if ( ! current_user_can ( $req_cap_to_edit ) )
			return;
		
		$options = cap_get_options();
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		$method = false;
		$done = false;
		$data = new CheezCapImportData();
		
		switch ( $action ) {
			case 'save':
				$method = 'Update';
				break;
			case 'Reset':
				$method = 'Reset';
				break;
			case 'Export':
				$method = 'Export';
				$done = 'cap_serialize_export';
				break;
			case 'Import':
				$method = 'Import';
				$data = unserialize( file_get_contents( $_FILES['file']['tmp_name'] ) );
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
	}
}

function cap_admin_page_display() {
	global $themename;

	if ( isset( $_REQUEST['saved'] ) )
		echo '<div id="message" class="updated fade"><p><strong>' . esc_html( $themename . ' settings saved.' ) . '</strong></p></div>';
	if ( isset( $_REQUEST['reset'] ) )
		echo '<div id="message" class="updated fade"><p><strong>' . esc_html( $themename . ' settings reset.' ) . '</strong></p></div>';
	?>

	<div class="wrap">
		<h2><b><?php echo esc_html( $themename . ' Theme Options.' ); ?></b></h2>

		<form method="post">

			<div id="config-tabs">
				<ul>
	<?php
	$groups = cap_get_options();
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

function cap_admin_css() {
	wp_enqueue_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.7.3/themes/base/jquery-ui.css', false, '1.7.3' );
}

function cap_admin_js_libs() {
	wp_enqueue_script( 'jquery' );
	wp_enqueue_script( 'jquery-ui-tabs' );
}

function cap_admin_js_footer() {
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
