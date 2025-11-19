<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Settings for local_points.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_points', get_string('pluginname', 'local_points'));

    // Enable/disable plugin.
    $settings->add(new admin_setting_configcheckbox(
        'local_points/enabled',
        get_string('enabled', 'local_points'),
        get_string('enabled_desc', 'local_points'),
        1
    ));

    // Show points in navigation.
    $settings->add(new admin_setting_configcheckbox(
        'local_points/showinnav',
        get_string('showinnav', 'local_points'),
        get_string('showinnav_desc', 'local_points'),
        1
    ));

    // Leaderboard size.
    $settings->add(new admin_setting_configtext(
        'local_points/leaderboardsize',
        get_string('leaderboardsize', 'local_points'),
        get_string('leaderboardsize_desc', 'local_points'),
        10,
        PARAM_INT
    ));

    // Allow negative points.
    $settings->add(new admin_setting_configcheckbox(
        'local_points/allownegative',
        get_string('allownegative', 'local_points'),
        get_string('allownegative_desc', 'local_points'),
        0
    ));

    // Points display name.
    $settings->add(new admin_setting_configtext(
        'local_points/pointsname',
        get_string('pointsname', 'local_points'),
        get_string('pointsname_desc', 'local_points'),
        get_string('points', 'local_points'),
        PARAM_TEXT
    ));

    // Default points for common actions.
    $settings->add(new admin_setting_heading(
        'local_points/defaultsheading',
        get_string('defaultpoints', 'local_points'),
        get_string('defaultpoints_desc', 'local_points')
    ));

    $settings->add(new admin_setting_configtext(
        'local_points/defaultactivitycompletion',
        get_string('defaultactivitycompletion', 'local_points'),
        '',
        10,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_points/defaultcoursecompletion',
        get_string('defaultcoursecompletion', 'local_points'),
        '',
        100,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_points/defaultforumpost',
        get_string('defaultforumpost', 'local_points'),
        '',
        5,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_points/defaultquizsubmission',
        get_string('defaultquizsubmission', 'local_points'),
        '',
        15,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_points/defaultassignmentsubmission',
        get_string('defaultassignmentsubmission', 'local_points'),
        '',
        20,
        PARAM_INT
    ));

    // Add link to manage rules.
    $ADMIN->add('localplugins', $settings);

    // Add rules management page.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_points_rules',
        get_string('managerules', 'local_points'),
        new moodle_url('/local/points/rules.php'),
        'local/points:managerules'
    ));

    // Add global points overview page.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_points_overview',
        get_string('pointsoverview', 'local_points'),
        new moodle_url('/local/points/manage.php'),
        'local/points:viewall'
    ));

    // Add points report page.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_points_report',
        get_string('pointsreport', 'local_points'),
        new moodle_url('/local/points/report.php'),
        'local/points:viewall'
    ));

    // Add store management page.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_points_store',
        get_string('managerewards', 'local_points'),
        new moodle_url('/local/points/store/manage.php'),
        'local/points:managerules'
    ));

    // Add categories management page.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_points_categories',
        get_string('managecategories', 'local_points'),
        new moodle_url('/local/points/store/manage_categories.php'),
        'local/points:managerules'
    ));

    // Add redemptions management page.
    $ADMIN->add('localplugins', new admin_externalpage(
        'local_points_redemptions',
        get_string('manageredemptions', 'local_points'),
        new moodle_url('/local/points/store/manage_redemptions.php'),
        'local/points:managerules'
    ));
}
