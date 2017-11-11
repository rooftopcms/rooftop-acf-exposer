=== Rooftop ACF Exposer ===
Contributors: rooftopcms
Tags: rooftop, api, admin, headless, acf
Requires at least: 4.7
Tested up to: 4.8.1
Stable tag: 4.8
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

rooftop-acf-exposer includes fields from the excellent Advanced Custom Fields plugin into the API.

== Description ==

This plugin exposes Advanced Custom Fields (acf) data in the Wordpress API. It has been developed as part of (Rooftop CMS)[http://www.rooftopcms.com]: an API-first Wordpress CMS for developers and content creators.

Track progress, raise issues and contribute at http://github.com/rooftopcms/rooftop-acf-exposer

== Installation ==

rooftop-acf-exposer is a Composer plugin, so you can include it in your Composer.json.

Otherwise you can install manually:

1. Upload the `rooftop-acf-exposer` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. There is no step 3 :-)

== Frequently Asked Questions ==

= Can this be used without Rooftop CMS? =

Yes, it's a Wordpress plugin you're welcome to use outside the context of Rooftop CMS. We haven't tested it, though.

= Do I need a licence for Advanced Custom Fields? =

The licence for ACF is not open source, but there is a freely-downloadable version of ACF [here](http://www.advancedcustomfields.com/). The plugin doesn't (yet) expose data from the ACF PRO version.

== Changelog ==

= 1.2.2 =
* Support for writing back to ACF fields through the API

= 1.2.1 =
* Preliminary support for ACF fields in taxonomy term responses
* Updated readme for packaging

= 1.2.0 =
* Fixes & Updated ACF version

= 0.0.1 =
* Initial release


== What's Rooftop CMS? ==

Rooftop CMS is a hosted, API-first WordPress CMS for developers and content creators. Use WordPress as your content management system, and build your website or application in the language best suited to the job.

https://www.rooftopcms.com
