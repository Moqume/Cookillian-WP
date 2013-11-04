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
use Pf4wp\Info\PluginInfo;

class Main extends \Pf4wp\WordpressPlugin
{
    const OPTIN_ID    = '_opt_in';
    const OPTOUT_ID   = '_opt_out';
    const RESP_ID     = '_resp';
    const COOKIE_LIFE = '+3 years';
    const UNKNOWN     = 'Unknown';

    // Non-persistent cache
    protected $np_cache = array('known_cookies' => array());

    // Common crawlers/spiders
    protected $crawlers = array(
        'Yandex','YaDirectBot','Googlebot','bingbot','msnbot','Teoma','Slurp','YahooSeeker','BlitzBOT',
        'B-l-i-t-z-B-O-T','Baiduspider','btbot','Charlotte','Exabot','FAST-WebCrawler','FurlBot',
        'FyberSpider','GalaxyBot','genieBot','GurujiBot','holmes','LapozzBot','LexxeBot','MojeekBot',
        'NetResearchServer','NG-Search','nuSearch','PostBot','Scrubby','Seekbot','ShopWiki',
        'Speedy Spider','StackRambler', 'Sogou', 'WocBot', 'yacybot', 'YodaoBot', 'PaperLiBot',
    );

    // Country code -> Continent match up
    protected $country_codes   = array("AP","EU","AD","AE","AF","AG","AI","AL","AM","CW","AO","AQ","AR","AS","AT","AU","AW","AZ","BA","BB","BD","BE","BF","BG","BH","BI","BJ","BM","BN","BO","BR","BS","BT","BV","BW","BY","BZ","CA","CC","CD","CF","CG","CH","CI","CK","CL","CM","CN","CO","CR","CU","CV","CX","CY","CZ","DE","DJ","DK","DM","DO","DZ","EC","EE","EG","EH","ER","ES","ET","FI","FJ","FK","FM","FO","FR","SX","GA","GB","GD","GE","GF","GH","GI","GL","GM","GN","GP","GQ","GR","GS","GT","GU","GW","GY","HK","HM","HN","HR","HT","HU","ID","IE","IL","IN","IO","IQ","IR","IS","IT","JM","JO","JP","KE","KG","KH","KI","KM","KN","KP","KR","KW","KY","KZ","LA","LB","LC","LI","LK","LR","LS","LT","LU","LV","LY","MA","MC","MD","MG","MH","MK","ML","MM","MN","MO","MP","MQ","MR","MS","MT","MU","MV","MW","MX","MY","MZ","NA","NC","NE","NF","NG","NI","NL","NO","NP","NR","NU","NZ","OM","PA","PE","PF","PG","PH","PK","PL","PM","PN","PR","PS","PT","PW","PY","QA","RE","RO","RU","RW","SA","SB","SC","SD","SE","SG","SH","SI","SJ","SK","SL","SM","SN","SO","SR","ST","SV","SY","SZ","TC","TD","TF","TG","TH","TJ","TK","TM","TN","TO","TL","TR","TT","TV","TW","TZ","UA","UG","UM","US","UY","UZ","VA","VC","VE","VG","VI","VN","VU","WF","WS","YE","YT","RS","ZA","ZM","ME","ZW","A1","A2","O1","AX","GG","IM","JE","BL","MF","BQ");
    protected $continent_codes = array("AS","EU","EU","AS","AS","NA","NA","EU","AS","NA","AF","AN","SA","OC","EU","OC","NA","AS","EU","NA","AS","EU","AF","EU","AS","AF","AF","NA","AS","SA","SA","NA","AS","AN","AF","EU","NA","NA","AS","AF","AF","AF","EU","AF","OC","SA","AF","AS","SA","NA","NA","AF","AS","EU","EU","EU","AF","EU","NA","NA","AF","SA","EU","AF","AF","AF","EU","AF","EU","OC","SA","OC","EU","EU","NA","AF","EU","NA","AS","SA","AF","EU","NA","AF","AF","NA","AF","EU","AN","NA","OC","AF","SA","AS","AN","NA","EU","NA","EU","AS","EU","AS","AS","AS","AS","AS","EU","EU","NA","AS","AS","AF","AS","AS","OC","AF","NA","AS","AS","AS","NA","AS","AS","AS","NA","EU","AS","AF","AF","EU","EU","EU","AF","AF","EU","EU","AF","OC","EU","AF","AS","AS","AS","OC","NA","AF","NA","EU","AF","AS","AF","NA","AS","AF","AF","OC","AF","OC","AF","NA","EU","EU","AS","OC","OC","OC","AS","NA","SA","OC","OC","AS","AS","EU","NA","OC","NA","AS","EU","OC","SA","AS","AF","EU","EU","AF","AS","OC","AF","AF","EU","AS","AF","EU","EU","EU","AF","EU","AF","AF","SA","AF","NA","AS","AF","NA","AF","AN","AF","AS","AS","OC","AS","AF","OC","AS","EU","NA","OC","AS","AF","EU","AF","OC","NA","SA","AS","EU","NA","SA","NA","NA","AS","OC","OC","OC","AS","AF","EU","AF","AF","EU","AF","--","--","--","EU","EU","EU","EU","NA","NA","NA");

    // Flag to indicate (to JS) whether cookies are blocked
    protected $cookies_blocked;

    // References for MaxMind geolocation
    protected $maxmind_db;
    protected $maxmind_db_v6;

    // Slug for the cookie menu
    public $cookie_menu_slug = array();

    // Slug for the stats menu
    public $stats_menu_slug = array();

    // Shortname - pf4wp
    public $short_name = 'cookillian';

    // Public-side AJAX is enabled - pf4wp
    public $public_ajax = true;

    // Default options - Pf4wp
    protected $default_options = array(
        'geo_service'         => 'geoplugin',
        'auto_add_cookies'    => true,
        'delete_root_cookies' => true,
        'countries'           => array(),
        'known_cookies'       => array(),
        'alert_show'          => 'auto',
        'alert_content_type'  => 'default',
        'alert_content'       => "This site uses cookies to store information on your computer. Some of these cookies are essential to make our site work and others help us to improve by giving us some insight into how the site is being used. <a href=\"#\">More information</a>. \r\n\r\nBy using our site you accept the terms of our <a href=\"#\">Privacy Policy</a>. \r\n",
        'alert_heading'       => 'Information regarding cookies',
        'alert_ok'            => 'Yes, I\'m happy with this',
        'alert_no'            => 'No! Only store this answer, but nothing else',
        'required_text'       => 'This cookie is required for the operation of this website.',
        'stats'               => array(),
        'js_wrap'             => true,
        'show_on_unknown_location' => true,
        'noscript_tag'        => true,
        'alert_style'         => 'default',
        'delete_cookies'      => 'before_optout',
        'geo_cache_time'      => 1440, // in minutes
        'geo_backup_service'  => true,
        'dashboard_max_stats' => 5,
        'max_new_cookies'     => 30,
    );

    /** -------------- HELPERS -------------- */

    /**
     * Obtains a list of countries
     *
     * @param bool $mark_selected Adds a 'selected' value to the results, based on countries options
     * @return array Array containing contries in key/value pairs, where key is the 2-digit country code and vaue the country name
     */
    public function getCountries($mark_selected = false)
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

                            // Convert from all upper-case to something easier on the eyes
                            if (is_callable('mb_convert_case')) {
                                // Prefer the use of mb_convert_case
                                $country = mb_convert_case($country, MB_CASE_TITLE);
                            } else {
                                $country = ucwords(strtolower($country));
                            }

                            $this->np_cache['countries'][$code] = array('country' => $country);
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
     * Convert the country code to a readable name
     *
     * @param string $country_code The 2-letter country code
     * @return string The name of the country
     */
    public function getCountryName($country_code)
    {
        $countries = $this->getCountries();

        switch ($country_code) {
            case 'EU' :
                $country_long = 'Europe';
                break;

            case 'AP' :
                $country_long = 'Asia/Pacific';
                break;

            default :
                $country_long = isset($countries[$country_code]) ? $countries[$country_code]['country'] : static::UNKNOWN;
                break;
        }

        return $country_long;
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

        $cache_id = $this->short_name . '_ip_' . substr(md5($ip), 0, 8);

        // First attempt to fetch from local NP cache (fastest)
        if (isset($this->np_cache[$cache_id]))
            return $this->np_cache[$cache_id]; // Note: Will also return on empty results, avoids hammering 3rd party

        // Next attempt to fetch from transient cache (2nd fastest)
        if ($result = get_site_transient($cache_id)) {
            $this->np_cache[$cache_id] = $result; // Save to local NP cache
            return $result;
        }

        // Nothing in cache or empty result, so start working on it
        switch ($this->options->geo_service) {
            case 'geoplugin' :
                $remote = wp_remote_get('http://www.geoplugin.net/php.gp?ip=' . $ip);

                if (!is_wp_error($remote) && $remote['response']['code'] == 200) {
                    try {
                        $r = @unserialize($remote['body']);

                        $result = isset($r['geoplugin_countryCode']) ? $r['geoplugin_countryCode'] : '';
                    } catch (\Exception $e) {
                        $result = '';
                    }
                }
                break;

            case 'cloudflare' :
                $result = isset($_SERVER['HTTP_CF_IPCOUNTRY']) ? $_SERVER['HTTP_CF_IPCOUNTRY'] : '';
                break;

            case 'maxmind' :
                $result = $this->getMaxmindCountryCode($ip);
                break;
        }

        // Ensure it's an empty string if no valid country was found (for type check when retrieving the transient)
        if (empty($result) || $result == 'XX' || strlen($result) > 2)
            $result = '';

        if ($result == '' && $this->options->geo_backup_service) {
            // Use the backup service to verify that the IP is indeed unknown, and not a lookup failure @since 1.0.29
            $remote = wp_remote_get('http://freegeoip.net/json/' . $ip);

            // Note: will return 403 if 1000 requests per hour is exceeded
            if (!is_wp_error($remote) && $remote['response']['code'] == 200) {
                try {
                    $r = @json_decode($remote['body']);

                    $result = (isset($r->country_code) && $r->country_code != 'RD' && !empty($r->country_code)) ? $r->country_code : '';
                } catch (\Exception $e) {
                    $result = '';
                }
            }
        }

        // Ensure it's upper-case
        $result = strtoupper($result);

        // Save into caches
        set_site_transient($cache_id, $result, $this->options->geo_cache_time * 60); // Note: empty results are re-detected ASAP
        $this->np_cache[$cache_id] = $result; // Non-persistent cache, just for the life of this object

        return $result;
    }

    /**
     * Obtains the country code from a MaxMind database or Apache module
     *
     * The preferred method is to use the results from the Apache module or FastCGI
     * environment (handles 300K - 7.3 Million queries per second vs. 7K per second).
     *
     * @since 1.0.13
     * @param string $ip The IP to return the country for
     * @return string 2-digit country code or empty if unable to determine
     */
    protected function getMaxmindCountryCode($ip)
    {
        // First determine if we can use the Apache module (fastest)
        if (is_callable('apache_note') && ($country = apache_note('GEOIP_COUNTRY_CODE')))
            return $country;

        // Alternate style (common for FastCGI and NginX installs)
        if (isset($_SERVER['GEOIP_COUNTRY_CODE']) && ($country = $_SERVER['GEOIP_COUNTRY_CODE']))
            return $country;

        // Nothing so far, looks like we'll have to get it from a local database ...
        if (!function_exists('geoip_country_code_by_addr'))
            include_once $this->getPluginDir() . 'vendor/MaxMind/geoip.inc';

        if (strpos($ip, ':') !== false) {
            // IPv6 lookup

            // We haven't tried to load the database yet
            if (!isset($this->maxmind_db_v6)) {
                if (@is_file($this->options->maxmind_db_v6) && @is_readable($this->options->maxmind_db_v6)) {
                    try
                    {
                        /* Note, we use the "Standard" method vs. "Memory" as not everyone has
                         * gobs of memory allocated to their host (think web hosts vs. dedicated servers)
                         */
                        $this->maxmind_db_v6 = geoip_open($this->options->maxmind_db, GEOIP_STANDARD);
                    }
                    catch (\Exception $e)
                    {
                        $this->maxmind_db_v6 = false;
                    }
                }
            }

            // Perform the lookup, provided we have a valid database
            if ($this->maxmind_db_v6)
                $country = geoip_country_code_by_addr_v6($this->maxmind_db_v6, $ip);
        } else {
            // It's an IPv4 lookup
            if (!isset($this->maxmind_db)) {
                if (@is_file($this->options->maxmind_db) && @is_readable($this->options->maxmind_db)) {
                    try
                    {
                        $this->maxmind_db = geoip_open($this->options->maxmind_db, GEOIP_STANDARD);
                    }
                    catch (\Exception $e)
                    {
                        $this->maxmind_db = false;
                    }
                }
            }

            if ($this->maxmind_db)
                $country = geoip_country_code_by_addr($this->maxmind_db, $ip);
        }

        if ($country)
            return $country;

        return ''; // Ensure we always return an empty string if no valid country was found
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
     * @param string $referrer Referrer of the current page (AJAX, @since 1.0.23)
     * @return bool Returns `true` if cookies are blocked
     */
    public function handleCookies($referrer = null)
    {
        // Don't do anything if it's a crawler - @since 1.1.14
        if ($this->isCrawler())
            return false;

        // We detect unknown cookies first
        $this->detectUnknownCookies();

        // Return as 'true' if we're in debug mode and the user is logged in (so it can be tested)
        if ($this->options->debug_mode && is_user_logged_in())
            return true;

        // Don't handle any cookies if someone's logged in or the visitor opted in to recive cookies
        if ($this->optedIn() || is_user_logged_in())
            return false;

        // If the user has not specifically opted out...
        if (!$this->optedOut()) {
            // Find out if the visitor has implied consent, and if so, add a statistic, set a cookie and return
            if ($this->isImpliedConsent($referrer)) {
                $this->addStat('optin');

                $cookie_path = trailingslashit(parse_url(get_home_url(), PHP_URL_PATH));
                Cookies::set($this->short_name . static::OPTIN_ID, 2, strtotime(static::COOKIE_LIFE), true, false, $cookie_path);

                return false;
            }

            // Find out if the visitor is from one of the user-defined countries
            if (!$this->isSelectedCountry($this->getRemoteIP()))
                return false;

            // If we are to delete cookies after opt out, we stop here now, but as "true"
            if ($this->options->delete_cookies == 'after_optout')
                return true;
        }

        // From this point, cookies will be deleted based on their settings.
        $this->deleteCookies();

        return true;
    }

    /**
     * Deletes any cookies present
     */
    public function deleteCookies()
    {
        $session_name = session_name();

        // Check if PHP sessions are based on cookies
        if (ini_get('session.use_cookies')) {
            if (!$this->options->php_sessions_required && session_id()) {
                // If sessions aren't required and there's one open, destroy it
                $_SESSION = array();
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 3600,
                    $params["path"],   $params["domain"],
                    $params["secure"], $params["httponly"]
                );

                @session_destroy();
            }

            // Prevent session to be re-opened with cookies
            @ini_set('session.use_cookies', '0');
        }

        // Iterate cookies and remove cookies that aren't required
        foreach ($_COOKIE as $cookie_name => $cookie_value) {
            // Sanitize the cookie name
            $cookie_name = $this->sanitizeCookieName($cookie_name);

            // Skip our own or session cookies
            if ($cookie_name == $this->short_name . static::OPTOUT_ID ||
                $cookie_name == $this->short_name . static::OPTIN_ID  ||
                $cookie_name == $session_name)
                continue;

            // Set the $is_required
            $this->isKnownCookie($cookie_name, $is_required);

            // If the cookie is not required, delete it
            if (!$is_required) {
                if (isset($_SERVER['HTTP_HOST'])) {
                    list($wc, $domain) = explode('.', $_SERVER['HTTP_HOST'], 2);

                    if (strpos($domain, '.') === false)
                        $domain = $_SERVER['HTTP_HOST'];

                    // Attempt to delete a root "wildcard" cookie
                    if ($this->options->delete_root_cookies)
                        setcookie($cookie_name, '', time() - 3600, '/', '.' . $domain);

                    // Attempt to delete a local "wildcard" cookie
                    setcookie($cookie_name, '', time() - 3600, false, '.' . $domain);
                }

                // Attempt to delete the "root" cookie
                if ($this->options->delete_root_cookies)
                    setcookie($cookie_name, '', time() - 3600, '/');

                // Delete local cookie
                Cookies::delete($cookie_name);
            }
        }
    }

    /**
     * Determines if the IP is in the user-defined list of countries
     *
     * Note: In some cases, the geolocation service may return "AP" (Asia/Pacific) or
     * "EU" (Europe), giving no indication of the actual country. In such cases, we check
     * if an entry in the user-defined list falls within either AP or EU and return true
     * as well (to ensure it does not get excluded)
     *
     * @return bool Returns `true` if the country is in the user-defined list (or unknown and needs to be handled anyway)
     */
    public function isSelectedCountry($ip)
    {
        $selected_countries = $this->options->countries;
        $continents         = array_combine($this->country_codes, $this->continent_codes);
        $result             = false; // default result (not in list)

        // If no countries have been selected in the options, we're done.
        if (empty($selected_countries))
            return $result;

        // Check where the visitor is from according to the geoIP service
        $remote_country = $this->getCountryCode($ip);

        switch ($remote_country) {
            case '' :
                // Unknown country, are we asked to return "true" if we can't determine the country?
                $result = ($this->options->show_on_unknown_location);
                break;

            case 'EU' :
                // Special case #1 - country unknown, continent is Europe
                $eu     = array_intersect($continents, array($continents['EU']));       // Limit continents to EU
                $in_eu  = array_intersect_key($eu, array_flip($selected_countries));    // Find selected countries in EU
                $result = (count($in_eu) > 0);                                          // If one or more selected in EU, true
                break;

            case 'AP' :
                // Special case #2 - country unknown, continent is Asia/Pacific (not Oceanea)
                $ap     = array_intersect($continents, array($continents['AP']));
                $in_ap  = array_intersect_key($ap, array_flip($selected_countries));
                $result = (count($in_ap) > 0);
                break;

            default :
                // Simple lookup
                $result = in_array($remote_country, $selected_countries);
                break;
        }

        return $result;
    }

    /**
     * Detects any unknown cookie and adds them to our list of "known" cookies
     */
    protected function detectUnknownCookies()
    {
        if (!$this->options->auto_add_cookies)
            return;

        $new_cookies_count = $this->countNewCookies();
        $new_cookies       = array();
        $session_name      = (ini_get('session.use_cookies')) ? session_name() : false;

        foreach ($_COOKIE as $cookie_name => $cookie_value) {
            if ($this->options->max_new_cookies > 0 && $new_cookies_count > $this->options->max_new_cookies)
                break; // Stop adding more new cookies

            // Sanitize the cookie name
            $cookie_name = $this->sanitizeCookieName($cookie_name);

            if (in_array($cookie_name, array(
                    $this->short_name . static::OPTOUT_ID,  // Opt-out cookie
                    $this->short_name . static::OPTIN_ID,   // Opt-in cookie
                ))) continue; // Skip

            if (!$this->isKnownCookie($cookie_name)) {
                // We have a new cookie

                if ($cookie_name == $session_name) {
                    // It's a PHP Session cookie
                    $new_cookies[$session_name] = array(
                        'desc'     => 'PHP Session',
                        'group'    => 'PHP',
                        'required' => $this->options->php_sessions_required,
                        'ua'       => $_SERVER['HTTP_USER_AGENT'],
                    );
                } else {
                    // It's something else
                    $new_cookies[$cookie_name] = array(
                        'desc'  => '',
                        'group' => static::UNKNOWN,
                        'ua'    => $_SERVER['HTTP_USER_AGENT'],
                    );
                }

                $new_cookies_count++;
            }
        }

        if (!empty($new_cookies)) {
            // Invalidate 'known_cookies' NP cache
            $this->np_cache['known_cookies'] = array();

            // Merge new cookies with existing ones
            $cookies = array_merge($this->options->known_cookies, $new_cookies);

            // Ensure the session cookie has the correct requirement set at all times
            if ($session_name && isset($cookies[$session_name]))
                $cookies[$session_name]['required'] = $this->options->php_sessions_required;

            // Save cookies
            $this->options->known_cookies = $cookies;
        }
    }

    /**
     * Sanitizes the cookie name
     *
     * @since 1.0.30
     * @param string $cookie_name The cookie name to be sanitized
     * @return string the sanitized cookie name
     */
    public function sanitizeCookieName($cookie_name)
    {
        // Strip HTML tags
        $cookie_name = strip_tags($cookie_name);

        // Strip invalid characters
        $cookie_name = preg_replace('@[^\w-\&\$#]@', '', $cookie_name);

        // Return a nicely trimmed cookie name
        return trim($cookie_name);
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
        $cache_id = md5($cookie_name);

        // Ensure we have base NP for known cookies (easier for invalidating)
        if (!isset($this->np_cache['known_cookies']))
            $this->np_cache['known_cookies'] = array();

        // If we have done a lookup already, return those results
        if (isset($this->np_cache['known_cookies'][$cache_id])) {
            extract($this->np_cache['known_cookies'][$cache_id], EXTR_OVERWRITE);

            return $result;
        }

        // Peform a simple check on stored known cookies first
        $known_cookies = $this->options->known_cookies;

        // Force WordPress cookies, as this would otherwise cause a lock-out
        if (!array_key_exists('wordpress_test_cookie', $known_cookies)) {
            $known_cookies['wordpress_test_cookie'] = array('required' => true);
        }

        if (!array_key_exists('wordpress_*', $known_cookies)) {
            $known_cookies['wordpress_*'] = array('required' => true);
        }

        if (!array_key_exists($cookie_name, $known_cookies)) {
            // Simple check found nothing, see if we need to perform a heavier check using wildcards
            foreach ($known_cookies as $known_cookie_name => $known_cookie_value) {
                if (strpos($known_cookie_name, '*') !== false || strpos($known_cookie_name, '?') !== false) {
                    $pattern = '/^' . strtr($known_cookie_name, array('*' => '.+', '?' => '.', '/' => '\/')) . '$/';

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

        // Save the results into an NP cache before returning
        $this->np_cache['known_cookies'][$cache_id] = array(
            'result'    => $result,
            'required'  => $required,
        );

        return $result;
    }

    /**
     * Returns if there are any new cookies that need attention
     *
     * @since 1.0.30
     * @return bool
     */
    public function hasNewCookies()
    {
        if (!isset($this->np_cache['has_new_cookies'])) {
            $cookies = $this->options->known_cookies;
            $groups  = array();
            array_walk_recursive($cookies, function($v, $k) use(&$groups) { if($k == 'group') array_push($groups, $v); });

            $this->np_cache['has_new_cookies'] = in_array(static::UNKNOWN, $groups);
        }

        return $this->np_cache['has_new_cookies'];
    }

    /**
     * Returns if the number of new cookies
     *
     * @since 1.1.11
     * @return int
     */
    public function countNewCookies()
    {
        if (!$this->hasNewCookies())
            return 0;

        $new_cookies = array();
        $cookies     = $this->options->known_cookies;

        array_walk($cookies, function($v, $k) use (&$new_cookies) { if (isset($v['group']) && $v['group'] == \Myatu\WordPress\Cookillian\Main::UNKNOWN) $new_cookies[] = $k; });

        return count($new_cookies);
    }

    /**
     * Checks if the visitor has seen the alert before, and implied consent
     *
     * @since 1.0.23
     * @param string $referrer Optional referrer (AJAX)
     * @return bool Returns true if the visitor implied consent
     */
    protected function isImpliedConsent($referrer = null)
    {
        $parse = function($url){ return rtrim(parse_url($url,PHP_URL_HOST) . parse_url($url, PHP_URL_PATH), '/'); };

        // Check if we're allowing implied consent
        if (!$this->options->implied_consent)
            return false;

        // Check if it's a Firefox "Pre-fetch" request (Why-oh-why does this not show up in Firebug! Grr...)
        if (isset($_SERVER['HTTP_X_MOZ']) && $_SERVER['HTTP_X_MOZ'] == 'prefetch')
            return false;

        // No referrer provided, grab it
        if (is_null($referrer)) {
            if (isset($_SERVER['HTTP_REFERER']))
                $referrer = $_SERVER['HTTP_REFERER'];
        }

        // If there's no referer, then the visitor must not have seen the alert before
        if (!$referrer)
            return false;

        // Figure out if the referer was within the home url, and not the same page (regardless of scheme, query args, anchors, etc)
        $parsed_home_url  = $parse(get_home_url());
        $parsed_referrer  = $parse($referrer);
        $guessed_url      = $parse(Helpers::doingAjax() ? $_SERVER['HTTP_REFERER'] : wp_guess_url());

        if ($guessed_url == $parsed_referrer)
            return false; // Same as calling page

        return (strpos($parsed_referrer, $parsed_home_url) === 0);
    }

    /**
     * Returns the DNT/Do Not Track browser header value
     *
     * See http://tools.ietf.org/html/draft-mayer-do-not-track-00. If the header
     * is not present, there is no expressed preference.
     *
     * @since 1.0.14
     * @return string The value specified by the visitor, or empty if no DNT preference expressed
     */
    public function getDNT()
    {
        if (isset($_SERVER['HTTP_X_DO_NOT_TRACK']))
            return $_SERVER['HTTP_X_DO_NOT_TRACK'];

        if (isset($_SERVER['HTTP_DNT']))
            return $_SERVER['HTTP_DNT'];

        return '';
    }

    /**
     * Returns whether cookies were (supposed to be) deleted
     *
     * @since 1.0.25
     */
    public function hasDeletedCookies()
    {
        return ($this->cookies_blocked && ($this->options->delete_cookies == 'before_optout' || $this->optedOut()) && !(is_user_logged_in() && $this->options->debug_mode));
    }

    /**
     * Returns whether the visitor implied consent
     *
     * @since 1.0.23
     */
    public function hasImpliedConsent()
    {
        return (Cookies::get($this->short_name . static::OPTIN_ID, false) == 2);
    }

    /**
     * Returns whether the visitor has opted in
     *
     * @return bool
     */
    public function optedIn()
    {
        return (($this->getDNT() === '0') || (Cookies::get($this->short_name . static::OPTIN_ID, false) !== false));
    }

    /**
     * Returns whether the visitor has opted out
     *
     * Since 1.0.14, also checks for DNT/Do_Not_Track browser headers
     *
     * @return bool
     */
    public function optedOut()
    {
        return (($this->getDNT() === '1') || (Cookies::get($this->short_name . static::OPTOUT_ID, false) !== false));
    }

    /**
     * Returns whether the user agent is a known crawler
     *
     * @return bool
     */
    protected function isCrawler()
    {
        return (isset($_SERVER['HTTP_USER_AGENT']) && preg_match('#' . implode('|', $this->crawlers) . '#i', $_SERVER['HTTP_USER_AGENT']));
    }


    /**
     * Adds a statistic about the responses (or lack thereof)
     *
     * This saves the details in the 'stats' array, where the first key is the year,
     * 2nd key the month and 3rd the country of the visitor. It contains how often
     * the alert was displayed, how often one opted in and how often one opted out.
     *
     * @param string $type One of: 'displayed', 'optin', 'optout'
     *
     */
    protected function addStat($type)
    {
        // Don't track if in debug mode and user is logged in or it's a crawler
        if (($this->options->debug_mode && is_user_logged_in()) || $this->isCrawler())
            return;

        $remote_country = $this->getCountryCode($this->getRemoteIP());
        $stats          = $this->options->stats;
        $country_stats  = array(0, 0, 0);
        $date           = getdate();
        $year           = $date['year'];
        $month          = $date['month'];

        // Ensure we have a year
        if (!isset($stats[$year]))
            $stats[$year] = array($month => array());

        // Ensure we have a month
        if (!isset($stats[$year][$month]))
            $stats[$year][$month] = array();

        // Grab the current country stats, if any
        if (isset($stats[$year][$month][$remote_country]))
            $country_stats = $stats[$year][$month][$remote_country];

        // Update the stats
        switch ($type) {
            case 'displayed' :
                $country_stats[0]++;
                break;

            case 'optin' :
                if ($country_stats[0] > ($country_stats[1] + $country_stats[2]))
                    $country_stats[1]++;
                break;

            case 'optout' :
                if ($country_stats[0] > ($country_stats[1] + $country_stats[2]))
                    $country_stats[2]++;
                break;
        }

        if ($country_stats != array(0, 0, 0)) {
            // Return thde updated stats back to where they belong
            $stats[$year][$month][$remote_country] = $country_stats;

            // And save
            $this->options->stats = $stats;
        }
    }

    /**
     * Resets the stats
     *
     * @param int $year The year to reset the statistics for (optional, by default ALL statistics are cleared)
     * @since 1.0.23
     */
    protected function resetStats($year = null)
    {
        if (is_null($year)) {
            // Reset all
            $this->options->stats = array();
        } else {
            // Reset a specific year
            $stats = $this->options->stats;

            if (isset($stats[$year])) {
                unset($stats[$year]);

                $this->options->stats = $stats;
            }
        }
    }

    /**
     * Converts stats to a CSV and outputs it for download
     *
     * @since 1.0.28
     */
    protected function downloadStats()
    {
        $stats     = $this->options->stats;
        $csv_stats = array(
            array('Date', 'Country Code', 'Country Name', 'Displayed', 'Opted In', 'Opted Out', 'Ignored')
        );

        if ($fh = fopen('php://output', 'w')) {
            header('Content-Type: text/csv' );
            header('Content-Disposition: attachment;filename=stats.csv');
            fputcsv($fh, array('Date', 'Country Code', 'Country Name', 'Displayed', 'Opted In', 'Opted Out', 'Ignored'));

            // No, it's not pretty, but it does the job.
            foreach ($stats as $year => $stats_year_values) {
                foreach ($stats_year_values as $month => $stats_month_values) {
                    foreach ($stats_month_values as $country => $values) {
                        $date = new \DateTime($year . ' ' . $month);
                        fputcsv($fh, array($date->format('Y/m/d'), $country, $this->getCountryName($country), $values[0], $values[1], $values[2], $values[0] - ($values[1] + $values[2])));
                    }
                }
            }

            fclose($fh);
            die();
        }
    }

    /**
     * Processes a response to the question of permitting cookies
     *
     * @param int $answer 0 = opt out, 1 = opt in, 2 = reset
     * @param bool $redirect Set to `true` if the visitor needs to be redirected back to original location
     */
    public function processResponse($answer, $redirect = true)
    {
        $opt_in_or_out = '';
        $redir_url     = (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : add_query_arg(array($this->short_name . static::RESP_ID => false)));
        $cookie_path   = trailingslashit(parse_url(get_home_url(), PHP_URL_PATH));

        switch ($answer) {
            case 2 :
                // Reset/clear previous opt-in or out
                Cookies::delete($this->short_name . static::OPTIN_ID);
                Cookies::delete($this->short_name . static::OPTOUT_ID);
                break;

            case 1 :
                // Opt In
                $opt_in_or_out = $this->short_name . static::OPTIN_ID;

                if (Cookies::get($opt_in_or_out, false) === false)
                    $this->addStat('optin'); // Visitor wasn't click-happy, so add the stat

                break;

            case 0 :
                // Opt Out
                $opt_in_or_out = $this->short_name . static::OPTOUT_ID;

                if (Cookies::get($opt_in_or_out, false) === false)
                    $this->addStat('optout');

                break;
        }

        if (!empty($opt_in_or_out)) {
            // Set a cookie with the visitor's response
            Cookies::set($opt_in_or_out, 1, strtotime(static::COOKIE_LIFE), true);
        }

        if ($redirect) {
            // And send the visitor back to where they were, if possible
            wp_redirect($redir_url); die();
        }
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

    /**
     * Helper to encase a JavaScript code block
     */
    protected function jsBlock($code)
    {
        return sprintf("<script type=\"text/javascript\">\r\n/* <![CDATA[ */\r\n%s\r\n/* ]]> */\r\n</script>\r\n", $code);
    }

    /**
     * Handles a file upload (after WordPress handled it)
     *
     * The extra step that's performed here, is if the file is a ZIP file, it will
     * extract the contents and use a *single* file from it as the uploaded file.
     *
     * @param array Array containing return value of wp_handle_upload()
     * @return array Array containing return values similar to wp_handle_upload()
     * @since 1.0.17
     */
    protected function handleFileUpload($upload)
    {
        global $wp_filesystem;

        if (isset($upload['error']))
            return $upload;

        $ext    = strtolower(pathinfo($upload['file'], PATHINFO_EXTENSION));
        $result = $upload;

        if ($upload['type'] == 'application/zip' ||
            $upload['type'] == 'application/x-zip' ||
            $upload['type'] == 'application/x-zip-compressed' ||
            $ext = 'zip') {
            // Compressed upload

            // Initialize the WP File system if need be
            if (!isset($wp_filesystem) && function_exists('WP_Filesystem'))
                WP_Filesystem();

            // Create a working directory to extract file(s) to
            $temp_dir = \Pf4wp\Storage\StoragePath::validate(trailingslashit(realpath(sys_get_temp_dir()))  . 'cookillian_' . substr(md5(time() . rand()), 0, 8), false);

            // If we have a valid working directory and could extract the ZIP file, look at the contents
            if ($temp_dir && unzip_file($upload['file'], $temp_dir)) {
                $total_files = 0;
                $the_file    = '';

                if ($dh = opendir($temp_dir)) {
                    while (false !== ($file = readdir($dh)) && $total_files < 2) {
                        if ($file != '.' && $file != '..') {
                            $total_files++;
                            $the_file = $file;
                        }
                    }

                    closedir($dh);
                }

                if ($total_files > 2) {
                    // More than one file extracted, K.I.S.S.
                    $result = array('error' => 'ZIP contains too many files, don\'t know which one to use.');
                } if ($total_files == 0) {
                    // No files extracted, kept it way too simle this time
                    $result = array('error' => 'ZIP file contains nothing.');
                } else {
                    // Aha! We extracted file, let's use that instead
                    $dir = trailingslashit(dirname($upload['file']));

                    @unlink($upload['file']);
                    if (@rename($temp_dir . $the_file, $dir . $the_file)) {
                        $result['file'] = $dir . $the_file;
                    } else {
                        $result = array('error' => 'Unable to move extracted file to upload directory.');
                    }
                }
            } else {
                $result = array('error' => 'Unable to extract the ZIP file.');
            }

            // Clean up temp dir
            if ($temp_dir)
                \Pf4wp\Storage\StoragePath::delete($temp_dir);
        }

        return $result;
    }

    /**
     * Clears transients related to Cookillian
     *
     * @since 1.0.17
     */
    protected function clearTransients()
    {
        global $wpdb;

        try
        {
            // Site transients are stored under `sitemeta` on multisites
            if (defined('MULTISITE') && MULTISITE) {
                $site_transient_location = $wpdb->sitemeta;
            } else {
                $site_transient_location = $wpdb->options;
            }

            return $wpdb->get_results("DELETE FROM `{$site_transient_location}` WHERE `option_name` LIKE '_site_transient%_{$this->short_name}_%'");
        }
        catch (\Exception $e)
        {
            return false;
        }
    }

    /**
     * Determine if existing Geolocation information is available
     *
     * @return string|bool Returns a string of a known geolocation provider if data is available, false otherwise
     * @since 1.0.17
     */
    protected function hasGeoData()
    {
        if ((is_callable('apache_note') && apache_note('GEOIP_COUNTRY_CODE') !== false) || isset($_SERVER['GEOIP_COUNTRY_CODE']))
            return 'maxmind';

        if (isset($_SERVER['HTTP_CF_IPCOUNTRY']))
            return 'cloudflare';

        return false;
    }

    /**
     * Checks if a caching plugin is active
     */
    protected function hasActiveCachingPlugin()
    {
        $installed = PluginInfo::isInstalled(array(
            'WP Super Cache', 'W3 Total Cache', 'Quick Cache',
        ), true);

        return (!empty($installed));
    }


    /** -------------- EVENTS -------------- */

    public function onActivation()
    {
        // Pre-fill the 'Known Cookies' with those created by WordPress
        $known_cookies = $this->options->known_cookies;

        if (empty($known_cookies)) {
            $this->options->known_cookies = array(
                'wordpress_*' => array(
                    'desc'     => 'This cookie stores WordPress authentication details.',
                    'group'    => 'WordPress',
                    'required' => true,
                ),
                'wordpress_test_cookie' => array(
                    'desc'     => 'This cookie helps WordPress determine if it can store cookies',
                    'group'    => 'WordPress',
                    'required' => true,
                ),
                'wp-settings-*' => array(
                    'desc'  => 'This cookie helps remember your personal preferences within WordPress.',
                    'group' => 'WordPress',
                ),
                'comment_author_*' => array(
                    'desc'  => 'This cookie remembers your last comment details, such as your name and email address, so that you will not have to type it again.',
                    'group' => 'WordPress',
                ),

                $this->short_name . '_opt_*' => array(
                    'desc'  => 'This cookie stores your preference regarding the use of cookies on this website.',
                    'group' => 'Website',
                ),
            );

            // Invalidate NP cache, just in case
            $this->np_cache['known_cookies'] = array();
        }

        // And pre-fill the countries
        $countries = $this->options->countries;

        if (empty($countries))
            $this->options->countries = array(
                'AT', // Austria
                'BE', // Belgium
                'BG', // Bulgaria
                'CY', // Cyprus
                'CZ', // Czech Republic
                'DK', // Denmark
                'EE', // Estonia
                'FI', // Finland
                'FR', // France
                'DE', // Germany
                'GR', // Greece
                'HU', // Hungary
                'IE', // Ireland
                'IT', // Italy
                'LV', // Latvia,
                'LT', // Lithuania
                'LU', // Luxembourg
                'MT', // Malta
                'NL', // Netherlands
                'PL', // Poland
                'RO', // Romania
                'SK', // Slovakia
                'SI', // Slovenia
                'ES', // Spain
                'SE', // Sweden
                'GB', // United Kingdom
            );
    }

    /**
     * Called when the plugin is de-activated
     */
    public function onDeactivation()
    {
        $this->clearTransients();
    }

    /**
     * Called when the plugin has been upgraded
     */
    public function onUpgrade($previous_version, $current_version)
    {
        $this->clearTransients();
    }

    /**
     * Register additional actions
     */
    public function onRegisterActions()
    {
        // Do not bother with this if we're processing an AJAX call
        if (Helpers::doingAjax()) {
            $this->handleCookies();
            return;
        }

        // Was there a response to the cookie alert?
        if (isset($_REQUEST[$this->short_name . static::RESP_ID]))
            $this->processResponse((int)$_REQUEST[$this->short_name . static::RESP_ID]);

        add_action('shutdown', array($this, 'onShutdown'), 99, 0);

        // Shortcode
        add_shortcode($this->short_name, array($this, 'onShortCode'));

        // Filters
        add_filter($this->short_name . '_alert',             array($this, 'onFilterAlert'));
        add_filter($this->short_name . '_blocked_cookies',   array($this, 'onFilterBlockedCookies'));
        add_filter($this->short_name . '_opted_in',          array($this, 'onFilterOptedIn'));
        add_filter($this->short_name . '_opted_out',         array($this, 'onFilterOptedOut'));

        // Cookies are handled as early as possible here, disabling sessions, etc.
        $this->cookies_blocked = $this->handleCookies();

        // Include the api_helpers file
        require_once $this->getPluginDir() . 'inc/api_helpers.php';
    }

    /**
     * Event to handle cookies on shutdown
     *
     * @see onRegisterActions()
     */
    public function onShutdown()
    {
        // Cookies are once more handled here, to delete any cookies added after we first handled them
        if (!headers_sent())
            $this->handleCookies();
    }

    /**
     * Registers the Dashboard widget(s)
     *
     * @since 1.0.30
     */
    public function onDashboardWidgetRegister()
    {
        // Queue the stylesheet too
        list($css_url, $version, $debug) = $this->getResourceUrl('css');
        wp_enqueue_style($this->getName() . '-admin', $css_url . 'admin' . $debug . '.css', false, $version);


        new Dashboard\Main($this);
    }

    /**
     * Load Admin JS
     */
    public function onAdminScripts()
    {
        list($js_url, $version, $debug) = $this->getResourceUrl();

        wp_enqueue_script($this->getName() . '-admin', $js_url . 'admin' . $debug . '.js', array('jquery'), $version);

        wp_localize_script($this->getName() . '-admin', $this->getName() . '_translate', array(
            'add_cookie_group'  => __('Add new group', $this->getName()),
            'sel_cookie_group'  => __('Select a group', $this->getName()),
            'are_you_sure'      => __('Are you sure?', $this->getName()),
        ));
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
     * Handles an AJAX request
     *
     * The AJAX calls are used to bypass caches such as W3TC and WP Super Cache, which
     * return a static version of the last rendered page. This would mean that handleCookies()
     * is also not called, until the AJAX 'init' action.
     *
     * Note: NONCE is valid up to 24 hours, so W3TC or WP Super Cache should not keep a page
     * cached for longer than that.
     *
     * @param string $action The AJAX action to perform
     * @param mixed $data Data supplied with the AJAX action
     * @since 1.0.22
     */
    public function onAjaxRequest($action, $data)
    {
        switch ($action) {
            case 'init' :
                if (!isset($data['true_referrer']))
                    return; // Malformed request

                // Performs handleCookies(), and returns JS data based on the result
                $cookies_blocked = $this->handleCookies($data['true_referrer']);
                $deleted_cookies = ($cookies_blocked && ($this->options->delete_cookies == 'before_optout' || $this->optedOut()) && !(is_user_logged_in() && $this->options->debug_mode));

                $vars = array(
                    'blocked_cookies' => $cookies_blocked,
                    'deleted_cookies' => $deleted_cookies,
                    'implied_consent' => $this->hasImpliedConsent(),
                    'opted_out'       => $this->optedOut(),
                    'opted_in'        => $this->optedIn(),
                    'is_manual'       => ($this->options->alert_show == 'manual'), // Used internally, check if alert is inserted manually
                    'has_nst'         => ($this->options->noscript_tag && $cookies_blocked && !$this->optedOut() && !$this->hasActiveCachingPlugin()), // Used internally, check if "noscript" tag should be present
                );

                if (!$deleted_cookies) {
                    // Cookies are not deleted, add some extra JS, if defined (save on extra AJAX calls)
                    if ($this->options->script_header) {
                        // Add header scripts
                        $vars['header_script'] = $this->options->script_header;

                        if ($this->options->js_wrap)
                            $vars['header_script'] = $this->jsBlock($vars['header_script']);
                    }

                    if ($this->options->script_footer) {
                        // Add footer scripts
                        $vars['footer_script'] = $this->options->script_footer;

                        if ($this->options->js_wrap)
                            $vars['footer_script'] = $this->jsBlock($vars['footer_script']);
                    }
                }

                if ($this->options->debug_mode) {
                    // Spit out some debug data - this is only shown on JS Console
                    $countries          = $this->getCountries();
                    $ip                 = $this->getRemoteIP();
                    $country_short      = $this->getCountryCode($ip);

                    $vars['debug'] = array(
                        'handle'              => !($this->optedIn() || is_user_logged_in()),
                        'logged_in'           => is_user_logged_in(),
                        'country_list_ok'     => !empty($countries),
                        'ip'                  => $ip,
                        'country_short'       => $country_short,
                        'country_long'        => $this->getCountryName($country_short),
                        'is_selected_country' => $this->isSelectedCountry($ip),
                    );
                }

                $this->ajaxResponse($vars);
                break;

            case 'displayed' :
                $this->handleCookies(false, false); // Ensure this call doesn't create cookies

                // Lets the plugin know that an alert was displayed
                $this->addStat('displayed');

                $this->ajaxResponse(true);
                break;

            case 'delete_cookies' :
                // Delete the cookies
                $this->deleteCookies();

                $this->ajaxResponse(true);
                break;

            case 'opt_out' :
                // Perform an opt out
                $this->processResponse(0, false);

                // Scrub cookies now
                $this->handleCookies(false, false);

                $this->ajaxResponse(true);
                break;

            case 'opt_in' :
                // Perform an opt in
                $this->processResponse(1, false);

                $this->ajaxResponse(true);
                break;

            case 'reset_optinout' :
                // Reset the opt in or opt out choice
                $this->processResponse(2, false);

                $this->ajaxResponse(true);
                break;

            case 'clear_geo_cache' :
                // Clears the Geolocation cache - PRIVILEGED CALL
                if (!current_user_can('edit_plugins'))
                    return;

                $this->clearTransients();

                $this->ajaxResponse(true);
                break;
        }
    }

    /**
     * Queue the public-side JS
     */
    public function onPublicScripts()
    {
        list($js_url, $version, $debug) = $this->getResourceUrl();

        wp_enqueue_script($this->getName() . '-pub', $js_url . 'pub' . $debug . '.js', array('jquery'), $version);

        // Output JS variables that can remain static with caching
        $extra_js_vars = array(
            'use_async_ajax' => $this->options->async_ajax,
            'scrub_cookies'  => $this->options->scrub_cookies,
        );

        echo $this->jsBlock(sprintf('var cookillian = %s;', json_encode((Object)$extra_js_vars)));
    }

    /**
     * Queue public-side CSS
     */
    public function onPublicStyles()
    {
        if ($this->options->alert_style == 'default') {
            list($css_url, $version, $debug) = $this->getResourceUrl('css');

            wp_enqueue_style($this->getName() . '-pub', $css_url . 'pub' . $debug . '.css', false, $version);
        } else if ($this->options->alert_custom_style) {
            printf("<style type=\"text/css\">\n%s\n</style>\n", $this->options->alert_custom_style);
        }
    }

    /**
     * Renders the footer on the public side
     */
    public function onPublicFooter()
    {
        if ($this->options->alert_show == 'auto')
            echo apply_filters('cookillian_alert', '');

        // Provide a "noscript" tag for browsers that do not have JS enabled (not compatible with caching plugins)
        if ($this->options->noscript_tag && $this->cookies_blocked && !$this->optedOut() && !$this->hasActiveCachingPlugin()) {
            $this->addStat('displayed');

            $extra_styling = ($this->options->alert_show == 'manual') ? '' : 'position:absolute;left:0;top:0;';

            printf("<noscript><style type=\"text/css\" media=\"screen\">.cookillian-alert{%s display:block !important;} .cookillian-alert .close{display: none;}</style></noscript>", $extra_styling);
        }
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

        // If cookies are found to be blocked and we haven't specifically opted out, we show an alert
        if ($this->options->alert_content_type == 'default') {
            // Default alert
            $vars = array_merge(
                array(
                    'alert_content' => wpautop($this->options->alert_content),
                    'response_no'   => add_query_arg(array($this->short_name . static::RESP_ID => 0)),
                    'response_ok'   => add_query_arg(array($this->short_name . static::RESP_ID => 1)),
                    'manual'        => ($this->options->alert_show == 'manual'),
                ),
                $this->options->fetch(array('alert_heading', 'alert_ok', 'alert_no'))
            );

            $result = $this->template->render('ask.html.twig', $vars);
        } else {
            // Custom alert
            $result = sprintf('<div class="cookillian-alert" style="display:none;">%s</div>', $this->options->alert_custom_content);
        }

        return $result;
    }

    /**
     * Filter to return the $blocked_cookies status
     *
     * @param mixed $original Original value passed to the filter
     */
    public function onFilterBlockedCookies($original)
    {
        if (isset($this->cookies_blocked))
            return $this->cookies_blocked;

        return $original;
    }

    /**
     * Filter for the optedIn() function
     *
     * @param mixed $original Original value passed to the filter (ignored)
     */
    public function onFilterOptedIn($original)
    {
        return $this->optedIn();
    }

    /**
     * Api for the OptedOut() function
     *
     * @param mixed $original Original value passed to the filter (ignored)
     */
    public function onFilterOptedOut($original)
    {
        return $this->optedOut();
    }

    /**
     * Handles shortcodes
     *
     * Shortcodes:
     *  - alert                 Displays the Cookie Alert, if required
     *  - cookies (group[s])    Displays information about all cookies, or those within a certain groups (comma seperated)
     *  - exclude group[s]      Excludes group[s] (comma seperated, only when no cookie groups specified - since 1.0.31)
     */
    public function onShortCode($atts)
    {
        $known_cookies      = $this->options->known_cookies;
        $cookies_to_display = array();

        if (count($atts) >= 1) {
            // Check for singular items:
            if (in_array('alert', $atts)) {
                return apply_filters('cookillian_alert', '');
            }

            if (in_array('cookies', $atts)) {
                // Display all cookies, except if there's excluded groups
                if (isset($atts['exclude'])) {
                    $excluded = explode(',', strtolower(str_replace(' ', '', $atts['exclude'])));

                    array_walk($known_cookies, function($v, $k) use(&$cookies_to_display, $excluded) { if (!in_array(strtolower($v['group']), $excluded)) $cookies_to_display[$k] = $v; });
                } else {
                    $cookies_to_display = $known_cookies;
                }
            } else if (array_key_exists('cookies', $atts)) {
                // Specific cookie group specified (exclude doesn't apply)
                $groups = explode(',', strtolower(str_replace(' ', '', $atts['cookies'])));

                array_walk($known_cookies, function($v, $k) use(&$cookies_to_display, $groups) { if (in_array(strtolower($v['group']), $groups)) $cookies_to_display[$k] = $v; });
            }

            if ($cookies_to_display) {
                // We've got cookies to display, deep strip slashes (pf4wp doesn't do that)
                $cookies_to_display = $this->deepStripSlashes($cookies_to_display);

                // Sort by group
                uasort($cookies_to_display, function($a,$b) { return strcasecmp($a['group'], $b['group']); });

                return $this->template->render('cookie_table.html.twig', array(
                    'cookies'       => $cookies_to_display,
                    'required_text' => $this->options->required_text,
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

        // Save the slug (used by dashboard)
        $this->cookie_menu_slug = array(\Pf4wp\Menu\CombinedMenu::SUBMENU_ID => $cookie_menu->getSlug());

        // Add statistics menu
        $stats_menu = $mymenu->addSubmenu(__('Statistics', $this->getName()), array($this, 'onStatsMenu'));

        // Save the slug for the statistics menu, too
        $this->stats_menu_slug = array(\Pf4wp\Menu\CombinedMenu::SUBMENU_ID => $stats_menu->getSlug());

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

            if (isset($_POST['clear-geo-cache'])) {
                // All we need to do is clear the transients
                $this->clearTransients();

                return;
            }

            // Save
            $this->options->load($_POST, array(
                'auto_add_cookies'      => 'bool',
                'delete_root_cookies'   => 'bool',
                'php_sessions_required' => 'bool',
                'geo_service'           => array('in_array', array('geoplugin','cloudflare','maxmind')),
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
                'debug_mode'            => 'bool',
                'js_wrap'               => 'bool',
                'show_on_unknown_location' => 'bool',
                'maxmind_db'            => 'string', // Note, this will be overriden with file uploads
                'maxmind_db_v6'         => 'string',
                'implied_consent'       => 'bool',
                'noscript_tag'          => 'bool',
                'alert_style'           => array('in_array', array('default', 'custom')),
                'alert_custom_style'    => 'string',
                'delete_cookies'        => array('in_array', array('before_optout', 'after_optout')),
                'geo_cache_time'        => 'int',
                'geo_backup_service'    => 'bool',
                'async_ajax'            => 'bool',
                'max_new_cookies'       => 'int',
                'scrub_cookies'         => 'bool',
            ));

            // Extra sanity check for geo_cache_time, one minute is absolute minimum
            if ($this->options->geo_cache_time < 1)
                $this->options->geo_cache_time = 1;

            // Reset max_new_cookies to to zero if below
            if ($this->options->max_new_cookies < 0)
                $this->options->max_new_cookies = 0;

            // Save country selections
            $this->options->countries = (isset($_POST['countries'])) ? $_POST['countries'] : array();

            // Handle file uploads
            if (isset($_FILES)) {
                foreach ($_FILES as $uploaded_file_id => $uploaded_file) {
                    if (in_array($uploaded_file_id, array('maxmind_db_file', 'maxmind_db_v6_file')) && $uploaded_file['error'] !== UPLOAD_ERR_NO_FILE) {
                        $handled_file = $this->handleFileUpload(wp_handle_upload($uploaded_file, array('test_form'=>false)));

                        if (isset($handled_file['error'])) {
                            AdminNotice::add(sprintf(__("The file <strong>%s</strong> could not be saved. %s", $this->getName()), $uploaded_file['name'], $handled_file['error']), true);
                        } else {
                            switch ($uploaded_file_id) {
                                case 'maxmind_db_file' :
                                    if ($this->options->maxmind_db && ($this->options->maxmind_db !== $handled_file['file']))
                                        @unlink($this->options->maxmind_db); // File is being replaced, delete old one

                                    $this->options->maxmind_db = $handled_file['file'];
                                    break;

                                case 'maxmind_db_v6_file' :
                                    if ($this->options->maxmind_db_v6 && ($this->options->maxmind_db_v6 !== $handled_file['file']))
                                        @unlink($this->options->maxmind_db_v6);

                                    $this->options->maxmind_db_v6 = $handled_file['file'];
                                    break;
                            }
                        }
                    }
                }
            }

            AdminNotice::add(__('Settings have been saved', $this->getName()));
        }

        // Additional warning for CloudFlare
        if ($this->options->geo_service == 'cloudflare' && !(isset($_SERVER['HTTP_CF_IPCOUNTRY'])))
            AdminNotice::add(__('<strong>Warning!</strong> No CloudFlare Geolocation data detected.', $this->getName()), true);

        // Additional warning for MaxMind
        if ($this->options->geo_service == 'maxmind' && !($this->hasGeoData() == 'maxmind')) {
            $default_warning = __('<strong>Warning!</strong> MaxMind Apache Module or Nginx GeoIP Module not detected', $this->getName());
            $warning         = '';

            if (!$this->options->maxmind_db) {
                $warning = $default_warning . __(', and no IPv4 database specified. No IPv4 geolocation can be performed!', $this->getName());
            } elseif (!(@is_file($this->options->maxmind_db) && @is_readable($this->options->maxmind_db))) {
                $warning = $default_warning . __(', and IPv4 database could not be accessed. No IPv4 geolocation can be performed!', $this->getName());
            }

            if (!$this->options->maxmind_db_v6) {
                $warning = $default_warning . __(', and no IPv6 database specified. No IPv6 geolocation can be performed!', $this->getName());
            } elseif (!(@is_file($this->options->maxmind_db_v6) && @is_readable($this->options->maxmind_db_v6))) {
                $warning = $default_warning . __(', and IPv6 database could not be accessed. No IPv4 geolocation can be performed!', $this->getName());
            }

            if ($warning)
                AdminNotice::add($warning, true);
        }

        if ($this->options->max_new_cookies > 0 && $this->countNewCookies() >= $this->options->max_new_cookies)
            AdminNotice::add(sprintf("Cookillian has reached the maximum new cookies it may detect. Please refer to the <a href=\"%s\">Cookies</a> page and edit or delete any new cookies as neccesary.", add_query_arg($this->cookie_menu_slug, $this->getParentMenuUrl())));
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
                'desc'      => __('This service is provided free of charge by <a href="http://www.geoplugin.com/" target="_blank" title="geoPlugin for IP geolocation">geoPlugin</a>', $this->getName()),
            ),
            'cloudflare'    => array(
                'title'     => __('CloudFlare', $this->getName()),
                'checked'   => ($this->options->geo_service == 'cloudflare'),
                'desc'      => __('If you use <a href="http://www.cloudflare.com/" target="_blank" title="CloudFlare">CloudFlare</a>, this will provide you with free and <u>fast</u> access to IP geolocation', $this->getName()),
            ),
            'maxmind'    => array(
                'title'     => __('MaxMind', $this->getName()),
                'checked'   => ($this->options->geo_service == 'maxmind'),
                'desc'      => __('Use a local <a href="http://www.maxmind.com/" target="_blank" title="MaxMind">MaxMind</a> database or Apache module/NginX GeoIP module', $this->getName()),
            ),
        );

        // Get the debug information and add the current detected IP and country to it
        $ip = $this->getRemoteIP();

        $debug_info = array_merge($this->getDebugInfo(), array(
            'Detected IP'           => $ip,
            'Detected Country'      => $this->getCountryName($this->getCountryCode($ip)),
            'Active caching plugin' => ($this->hasActiveCachingPlugin()) ? 'Yes' : 'No',
        ));

        // Export the options, which will be added to vars
        $export_options = $this->options->fetch(array(
            'auto_add_cookies', 'delete_root_cookies', 'php_sessions_required',
            'alert_show', 'alert_content_type', 'alert_content', 'alert_heading', 'alert_ok', 'alert_no',
            'alert_custom_content', 'required_text', 'script_header', 'script_footer', 'debug_mode',
            'js_wrap', 'show_on_unknown_location', 'maxmind_db', 'maxmind_db_v6', 'implied_consent',
            'noscript_tag', 'alert_style', 'alert_custom_style', 'delete_cookies', 'geo_cache_time',
            'geo_backup_service', 'async_ajax', 'max_new_cookies', 'scrub_cookies'
        ));

        $vars = array_merge(array(
            'nonce'                 => wp_nonce_field('onSettingsMenu', '_nonce', true, false),
            'submit_button'         => get_submit_button(),
            'plugin_base_url'       => $this->getPluginUrl(),
            'plugin_name'           => $this->getDisplayName(),
            'plugin_version'        => $this->getVersion(),
            'plugin_home'           => \Pf4wp\Info\PluginInfo::getInfo(false, $this->getPluginBaseName(), 'PluginURI'),
            'countries'             => $this->getCountries(true),
            'geo_services'          => $geo_services,
            'debug_info'            => $debug_info,
            'has_geo_data'          => $this->hasGeoData(),
            'has_caching'           => $this->hasActiveCachingPlugin(),
        ), $export_options);

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

            // Save and invalidate known_cookies np cache
            $this->options->known_cookies = $_POST['known_cookies'];
            $this->np_cache['known_cookies'] = array();

            AdminNotice::add(__('Cookies have been saved', $this->getName()));
        }
    }

    /**
     * Renders the Cookie menu page
     */
    public function onCookiesMenu()
    {
        $known_cookies = $this->deepStripSlashes($this->options->known_cookies);

        // Retrieve a sorted list of group names
        $groups = array();
        array_walk_recursive($known_cookies, function($v, $k) use(&$groups) { if($k == 'group') array_push($groups, $v); });
        $groups = array_unique($groups);
        sort($groups);

        $vars = array(
            'nonce'              => wp_nonce_field('onCookiesMenu', '_nonce', true, false),
            'submit_button'      => get_submit_button(null, 'primary', 'submit', false),
            'known_cookies'      => $known_cookies,
            'known_cookie_count' => count($known_cookies),
            'debug_mode'         => $this->options->debug_mode,
            'groups'             => $groups,
            'is_rtl'             => is_rtl(),
            'action_url'         => add_query_arg(array()),
        );

        $this->template->display('cookies.html.twig', $vars);
    }

    public function onStatsMenuLoad()
    {
        if (!empty($_POST) && isset($_POST['_nonce'])) {
            if (!wp_verify_nonce($_POST['_nonce'], 'onStatsMenu'))
                wp_die(__('You do not have permission to do that [nonce].', $this->getName()));

            if (isset($_POST['clear-stats'])) {
                // Reset statitics
                $year = null;

                if (isset($_POST['stat_year']) && is_numeric($_POST['stat_year']))
                    $year = intval($_POST['stat_year']);

                $this->resetStats($year);
            }

            if (isset($_POST['download-stats'])) {
                // Download statistics
                $this->downloadStats();
            }
        }
    }

    /**
     * Renders the statistics menu page
     */
    public function onStatsMenu()
    {
        $stats = $this->options->stats;
        $date  = getdate();
        $year  = $date['year'];
        $years = array_keys($stats);

        // Sort available years
        sort($years);

        if (!empty($_POST)) {
            // Pick a year
            if (isset($_POST['select-year']) && isset($_POST['stat_year']) && is_numeric($_POST['stat_year']))
                $year = intval($_POST['stat_year']);
        }

        $vars = array(
            'nonce'     => wp_nonce_field('onStatsMenu', '_nonce', true, false),
            'year'      => $year,
            'years'     => (!empty($years)) ? $years : array($year),
            'stats'     => (isset($stats[$year])) ? $stats[$year] : array(),
            'countries' => $this->getCountries(),
        );

        $this->template->display('stats.html.twig', $vars);
    }
}
