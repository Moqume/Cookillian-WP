<?php
/**
 * API helpers for exposing Cookillian filters as regular functions
 */

use \Myatu\WordPress\Cookillian\Main;

if (!class_exists('\Myatu\WordPress\Cookillian\Main'))
    return;

if (!function_exists('cookillian_get_alert_block')) :
function cookillian_get_alert_block($original = '')
{
    return Main::instance()->onFilterAlert($original);
}
endif;

if (!function_exists('cookillian_insert_alert_block')) :
function cookillian_insert_alert_block($original = '')
{
    echo Main::instance()->onFilterAlert($original);
}
endif;

if (!function_exists('cookillian_blocked_cookies')) :
function cookillian_blocked_cookies()
{
    return Main::instance()->onFilterBlockedCookies(false);
}
endif;

if (!function_exists('cookillian_opted_in')) :
function cookillian_opted_in()
{
    return Main::instance()->onFilterOptedIn(false);
}
endif;

if (!function_exists('cookillian_opted_out')) :
function cookillian_opted_out()
{
    return Main::instance()->onFilterOptedOut(false);
}
endif;

// ---

if (!function_exists('cookillian_deleted_cookies')) :
function cookillian_deleted_cookies()
{
    return Main::instance()->hasDeletedCookies();
}
endif;

// @since 1.0.23
if (!function_exists('cookillian_implied_consent')) :
function cookillian_implied_consent()
{
    return Main::instance()->hasImpliedConsent();
}
endif;

// @since 1.0.26
if (!function_exists('cookillian_do_delete_cookies')) :
function cookillian_do_delete_cookies()
{
    return Main::instance()->deleteCookies();
}
endif;

// @since 1.0.26
if (!function_exists('cookillian_do_opt_in')) :
function cookillian_do_opt_in()
{
    return Main::instance()->processResponse(1, false);
}
endif;

// @since 1.0.26
if (!function_exists('cookillian_do_opt_out')) :
function cookillian_do_opt_out()
{
    return Main::instance()->processResponse(0, false);
}
endif;

// @since 1.0.26
if (!function_exists('cookillian_do_reset_optinout')) :
function cookillian_do_reset_optinout()
{
    return Main::instance()->processResponse(2, false);
}
endif;
