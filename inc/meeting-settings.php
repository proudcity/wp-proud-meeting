<?php

if (!defined('ABSPATH')) exit;

/**
 * Provides settings for Proud Meetings
 *
 * @package Proud\Meeting\Settings
 * @author  Curtis McHale, <curtis@proudcity.com>
 * @license https://opensource.org/licenses/gpl-license.php GNU Public License
 * @see     https://proudcity.com
 */
class ProudMeetingSettings
{

    private static $_instance;

    /**
     * Spins up the instance of the plugin so that we don't get many instances running at once
     *
     * @since  1.0
     * @author SFNdesign, Curtis McHale
     *
     * @uses $instance->init()                      The main get it running function
     *
     * @return null
     */
    public static function instance()
    {

        if (! self::$_instance) {
            self::$_instance = new ProudMeetingSettings();
            self::$_instance->init();
        }
    } // instance

    /**
     * Spins up all the actions/filters in the plugin to really get the engine running
     *
     * @since  1.0
     * @author SFNdesign, Curtis McHale
     *
     * @return null
     */
    public function init()
    {
        add_action('admin_menu', array($this, 'meetingSettingsPage'));

        add_action('admin_init', array($this, 'meetingsRegisterSettings'));
    } // init

    /**
     * Sets up the submenu page
     *
     * @uses add_submenu_page() Sets up our submenu page
     *
     * @return null
     */
    public function meetingSettingsPage()
    {
        add_submenu_page(
            'edit.php?post_type=meeting',     // parent slug (Meetings CPT menu)
            'Meetings Settings',               // page title
            'Settings',                        // menu title
            'proud_admin',                  // capability
            'meetings-settings',               // menu slug
            array($this, 'renderMeetingSettingsPage')    // callback
        );
    }

    /**
     * Gives us the HTML for the settings page
     *
     * @return null
     */
    public function renderMeetingSettingsPage()
    {
?>
        <div class="wrap">
            <h1>Meetings Settings</h1>
            <form method="post" action="options.php">
                <?php settings_fields('meetings_settings_group'); ?>
                <?php do_settings_sections('meetings_settings_group'); ?>

                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><label for="meetings_time_display">Meeting Publish and Modified Times</label></th>
                        <td>
                            <input type="checkbox" id="meetings_time_display" name="meetings_time_display"
                                <?php checked('on', get_option('meetings_time_display'), true); ?>
                                class="regular-text" />
                            <p class="description">Show the published time and last modified time for a meeting.</p>
                        </td>
                    </tr>

                    <tr valign="top">
                        <th scope="row"><label for="advanced_meetings_time_display">Advanced Meeting Publish and Modified Times</label></th>
                        <td>
                            <input type="checkbox" id="advanced_meetings_time_display" name="advanced_meetings_time_display"
                                <?php checked('on', get_option('advanced_meetings_time_display'), true); ?>
                                class="regular-text" />
                            <p class="description">This will show that a meeting was updated if the Agenda, Agenda Packet, or Minutes text is updated or if the attachment field associated with each option is updated.</p>
                        </td>
                    </tr>

                </table>

                <?php submit_button(); ?>
            </form>
        </div>
<?php
    }

    /**
     * Registers the settings
     *
     * @return null
     */
    function meetingsRegisterSettings()
    {
        register_setting('meetings_settings_group', 'meetings_time_display');
        register_setting('meetings_settings_group', 'advanced_meetings_time_display');
    }
}

ProudMeetingSettings::instance();
