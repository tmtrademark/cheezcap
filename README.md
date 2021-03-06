CheezCap - Cheezburger Custom Administration Panel
================
* CheezCap - Cheezburger Custom Administration Panel
* (c) 2008 - 2011 Cheezburger Network (Pet Holdings, Inc.)
* LOL: http://cheezburger.com
* Source: http://github.com/cheezburger/cheezcap/
* Authors: Kyall Barrows, Toby McKes, Stefan Rusek, Scott Porad
* UnLOLs by Mo Jangda (batmoo@gmail.com)
* License: GNU General Public License, version 2 (GPL), http://www.gnu.org/licenses/gpl-2.0.html
 

This is a fork of the original CheezCap developed by the fine Cheez-loving Cats over at ICHC. In has various bits of cleanup, the biggest being that it can be shared across multiple themes.

Automattic has a usually more maintained fork at http://github.com/automattic/cheezcap

"I'm In Yur Dashburd Tweakin' Yur Settings."

##
## Quick Start
##

1.  Copy the cheezcap folder into an appropriate location (maybe where you store your other shared plugins).
2.  Add the following line to functions.php (if you don't have a functions.php, create one in your theme directory). Adjust the path as needed.
<code>
	require_once( WP_PLUGINS_DIR . '/cheezcap/cheezcap.php');
</code>
3.  Use the included config-sample.php as a starting point to set up your options. Copy the modified version into your theme and include it.
<code>
	require_once( dirname( __FILE__ ) . '/cheezcap-config.php');
</code>
4.  Sprinkle theme options around your code, like this:
<code>
	global $cap;
	if ($cap->my_boolean_option) {
		// do stuff	
	}
</code>
4b.  Or use the helper function
<code>
	cheezcap_get_option( 'my_boolean_option', true, 'esc_html' );
</code>
5.  Enjoy!



##
## Background
##

In order to use the same WordPress theme for many different sites, one of the things we've 
done at Cheezburger is create themes with lots and lots of theme options. CheezCap is a simple library 
we've made for creating custom wp-admin panels really, really easily.

At a high level, the way it works is simple: edit the arrays in config.php in order to setup your
theme options, and then use the values from those theme options to customize your theme.  

It's just that easy!

## 
## Installation
##

Follow the "Quick Start" instructions above.

Finally, to verify that CheezCap has installed correctly, simply open /wp-admin and look for the 
"CheezCap Settings" link on the left navigation panel toward the bottom.

##
## Configuration
## 

Setting up your options happens inside config.php, and involves editing an array function 
called cap_get_options().  What you will be doing is building an array that 
contains "Group" objects.

A Group is a grouping of theme options.  Each Group appears as a tab on your custom admin panel.

The way we have it written in config.php is that each group is instantiated inline, that is, within 
in the array, although you don't have to do it that way.  When you instantiate a new Group, there are 
three parameters:

 1.  Name = will appear on the tab, should be human readable
 2.  ID = used internally, cannot have spaces and must be unique
 3.  Options Array = this is another array of options

The options array consists of Options objects.  Each option represents a value that you can use to 
customize your theme.  (Like the groups, we instantiate these inline, but it's not required.)  There
are three types of Options available to create:

 1. Boolean Option
 2. Text Option
 3. Dropdown Option
 4. Multiple Checkboxes Option

## 1. Boolean Option
The simplest form of option...creates a true or false dropdown that can be used to turn features on or off.

   new BooleanOption(Name, Description, OptionID, Default)

   Name = a human readable name for the option.
   Description = a human readable description for the option. 
   OptionID = a machine readable option identifier, cannot have spaces and must be unique
   Default = a boolean describing the default value for the option; if not specified, the default is "false"

## 2. Text Option 
A simple text field that can be used for configurable text, etc.

   new TextOption(Name, Description, OptionID, Default, UseTextArea, ValidationCallback)

   Name = a human readable name for the option.
   Description = a human readable description for the option. 
   OptionID = a machine readable option identifier, cannot have spaces and must be unique
   Default = a string as the default value for the option; if not specified, the default is ""
   UseTextArea = a boolean describing if the text option should be written as a text area; if not specified, the 
                 default is false;
   ValidationCallback = optional custom validation callback (see example)			 

## 3. Dropdown Option
Allows you to create a dropdown with custom values by passing the constructor an array of options

   new DropdownOption(Name, Description, OptionID, OptionsArray, DefaultIndex, OptionsLabelsArray, ValidationCallback)

   Name = a human readable name for the option.
   Description = a human readable description for the option. 
   OptionID = a machine readable option identifier, cannot have spaces and must be unique
   OptionsArray = an array containing the values for the dropdown menu
   DefaultIndex = an integer identifying the item in the array that is the default value; if not specified,
                  the default is 0.
   OptionsLabelsArray = if you want to separate the labels from values, pass in an array with the labels matching indexes in the
   						OptionsArray
   ValidationCallback = optional custom validation callback (see example)					

## 4. Multiple Checkboxes Option
Allows you to create a multiple checkboxes option with custom values by passing the constructor an array of options
   new MultipleCheezcapOption( Name, Description, OptionID, OptionsValues, OptionsLabels, OptionsChecked, ValidationCallback )

   Name = a human readable name for the option.
   Description = a human readable description for the option. 
   OptionID = a machine readable option identifier, cannot have spaces and must be unique
   OptionsValues = an array containing the values for the dropdown menu
   OptionsLabels = an array of labels corresponding to OptionValues
   OptionsChecked = an array of keys in OptionsValues that should be checked (selected)
   ValidationCallback = optional custom validation callback (see example)

##
## Usage
##

CheezCap makes it easy to access the values that are set in your custom admin pages is easy.
You can use the built-in helper function:

	cheezcap_get_option( $option, $echo = false, $sanitize_callback = '' )
