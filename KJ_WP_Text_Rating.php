<?php
/**
 * Plugin Name: WordPress Text Rating Plugin
 * Plugin URI: TODO
 * Description: A WordPress plugin that enables Visitors on your Blog to rate Posts (and Pages) with custom expression like "Awesome", "Great" or "Bad". Additionally this Plugin provides a way for Themes to get the best rated Posts.
 * Version: 1.0
 * Author: Kim D. Jeker
 * Author URI: http://kije.ch
 * License: GPLv3
    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

defined('ABSPATH') or die("No script kiddies please!");

require_once 'KJ_WP_Text_Rating_DB.php';
include_once 'KJ_WP_Text_Rating_Settings.php';

class KJ_WP_Text_Rating {

    public function __construct() {
        $this->addActions();
        $this->addFilters();
        $this->addOptions();
    }

    public function addActions() {

    }

    public function addFilters() {

    }

    public function addOptions() {

    }

}

function KJ_WP_Text_Rating() {
    static $KWTR;

    if (!$KWTR) {
        $KWTR = new KJ_WP_Text_Rating();
    }

    return $KWTR;
}

add_action(
    'init',
    'KJ_WP_Text_Rating'
);