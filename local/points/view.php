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
 * View user points.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

$userid = optional_param('userid', $USER->id, PARAM_INT);
$courseid = optional_param('courseid', null, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 20, PARAM_INT);

require_login();

$context = context_system::instance();

// Check permissions.
if ($userid == $USER->id) {
    require_capability('local/points:viewown', $context);
} else {
    require_capability('local/points:viewall', $context);
}

// Get user.
$user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url('/local/points/view.php', ['userid' => $userid, 'courseid' => $courseid]);
$PAGE->set_title(get_string('mypoints', 'local_points'));
$PAGE->set_heading(get_string('mypoints', 'local_points'));
$PAGE->set_pagelayout('standard');

// Breadcrumbs.
$PAGE->navbar->add(get_string('mypoints', 'local_points'));

echo $OUTPUT->header();

// User info.
echo $OUTPUT->user_picture($user, ['size' => 100]);
echo html_writer::tag('h2', fullname($user));

// Points summary.
$globalpoints = \local_points\manager::get_points($userid, null);
$totalpoints = \local_points\manager::get_total_points($userid);

echo html_writer::start_div('points-summary card mb-4');
echo html_writer::start_div('card-body');
echo html_writer::start_div('row');

// Global points.
echo html_writer::start_div('col-md-6 text-center');
echo html_writer::tag('h4', get_string('globalpoints', 'local_points'));
echo html_writer::tag('p', $globalpoints, ['class' => 'h1 text-primary']);
echo html_writer::end_div();

// Total points.
echo html_writer::start_div('col-md-6 text-center');
echo html_writer::tag('h4', get_string('totalpoints', 'local_points'));
echo html_writer::tag('p', $totalpoints, ['class' => 'h1 text-success']);
echo html_writer::end_div();

echo html_writer::end_div();
echo html_writer::end_div();
echo html_writer::end_div();

// Course filter.
$courses = enrol_get_users_courses($userid, true);
if (!empty($courses)) {
    echo html_writer::start_div('course-filter mb-4');

    $options = ['' => get_string('allcourses', 'local_points')];
    foreach ($courses as $course) {
        $options[$course->id] = $course->fullname;

        // Show course points.
        $coursepoints = \local_points\manager::get_points($userid, $course->id);
        if ($coursepoints > 0) {
            $options[$course->id] .= " ({$coursepoints} " . get_string('points', 'local_points') . ")";
        }
    }

    $select = new single_select(
        new moodle_url('/local/points/view.php', ['userid' => $userid]),
        'courseid',
        $options,
        $courseid,
        null
    );
    $select->set_label(get_string('selectcourse', 'local_points'));
    echo $OUTPUT->render($select);

    echo html_writer::end_div();
}

// Points history.
echo html_writer::tag('h3', get_string('pointshistory', 'local_points'), ['class' => 'mt-4']);

// Get history.
$history = \local_points\manager::get_history($userid, $courseid);

if (empty($history)) {
    echo html_writer::tag('p', get_string('nohistory', 'local_points'), ['class' => 'alert alert-info']);
} else {
    // Build table.
    $table = new html_table();
    $table->head = [
        get_string('date', 'local_points'),
        get_string('points', 'local_points'),
        get_string('reason', 'local_points'),
    ];

    if (!$courseid) {
        $table->head[] = get_string('course');
    }

    $table->attributes['class'] = 'table table-striped';

    foreach ($history as $record) {
        $row = [];

        // Date.
        $row[] = userdate($record->timecreated, get_string('strftimedatetime', 'langconfig'));

        // Points.
        $pointsclass = $record->points > 0 ? 'text-success' : 'text-danger';
        $pointsprefix = $record->points > 0 ? '+' : '';
        $row[] = html_writer::tag('span', $pointsprefix . $record->points, ['class' => $pointsclass . ' font-weight-bold']);

        // Reason.
        $row[] = format_string($record->reason);

        // Course.
        if (!$courseid) {
            if ($record->courseid) {
                $course = $DB->get_record('course', ['id' => $record->courseid]);
                $row[] = $course ? format_string($course->shortname) : '-';
            } else {
                $row[] = get_string('globalpoints', 'local_points');
            }
        }

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

// Leaderboard section.
echo html_writer::tag('h3', get_string('leaderboard', 'local_points'), ['class' => 'mt-4']);

$leaderboardsize = get_config('local_points', 'leaderboardsize') ?: 10;

if ($courseid) {
    $leaderboard = \local_points\manager::get_course_leaderboard($courseid, $leaderboardsize);
    echo html_writer::tag('h4', get_string('courseleaderboard', 'local_points'));
} else {
    $leaderboard = \local_points\manager::get_leaderboard(null, $leaderboardsize);
    echo html_writer::tag('h4', get_string('globalleaderboard', 'local_points'));
}

if (!empty($leaderboard)) {
    $table = new html_table();
    $table->head = [
        get_string('rank', 'local_points'),
        get_string('user', 'local_points'),
        get_string('points', 'local_points'),
    ];
    $table->attributes['class'] = 'table table-striped leaderboard-table';

    $rank = 1;
    foreach ($leaderboard as $entry) {
        $row = [];

        // Rank.
        $rankclass = '';
        if ($rank == 1) $rankclass = 'text-warning font-weight-bold';
        elseif ($rank == 2) $rankclass = 'text-secondary font-weight-bold';
        elseif ($rank == 3) $rankclass = 'text-danger font-weight-bold';

        $row[] = html_writer::tag('span', $rank, ['class' => $rankclass]);

        // User.
        $userobj = new stdClass();
        $userobj->id = $entry->userid;
        $userobj->firstname = $entry->firstname;
        $userobj->lastname = $entry->lastname;
        $userobj->email = $entry->email;

        $userpic = $OUTPUT->user_picture($userobj, ['size' => 30]);
        $username = fullname($userobj);

        if ($entry->userid == $USER->id) {
            $username = html_writer::tag('strong', $username);
        }

        $row[] = $userpic . ' ' . $username;

        // Points.
        $row[] = html_writer::tag('span', $entry->points, ['class' => 'font-weight-bold text-primary']);

        $table->data[] = $row;
        $rank++;
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
