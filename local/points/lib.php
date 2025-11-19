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
 * Library functions for local_points.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Extend navigation to add points link.
 *
 * @param global_navigation $navigation
 */
function local_points_extend_navigation(global_navigation $navigation) {
    global $USER, $PAGE;

    if (!isloggedin() || isguestuser()) {
        return;
    }

    // Add to user menu.
    if (has_capability('local/points:viewown', \context_system::instance())) {
        $node = $navigation->add(
            get_string('mypoints', 'local_points'),
            new moodle_url('/local/points/view.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_points_mypoints',
            new pix_icon('i/grades', '')
        );

        // Add store link.
        $navigation->add(
            get_string('store', 'local_points'),
            new moodle_url('/local/points/store/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_points_store',
            new pix_icon('i/cart', '')
        );
    }
}

/**
 * Extend settings navigation for courses.
 *
 * @param settings_navigation $settingsnav
 * @param context $context
 */
function local_points_extend_settings_navigation(settings_navigation $settingsnav, context $context) {
    global $PAGE;

    if ($context->contextlevel != CONTEXT_COURSE) {
        return;
    }

    $courseid = $context->instanceid;

    if ($courseid == SITEID) {
        return;
    }

    // Find course admin node.
    if ($coursenode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        if (has_capability('local/points:viewcourse', $context)) {
            $url = new moodle_url('/local/points/course.php', ['courseid' => $courseid]);
            $node = navigation_node::create(
                get_string('coursepoints', 'local_points'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'local_points_course',
                new pix_icon('i/grades', '')
            );
            $coursenode->add_node($node);
        }

        if (has_capability('local/points:managerulesincourse', $context)) {
            $url = new moodle_url('/local/points/rules.php', ['courseid' => $courseid]);
            $node = navigation_node::create(
                get_string('managerules', 'local_points'),
                $url,
                navigation_node::TYPE_SETTING,
                null,
                'local_points_rules',
                new pix_icon('i/settings', '')
            );
            $coursenode->add_node($node);
        }
    }
}

/**
 * Add points block to course.
 *
 * @param core_renderer $output
 * @return string HTML
 */
function local_points_render_course_block($output) {
    global $USER, $COURSE;

    if (!isloggedin() || isguestuser()) {
        return '';
    }

    $context = \context_course::instance($COURSE->id);

    if (!has_capability('local/points:viewcourse', $context)) {
        return '';
    }

    $points = \local_points\manager::get_points($USER->id, $COURSE->id);

    $html = html_writer::start_div('local-points-block card');
    $html .= html_writer::start_div('card-body');
    $html .= html_writer::tag('h5', get_string('yourpoints', 'local_points'), ['class' => 'card-title']);
    $html .= html_writer::tag('p', $points, ['class' => 'points-value h2 text-primary']);
    $html .= html_writer::link(
        new moodle_url('/local/points/view.php', ['courseid' => $COURSE->id]),
        get_string('viewdetails', 'local_points'),
        ['class' => 'btn btn-sm btn-outline-primary']
    );
    $html .= html_writer::end_div();
    $html .= html_writer::end_div();

    return $html;
}

/**
 * Fragment to render points summary.
 *
 * @param array $args Arguments
 * @return string HTML
 */
function local_points_output_fragment_points_summary($args) {
    global $OUTPUT, $USER;

    $userid = $args['userid'] ?? $USER->id;
    $courseid = $args['courseid'] ?? null;

    $points = \local_points\manager::get_points($userid, $courseid);
    $totalpoints = \local_points\manager::get_total_points($userid);
    $history = \local_points\manager::get_history($userid, $courseid, 5);

    $data = [
        'points' => $points,
        'totalpoints' => $totalpoints,
        'hashistory' => !empty($history),
        'history' => array_values(array_map(function($item) {
            return [
                'points' => $item->points,
                'reason' => $item->reason,
                'date' => userdate($item->timecreated, get_string('strftimedatetime', 'langconfig')),
                'positive' => $item->points > 0,
            ];
        }, $history)),
    ];

    return $OUTPUT->render_from_template('local_points/points_summary', $data);
}

/**
 * Get user's points for display in user profile.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user
 * @param bool $iscurrentuser
 * @param stdClass $course
 */
function local_points_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    global $USER;

    if (!has_capability('local/points:viewown', \context_system::instance())) {
        return;
    }

    // Check if user can view this profile's points.
    if (!$iscurrentuser && !has_capability('local/points:viewall', \context_system::instance())) {
        return;
    }

    $category = new \core_user\output\myprofile\category('local_points', get_string('pluginname', 'local_points'), 'contact');
    $tree->add_category($category);

    $totalpoints = \local_points\manager::get_total_points($user->id);
    $globalpoints = \local_points\manager::get_points($user->id, null);

    $tree->add_node(new \core_user\output\myprofile\node(
        'local_points',
        'totalpoints',
        get_string('totalpoints', 'local_points'),
        null,
        null,
        $totalpoints
    ));

    $tree->add_node(new \core_user\output\myprofile\node(
        'local_points',
        'globalpoints',
        get_string('globalpoints', 'local_points'),
        null,
        null,
        $globalpoints
    ));

    $url = new moodle_url('/local/points/view.php', ['userid' => $user->id]);
    $tree->add_node(new \core_user\output\myprofile\node(
        'local_points',
        'viewhistory',
        get_string('viewhistory', 'local_points'),
        null,
        $url
    ));
}

/**
 * Serves any files associated with the plugin.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param context $context
 * @param string $filearea
 * @param array $args
 * @param bool $forcedownload
 * @param array $options
 * @return bool
 */
function local_points_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    if ($filearea === 'rewardimage') {
        $itemid = array_shift($args);
        $filename = array_pop($args);
        $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

        $fs = get_file_storage();
        $file = $fs->get_file($context->id, 'local_points', $filearea, $itemid, $filepath, $filename);

        if (!$file || $file->is_directory()) {
            return false;
        }

        send_stored_file($file, 0, 0, $forcedownload, $options);
        return true;
    }

    return false;
}
