<?php
//
// CheezCap - Cheezburger Custom Administration Panel
// (c) 2008 - 2011 Cheezburger Network (Pet Holdings, Inc.)
// LOL: http://cheezburger.com
// Source: http://github.com/cheezburger/cheezcap
// Authors: Kyall Barrows, Toby McKes, Stefan Rusek, Scott Porad
// License: GNU General Public License, version 2 (GPL), http://www.gnu.org/licenses/gpl-2.0.html
//

require_once( 'library.php' );
require_once( 'config.php' );

$cap = new autoconfig();

if ( ! defined( 'LOADED_CONFIG' ) ) {
    add_action( 'admin_menu', 'cap_add_admin' );
    define( 'LOADED_CONFIG', 1 );
}

function cap_add_admin() {
	global $themename, $req_cap_to_edit, $cap_menu_position, $cap_icon_url;

	if ( ! current_user_can ( $req_cap_to_edit ) )
		return;

	if ( isset( $_GET['page'] ) && $_GET['page'] == basename( __FILE__ ) ) {
		$options = cap_get_options();
		$action = isset( $_REQUEST['action'] ) ? $_REQUEST['action'] : '';
		$method = false;
		$done = false;
		$data = new ImportData();
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

	$pgName = "$themename Settings";
	$hook = add_menu_page( $pgName, $pgName, isset( $req_cap_to_edit ) ? $req_cap_to_edit : 'manage_options', basename( __FILE__ ), 'top_level_settings', isset( $cap_icon_url ) ? $cap_icon_url : $default, isset( $cap_menu_position ) ? $cap_menu_position : $default );
	add_action( "admin_print_scripts-$hook", 'cap_admin_js_libs' );
	add_action( "admin_footer-$hook", 'cap_admin_js_footer' );
	add_action( "admin_print_styles-$hook", 'cap_admin_css' );
}
