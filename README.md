Cookillian
==========

_Cookillian_ makes it easier to comply with the EC Cookie Directive, which affects the United Kingdom on May 25th 2012 and other European countries.

Cookillian will automatically detect if a visitor is located in one of the countries defined by you - likely the countries affected by the EC Cookie Direcitve - and will automatically disable any cookies that are set from within WordPress or a 3rd party plugin. The user will then be presented with an fully customizable alert about cookies, and given the option to opt in or out of using cookies.

It will also collect basic information about any cookies set from within WordPress or 3rd party plugins, allowing you to add a description and whether the cookie is required for the website to operate. If the cookie is required, Cookillian will allow it to be set regardless if the visitor has opted in or out. Using a shortcode, a full description of the cookies used by the website can be presented to the visitor to assist with compliance and/or privacy notices.

If the visitor allows for cookies (either through opt in or a visitor outside the countries specified), then additional Javascript can be included at the website's header and/or footer, allowing the inclusion of, for example, Google Analytics. This allows for better control over 3rd party cookies.

With the included statistics, you can see how many visitors have decided to opt in, out or ignore the cookie alert per country, for each month of the year.

[![Click here to lend your support to: Myatu's OSS Development and make a donation at www.pledgie.com !](http://www.pledgie.com/campaigns/16906.png?skin_name=chrome)](http://pledgie.com/campaigns/16906)

Features
--------

* Selective filtering/alerts based on the visitor's originating country
* Automatic alerts, or manually displayed using a WordPress filter or shortcode
* Fully customizable alert/information about cookies
* Optional JavaScript loading if cookies are permitted
* Support for Cookie-based PHP Sessions
* Define cookies that are required for the operation of the website
* Automatic collection of cookies used by the website
* Automatic rendering of details about cookies using shortcodes
* Support for [geoPlugin](http://www.geoplugin.com) geoloction service
* Support for [CloudFlare](http://www.cloudflare) geolocation HTTP headers
* Support for [MaxMind](http://www.maxmind.com) geolocation database or Apache module/NginX GeoIP module
* Exposed JavaScript variables regarding cookie permissions, opt-in and opt-out for complex sites
* Statistics to track the impact of the EC Directive
* Support for the DNT/Do Not Track browser headers (http://donottrack.us)

License
-------

[GNU GPL version 3](http://www.gnu.org/licenses/gpl-3.0.txt)

Requirements
------------

* PHP version _5.3_ or better
* WordPress version _3.3_ or better

A browser with Javascript enabled is highly recommended. This plugin will ___NOT___ work
with PHP versions older than 5.3.
