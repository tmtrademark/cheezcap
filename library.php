<?php
//
// CheezCap - Cheezburger Custom Administration Panel
// (c) 2008 - 2011 Cheezburger Network (Pet Holdings, Inc.)
// LOL: http://cheezburger.com
// Source: http://github.com/cheezburger/cheezcap/
// Authors: Kyall Barrows, Toby McKes, Stefan Rusek, Scott Porad
// License: GNU General Public License, version 2 (GPL), http://www.gnu.org/licenses/gpl-2.0.html
//

class Group {
	var $name;
	var $id;
	var $options;

	function Group( $_name, $_id, $_options ) {
		$this->name = $_name;
		$this->id = "cap_$_id";
		$this->options = $_options;
	}

	function WriteHtml() {
		?>
		<table class="form-table" width="100%">
			<tr valign="top">
				<th scope="row">Option</th>
				<th scope="row">Value</th>
			</tr>
		<?php
			for ( $i=0; $i < count( $this->options ); $i++ ) {
				$this->options[$i]->WriteHtml();
			}
		?>
		</table>
		<?php
	}
}

class Option {
	var $name;
	var $desc;
	var $id;
	var $_key;
	var $std;

	function Option( $_name, $_desc, $_id, $_std ) {
		$this->name = $_name;
		$this->desc = $_desc;
		$this->id = "cap_$_id";
		$this->_key = $_id;
		$this->std = $_std;
	}

	function WriteHtml() {
		echo '';
	}

	function Update( $ignored ) {
		$value = stripslashes_deep( $_POST[$this->id] );
		update_option( $this->id, $value );
	}

	function Reset( $ignored ) {
		update_option( $this->id, $this->std );
	}

	function Import( $data ) {
		if ( array_key_exists( $this->id, $data->dict ) )
			update_option( $this->id, $data->dict[$this->id] );
	}

	function Export( $data ) {
		$data->dict[$this->id] = get_option( $this->id );
	}

	function get() {
		return get_option( $this->id );
	}
}

class TextOption extends Option {
	var $useTextArea;

	function TextOption( $_name, $_desc, $_id, $_std = '', $_useTextArea = false ) {
		$this->Option( $_name, $_desc, $_id, $_std );
		$this->useTextArea = $_useTextArea;
	}

	function WriteHtml() {
		$stdText = $this->std;

		$stdTextOption = get_option( $this->id );
	        if ( ! empty( $stdTextOption ) )
			$stdText = $stdTextOption;

		?>
		<tr valign="top">
			<th scope="row"><?php echo esc_html( $this->name . ':' ); ?></th>
		<?php
		$commentWidth = 2;
		if ( $this->useTextArea ) :
			$commentWidth = 1;
		?>
			<td rowspan="2"><textarea style="width:100%;height:100%;" name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>"><?php echo esc_textarea( $stdText ); ?></textarea>
		<?php
		else :
		?>
			<td><input name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>" type="text" value="<?php echo esc_attr( $stdText ); ?>" size="40" />
		<?php
		endif;
		?>
			</td>
		</tr>
                <tr valign="top"><td colspan="<?php echo absint( $commentWidth ); ?>"><small><?php echo esc_html( $this->desc ); ?></small></td></tr><tr valign="top"><td colspan="2"><hr /></td></tr>
		<?php
	}

	function get() {
		$value = get_option( $this->id );
		if ( empty( $value ) )
			return $this->std;
		return $value;
	}
}

class DropdownOption extends Option {
	var $options;

	function DropdownOption( $_name, $_desc, $_id, $_options, $_stdIndex = 0 ) {
		$this->Option( $_name, $_desc, $_id, $_stdIndex );
		$this->options = $_options;
	}

	function WriteHtml() {
		?>
		<tr valign="top">
			<th scope="row"><?php echo esc_html( $this->name ); ?></th>
			<td>
				<select name="<?php echo esc_attr( $this->id ); ?>" id="<?php echo esc_attr( $this->id ); ?>">
		<?php
		foreach( $this->options as $option ) :
		?>
					<option<?php if ( get_option( $this->id ) == $option || ( ! get_option( $this->id ) && $this->options[$this->std] == $option ) ) { echo ' selected="selected"'; } ?>><?php echo esc_html( $option ); ?></option>
		<?php
		endforeach;
		?>
				</select>
			</td>
		</tr>
		<tr valign="top">
			<td colspan=2>
				<small><?php echo esc_html( $this->desc ); ?></small><hr />
			</td>
		</tr>
		<?php
	}

	function get() {
		$value = get_option( $this->id, $this->std );
        	if ( strtolower( $value ) == 'disabled' )
			return false;
		return $value;
	}
}

class BooleanOption extends DropdownOption {
	var $default;

	function BooleanOption( $_name, $_desc, $_id, $_default = false ) {
		$this->default = $_default;
		$this->DropdownOption( $_name, $_desc, $_id, array( 'Disabled', 'Enabled' ), $_default ? 1 : 0 );
	}

	function get() {
		$value = get_option( $this->id, $this->default );
		if ( is_bool( $value ) )
			return $value;
		switch ( strtolower( $value ) ) {
			case 'true':
			case 'enable':
			case 'enabled':
				return true;
			default:
				return false;
		}
	}
}

// This class is the handy short cut for accessing config options
//
// $cap->post_ratings is the same as get_bool_option("cap_post_ratings", false)
//
class autoconfig {
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

function top_level_settings() {
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

class ImportData {
	var $dict = array();
}

function cap_serialize_export( $data ) {
	header( 'Content-disposition: attachment; filename=theme-export.txt' );
	echo serialize( $data );
	exit();
}