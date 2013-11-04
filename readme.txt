=== Cookillian ===
Contributors: Myatu
Donate link: http://pledgie.com/campaigns/16906
Tags: cookie, ec, europe, uk, cookie law, directive, eu cookie directive, filter, block,
Requires at least: 3.3
Tested up to: 3.7.1
Stable tag: 1.2
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Provides extensible support for EU/UK compliance of the EC Cookie Directive (2009/136/EC), based on a visitor's location.

== Description ==

_Cookillian_ makes it easier to comply with the EC Cookie Directive (EU Cookie Law), which affects the United Kingdom on May 25th 2012 and other European countries.

Cookillian will automatically detect if a visitor is located in one of the countries defined by you - likely the countries affected by the EC Cookie Direcitve - and will optionally disable any cookies that are set from within WordPress or a 3rd party plugin. The user will then be presented with an fully customizable alert about cookies, and given the option to opt in or out of using cookies.

It will also collect basic information about any cookies set from within WordPress or 3rd party plugins, allowing you to add a description and whether the cookie is required for the website to operate. If the cookie is required, Cookillian will allow it to be set regardless if the visitor has opted in or out. Using a shortcode, a full description of the cookies used by the website can be presented to the visitor to assist with compliance and/or privacy notices.

If the visitor allows for cookies (either through opt in or a visitor outside the countries specified), then additional Javascript can be included at the website's header and/or footer, allowing the inclusion of, for example, Google Analytics. This allows for better control over 3rd party cookies.

With the included statistics, you can see how many visitors have decided to opt in, out or ignore the cookie alert per country, for each month of the year.

= Features =

* Selective alerts based on the visitor's originating country
* Support for both "Explicit" and "Implied" consent
* Attempts to remove cookies either before or after a visitor has opted out (selectable)
* Optional JavaScript loading if cookies are permitted
* Automatic alerts, or manually displayed using a WordPress filter, API or shortcode
* Fully customizable alert text and styling
* Support for Cookie-based PHP Sessions
* Define cookies that are required for the operation of the website
* Automatic collection of cookies used by the website
* Automatic rendering of details about cookies using shortcodes
* Support for [geoPlugin](http://www.geoplugin.com) geolocation service
* Support for [CloudFlare](http://www.cloudflare.com) geolocation HTTP headers
* Support for [MaxMind](http://www.maxmind.com) geolocation database or Apache module/NginX GeoIP module
* Backup geoloaction service, should the default geolocation service fail
* JavaScript and PHP API
* Statistics to track the impact of the EC Directive
* Debug mode for web development and testing
* Supports caching plugins, such as WP Super Cache
* Support for the DNT/Do Not Track browser headers (http://donottrack.us)
* Dashboard widget for quick overview of the statistics, and if new cookies have been detected

Visit the [official website](http://myatus.com/projects/cookillian/) for more details.

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

= 1.2 (November 4 2013) =
* Fixed: WordPress 'wordpress_test_cookie' as well as 'wordpress_*' set to a required cookie in all circumstances, to avoid accidental lock-outs
* Fixed: TLD not always correctly determined, causing a the cookie opt-in/out cookie not to be set
* Changed: Removed AJAX NONCE check at the public side

= 1.1.18 (June 25 2012) =
* __Added:__ Option to periodically scrub cookies, to help capture JavaScript cookies
* Fixed: Optional JavaScript was not called when cookies were still permitted ("delete after" option)
* Changed: Additional web-crawler checks
* Changed: Added AJAX handlers to default alert opt-in and out buttons, where supported
* Changed: Implemented Noah Sloan's writeCapture to accept a wider range of legacy Javascript (Google AdSense)

= 1.1.13 (June 10 2012) =
* __Added:__ Option to limit the amount of new cookies Cookillian will detect (30 by default)
* Fixed: Issue where wp_print_script action was called more than once, causing Ajax code to override objects (Pf4wp)
* Fixed: Minor bug in statistics, where the most recent entry could not be collapsed
* Fixed: Issue where Firefox/Mozilla prefetch "feature" interfered with implied consent detection
* Fixed: On PHP installations where mb_ functions are not available, fall back to a different method
* Changed: Detected cookies now include the User Agent details, which is displayed on the __Cookies__ page when _Debug Mode_ is enabled

= 1.1.7 (June 2 2012) =
* __Added:__ The option for asynchronous AJAX initialization
* __Added:__ Two new JS API events (_cookillian_load_ and _cookillian_ready_) and JS API function (_insertString()_)
* __Added:__ Collapsible months on the __Statistics__ page
* Fixed: No longer permit statistics to be added beyond the count of shown alerts
* Fixed: Regression bug that prevented the "More..." from displaying on Dashboard widget
* Fixed: Implied consent not always honored due to incorrect "true_referrer" variable sent back

= 1.1 (May 31 2012) =
* __Added:__ Support for "Implied Consent"
* __Added:__ Support for caching plugins, such as WP Super Cache and W3 Total Cache
* __Added:__ Option to provide custom styling for the alert from the menu
* __Added:__ Option to delete cookies before or after the visitor has opted out
* __Added:__ Option to adjust geolocation cache time, as well as clear it
* __Added:__ Backup geolocation service, provided by [freegeoip.net](http://www.freegeoip.net), should the default geoloaction service fail.
* __Added:__ Export option (CSV) for statistics
* __Added:__ Dashboard widget, giving quick overview of newly added cookies and top statistics
* Fixed: Fixed a bug that overwrote existing cookies from the __Cookies__ listing
* Fixed: Empty country name on "Unknown" country in statistics
* Fixed: If a generic "EU" or "AP" is retured by the geolocation service, determine if selected countries falls within that region
* Changed: On JavaScript-enabled browsers, the _Delete_ box has been replaced by a _Remove_ button in the __Cookies__ listing
* Changed: Made the _noscript_ tag optional, to accomodate caching plugins
* Changed: Shortcode for listing cookies now accepts multiple groups, as well as exclusion

= 1.0.17.1 (22 May 2012) =
* Fixed: Fixed bug that caused the plugin from operating on certain systems (apache_note())

= 1.0.17 (22 May 2012) =
* __Added:__ Support for [MaxMind](http://www.maxmind.com) geolocation database or Apache module/NginX GeoIP module
* __Added:__ Option to display an alert if the visitor's country could not be determined
* __Added:__ Option for DNT/Do Not Track browser headers (http://donottrack.us)
* Fixed: Type-check prevented undetermined countries to remain in cache for 24 hours
* Fixed: IP geolocation data for geoPlugin was incorrectly unserialized
* Changed: Alert is now only displayed to logged in users in "Debug Mode"
* Changed: All EC member states are selected by default on new installations

= 1.0.10 (16 May 2012) =
* Changed: Updated Pf4wp vendor library, adding debug information in footer
* Fixed: Issues with Twig vendor library, resulting in _Twig_Error_Runtime_ errors.

= 1.0.8 (15 May 2012) =
* __Added:__ Debug mode, to allow for easier fault finding and assist with designing a website.
* __Added:__ URI to remove opt-in or opt-out status (`?cookillian_resp=2`)
* Changed: Wrapping of optional JavaScript in `<script>` tags is now optional (enabled by default)
* Fixed: Cookies were not automatically detected for visitors outside of the selected countries

= 1.0.4 (11 May 2012) =
* Changed: Corrected mistake in Readme title

= 1.0.3 (9 May 2012) =
* __Added:__ PHP/WordPress filters for opt-in, opt-out and blocked/unblocked cookies status
* Changed: Updated the vendor libraries

= 1.0.1 (5 May 2012) =

* First Release

== Frequently Asked Questions ==

= Help, it's broken! What do I do now? =

If something does not appear to be working as it should, [search the support forum](http://wordpress.org/support/plugin/cookillian) or write a new topic that describes the problem(s) you are experiencing. I will do my best to provide a solution as soon as possible.

= I have a PHP version older than 5.3, can I make it work? =

This plugin makes use of many features introduced in PHP version 5.3, and an attempt to make it work with older versions of PHP is equivalent to a complete rewrtie of the plugin.

Many hosting providers are already providing PHP 5.3+ to their customers, and others allow for an easy upgrade. Also consider that PHP 5.3 was first released in 2009 and fixes many bugs and security issues, and support for PHP 5.2 was [stopped in 2010](http://www.php.net/archive/2010.php#id2010-12-09-1).

= How can I upgrade to PHP version 5.3? =

This depends. If you have your very own server, then this is Operating System specific and you will need to consult its documentation on how to upgrade. Most commonly in Linux environments this consists of running `apt-get`, `yum` or `pacman` from the CLI.

If you are using a web hosting provider, then you need to contact the provider regarding this. Some can move your website to a different server with a newer version of PHP 5.3, while others make it as simple as adding/changing a line in the `.htaccess` file or a setting in the control panel. For example:

* 1&1 Webhosting: Add `AddHandler x-mapp-php6 .php` to the `.htaccess` file
* OVH: Add `SetEnv PHP_VER 5_3` or `SetEnv PHP_VER 5_TEST` to the `.htaccess` file
* GoDaddy Linux Shared Hosting: Add `AddHandler x-httpd-php5-3 .php` to the `.htaccess` file
* GoDaddy 4GH Hosting: Visit GoDaddy's __Hosting Control Center__ -> __Content__ -> __Programming Languages__
* HostGator: Add `Action application/x-hg-php53 /cgi-sys/php53` and `AddHandler application/x-hg-php53 .php` to the `.htaccess` file
* Bluehost: Add `AddHandler application/x-httpd-php53 .php` to the `.htaccess` file (Note: may require a support request/ticket to enable PHP 5.3)

= Will this plugin make my website entirely compliant? =

The plugin is to assist with compliance, but it may not be a full-stop solution.

For example, this plugin will stop WordPress and any other WordPress plugins you've installed from setting a cookie. But, if there's Javascript used on your website, they may still set cookies that are beyond the control of Cookillian. Google Analytics is probably the most common one, but other things like _Share on Facebook_ or _Share on Twitter_ buttons could set their own cookies.

That's why there's the option within the plugin to include JavaScript in the header/footer if the visitor has agreed to receiving cookies - you'd need to remove that JavaScript from your website, and add it to the plugin option instead.

Cookillian will also list which cookies it has detected (including ones set by JavaScript). There are also extensions for browsers that will help you see which cookies have managed to get past Cookillian. Google Chrome users can use the _Developer Tools_ from the Menu bar as well.

If you have any cookie that are required for your website to operate, ie., a cookie that stores products placed in a shopping cart, you can set these in the plugin's __Cookies__ page as well.

= The alert has disappeared after I clicked "No", how do I get it back? =

You can reset your preference by adding `?cookillian_resp=2` to any URL of your website, such as `www.example.co.uk/?cookillian_resp=2`. Naturally, you can add this as a link on, for example, the Privacy Policy page to make it easier for visitors.

= How do I know if it is working? =

On the __Settings__ page, under the heading __Advanced Options__ near the bottom, you have the option to enable _Debug Mode_. For logged-in users, this will cause the alert to be displayed at all times, which allows you to see where it will be located.

Enabling the _Debug Mode_ also provides you with extra information when using the JavaScript console. The console can be viewed with [Firebug](http://getfirebug.com/), or the browser's _Developer Tools_.

= The alert is not displaying at all, help! =

The alert is not shown if:

* The visitor is logged in as a WordPress user (with any role), or
* The visitor is not in one of the defined countries, or
* The visitor already explicitly opted in/out, or
* The browser support the "Do Not Track" [(see donotrack.us)](http://donottrack.us) option, and enabled it.

If the alert is still not being displayed, enable the _Debug Mode_ as described above.

= How do I change where the alert is displayed? =

First you need to set __Show Alert__ to _Manually_ on the __Settings__ page. In its simplest form, you can use a WordPress shortcode `[cookillian alert]` in a post or page, which will be replaced by the alert if neccesary.

For slightly more complex use, you insert `<?php cookillian_insert_alert_block(); ?>` in the desired location of your theme.

= How do I change the appearance of the alert? =

You can use your own CSS styling through by choosing _Custom_ for the _Alert Styling_ on the __Settings__ page. The alert is wrapped in a `.cookillian-alert` class (also when added manually), providing the background and border colors. The alert heading is in an `.alert-heading` class and the Yes and No buttons in `.btn-ok` and `.btn-no` respectively. If your CSS styling does not appear, you may need to add `!important` to your styling.

= When I click on "Privacy Policy", nothing happens =

On the __Settings__ page, you will need to modify the __Alert Text__ by replacing the hash sign (#) within the `<a href="#">` HTML tags to the actual URL of your Privacy Policy (and the "More Information" link).

= I'm using a caching plugin and after a while the alert stops showing. Why? =

Cookillian uses a security token for its AJAX requests, which are valid for up to 24 hours. If a page is cached beyond this time, the security token (stored on the cached page) will be invalid and Cookillian can no longer perform AJAX requests. For this reason, it is recommended to cache pages for less than 24 hours.

WP Super Cache includes a _Garbage Collector_, checking cached pages at set intervals for cached pages that have expired. It has a minor issue, where the Garbage Collector will be reset when saving other settings, so you may have to double-check the Garbage Collector is still called at the correct intervals.

= How can I translate Cookillian to my own language? =

A generated .PO (.POT) file called `default.po` is included in the plugin's resources directory, generally `wp-content/plugins/cookillian/resources/l10n`. It can be translated using tools such as POEdit or manually in a text editor. Simply save the translated .PO and generated .MO file using the locale code (ie., `nl_NL.mo`) within the same directory, and Cookillian will automatically use it if the WordPress language is set to that locale.

If you wish to share the translations with other users of Cookillian, feel free to e-mail the translation to hello@myatus.com and I'll be happy to include it with the next release, along with a credit by-line for your hard work.
