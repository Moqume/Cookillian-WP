<?php

/*
 * Copyright (c) 2012 Mike Green <myatus@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Myatu\WordPress\Cookillian;

use Pf4wp\Common\Cookies;
use Pf4wp\Common\Helpers;
use Pf4wp\Help\ContextHelp;
use Pf4wp\Notification\AdminNotice;

class Main extends \Pf4wp\WordpressPlugin
{
    const OPTIN_ID    = '_opt_in';
    const OPTOUT_ID   = '_opt_out';
    const RESP_ID     = '_resp';
    const COOKIE_LIFE = '+3 years';

    public $short_name = 'cookillian';

    // Non-persistent cache
    protected $np_cache = array();

    // Flag to indicate (to JS) whether cookies are blocked
    protected $cookies_blocked;

    // Default options
    protected $default_options = array(
        'geo_service'        => 'geoplugin',
        'cookie_groups'      => array('Unknown'),
        'auto_add_cookies'   => true,
        'countries'          => array(),
        'known_cookies'      => array(),
        'alert_show'         => 'auto',
        'alert_content_type' => 'default',
        'alert_content'      => "This site uses cookies to store information on your computer. Some of these cookies are essential to make our site work and others help us to improve by giving us some insight into how the site is being used. <a href=\"#\">More information</a>. \r\n\r\nBy using our site you accept the terms of our <a href=\"#\">Privacy Policy</a>. \r\n",
        'alert_heading'      => 'Information regarding cookies',
        'alert_ok'           => 'Yes, I\'m happy with this',
        'alert_no'           => 'No! Only store this answer, but nothing else',
        'required_text'      => 'This cookie is required for the operation of this website.',
    );

    /** -------------- HELPERS -------------- */

    /**
     * Obtains a list of countries
     *
     * @param bool $mark_selected Adds a 'selected' value to the results, based on countries options
     * @return array Array containing contries in key/value pairs, whey key is the 2-digit country code and vaue the country name
     */
    protected function getCountries($mark_selected = false)
    {
        if (!isset($this->np_cache['countries'])) {
            $this->np_cache['countries'] = array(); // Initialize

            $file = $this->getPluginDir() . static::RESOURCES_DIR . 'countries.txt';

            // Read the 'countries.txt' file provided by ISO
            if (@is_file($file) && @is_readable($file)) {
                $fh = @fopen($file, 'r');

                if ($fh) {
                    while (($line = fgets($fh)) !== false) {
                        $line = trim($line);

                        if (!empty($line) && $line[0] !== '#') {
                            list($country, $code) = explode(';', $line, 2);
                            $this->np_cache['countries'][$code] = array('country' => mb_convert_case($country, MB_CASE_TITLE));
                        }
                    }
                }

                fclose($fh);
            }
        }

        $countries = $this->np_cache['countries'];

        // Mark the countries in the list as selected based on the options, if required
        if ($mark_selected) {
            // Fetch sorted list of selected countries
            $selected_countries = $this->options->countries;
            arsort($selected_countries);

            foreach ($selected_countries as $selected_country) {
                if (isset($countries[$selected_country])) {
                    // Grab the selected country from the countries list and mark it as such
                    $selected = $countries[$selected_country];
                    $selected['selected'] = true;

                    // Move the selected country to the top of the list
                    unset($countries[$selected_country]);
                    $countries = array($selected_country => $selected) + $countries;
                }
            }
        }

        return $countries;
    }

    /**
     * Obtains the country code using a geo location service, based on an IP
     *
     * @param string $ip The IP address
     * @return string The 2-letter country code (empty if unable to determine)
     */
    public function getCountryCode($ip)
    {
        if (empty($ip))
            return '';

        $cache_id = $this->short_name . '_ip_' . md5($ip);

        // First attempt to fetch from local NP cache (fastest)
        if (isset($this->np_cache[$cache_id]))
            return $this->np_cache[$cache_id];

        // Next attempt to fetch from transient cache (2nd fastest)
        $result = get_site_transient($cache_id);

        if ($result !== false) {
            $this->np_cache[$cache_id] = $result; // Save to local NP cache
            return $result;
        }

        // Nothing in cache, so start working on it
        switch ($this->options->geo_service) {
            case 'geoplugin' :
                $remote = wp_remote_get('http://www.geoplugin.net/php.gp?ip=' . $ip);

                if (!is_wp_error($remote)) {
                    try {
                        $r = @unserialize($remote[body]);

                        $result = isset($r['geoplugin_countryCode']) ? $r['geoplugin_countryCode'] : '';
                    } catch (\Exception $e) {
                        $result = '';
                    }
                }
                break;

            case 'cloudflare' :
                $result = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : '';
                break;
        }

        // Ensure it's an empty string if no valid country was found (for type check when retrieving the transient)
        if (empty($result) || $result == 'XX')
            $result = '';

        // Save into caches
        set_site_transient($cache_id, $result, 3600);   // One day
        $this->np_cache[$cache_id] = $result;           // Non-persistent

        return $result;
    }

    /**
     * Obtains the remote IP of the visitor
     *
     * @return string
     */
    public function getRemoteIP()
    {
        // CloudFlare
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
            return trim($_SERVER['HTTP_CF_CONNECTING_IP']);

        // Proxy type 1
        if (isset($_SERVER['HTTP_CLIENT_IP']))
            return trim($_SERVER['HTTP_CLIENT_IP']);

        // Proxy type 2
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $proxies = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'], 2);
            return trim($proxies[0]);
        }

        // Good 'ol "remote_addr"
        if (isset($_SERVER['REMOTE_ADDR']))
            return trim($_SERVER['REMOTE_ADDR']);

        // I give up ...
        return '';
    }

    /**
     * Cookie handler
     *
     * This checks if the visitor is from a specified country, adds unknown cookies
     * to the plugin cookie database and removes all cookies with the exception of
     * those marked as required.
     *
     * @return bool Returns `true` if cookies are blocked
     */
    public function handleCookies()
    {
        /* Don't handle any cookies if:
         * - we're in Admin (except doing Ajax),
         * - when someone's logged in or
         * - the visitor opted in to recive cookies
         */
        if ($this->optedIn() || (is_admin() && !Helpers::doingAjax()) || is_user_logged_in())
            return false;

        // If the user has opted out of cookies, we skip the country check
        if (!$this->optedOut()) {
            // Check where the visitor is from and continue if from one selected in options
            $countries = $this->options->countries;

            if (!empty($countries)) {
                $remote_country = $this->getCountryCode($this->getRemoteIP());

                // We're done if the visitor isn't in one of the selected countries
                if (!in_array($remote_country, $countries))
                    return false;
            }
        }

        // If we reach this point, cookies will be deleted based on their settings.
        $new_cookies  = array();
        $session_name = session_name();

        // Check if PHP sessions are based on cookies
        if (ini_get('session.use_cookies')) {
            // Ensure we have PHP session cookie name in the known cookies
            if (!$this->isKnownCookie($session_name, $is_required)) {
                // Not in known cookies, add it
                $new_cookies[$session_name] = array(
                    'desc'     => 'PHP Session',
                    'group'    => 'PHP',
                    'required' => $this->options->php_sessions_required,
                );
            } elseif ($is_required != $this->options->php_sessions_required) {
                // It's in known cookies, but requirement does not match settings
                $known_cookies = $this->options->known_cookies;

                $known_cookies[$session_name]['required'] = $this->options->php_sessions_required;

                // (direct assignment is not possible)
                $this->options->known_cookies = $known_cookies;
            }

            // If sessions aren't required and there's one open, destroy it
            if (!$this->options->php_sessions_required && session_id()) {
                // Destroy active session
                Cookies::delete($session_name);

                @session_destroy();
            }

            // Prevent session to be re-opened with cookies
            @ini_set('session.use_cookies', '0');
        }

        // Iterate cookies, add any unknown ones to the database and remove cookies that aren't required
        foreach ($_COOKIE as $cookie_name => $cookie_value) {
            // Skip the opt-out or session cookie
            if ($cookie_name == $this->short_name . static::OPTOUT_ID || $cookie_name == $session_name)
                continue;

            // Find out if it's a known cookie, and if it's required
            $is_known = $this->isKnownCookie($cookie_name, $is_required);

            // Add the cookie to the list of "known" cookies, if it's new
            if (!$is_known && $this->options->auto_add_cookies) {
                $new_cookies[$cookie_name] = array(
                    'desc'  => '',
                    'group' => 'Unknown',
                );
            }

            // If the cookie is not required, delete it
            if (!$is_required)
                Cookies::delete($cookie_name);
        }

        // Update known cookies with what we've found, if anything
        if (!empty($new_cookies))
            $this->options->known_cookies = array_merge($this->options->known_cookies, $new_cookies);

        return true;
    }

    /**
     * Returns whether a cookie is known, and whether it is required
     *
     * @param string $cookie_name The name of the cookie
     * @param bool $required Referenced variable that will be set to `true` if the cookie is required, `false` otherwise
     * @return bool Return `true` if the cookie is already know, `false` otherwise
     */
    public function isKnownCookie($cookie_name, &$required = false)
    {
        $required = false;
        $result   = false;

        // Peform a simple check on stored known cookies first
        $known_cookies = $this->options->known_cookies;

        if (!array_key_exists($cookie_name, $known_cookies)) {
            // Simple check found nothing, see if we need to perform a heavier check using wildcards
            foreach ($known_cookies as $known_cookie_name => $known_cookie_value) {
                if (strpos($known_cookie_name, '*') !== false || strpos($known_cookie_name, '?') !== false) {
                    $pattern = '/^' . strtr($known_cookie_name, array('*' => '.+', '?' => '.')) . '$/';

                    if (preg_match($pattern, $cookie_name)) {
                        $required = (isset($known_cookie_value['required']) && !empty($known_cookie_value['required']));
                        $result   = true;
                        break;
                    }
                }
            }
        } else {
            // A simple check found a match, determine if it's a reuired cookie
            $required = (isset($known_cookies[$cookie_name]['required']) && !empty($known_cookies[$cookie_name]['required']));
            $result   = true;
        }

        return $result;
    }

    /**
     * Returns whether the visitor has opted in
     *
     * @return bool
     */
    public function optedIn()
    {
        return (Cookies::get($this->short_name . static::OPTIN_ID, false) !== false);
    }

    /**
     * Returns whether the visitor has opted out
     *
     * @return bool
     */
    public function optedOut()
    {
        return (Cookies::get($this->short_name . static::OPTOUT_ID, false) !== false);
    }

    /**
     * Processes a response to the question of permitting cookies
     */
    public function processResponse($answer)
    {
        $opt_in_or_out = '';

        switch ($answer) {
            case 1 :
                // Opt In
                $opt_in_or_out = $this->short_name . static::OPTIN_ID;
                break;

            case 0 :
                // Opt Out
                $opt_in_or_out = $this->short_name . static::OPTOUT_ID;
                break;
        }

        // User tried to be funny and we lack humor
        if (empty($opt_in_or_out))
            return;

        // Set a cookie with the visitor's response
        Cookies::set($this->short_name . static::OPTIN_ID, 1, strtotime(static::COOKIE_LIFE), true, false, '/');

        // And send the visitor back to where they were
        wp_redirect($_SERVER['HTTP_REFERER']);

        die(); // That's all folks!
    }

    /**
     * Helper to strip slashes from saved options
     *
     * @param string|array The value of to strip the slashes from
     * @return string|array The scrubbed value (of same type as input value)
     */
    protected function deepStripSlashes($val)
    {
        return is_array($val) ? array_map(array($this, 'deepStripSlashes'), $val) : stripslashes($val);
    }

    /** -------------- EVENTS -------------- */

    public function onActivation()
    {
        // Pre-fill the 'Known Cookies' with those created by WordPress
        $known_cookies = $this->options->known_cookies;

        if (empty($known_cookie)) {
            $this->options->known_cookies = array(
                'wordpress_*' => array(
                    'desc'  => 'This cookie stores WordPress authentication details.',
                    'group' => 'Wordpress',
                ),
                'wp-settings-*' => array(
                    'desc'  => 'This cookie helps remember your personal preferences within WordPress.',
                    'group' => 'Wordpress',
                ),
            );
        }

        // And pre-fill the countries (United Kingdom)
        $countries = $this->options->countries;

        if (empty($countries))
            $this->options->countries = array('GB');
    }

    /**
     * Register additional actions
     */
    public function onRegisterActions()
    {
        add_action('shutdown', array($this, 'onShutdown'), 99, 0);
        add_filter($this->short_name . '_alert', array($this, 'onFilterAlert'));
        add_shortcode($this->short_name, array($this, 'onShortCode'));

        // Cookies are handled as early as possible here, disabling sessions, etc.
        $this->cookies_blocked = $this->handleCookies();

        // Was there a response to the cookie alert?
        if (isset($_REQUEST[$this->short_name . static::RESP_ID]))
            $this->processResponse((int)$_REQUEST[$this->short_name . static::RESP_ID]);
    }

    /**
     * Event to handle cookies on shutdown
     *
     * @see onRegisterActions()
     */
    public function onShutdown()
    {
        // Cookies are once more handled here, to delete any cookies added after we first handled them
        $this->handleCookies();
    }

    /**
     * Load Admin JS
     */
    public function onAdminScripts()
    {
        list($js_url, $version, $debug) = $this->getResourceUrl();

        wp_enqueue_script($this->getName() . '-admin', $js_url . 'admin' . $debug . '.js', array('jquery'), $version);
    }

    /**
     * Load Admin CSS
     */
    public function onAdminStyles()
    {
        list($css_url, $version, $debug) = $this->getResourceUrl('css');

        wp_enqueue_style($this->getName() . '-admin', $css_url . 'admin' . $debug . '.css', false, $version);
    }

    /**
     * Expose to public side of cookies are blocked
     */
    public function onPublicScripts()
    {
        // JavaScript exposing whether cookies have been blocked and whether the visitor has opted out or in
        printf("<script type=\"text/javascript\">\r\n/* <![CDATA[ */\r\nvar cookillian = {\"blocked_cookies\":%s,\"opted_out\":%s,\"opted_in\":%s,\"_manual\":%s};\r\n/* ]]> */\r\n</script>\r\n",
            ($this->cookies_blocked) ? 'true' : 'false',
            ($this->optedOut()) ? 'true' : 'false',
            ($this->optedIn()) ? 'true' : 'false',
            ($this->options->alert_show == 'manual') ? 'true' : 'false'
        );

        // @see onPublicfooter()
        if ($this->cookies_blocked && !$this->optedOut() && $this->options->alert_content_type == 'default') {
            list($js_url, $version, $debug) = $this->getResourceUrl();

            wp_enqueue_script($this->getName() . '-pub', $js_url . 'pub' . $debug . '.js', array('jquery'), $version);
        }

        // Load custom script header
        if (!$this->cookies_blocked && $this->options->script_header)
            printf("<script type=\"text/javascript\">\r\n/* <![CDATA[ */\r\n%s\r\n/* ]]> */\r\n</script>\r\n", stripslashes($this->options->script_header));
    }

    /**
     * Load Public CSS if cookies are blocked
     */
    public function onPublicStyles()
    {
        // @see onPublicfooter()
        if ($this->cookies_blocked && !$this->optedOut() && $this->options->alert_content_type == 'default') {
            list($css_url, $version, $debug) = $this->getResourceUrl('css');

            wp_enqueue_style($this->getName() . '-pub', $css_url . 'pub' . $debug . '.css', false, $version);
        }
    }

    /**
     * Renders the footer on the public side
     */
    public function onPublicFooter()
    {
        // Render the alert if set to automatically show
        if ($this->options->alert_show == 'auto')
            echo apply_filters('cookillian_alert', '');

        // Load custom script footer
        if (!$this->cookies_blocked && $this->options->script_footer)
            printf("<script type=\"text/javascript\">\r\n/* <![CDATA[ */\r\n%s\r\n/* ]]> */\r\n</script>\r\n", stripslashes($this->options->script_footer));
    }

    /**
     * Filter to obtain the alert to display
     *
     * @param string $original The original text to display (usually empty)
     * @return string The alert to display
     */
    public function onFilterAlert($original)
    {
        $result = $original;

        // If cookies are found to be blocked, and we haven't specifically opted out, we show an alert
        if ($this->cookies_blocked && !$this->optedOut()) {
            if ($this->options->alert_content_type == 'default') {
                // Default alert
                $vars = array(
                    'alert_content' => wpautop(stripslashes($this->options->alert_content)),
                    'alert_heading' => stripslashes($this->options->alert_heading),
                    'alert_ok'      => stripslashes($this->options->alert_ok),
                    'alert_no'      => stripslashes($this->options->alert_no),
                    'response_no'   => add_query_arg(array($this->short_name . static::RESP_ID => 0)),
                    'response_ok'   => add_query_arg(array($this->short_name . static::RESP_ID => 1)),
                    'manual'        => ($this->options->alert_show == 'manual'),
                );

                $result = $this->template->render('ask.html.twig', $vars);
            } else {
                // Custom alert
                $result = stripslashes($this->options->alert_custom_content);
            }
        }

        return $result;
    }

    /**
     * Handles shortcodes
     *
     * Shortcodes:
     *  - alert                 Displays the Cookie Alert, if required
     *  - cookies (group|all)   Displays information about all cookies, or those within a certain group
     */
    public function onShortCode($atts)
    {
        // Singular attributes
        if (count($atts) == 1) {
            if (isset($atts[0]))
                $atts = array_flip($atts);

            // 'alert' or 'alert=...'
            if (isset($atts['alert']))
                return apply_filters('cookillian_alert', '');

            // 'cookies' or 'cookies=...'
            if (isset($atts['cookies'])) {
                $known_cookies = $this->options->known_cookies;
                $cookies       = array();

                if (empty($atts['cookies'])) {
                    // Display all cookies
                    $cookies = $known_cookies;
                } else {
                    // Only display cookies in a certain group
                    $group = strtolower($atts['cookies']);

                    foreach ($known_cookies as $known_cookie_name => $known_cookie_value) {
                        if (strtolower($known_cookie_value['group']) == $group)
                            $cookies[$known_cookie_name] = $known_cookie_value;
                    }
                }

                // Strip slashes
                $cookies = $this->deepStripSlashes($cookies);

                // Sort by group
                uasort($cookies, function($a,$b) { return strcasecmp($a['desc'], $b['desc']); });

                return $this->template->render('cookie_table.html.twig', array(
                    'cookies'       => $cookies,
                    'required_text' => stripslashes($this->options->required_text),
                ));
            }
        }

        return '';
    }


    /**
     * Give the plugin a menu
     */
    public function onBuildMenu()
    {
        $mymenu = new \Pf4wp\Menu\SubHeadMenu($this->getName());
        $mymenu->setHomeTitle(__('Settings', $this->getName()));

        // Add settings menu
        $main_menu = $mymenu->addMenu(__('Cookies'), array($this, 'onSettingsMenu'));
        $main_menu->context_help = new ContextHelp($this, 'settings');

        // Add cookies menu
        $cookie_menu = $mymenu->addSubmenu(__('Cookies', $this->getName()), array($this, 'onCookiesMenu'));
        $cookie_menu->count = count($this->options->known_cookies);
        $cookie_menu->context_help = new ContextHelp($this, 'cookies');

        return $mymenu;
    }

    /**
     * Prepares the Settings menu page
     */
    public function onSettingsMenuLoad()
    {
        if (!empty($_POST) && isset($_POST['_nonce'])) {
            if (!wp_verify_nonce($_POST['_nonce'], 'onSettingsMenu'))
                wp_die(__('You do not have permission to do that [nonce].', $this->getName()));

            // Save
            $this->options->load($_POST, array(
                'auto_add_cookies'      => 'bool',
                'php_sessions_required' => 'bool',
                'geo_service'           => array('in_array', array('geoplugin','cloudflare')),
                'alert_show'            => array('in_array', array('auto', 'manual')),
                'alert_content_type'    => array('in_array', array('default', 'custom')),
                'alert_content'         => 'string',
                'alert_heading'         => 'string',
                'alert_ok'              => 'string',
                'alert_no'              => 'string',
                'alert_custom_content'  => 'string',
                'required_text'         => 'string',
                'script_header'         => 'string',
                'script_footer'         => 'string',
            ));

            $this->options->countries = (isset($_POST['countries'])) ? $_POST['countries'] : array();

            AdminNotice::add(__('Settings have been saved', $this->getName()));
        }
    }

    /**
     * Renders the Settings menu page
     */
    public function onSettingsMenu()
    {
        $geo_services = array(
            'geoplugin' => array(
                'title'     => __('geoPlugin', $this->getName()),
                'checked'   => ($this->options->geo_service == 'geoplugin'),
                'desc'      => __('This service is provided free of charge by <a href="http://www.geoplugin.com/" target="_new" title="geoPlugin for IP geolocation">geoPlugin</a>', $this->getName()),
            ),
            'cloudflare'    => array(
                'title'     => __('CloudFlare', $this->getName()),
                'checked'   => ($this->options->geo_service == 'cloudflare'),
                'desc'      => __('If you use <a href="http://www.cloudflare.com/" target="_new" title="CloudFlare">CloudFlare</a>, this will provide you with free and <u>fast</u> access to IP geolocation', $this->getName()),
            ),
        );

        $vars = array(
            'nonce'                 => wp_nonce_field('onSettingsMenu', '_nonce', true, false),
            'submit_button'         => get_submit_button(),
            'plugin_base_url'       => $this->getPluginUrl(),
            'plugin_name'           => $this->getDisplayName(),
            'plugin_version'        => $this->getVersion(),
            'plugin_home'           => \Pf4wp\Info\PluginInfo::getInfo(false, $this->getPluginBaseName(), 'PluginURI'),
            'countries'             => $this->getCountries(true),
            'geo_services'          => $geo_services,
            'auto_add_cookies'      => $this->options->auto_add_cookies,
            'php_sessions_required' => $this->options->php_sessions_required,
            'alert_show'            => $this->options->alert_show,
            'alert_content_type'    => $this->options->alert_content_type,
            'alert_content'         => stripslashes($this->options->alert_content),
            'alert_heading'         => stripslashes($this->options->alert_heading),
            'alert_ok'              => stripslashes($this->options->alert_ok),
            'alert_no'              => stripslashes($this->options->alert_no),
            'alert_custom_content'  => stripslashes($this->options->alert_custom_content),
            'required_text'         => stripslashes($this->options->required_text),
            'script_header'         => stripslashes($this->options->script_header),
            'script_footer'         => stripslashes($this->options->script_footer),
        );

        $this->template->display('settings.html.twig', $vars);
    }

    /**
     * Prepares the Cookies menu page
     */
    public function onCookiesMenuLoad()
    {
        if (!empty($_POST) && isset($_POST['_nonce'])) {
            if (!wp_verify_nonce($_POST['_nonce'], 'onCookiesMenu'))
                wp_die(__('You do not have permission to do that [nonce].', $this->getName()));

            foreach($_POST['known_cookies'] as $known_cookie_name => $known_cookie_value) {
                // Check if we need to delete this entry or the name is invalid/empty
                if (isset($known_cookie_value['delete']) || !isset($known_cookie_value['name']) || empty($known_cookie_value['name'])) {
                    unset($_POST['known_cookies'][$known_cookie_name]);
                    continue;
                }

                // Grab the name
                $name = $known_cookie_value['name'];

                // If the name has been changed, swap out the original name
                if ($known_cookie_name != $name) {
                    // Scrub the name field
                    unset($known_cookie_value['name']);

                    // Swap the names (create a new entry under new name, delete the entry under old name)
                    $_POST['known_cookies'][$name] = $known_cookie_value;
                    unset($_POST['known_cookies'][$known_cookie_name]);
                } else {
                    // Just scrub the 'name' field
                    unset($_POST['known_cookies'][$name]['name']);
                }

                // Ensure we have a group name
                if (empty($known_cookie_value['group']))
                    $_POST['known_cookies'][$name]['group'] = 'Unspecified';
            }

            // Save
            $this->options->known_cookies = $_POST['known_cookies'];

            AdminNotice::add(__('Cookies have been saved', $this->getName()));
        }
    }

    /**
     * Renders the Cookie menu page
     */
    public function onCookiesMenu()
    {
        $known_cookies = $this->deepStripSlashes($this->options->known_cookies);

        $vars = array(
            'nonce'              => wp_nonce_field('onCookiesMenu', '_nonce', true, false),
            'submit_button'      => get_submit_button(),
            'known_cookies'      => $known_cookies,
            'known_cookie_count' => count($known_cookies),
            'is_rtl'             => is_rtl(),
        );

        $this->template->display('cookies.html.twig', $vars);
    }

}