Titan Framework
=======

[![Build Status](https://travis-ci.org/gambitph/Titan-Framework.png?branch=master)](https://travis-ci.org/gambitph/Titan-Framework)

*The easiest to use WordPress options framework.*

Titan Framework allows theme and plugin developers to create a admin pages, options, meta boxes, and theme customizer options with just a few simple lines of code.

[Get it in the WordPress plugin repo](https://wordpress.org/plugins/titan-framework/)

#### Features
* Makes development unbelievably easy
* Built with optimization in mind
* Does NOT clutter the database
* Integrates with your project seamlessly
* Theme customizer live preview integration
* Supports child themes
* Automatic CSS generation with SCSS support

## Recent Changelog

#### Version 1.7.2
* EDD option can now check for updates all by itself (thank you julien731)
* `get_post_types` function now callable from `tf_create_options`
* Now passes theme-check (ignored `add_menu_page` error)

#### Version 1.7.1
* Bug fixes for the Easy Digital Download License option
* Enhanced date option parameters
* New parameters for Theme Customizer for creating panels

#### Version 1.7
* New Easy Digital Download License option (thank you julien731)
* New date option (thank you ardalann)
* Added new action tf_save_options_{namespace} which is called after saving options
* Fixed display issue with the font option in the theme customizer
* Fixed bug where empty multicheck returned an array
* Fix: customizer show_font_size & show_color

#### Version 1.6.1
* Added missing files in the SVN

#### Version 1.6
* New embed method (check the getting started section)
* New tf_create_options hook for creating options
* New number unit parameter
* Removed font awesome, now uses dashicons
* Added desc params to panels, tabs and meta boxes
* Added size attribute for the upload option
* Deleted Uncommon Ace Extensions
* Improve load script to meta boxes
* Lots of bug fixes

## Try it Out First

Want to see what Titan Framework can do? [Check out our live demo, no need to install anything!](http://demo.titanframework.net/wp-admin/)

## Getting Started With Titan Framework

Titan Framework aims to be easily used by everyone. The goal is to make it plug and play - just activate the plugin and start creating your options.

Read our guide on how to [get started with Titan Framework](http://wordpress.org/plugins/titan-framework/)


## Donate to the Development

If Titan Framework has helped you in any way, we would appreciate any amount of donations that you give us. Donations would mean more development time for the framework as I am continuously developing it during my free time.

[![Donate](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9X7HJBGJ37VH6)


## Help Spread the Word on Titan Framework

Titan is super new, so far the framework has been getting good feedback from the community. Help out and spread the word by starring this repo, sending tweets, writing blog posts about what you think about Titan, and [review the plugin in the WordPress plugin repo](http://wordpress.org/support/view/plugin-reviews/titan-framework).


## Are You Using Titan Framework in Your Project?

Let us know so we can showcase it in the site! Send me an email at bf.intal@gambit.ph, send the name, a screenshot, a link and a short description of your project.


## Contributing, Pull Requests Are Very Welcome

Have an idea for a cool option, or do you have a bug fix you want to implement? Please don't hessitate to place a *PR* (Pull Request).

PRs on these are welcome:
* Bug fixes
* Cool new options
* Cool new hooks
* WordPress standardization
* Code optimizations
* Anything under the sun as long as it's helpful :)


## Translations

We want Titan Framework to be used by everyone, and since not everyone speaks or reads english, we would appreciate it if you can [help translate the framework to your language](https://www.transifex.com/projects/p/titan-framework/).

#### Current Translations
* French (thanks @PunKeel)
* German (thanks @jascha)
* Italian (thanks @DavideVogliotti & Giuseppe Pignataro)
* Portuguese (thanks @pagelab)
* Spanish (thanks @maperezotero)
* Turkish (thanks @gurkankara)

## Important Links

* [WordPress plugin page](http://wordpress.org/plugins/titan-framework/)
* [Titan Framework main site](http://www.titanframework.net)
* [Documentation & tutorials](http://www.titanframework.net/docs)
* [Demo Theme with Titan Framework](https://github.com/gambitph/Titan-Framework-Demo-Theme)
* [Transifex project page](https://www.transifex.com/projects/p/titan-framework/)





