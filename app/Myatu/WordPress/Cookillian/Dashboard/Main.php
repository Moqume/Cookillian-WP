<?php

/*
 * Copyright (c) 2012 Mike Green <myatus@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Myatu\WordPress\Cookillian\Dashboard;

use Pf4wp\Dashboard\ControlledDashboardWidget;

/**
 * Dashboard widget, informing about new cookies if any and mini-stats
 *
 * @since 1.0.30
 */
class Main extends ControlledDashboardWidget
{
    protected $title = 'Cookillian';

    protected $new_cookies = array();

    public function register()
    {
        $new_cookies = array();

        // Find out which cookies are new, if there's any
        if ($this->owner->hasNewCookies()) {
            $cookies = $this->owner->options->known_cookies;

            array_walk($cookies, function($v, $k) use (&$new_cookies) { if (isset($v['group']) && $v['group'] == \Myatu\WordPress\Cookillian\Main::UNKNOWN) $new_cookies[] = $k; });
        }

        // Set "new_cookies" to class var
        $this->new_cookies = $new_cookies;

        // Update title
        if (count($new_cookies) > 0)
            $this->title .= ' <span class="awaiting-edit">' . count($new_cookies) . '</span>';

        parent::register();
    }

    public function onCallback()
    {
        // Fetch some statistics
        $curr_stats  = array();
        $stats       = $this->owner->options->stats;
        $max_stats   = $this->owner->options->dashboard_max_stats;
        $date        = getdate();
        $year        = $date['year'];
        $month       = $date['month'];
        $stats_count = 0;

        if (isset($stats[$year][$month])) {
            // Grab the stats for the current month
            $curr_stats  = $stats[$year][$month];

            // Grab the count of all stats in this month (for "More" link)
            $stats_count = count($curr_stats);

            // Sort them by amount displayed $x[0]
            uasort($curr_stats, function($a, $b) { return ($a[0] == $b[0]) ? 0 : (($a[0] > $b[0]) ? -1 : 1); } );

            // Reduce it to only the top $max_stats
            if ($max_stats > 0)
                $curr_stats = array_splice($curr_stats, 0, $max_stats);
        }

        $this->owner->template->display('dashboard_main.html.twig', array(
            'new_cookies'       => implode(', ', $this->new_cookies),
            'new_cookies_count' => count($this->new_cookies),
            'year'              => $year,
            'month'             => $month,
            'stats'             => $curr_stats,
            'stats_count'       => $stats_count,
            'countries'         => $this->owner->getCountries(),
            'max_stats'         => ($max_stats > 0) ? $max_stats : '',
            'cookie_url'        => add_query_arg($this->owner->cookie_menu_slug, $this->owner->getParentMenuUrl()),
            'stats_url'         => add_query_arg($this->owner->stats_menu_slug, $this->owner->getParentMenuUrl()),
        ));
    }

    public function onControlCallback($data)
    {
        if ($data) {
            // Save new settings
            $new_max_stats = $data['max_stats'];
            if ($new_max_stats < 0)
                $new_max_stats = 0;

            $this->owner->options->dashboard_max_stats = $new_max_stats;
        }

        // Load settings
        $max_stats = $this->owner->options->dashboard_max_stats;

        echo '<p>' . __('Show maximum of', $this->owner->getName()) . ' <input name="max_stats" type="number" min="0" value="'  . $max_stats  . '" style="width:50px" /> ' . __('top statistics.', $this->owner->getName())  . '</p>';
    }
}
