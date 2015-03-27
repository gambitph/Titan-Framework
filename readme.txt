=== Plugin Name ===
Contributors: bfintal
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=D2MK28E7BDLHC
Tags: framework, options, admin, admin panel, meta box, theme customizer, option framework, library, sdk, edd, settings, api, theme creator, theme framework
Requires at least: 3.8
Tested up to: 4.1
Stable tag: 1.7.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The most easy to use WordPress option framework.

== Description ==

Titan Framework allows theme and plugin developers to create a admin pages, options, meta boxes, and theme customizer options with just a few simple lines of code.

This means faster theme & plugin creation for everyone.

= The Goal =

Titan Framework aims to be easily used by everyone. The goal is to make it plug and play - just activate the plugin and start creating your options. Read our guide on how to [Get Started with Titan Framework](http://www.titanframework.net/how/)

* [Documentation and Tutorials for Developers](http://www.titanframework.net/docs)
* [Titan Framework GitHub Repository](https://github.com/gambitph/Titan-Framework)
* [Issue Tracker](https://github.com/gambitph/Titan-Framework/issues)

= Start Creating Your Theme =

[You can generate your own Underscores based theme with Titan Framework through our site](http://www.titanframework.net/)

The generated theme comes with sample pre-created options in the admin and theme customizer along with code documentation.

= Features =

* Makes development unbelievably easy
* Built with optimization in mind
* Does NOT clutter the database
* Integrates with your project seamlessly
* Theme customizer live preview integration
* Supports child themes
* Automatic CSS generation with SCSS support
* Full font style fields
* Easy Digital Download activation integration

= Easy creation of: =

* Admin menus and submenus
* Admin pages
* Admin options and tabs
* Meta boxes and options
* Theme customizer sections and options

= Options available in admin pages, meta boxes and theme customizer: =

* Checkbox
* Code (using [Ace](http://ace.c9.io/#nav=about))
* Color picker
* Date
* EDD License (Easy Digital Downloads license)
* Editor (WYSIWYG)
* Enable
* Font Style (Web safe fonts and Google WebFonts)
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
* Sortable
* Text
* Textarea

= Are You Using Titan Framework in Your Project? =

Let me know, send me an email at bf.intal@gambit.ph with the details of your project along with a screenshot and I'll add it to the showcase here and in the site.

= Supporting the Framework =

Titan is super new, so far the framework has been getting good feedback from the community. Help out and spread the word by starring this repo, sending tweets, writing blog posts about what you think about Titan, and [review the plugin](http://wordpress.org/support/view/plugin-reviews/titan-framework).

= Help Translate =

We want Titan Framework to be used by everyone, and since not everyone speaks or reads english, we would appreciate it if you can [help translate the framework to your language](https://www.transifex.com/projects/p/titan-framework/).

= Currently translated to =

* French (thanks @PunKeel)
* German (thanks @jascha)
* Italian (thanks @DavideVogliotti & Giuseppe Pignataro)
* Portuguese (thanks @pagelab)
* Spanish (thanks @maperezotero)
* Turkish (thanks @gurkankara)

= Donate to the Development =

If Titan Framework has helped you in any way, we would appreciate any amount of donations that you give us. Donations would mean more development time for the framework as I am continuously developing it during my free time.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9X7HJBGJ37VH6)

= Special Thanks to all the Contributors =

@ardallan, @BrazenlyGeek, @csloisel, @DavideVogliotti, @davidossahdez, @desaiuditd, @dovy, @kevinlangleyjr, @manishsongirkar, @mendezcode, @MickeyKay, @sagarjadhav, and @smccafferty

and to everyone else in the GitHub repo!

== Installation ==

1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Use the provided classes and functions in your theme or plugin. Read our guide on how to [Get Started with Titan Framework](http://www.titanframework.net/how/)

== Frequently Asked Questions ==

* [Site FAQs](http://www.titanframework.net/faqs/)
* [Documentation and Tutorials for Developers](http://titanframework.net)
* [Titan Framework GitHub Repository](https://github.com/gambitph/Titan-Framework)
* [Issue Tracker](https://github.com/gambitph/Titan-Framework/issues)

== Upgrade Notice ==

== Screenshots ==

1. An admin page with tabs and options created using Titan Framework
2. Supports theme customizer sections, options and live previewing
3. Meta box support for pages, posts and custom post types
4. Sample code on how to create admin pages and options

== Changelog ==

= 1.7.5 =
* Added `hidden` parameter for all options
* Fixed 4.1 display issues with the upload option
* Fixed an undefined notice that sometimes appears

= 1.7.4 =
* Faster SCSS parsing
* Faster loading time
* Unit parameter for number options now supported in the Theme Customizer
* Better font color option handling in Theme Customizer
* Now prevents SCSS errors from showing up
* Fixed name label issues with the enable option
* Better plugin checking method
* Plugin checker now integrates with TGM Plugin Activation
* Updated Ace

= 1.7.3 =
* Fixed bug introduced in 1.7.2 where admin options sometimes were not being saved

= 1.7.2 =
* EDD option can now check for updates all by itself (thank you julien731)
* `get_post_types` function now callable from `tf_create_options`
* Now passes theme-check (ignored `add_menu_page` error)

= 1.7.1 =
* Bug fixes for the Easy Digital Download License option
* Enhanced date option parameters
* New parameters for Theme Customizer for creating panels

= 1.7 =
* New Easy Digital Download License option (thank you julien731)
* New date option (thank you ardalann)
* Added new action tf_save_options_{namespace} which is called after saving options
* Fixed display issue with the font option in the theme customizer
* Fixed bug where empty multicheck returned an array
* Fix: customizer show_font_size & show_color

= 1.6.1 =
* Added missing files in the SVN

= 1.6 =
* New embed method (check the getting started section)
* New tf_create_options hook for creating options
* New number unit parameter
* Removed font awesome, now uses dashicons
* Added desc params to panels, tabs and meta boxes
* Added size attribute for the upload option
* Deleted Uncommon Ace Extensions
* Improve load script to meta boxes
* Lots of bug fixes

= 1.5 =
* Added German, Portuguese, Turkish and updated Italian translations
* Added `notification` and `paragraph` paramaters to the note option
* Added `include_fonts` parameter to the font option for specifying the selectable fonts
* Added `show_websafe_fonts` and `show_google_fonts` parameters to the font option
* Added `maxlength` parameter to the the text option
* Fixed Titan plugin detection code
* New more WordPress-centric styling of admin panels (special thanks to @sagarjadhav)
* A Lot of stabilization bug fixes

= 1.4.3 =
* Added a few global hooks
* Fixed missing hooks that prevented the Shortcode Extension from working
* Fixed missing HTML tags

= 1.4.2 =
* Fixed a typo

= 1.4.1 =
* Added some new hooks
* Added namespaces to all hooks
* Added meta links
* Fixed bug where font drop downs closed immediately in Firefox
* Fixed bug where getInstance did not return the same instance sometimes
* Fixed bug where the font option did not generate CSS correctly
* Fixed bug where only one Titan instance generated CSS files
* Fixed bug where CSS were being generated multiple times
* Fixed bug where option IDs in different instances caused an error
* Fixed bug where the live preview lagged a lot

= 1.4 =
* Added new Font option
* Added new Sortable option
* Fixed bug where generated CSS values aren't showing up (thanks @ardalann)

= 1.3 =
* Added Spanish translations (thanks @maperezotero)
* Added Code option that uses Ace
* No need to use the post ID when getting getOption
* createMetaBox can now accept an array in the post_type parameter
* Now using Travis CI
* Tons of bug fixes

= 1.2.1 =
* Added French translations (thanks @PunKeel)
* Added removeOption function
* Fixed bug where fonts sometimes cannot be changed
* Fixed fatal error encountered sometimes when generating CSS
* Fixed bug where other post types are unable to be saved

= 1.2 =
* Better embedding handling
* Automatic CSS generation

= 1.1.1 =
* Titan can now be embedded into themes and plugins
* Added Radio Image option
* Better layout for Google WebFont option
* Now enforcing unique option ids
* Fixed bug that shows up in fresh WP installs

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
