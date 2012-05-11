=== Cookillian ===
Contributors: Myatu
Donate link: http://pledgie.com/campaigns/16906
Tags: cookie, eu, ec, europe, uk, law, directive, filter, block
Requires at least: 3.3
Tested up to: 3.4-beta3
Stable tag: 1.0.4
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Provides extensible support for EU/UK compliance of the EC Cookie Directive (2009/136/EC), based on a visitor's location.

== Description ==

_Cookillian_ makes it easier to comply with the EC Cookie Directive, which affects the United Kingdom on May 25th 2012 and other European countries.

Cookillian will automatically detect if a visitor is located in one of the countries defined by you - likely the countries affected by the EC Cookie Direcitve - and will automatically disable any cookies that are set from within WordPress or a 3rd party plugin. The user will then be presented with an fully customizable alert about cookies, and given the option to opt in or out of using cookies.

It will also collect basic information about any cookies set from within WordPress or 3rd party plugins, allowing you to add a description and whether the cookie is required for the website to operate. If the cookie is required, Cookillian will allow it to be set regardless if the visitor has opted in or out. Using a shortcode, a full description of the cookies used by the website can be presented to the visitor to assist with compliance and/or privacy notices.

If the visitor allows for cookies (either through opt in or a visitor outside the countries specified), then additional Javascript can be included at the website's header and/or footer, allowing the inclusion of, for example, Google Analytics. This allows for better control over 3rd party cookies.

With the included statistics, you can see how many visitors have decided to opt in, out or ignore the cookie alert per country, for each month of the year.

= Features =

* Selective filtering/alerts based on the visitor's originating country
* Automatic alerts, or manually displayed using a WordPress filter or shortcode
* Fully customizable alert/information about cookies
* Optional JavaScript loading if cookies are permitted
* Support for Cookie-based PHP Sessions
* Define cookies that are required for the operation of the website
* Automatic collection of cookies used by the website
* Automatic rendering of details about cookies using shortcodes
* Support for [geoPlugin](http://www.geoplugin.com) geolocation service
* Support for [CloudFlare](http://www.cloudflare) geolocation HTTP headers
* Exposed PHP/WordPress Filters and JavaScript variables regarding cookie permissions, opt-in and opt-out for complex sites
* Statistics to track the impact of the EC Directive

== Installation ==

1. Upload the contents of the ZIP file to the `/wp-content/plugins/` directory
1. Activate the plugin through the __Plugins__ menu in WordPress
1. Access the plugin via __Settings__ -> __Cookies__ menu

Additional help is provided via the _Help_ tabs within the plugin

= Requirements =

* PHP version _5.3_ or better
* WordPress version _3.3_ or better

A browser with Javascript enabled is highly recommended. This plugin will ___NOT___ work
with PHP versions older than 5.3.

== Screenshots ==

1. The default alert dispalyed to visitors
2. Statistics to track compliance impact

== Changelog ==

= 1.0.4 (11 May 2012) =
Changed: Corrected mistake in Readme title

= 1.0.3 (9 May 2012) =
__Added:__ PHP/WordPress filters for opt-in, opt-out and blocked/unblocked cookies status
Changed: Updated the vendor libraries

= 1.0.1 (5 May 2012) =

First Release

== Frequently Asked Questions ==

= Help, it's broken! What do I do now? =

If something does not appear to be working as it should, [search the forum](http://wordpress.org/tags/cookillian) or [write a new topic](http://wordpress.org/tags/cookillian#postform) that describes the problem(s) you are experiencing.

= I have a PHP version older than 5.3, can I make it work? =

This plugin makes use of many features introduced in PHP version 5.3, and an attempt to make it work with older versions of PHP is equivalent to a complete rewrtie of the plugin.

Many hosting providers are already providing PHP 5.3+ to their customers, and others allow for an easy upgrade. Also consider that PHP 5.3 was first released in 2009 and fixes many bugs and security issues, and support for PHP 5.2 was [stopped in 2010](http://www.php.net/archive/2010.php#id2010-12-09-1).

= How can I upgrade to PHP version 5.3? =

This depends. If you have your very own server, then this is Operating System specific and you will need to consult its documentation on how to upgrade. Most commonly in Linux environments this consists of running `apt-get`, `yum` or `pacman` from the CLI.

If you are using a web hosting provider, then you need to contact the provider regarding this. Some can move your website to a different server with a newer version of PHP 5.3, while others make it as simple as adding/changing a line in the `.htaccess` file or a setting in the control panel. For example:

* 1&1 Webhosting: Add `AddType x-mapp-php6 .php` to the `.htaccess` file
* OVH: Add `SetEnv PHP_VER 5_3` or `SetEnv PHP_VER 5_TEST` to the `.htaccess` file
* GoDaddy Linux Shared Hosting: Add `AddHandler x-httpd-php5-3 .php` to the `.htaccess` file
* GoDaddy 4GH Hosting: Visit GoDaddy's __Hosting Control Center__ -> __Content__ -> __Programming Languages__
* HostGator: Add `Action application/x-hg-php53 /cgi-sys/php53` and `AddHandler application/x-hg-php53 .php` to the `.htaccess` file
* Bluehost: Add `AddHandler application/x-httpd-php53 .php` to the `.htaccess` file (Note: may require a support request/ticket to enable PHP 5.3)
