<?php
/**
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

defined('ABSPATH') or die("No script kiddies please!");

global $kwtr_db_version;
$kwtr_db_version = "1.0";

define('KJ_WP_TEXT_RATING_DB_TABLE_PREFIX', $wpdb->prefix . 'textrating');

class KJ_WP_Text_Rating_DB
{
    const TABLE_PREFIX = KJ_WP_TEXT_RATING_DB_TABLE_PREFIX;

    const USER_TOKEN_COOKIE_NAME = 'KJ_WP_Text_Rating_DB';
    protected static $user_token = null;

    /**
     * Installs the plugin
     */
    public static function install()
    {
        global $wpdb;
        global $kwtr_db_version;

        $sql = sprintf(
            '
            CREATE TABLE IF NOT EXISTS %1$s_settings (
                settings_key VARCHAR(50) NOT NULL,
                settings_value VARCHAR(300),

                PRIMARY KEY (settings_key,settings_value)
            ) ENGINE = INNODB COLLATE utf8_unicode_ci ;


            CREATE TABLE IF NOT EXISTS %1$s_terms (
                id INT AUTO_INCREMENT,
                name VARCHAR(50) NOT NULL,
                valence DOUBLE NOT NULL DEFAULT 0 COMMENT "Values < 0 means negative rating, > 0 means positive rating",

                PRIMARY KEY (id),
                CONSTRAINT %1$s_rating_words_name UNIQUE (name)
            ) ENGINE = INNODB COLLATE utf8_unicode_ci;

            -- Table for ratings
            CREATE TABLE IF NOT EXISTS %1$s_ratings (
              id INT AUTO_INCREMENT,
              date TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              term_id INT NOT NULL,
              post_id BIGINT(20) NOT NULL,

              user_token VARCHAR(250) NOT NULL COMMENT "Unique Token by User. To prevent multiple ratings from the same visitor on the same post.",

              PRIMARY KEY (id),
              CONSTRAINT %1$s_ratings_terms FOREIGN KEY (term_id) REFERENCES %1$s_terms(id)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
              CONSTRAINT %1$s_ratings_%2$s_posts FOREIGN KEY (post_id) REFERENCES %2$s_posts(ID)
                ON DELETE CASCADE
                ON UPDATE CASCADE,
              CONSTRAINT %1$s_ratings_unique_token  UNIQUE (post_id,rating_token)
            ) ENGINE = INNODB COLLATE utf8_unicode_ci;
            ',
            self::TABLE_PREFIX,
            $wpdb->prefix
        );

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        dbDelta($sql);

        add_option("kwtr_db_version", $kwtr_db_version);
    }

    /**
     * Set a Settings-Value
     * @param $key
     * @param $value
     */
    public static function setSetting($key, $value)
    {
        global $wpdb;

        $wpdb->replace(
            self::TABLE_PREFIX . '_settings',
            array(
                'settings_key' => $key,
                'settings_value' => $value
            ),
            array(
                '%s',
                '%s'
            )
        );
    }

    /**
     * Get all Settings
     * @return mixed
     */
    public static function getSettings()
    {
        global $wpdb;
        return $wpdb->get_results(sprintf('SELECT * FROM %1$s_settings;', self::TABLE_PREFIX), OBJECT);
    }

    /**
     * Get a setting by key
     * @param $key
     * @param null|mixed $default
     * @return null|mixed
     */
    public static function getSetting($key, $default = null)
    {
        global $wpdb;

        $res = $wpdb->get_var(
            sprintf('SELECT settings_value FROM %1$s_settings WHERE settings_key = %2$s;', self::TABLE_PREFIX, $key)
        );
        return ($res ? $res : $default);
    }

    /**
     * Adds a term
     * @param $name
     * @param $valence the valence of the term. Values < 0 means negative rating, > 0 means positive rating
     */
    public static function addTerm($name, $valence)
    {
        global $wpdb;

        $wpdb->replace(
            self::TABLE_PREFIX . '_terms',
            array(
                'name' => $name,
                'valence' => $valence
            ),
            array(
                '%s',
                '%f'
            )
        );
    }

    /**
     * Get all terms
     * @return mixed
     */
    public static function getTerms()
    {
        global $wpdb;
        return $wpdb->get_results(sprintf('SELECT * FROM %1$s_terms;', self::TABLE_PREFIX), OBJECT);
    }


    /**
     * Get a Term by name or id
     * @param $name the name or the id of the Term
     * @return mixed
     */
    public static function getTerm($name)
    {
        global $wpdb;

        return $wpdb->get_row(
            sprintf('SELECT * FROM %1$s_terms WHERE name = %2$s OR id = %2$s;', self::TABLE_PREFIX, $name)
        );
    }


    /**
     * Add a rating
     * @param string|int|object $term the term, its id or the term name
     * @param int|WP_Post $post
     * @param string $rating_token
     */
    public static function addRating($term, $post)
    {
        global $wpdb;

        $post_id = $post;

        if (is_object($post_id)) {
            $post_id = $post_id->ID;
        }

        if (self::userMayRatePost($post)) {
            return;
        }

        $term_id = $term;

        if (!is_numeric($term_id) && is_string($term_id)) {
            $term_id = self::getTerm($term_id);
        }
        if (is_object($term_id)) {
            $term_id = $term_id->id;
        }

        $wpdb->replace(
            self::TABLE_PREFIX . '_ratings',
            array(
                'name' => $term_id,
                'valence' => $post_id,
                'user_token' => self::$user_token
            ),
            array(
                '%d',
                '%d',
                '%s'
            )
        );

        do_action('post_rated');
    }

    /**
     * Get all ratings
     * @return mixed
     */
    public static function getRatings()
    {
        global $wpdb;
        return $wpdb->get_results(sprintf('SELECT * FROM %1$s_ratings;', self::TABLE_PREFIX), OBJECT);
    }

    /**
     * Get Ratings by Post
     * @param WP_Post|int $post
     * @return mixed
     */
    public static function getRatingsByPost($post)
    {
        global $wpdb;
        $post_id = $post;

        if (is_object($post_id)) {
            $post_id = $post_id->ID;
        }

        return $wpdb->get_results(
            sprintf('SELECT * FROM %1$s_ratings WHERE post_id = %2$d;', self::TABLE_PREFIX, $post_id)
        );
    }


    /**
     * @param $post
     * @return mixed
     */
    public static function getGroupedRatingsByPost($post)
    {
        global $wpdb;
        $post_id = $post;

        if (is_object($post_id)) {
            $post_id = $post_id->ID;
        }

        return $wpdb->get_results(
            sprintf(
                'SELECT id,date,sum(termi_id),$post_id,user_token FROM %1$s_ratings WHERE post_id = %2$d GROUP BY post_id;',
                self::TABLE_PREFIX,
                $post_id
            )
        );
    }

    public static function getScore($post)
    {
        global $wpdb;
        $post_id = $post;

        if (is_object($post_id)) {
            $post_id = $post_id->ID;
        }

        return $wpdb->get_var(
            sprintf(
                '
                            SELECT
                                SUM(terms.valence) AS score
                            FROM %1$s_ratings AS rating
                                LEFT JOIN %1$s_terms AS terms
                                    ON terms.id = rating.term_id
                            WHERE rating.post_id = %2$d;
                ',
                self::TABLE_PREFIX,
                $post_id
            )
        );
    }

    public static function getBestTerm() {

    }


    /**
     * @param string|int|object $term the term, its id or the term name
     * @return mixed
     */
    public static function getRatingByTerm($term)
    {
        global $wpdb;

        $term_id = $term;

        if (!is_numeric($term_id) && is_string($term_id)) {
            $term_id = self::getTerm($term_id);
        }
        if (is_object($term_id)) {
            $term_id = $term_id->id;
        }

        return $wpdb->get_results(
            sprintf('SELECT * FROM %1$s_ratings WHERE term_id = %2$d;', self::TABLE_PREFIX, $term_id)
        );
    }

    /**
     * Sets the user-token.
     * Must be called before headers are sent
     */
    public static function tokenizeUser()
    {
        if (headers_sent()) {
            return;
        }

        $token = sha1(
            $_SERVER['REMOTE_ADDR'] .
            microtime()
        );

        if (!self::userHasToken()) {
            setcookie(self::USER_TOKEN_COOKIE_NAME, $token, time() + 60 * 60 * 24 * 365);
            self::$user_token = $token;
        }
    }

    /**
     * Checks if the user has a token
     * @return bool
     */
    public static function userHasToken()
    {
        if (isset($_COOKIE[self::USER_TOKEN_COOKIE_NAME]) && !empty($_COOKIE[self::USER_TOKEN_COOKIE_NAME])) {
            if (!self::$user_token) {
                self::$user_token = $_COOKIE[self::USER_TOKEN_COOKIE_NAME];
            }

            return true;
        }

        return false;
    }

    /**
     * Checks if the user is allowed to rate a post
     * @param $post
     * @return bool
     */
    public static function userMayRatePost($post)
    {
        if (!self::userHasToken()) {
            return false;
        }

        $ratings = self::getRatingsByPost($post);

        foreach ($ratings as $rating) {
            if ($rating->rating_token == self::$user_token) {
                return false;
            }
        }

        return true;
    }
}

register_activation_hook(__FILE__, array('KJ_WP_Text_Rating_DB', 'install'));

KJ_WP_Text_Rating_DB::tokenizeUser();