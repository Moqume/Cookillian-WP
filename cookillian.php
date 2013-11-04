<?php
/*
Plugin Name: Cookillian
Plugin URI: http://myatus.com/projects/cookillian/
Description: Provides extensible support for EU/UK compliance of the EC Cookie Directive (2009/136/EC), based on a visitor's location.
Version: 1.2
Author: Mike Green (Myatu)
Author URI: http://www.myatus.com/
*/

/* Direct call check */

if (!function_exists('add_action')) return;

/* Bootstrap */

$_pf4wp_file = __FILE__;
$_pf4wp_version_check_wp = '3.3'; // Min version for WP

require dirname(__FILE__).'/vendor/pf4wp/lib/bootstrap.php'; // use dirname()!

if (!isset($_pf4wp_check_pass) || !isset($_pf4wp_ucl) || !$_pf4wp_check_pass) return;

/* Register Namespaces */

$_pf4wp_ucl->registerNamespaces(array(
    'Symfony\\Component\\ClassLoader'   => __DIR__.'/vendor/pf4wp/lib/vendor',
    'Pf4wp'                             => __DIR__.'/vendor/pf4wp/lib',
));
$_pf4wp_ucl->registerPrefixes(array(
    'Twig_' => __DIR__.'/vendor/Twig/lib',
));
$_pf4wp_ucl->registerNamespaceFallbacks(array(
    __DIR__.'/app',
));
$_pf4wp_ucl->register();

/* Fire her up, Scotty! */

call_user_func('Myatu\\WordPress\\Cookillian\\Main::register', __FILE__);
