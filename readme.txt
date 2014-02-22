=== Plugin Name ===
Contributors: bfintal
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=D2MK28E7BDLHC
Tags: framework, options, admin, admin panel, meta box, theme customizer, option framework, library, sdk
Requires at least: 3.8
Tested up to: 3.8.1
Stable tag: 1.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The most easy to use WordPress option framework.

== Description ==

Titan Framework allows theme and plugin developers to create a admin pages, options, meta boxes, and theme customizer options with just a few simple lines of code.

Titan Framework aims to be easily used by everyone. The goal is to make it plug and play - just activate the plugin and start creating your options. Read our guide on how to [Get Started with Titan Framework](https://www.titanframework.net/get-started/)

* [Documentation and Tutorials for Developers](http://titanframework.net)
* [Titan Framework GitHub Repository](https://github.com/gambitph/Titan-Framework)
* [Issue Tracker](https://github.com/gambitph/Titan-Framework/issues)

= Features =

* Makes development unbelievably easy
* Built with optimization in mind
* Does NOT clutter the database
* Integrates with your project seamlessly
* Theme customizer live preview integration

= Easy creation of: =

* Admin menus and submenus
* Admin pages
* Admin options and tabs
* Meta boxes and options
* Theme customizer sections and options

= Options available in admin pages, meta boxes and theme customizer: =

* Checkbox
* Color picker
* Editor (WYSIWYG)
* Heading
* Media uploader
* Multicheck
* Multicheck categories and taxonomies
* Multicheck pages and posts
* Note
* Number
* Radio buttons
* Radio palette picker
* Radio image
* Save and reset buttons
* Select (drop down)
* Select Google WebFont
* Select categories and taxonomies
* Select pages and posts
* Text
* Textarea

= Supporting the Framework =

Titan is super new, so far the framework has been getting good feedback from the community. Help out and spread the word by starring this repo, sending tweets, writing blog posts about what you think about Titan, and [review the plugin](http://wordpress.org/support/view/plugin-reviews/titan-framework).

= Donate to the Development =

If Titan Framework has helped you in any way, we would appreciate any amount of donations that you give us. Donations would mean more development time for the framework as I am continuously developing it during my free time.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9X7HJBGJ37VH6)

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the provided classes and functions in your theme or plugin. Read our guide on how to [Get Started with Titan Framework](https://www.titanframework.net/get-started/)

== Frequently Asked Questions ==

* [Site FAQs](https://www.titanframework.net/faqs/)
* [Documentation and Tutorials for Developers](http://titanframework.net)
* [Titan Framework GitHub Repository](https://github.com/gambitph/Titan-Framework)
* [Issue Tracker](https://github.com/gambitph/Titan-Framework/issues)

== Sample Code ==

= Creating an admin menu and submenu =

`$titan = TitanFramework::getInstance( 'my-plugin' );

// Create menu
$panel = $titan->createAdminPanel( array(
    'name' => 'Menu Name',
) );

$panel2 = $panel->createAdminPanel( array(
    'name' => 'Submenu Name',
) );`

= Creating an option in an admin page =

`$titan = TitanFramework::getInstance( 'my-plugin' );

// Create menu
$panel = $titan->createAdminPanel( array(
    'name' => 'Menu Name',
) );

// Create a select option
$panel->createOption( array(
    'name' => 'Select One',
    'id' => 'my_selected_id
    'type' => 'select',
    'options' => array(
        '1' => 'Option one',
        '2' => 'Option two',
        '3' => 'Option three',
    ),
    'default' => '3',
    'desc' => 'Some description',
) );`

= Create a meta box with an option =

`$titan = TitanFramework::getInstance( 'my-plugin' );

// Create menu
$box = $titan->createMetaBox( array(
    'name' => 'Menu Name',
) );

$box->createOption( array(
    'name' => 'My Text',
    'type' => 'text',
    'id' => 'my_text_id',
    'desc' => 'Some description',
) );`

= Create a theme customizer with an option with live preview =

`$titan = TitanFramework::getInstance( 'my-plugin' );

$section = $titan->createThemeCustomizerSection( array(
    'name' => 'My Section',
    'desc' => 'Section description',
) );

$section->createOption( array(
    'id' => 'my_color',
    'name' => 'My Color',
    'type' => 'color',
    'default' => '#555555',
    'livepreview' => "$('#main').css('backgroundColor', value);",
) );`

= Getting values =

`$titan = TitanFramework::getInstance( 'my-plugin' );

// Get an option or an admin option
$myValue = $titan->getOption( 'option_name' );

// Get a theme customizer option
$myValue = $titan->getOption( 'option_name', $post_id );`

**For developers: for documentation and examples, please visit our website at [titanframework.net](http://titanframework.net)**

== Upgrade Notice ==

== Screenshots ==

== Changelog ==

= 1.1 =
* Added WYSIWYG editor option
* Added Radio Palette option
* Fixed bug where special characters in admin pages and tabs were not redirecting correctly
* Fixed minor bug where customizer options become reordered
* Minor bug fixes

= 1.0.1 - 1.0.2 =
* Added styling to the admin options
* Fixed minor debug error in options (Thanks to @Dovy)

= 1.0 =
First release