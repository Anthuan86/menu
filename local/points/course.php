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
 * Course points view.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

$courseid = required_param('courseid', PARAM_INT);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);

require_login($course);

$context = context_course::instance($courseid);
require_capability('local/points:viewcourse', $context);

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url('/local/points/course.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('coursepoints', 'local_points'));
$PAGE->set_heading($course->fullname . ' - ' . get_string('coursepoints', 'local_points'));
$PAGE->set_pagelayout('incourse');

echo $OUTPUT->header();

// Current user's points.
$mypoints = \local_points\manager::get_points($USER->id, $courseid);

echo html_writer::start_div('my-points-card card mb-4');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('h4', get_string('yourpoints', 'local_points'));
echo html_writer::tag('p', $mypoints, ['class' => 'h1 text-primary']);
echo html_writer::link(
    new moodle_url('/local/points/view.php', ['userid' => $USER->id, 'courseid' => $courseid]),
    get_string('viewhistory', 'local_points'),
    ['class' => 'btn btn-outline-primary']
);
echo html_writer::end_div();
echo html_writer::end_div();

// Course leaderboard.
echo html_writer::tag('h3', get_string('courseleaderboard', 'local_points'));

$leaderboardsize = get_config('local_points', 'leaderboardsize') ?: 10;
$leaderboard = \local_points\manager::get_course_leaderboard($courseid, $leaderboardsize);

if (empty($leaderboard)) {
    echo html_writer::tag('p', get_string('nopointsyet', 'local_points'), ['class' => 'alert alert-info']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('rank', 'local_points'),
        get_string('user', 'local_points'),
        get_string('points', 'local_points'),
    ];
    $table->attributes['class'] = 'table table-striped';

    $rank = 1;
    foreach ($leaderboard as $entry) {
        $row = [];

        // Rank with medal styling.
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
        $userobj->picture = $entry->picture ?? 0;
        $userobj->imagealt = $entry->imagealt ?? '';
        $userobj->firstnamephonetic = $entry->firstnamephonetic ?? '';
        $userobj->lastnamephonetic = $entry->lastnamephonetic ?? '';
        $userobj->middlename = $entry->middlename ?? '';
        $userobj->alternatename = $entry->alternatename ?? '';

        $userpic = $OUTPUT->user_picture($userobj, ['size' => 35]);
        $username = fullname($userobj);

        // Highlight current user.
        if ($entry->userid == $USER->id) {
            $username = html_writer::tag('strong', $username . ' (' . get_string('you', 'moodle') . ')');
        }

        $row[] = $userpic . ' ' . $username;

        // Points.
        $row[] = html_writer::tag('span', $entry->points, ['class' => 'font-weight-bold text-primary']);

        $table->data[] = $row;
        $rank++;
    }

    echo html_writer::table($table);
}

// Admin links.
if (has_capability('local/points:awardincourse', $context)) {
    echo html_writer::start_div('admin-links mt-4');
    echo html_writer::link(
        new moodle_url('/local/points/manage.php', ['courseid' => $courseid]),
        get_string('awardpoints', 'local_points'),
        ['class' => 'btn btn-primary mr-2']
    );

    if (has_capability('local/points:managerulesincourse', $context)) {
        echo html_writer::link(
            new moodle_url('/local/points/rules.php', ['courseid' => $courseid]),
            get_string('managerules', 'local_points'),
            ['class' => 'btn btn-secondary']
        );
    }
    echo html_writer::end_div();
}

echo $OUTPUT->footer();
