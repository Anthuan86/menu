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

// Check permissions - only require capability when viewing other users.
if ($userid != $USER->id) {
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
echo html_writer::start_div('text-center mb-4');
echo $OUTPUT->user_picture($user, ['size' => 100]);
echo html_writer::tag('h2', fullname($user));
echo html_writer::end_div();

// Get total points (sum of all courses and global).
$totalpoints = $DB->get_field_sql(
    'SELECT COALESCE(SUM(points), 0) FROM {local_points_user} WHERE userid = :userid',
    ['userid' => $userid]
);
$totalpoints = $totalpoints ? $totalpoints : 0;

// Points display - only totals.
echo html_writer::start_div('points-summary card mb-4');
echo html_writer::start_div('card-body text-center');
echo html_writer::tag('h3', get_string('totalpoints', 'local_points'));
echo html_writer::tag('p', number_format($totalpoints), ['class' => 'display-1 text-primary font-weight-bold']);
echo html_writer::tag('p', get_string('points', 'local_points'), ['class' => 'text-muted']);
echo html_writer::end_div();
echo html_writer::end_div();

// Link to store.
echo html_writer::start_div('text-center mb-4');
echo html_writer::link(
    new moodle_url('/local/points/store/index.php'),
    get_string('gotostore', 'local_points'),
    ['class' => 'btn btn-lg btn-primary']
);
echo html_writer::end_div();

// Points history section.
echo html_writer::tag('h3', get_string('pointshistory', 'local_points'), ['class' => 'mt-4 mb-3']);

// Get history.
$sql = "SELECT h.*, c.shortname as coursename
        FROM {local_points_history} h
        LEFT JOIN {course} c ON c.id = h.courseid
        WHERE h.userid = :userid
        ORDER BY h.timecreated DESC";
$history = $DB->get_records_sql($sql, ['userid' => $userid], 0, 50);

if (empty($history)) {
    echo html_writer::tag('p', get_string('nohistory', 'local_points'), ['class' => 'alert alert-info']);
} else {
    // Build table.
    $table = new html_table();
    $table->head = [
        get_string('date', 'local_points'),
        get_string('points', 'local_points'),
        get_string('reason', 'local_points'),
        get_string('course'),
    ];
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
        if ($record->coursename) {
            $row[] = $record->coursename;
        } else {
            $row[] = get_string('globalpoints', 'local_points');
        }

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
