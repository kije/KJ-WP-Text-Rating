<?php
/**
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

class KJ_WP_Text_Rating_Settings {

    public function __construct() {
        add_options_page( 'WordPress Text Rating Plugin - Settings', 'Text Rating', 'manage_options', 'kj-wp-text-rating-settings', array($this, 'printSettingsPage') );
    }

    public function printSettingsPage() {
        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }

        include 'settings_page.phtml';
    }
}

function KJ_WP_Text_Rating_Settings() {
    static $KWTRS;
    if (!$KWTRS) {
        $KWTRS = new KJ_WP_Text_Rating_Settings();
    }

    return$KWTRS;
}

add_action( 'admin_menu', 'KJ_WP_Text_Rating_Settings' );