<?php

/*
Plugin Name: Our Test Plugin
Description: A truly amazing plugin.
Version: 1.0
Author: Jelena
Author URI: https://github.com/JelenaTakac
Text Domain: wcpdomain
Domain Path: /languages
*/

// add_filter('the_content', 'addToEndOfPost');

// function addToEndOfPost($content) {
//     if (is_single() && is_main_query()) {
//         return $content . '<p>My name is Jelena</p>';
//     }

//     return $content;
// }

class WordCountAndTimePlugin
{
    function __construct()
    {
        add_action('admin_menu', array($this, 'adminPage')); // 'admin_menu' - Fires before the administration menu loads in the admin.
        add_action('admin_init', array($this, 'settings')); // 'admin_init' is triggered before any other hook when a user accesses the admin area.
        add_filter('the_content', array($this, 'ifWrap'));

        // Tell WordPress to load our text domain files
        add_action('init', array($this, 'languages'));
    }

    function languages() {
        load_plugin_textdomain('wcpdomain', false, dirname(plugin_basename(__FILE__)) . '/languages'); // load_plugin_textdomain - Loads a plugin’s translated strings.
    }

    function ifWrap($content) {
        if (is_single() AND is_main_query() AND 
        (get_option('wcp_wordcount', '1') OR
            get_option('wcp_charactercount', '1') OR
            get_option('wcp_readtime', '1')
        )) {
            return $this->createHTML($content);
        } 
        return $content;
    }

    function createHTML($content) {
        $html = '<h3>'  . esc_html(get_option('wcp_headline', 'Post Statistics')) .  '</h3><p>';

        if (get_option('wcp_wordcount', '1') OR get_option('wcp_readtime', '1')) {
            $wordCount = str_word_count(strip_tags($content));
        }
        
        if (get_option('wcp_wordcount', '1')) {
            $html .= esc_html__('This post has', 'wcpdomain') . ' ' . $wordCount . ' ' . __('words', 'wcpdomain') . '.<br>';
        }

        if (get_option('wcp_charactercount', '1')) {
            $html .= 'This post has ' . strlen(strip_tags($content)) . ' characters.<br>';
        }

        if (get_option('wcp_readtime', '1')) {
            $html .= 'This post will take about ' . round($wordCount/225) . ' minute(s) to read.<br>';
        }

        if (get_option('wcp_location', '0') == '0') {
            // on beggining
            return $html . $content;
        } 
        return $content . $html;
    }

    function settings()
    {
        add_settings_section('wcp_first_section', null, null, 'word-count-settings-page'); // add_settings_section - Adds a new section to a settings page.

        add_settings_field('wcp_location', 'Display Location', array($this, 'locationHTML'), 'word-count-settings-page', 'wcp_first_section'); // add_settings_field - Adds a new field to a section of a settings page.
        register_setting('wordcountplugin', 'wcp_location', array('sanitize_callback' => array($this, 'sanitizeLocation'), 'default' => '0')); // register_setting - Registers a setting and its data inside database

        add_settings_field('wcp_headline', 'Headline Text', array($this, 'headlineHTML'), 'word-count-settings-page', 'wcp_first_section');
        register_setting('wordcountplugin', 'wcp_headline', array('sanitize_callback' => 'sanitize_text_field', 'default' => 'Post Statistics'));

        add_settings_field('wcp_wordcount', 'Word Count', array($this, 'checkboxHTML'), 'word-count-settings-page', 'wcp_first_section', array('theName' => 'wcp_wordcount'));
        register_setting('wordcountplugin', 'wcp_wordcount', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1'));

        add_settings_field('wcp_charactercount', 'Character Count', array($this, 'checkboxHTML'), 'word-count-settings-page', 'wcp_first_section', array('theName' => 'wcp_charactercount'));
        register_setting('wordcountplugin', 'wcp_charactercount', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1'));

        add_settings_field('wcp_readtime', 'Read Time', array($this, 'checkboxHTML'), 'word-count-settings-page', 'wcp_first_section', array('theName' => 'wcp_readtime'));
        register_setting('wordcountplugin', 'wcp_readtime', array('sanitize_callback' => 'sanitize_text_field', 'default' => '1'));
        
    }

    function sanitizeLocation($input)
    {
        if ($input != '0' and $input != '1') {
            add_settings_error('wcp_location', 'wcp_location_error', 'Display location must be either beginning or end.');
            return get_option('wcp_location');
        }
        return $input;
    }

    function checkboxHTML($args)
    { ?>
        <input type="checkbox" name="<?php echo $args['theName'] ?>" value="1" <?php checked(get_option($args['theName']), '1') ?>>
    <?php }


    function locationHTML()
    { ?>
        <select name="wcp_location">
            <option value="0" <?php selected(get_option('wcp_location'), '0') ?>>Beginning of post</option>
            <option value="1" <?php selected(get_option('wcp_location'), '1') ?>>End of post</option>
        </select>
    <?php }

    function headlineHTML()
    { ?>
        <input type="text" name="wcp_headline" value="<?php echo esc_attr(get_option('wcp_headline')); ?>">
    <?php }


    /*
    function wordcountHTML()
    { ?>
        <input type="checkbox" name="wcp_wordcount" value="1" <?php checked(get_option('wcp_wordcount'), '1') ?>>
    <?php }

    function charactercountHTML()
    { ?>
        <input type="checkbox" name="wcp_charactercount" value="1" <?php checked(get_option('wcp_charactercount'), '1') ?>>
    <?php }

    function readtimeHTML()
    { ?>
        <input type="checkbox" name="wcp_readtime" value="1" <?php checked(get_option('wcp_readtime'), '1') ?>>
    <?php }
    */

    function adminPage()
    {
        // add_options_page - Adds a submenu page to the Settings main menu.
        add_options_page('Word Count Settings', __('Word Count', 'wcpdomain'), 'manage_options', 'word-count-settings-page', array($this, 'ourHTML'));
    }

    // this function will render the our HTML
    function ourHTML()
    { ?>
        <div class="wrap">
            <h1>Word Count Settings</h1>
            <form action="options.php" method="POST">
                <?php
                settings_fields('wordcountplugin'); // settings_fields - Outputs nonce, action, and option_page fields for a settings page (WordPress will do wverything for us - add the appropriate hidden HTML fields with the nonce value, action value. It's going to handle sort of the security and permission aspects for us.)
                do_settings_sections('word-count-settings-page'); // do_settings_sections - Prints out all settings sections added to a particular settings page
                submit_button();
                ?>
            </form>
        </div>
<?php }
}

$wordCountAndTimePlugin = new WordCountAndTimePlugin();


?>