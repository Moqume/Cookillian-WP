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
use Pf4wp\Notification\AdminNotice;

class Main extends \Pf4wp\WordpressPlugin
{
    const OPTIN_ID = '_option';

    // Non-persistent cache
    protected $np_cache = array();

    protected $default_options = array(
        'geo_service'   => 'geoplugin',
        'cookie_groups' => array('Unknown'),
        'known_cookies' => array(),
        'countries'     => array(),
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
                    $r = @unserialize($remote[body]);

                    $result = isset($r['geoplugin_countryCode']) ? $r['geoplugin_countryCode'] : '';
                }
                break;

            case 'cloudflare' :
                $result = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : '';
                break;
        }

        // Ensure it's an empty string if no valid country was found (for type check when retrieving transient)
        if (empty($result) || $result == 'XX')
            $result = '';

        // Save into caches
        set_site_transient($cache_id, $result, 3600);
        $this->np_cache[$cache_id] = $result;

        return $result;
    }

    /**
     * Obtains the remote IP of the visitor
     *
     * @return string
     */
    public function getRemoteIP()
    {
        $result = '';

        // CloudFlare
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP']))
            $result = trim($_SERVER['HTTP_CF_CONNECTING_IP']);

        // Proxy type 1
        if (empty($result) && isset($_SERVER['HTTP_CLIENT_IP']))
            $result = trim($_SERVER['HTTP_CLIENT_IP']);

        // Proxy type 2
        if (empty($result) && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $proxies = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'], 2);
            $result = trim($proxies[0]);
        }

        // Good 'ol "remote_addr"
        if (empty($result) && isset($_SERVER['REMOTE_ADDR']))
            $result = trim($_SERVER['REMOTE_ADDR']);

        return $result;
    }

    /**
     * Cookie handler
     *
     * This checks if the visitor is from a specified country, adds unknown cookies
     * to the plugin cookie database and removes all cookies with the exception of
     * those marked as required.
     *
     * @return bool Returns true if no cookies should be set (for JS)
     */
    public function handleCookies()
    {
        // Don't handle any cookies if we're in Admin (except doing Ajax) or when someone's logged in
        if ((is_admin() && !Helpers::doingAjax()) /*|| is_user_logged_in()*/)
            return false;

        $opted_in = Cookies::get($this->short_name . static::OPTIN_ID, false);
        $check    = false;

        // If the user has opted in already, don't worry about anything else here
//        if ($opted_in)
//            return false;

        // Have a look where the visitor is from, and if it matches our settings, continue checking
        $countries = $this->options->countries;

        if (!empty($countries)) {
            $remote_country = $this->getCountryCode(/*$this->getRemoteIP()*/'188.28.61.44');

            $check = in_array($remote_country, $countries);
        }

        // If there's nothing to check, then we're done
//        if (!$check)
//            return false;

        // Disable session if it is based on cookies and we don't require sessions
        if (!$this->options->php_sessions_required && ini_get('session.use_cookies')) {
            if (session_id()) {
                // Destroy active session
                Cookies::delete(@session_name());

                @session_destroy();
            }

            // Prevent session to be re-opened with cookies
            @ini_set('session.use_cookies', '0');
        }

        // Iterate cookies, add any unknown ones to the database and remove cookies that aren't required
        $new_cookies = array();

        foreach ($_COOKIE as $cookie_name => $cookie_value) {
            $is_known = $this->isKnownCookie($cookie_name, $is_required);

            if (!$is_known) {
                $new_cookies[$cookie_name] = array(
                    'desc'  => '',
                    'group' => 'Unknown',
                );
            }

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
    public function isKnownCookie($cookie_name, &$required)
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
                        $required = (isset($known_cookie_value['required']) && $known_cookie_value['required'] === true);
                        $result   = true;
                        break;
                    }
                }
            }
        } else {
            $required = (isset($known_cookies[$cookie_name]['required']) && $known_cookies[$cookie_name]['required'] === true);
            $result   = true;
        }

        return $result;
    }

    /** -------------- EVENTS -------------- */

    /**
     * Register additional actions
     */
    public function onRegisterActions()
    {
        add_action('shutdown', array($this, 'onShutdown'), 99, 0);

        // Cookies are handled as early as possible here, disabling sessions, etc.
        $this->handleCookies();

        $this->options->known_cookies = null;
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
     * Give the plugin a menu
     */
    public function onBuildMenu()
    {
        $mymenu = new \Pf4wp\Menu\SubHeadMenu($this->getName());

        // Add settings menu
        $main_menu = $mymenu->addMenu(__('Cookies'), array($this, 'onCookiesAdminMenu'));
        //$main_menu->context_help = new ContextHelp($this, 'settings');

        return $mymenu;
    }

    /**
     * Prepares the Cookies Admin menu page
     */
    public function onCookiesAdminMenuLoad()
    {
        if (!empty($_POST) && isset($_POST['_nonce'])) {
            if (!wp_verify_nonce($_POST['_nonce'], 'onCookiesAdminMenu'))
                wp_die(__('You do not have permission to do that [nonce].', $this->getName()));

            $this->options->countries = (isset($_POST['countries'])) ? $_POST['countries'] : array();

            AdminNotice::add(__('Settings have been saved', $this->getName()));
        }
    }

    /**
     * Renders the Cookies Admin menu page
     */
    public function onCookiesAdminMenu()
    {
        $vars = array(
            'nonce'         => wp_nonce_field('onCookiesAdminMenu', '_nonce', true, false),
            'submit_button' => get_submit_button(),
            'countries'     => $this->getCountries(true),
        );

        $this->template->display('cookies.html.twig', $vars);
    }

}
