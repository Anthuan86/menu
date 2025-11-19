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
 * Points report - Shows who earned points and under which rules.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');

$courseid = optional_param('courseid', null, PARAM_INT);
$ruleid = optional_param('ruleid', 0, PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);
$datefrom = optional_param('datefrom', 0, PARAM_INT);
$dateto = optional_param('dateto', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

require_login();

if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    require_capability('local/points:viewcourse', $context);
} else {
    $context = context_system::instance();
    require_capability('local/points:viewall', $context);
}

// Setup page.
$PAGE->set_context($context);
$url = new moodle_url('/local/points/report.php', ['courseid' => $courseid]);
$PAGE->set_url($url);
$PAGE->set_title(get_string('pointsreport', 'local_points'));
$PAGE->set_heading(get_string('pointsreport', 'local_points'));
$PAGE->set_pagelayout('report');

// Build the table.
$table = new flexible_table('local_points_report');

$columns = ['timecreated', 'user', 'points', 'reason', 'rulename', 'course', 'awardedby'];
$headers = [
    get_string('date', 'local_points'),
    get_string('user', 'local_points'),
    get_string('points', 'local_points'),
    get_string('reason', 'local_points'),
    get_string('rulename', 'local_points'),
    get_string('course'),
    get_string('awardedby', 'local_points'),
];

$table->define_columns($columns);
$table->define_headers($headers);
$table->define_baseurl($url);
$table->sortable(true, 'timecreated', SORT_DESC);
$table->collapsible(true);
$table->initialbars(true);
$table->pageable(true);
$table->is_downloadable(true);
$table->show_download_buttons_at([TABLE_P_BOTTOM, TABLE_P_TOP]);

// Setup for downloading.
$table->setup();

if ($table->is_downloading()) {
    // No output before table for downloading.
} else {
    echo $OUTPUT->header();

    // Filters form.
    echo html_writer::start_tag('form', ['method' => 'get', 'action' => $url, 'class' => 'mb-4']);
    echo html_writer::start_div('card');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('filters', 'local_points'), ['class' => 'card-title']);

    echo html_writer::start_div('row');

    // Rule filter.
    echo html_writer::start_div('col-md-3 mb-2');
    $rules = $DB->get_records_menu('local_points_rules', null, 'name', 'id, name');
    $rules = [0 => get_string('allrules', 'local_points')] + $rules;
    echo html_writer::label(get_string('rulename', 'local_points'), 'ruleid', true, ['class' => 'd-block']);
    echo html_writer::select($rules, 'ruleid', $ruleid, null, ['class' => 'form-control', 'id' => 'ruleid']);
    echo html_writer::end_div();

    // User filter - dropdown with users who have points.
    echo html_writer::start_div('col-md-3 mb-2');

    // Get users who have points in history.
    $usersql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
                FROM {user} u
                JOIN {local_points_history} h ON h.userid = u.id
                ORDER BY u.lastname, u.firstname";
    $userswitpoints = $DB->get_records_sql($usersql);

    $useroptions = [0 => get_string('allusers', 'local_points')];
    foreach ($userswitpoints as $u) {
        $useroptions[$u->id] = fullname($u);
    }

    echo html_writer::label(get_string('user', 'local_points'), 'userid', true, ['class' => 'd-block']);
    echo html_writer::select($useroptions, 'userid', $userid, null, ['class' => 'form-control', 'id' => 'userid']);
    echo html_writer::end_div();

    // Course filter (if not already filtered).
    if (!$courseid) {
        echo html_writer::start_div('col-md-3 mb-2');
        $courses = get_courses();
        $courseoptions = ['' => get_string('allcourses', 'local_points')];
        foreach ($courses as $c) {
            if ($c->id != SITEID) {
                $courseoptions[$c->id] = $c->shortname;
            }
        }
        echo html_writer::label(get_string('course'), 'filtercourseid', true, ['class' => 'd-block']);
        echo html_writer::select($courseoptions, 'courseid', $courseid, null, ['class' => 'form-control', 'id' => 'filtercourseid']);
        echo html_writer::end_div();
    } else {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'courseid', 'value' => $courseid]);
    }

    // Submit button.
    echo html_writer::start_div('col-md-3 mb-2');
    echo html_writer::empty_tag('br');
    echo html_writer::empty_tag('input', [
        'type' => 'submit',
        'value' => get_string('filter', 'local_points'),
        'class' => 'btn btn-primary',
    ]);
    echo ' ';
    echo html_writer::link($url, get_string('reset'), ['class' => 'btn btn-secondary']);
    echo html_writer::end_div();

    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_tag('form');

    // Summary statistics.
    echo html_writer::start_div('row mb-4');

    // Total points awarded.
    $totalparams = [];
    $totalwhere = [];

    if ($courseid) {
        $totalwhere[] = 'courseid = :courseid';
        $totalparams['courseid'] = $courseid;
    }
    if ($ruleid) {
        $totalwhere[] = 'ruleid = :ruleid';
        $totalparams['ruleid'] = $ruleid;
    }
    if ($userid) {
        $totalwhere[] = 'userid = :userid';
        $totalparams['userid'] = $userid;
    }

    $totalwheresql = !empty($totalwhere) ? 'WHERE ' . implode(' AND ', $totalwhere) : '';

    $totalawards = $DB->count_records_sql(
        "SELECT COUNT(*) FROM {local_points_history} $totalwheresql",
        $totalparams
    );

    $totalpoints = $DB->get_field_sql(
        "SELECT COALESCE(SUM(points), 0) FROM {local_points_history} $totalwheresql",
        $totalparams
    );

    $uniqueusers = $DB->get_field_sql(
        "SELECT COUNT(DISTINCT userid) FROM {local_points_history} $totalwheresql",
        $totalparams
    );

    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card text-center');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('totalawards', 'local_points'), ['class' => 'card-title']);
    echo html_writer::tag('p', $totalawards, ['class' => 'h2 text-primary']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card text-center');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('totalpointsawarded', 'local_points'), ['class' => 'card-title']);
    echo html_writer::tag('p', $totalpoints, ['class' => 'h2 text-success']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::start_div('col-md-4');
    echo html_writer::start_div('card text-center');
    echo html_writer::start_div('card-body');
    echo html_writer::tag('h5', get_string('uniqueusers', 'local_points'), ['class' => 'card-title']);
    echo html_writer::tag('p', $uniqueusers, ['class' => 'h2 text-info']);
    echo html_writer::end_div();
    echo html_writer::end_div();
    echo html_writer::end_div();

    echo html_writer::end_div();
}

// Build query.
$where = [];
$params = [];

if ($courseid) {
    $where[] = 'h.courseid = :courseid';
    $params['courseid'] = $courseid;
}

if ($ruleid) {
    $where[] = 'h.ruleid = :ruleid';
    $params['ruleid'] = $ruleid;
}

if ($userid) {
    $where[] = 'h.userid = :userid';
    $params['userid'] = $userid;
}

$wheresql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total records.
$total = $DB->count_records_sql("SELECT COUNT(*) FROM {local_points_history} h $wheresql", $params);

// Get sort.
$sort = $table->get_sql_sort();
if (empty($sort)) {
    $sort = 'h.timecreated DESC';
} else {
    // Fix sort column references.
    $sort = str_replace('timecreated', 'h.timecreated', $sort);
    $sort = str_replace('points', 'h.points', $sort);
}

// Get records.
$sql = "SELECT h.id, h.userid, h.courseid, h.points, h.reason, h.ruleid, h.timecreated, h.awardedby,
               u.firstname, u.lastname, u.email,
               r.name as rulename,
               c.shortname as coursename,
               ab.firstname as awardedbyfirst, ab.lastname as awardedbylast
        FROM {local_points_history} h
        JOIN {user} u ON u.id = h.userid
        LEFT JOIN {local_points_rules} r ON r.id = h.ruleid
        LEFT JOIN {course} c ON c.id = h.courseid
        LEFT JOIN {user} ab ON ab.id = h.awardedby
        $wheresql
        ORDER BY $sort";

$records = $DB->get_records_sql($sql, $params, $page * $perpage, $perpage);

// Populate table.
foreach ($records as $record) {
    $row = [];

    // Date.
    $row[] = userdate($record->timecreated, get_string('strftimedatetime', 'langconfig'));

    // User.
    $userlink = html_writer::link(
        new moodle_url('/local/points/view.php', ['userid' => $record->userid]),
        fullname($record)
    );
    $row[] = $userlink;

    // Points.
    $pointsclass = $record->points > 0 ? 'text-success' : 'text-danger';
    $pointsprefix = $record->points > 0 ? '+' : '';
    $row[] = html_writer::tag('span', $pointsprefix . $record->points, ['class' => $pointsclass . ' font-weight-bold']);

    // Reason.
    $row[] = format_string($record->reason);

    // Rule name.
    if ($record->rulename) {
        $rulelink = html_writer::link(
            new moodle_url('/local/points/rules.php', ['action' => 'edit', 'ruleid' => $record->ruleid]),
            format_string($record->rulename)
        );
        $row[] = $rulelink;
    } else {
        $row[] = html_writer::tag('em', get_string('manuallyawarded', 'local_points'));
    }

    // Course.
    if ($record->coursename) {
        $row[] = $record->coursename;
    } else {
        $row[] = get_string('globalpoints', 'local_points');
    }

    // Awarded by.
    if ($record->awardedby) {
        $row[] = $record->awardedbyfirst . ' ' . $record->awardedbylast;
    } else {
        $row[] = get_string('system', 'local_points');
    }

    $table->add_data($row);
}

$table->pagesize($perpage, $total);
$table->finish_output();

if (!$table->is_downloading()) {
    // Links.
    echo html_writer::start_div('mt-4');
    echo html_writer::link(
        new moodle_url('/local/points/manage.php', ['courseid' => $courseid]),
        get_string('backtooverview', 'local_points'),
        ['class' => 'btn btn-secondary']
    );
    echo ' ';
    echo html_writer::link(
        new moodle_url('/local/points/rules.php', ['courseid' => $courseid]),
        get_string('managerules', 'local_points'),
        ['class' => 'btn btn-primary']
    );
    echo html_writer::end_div();

    echo $OUTPUT->footer();
}
