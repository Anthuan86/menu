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
 * Points management overview.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/tablelib.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = optional_param('courseid', null, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$page = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 50, PARAM_INT);

require_login();

if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    require_capability('local/points:awardincourse', $context);
} else {
    $context = context_system::instance();
    require_capability('local/points:viewall', $context);
}

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url('/local/points/manage.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('pointsoverview', 'local_points'));
$PAGE->set_heading(get_string('pointsoverview', 'local_points'));
$PAGE->set_pagelayout('admin');

// Form for awarding points.
class award_points_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $courseid = $this->_customdata['courseid'] ?? null;

        $mform->addElement('header', 'awardheader', get_string('awardpoints', 'local_points'));

        // User selector.
        $options = [
            'ajax' => 'core_user/form_user_selector',
            'multiple' => false,
            'courseid' => $courseid ?: SITEID,
            'valuehtmlcallback' => function($value) {
                global $DB, $OUTPUT;
                $user = $DB->get_record('user', ['id' => $value]);
                if (!$user) {
                    return false;
                }
                return $OUTPUT->user_picture($user, ['size' => 24]) . ' ' . fullname($user);
            }
        ];
        $mform->addElement('autocomplete', 'userid', get_string('awardto', 'local_points'), [], $options);
        $mform->addRule('userid', null, 'required', null, 'client');

        // Points.
        $mform->addElement('text', 'points', get_string('pointstoaward', 'local_points'));
        $mform->setType('points', PARAM_INT);
        $mform->addRule('points', null, 'required', null, 'client');
        $mform->addRule('points', null, 'numeric', null, 'client');

        // Reason.
        $mform->addElement('text', 'reason', get_string('awardreason', 'local_points'), ['size' => 50]);
        $mform->setType('reason', PARAM_TEXT);
        $mform->addRule('reason', null, 'required', null, 'client');
        $mform->setDefault('reason', get_string('manuallyawarded', 'local_points'));

        // Hidden course ID.
        if ($courseid) {
            $mform->addElement('hidden', 'courseid', $courseid);
            $mform->setType('courseid', PARAM_INT);
        }

        $this->add_action_buttons(false, get_string('awardpoints', 'local_points'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['points']) || $data['points'] == 0) {
            $errors['points'] = get_string('error_invalidpoints', 'local_points');
        }

        // Check if negative points are allowed.
        if ($data['points'] < 0 && !get_config('local_points', 'allownegative')) {
            $errors['points'] = get_string('error_invalidpoints', 'local_points');
        }

        return $errors;
    }
}

// Process award form.
$mform = new award_points_form(null, ['courseid' => $courseid]);

if ($data = $mform->get_data()) {
    // Award points.
    $success = \local_points\manager::award_points(
        $data->userid,
        $data->points,
        $data->reason,
        $courseid,
        null,
        null,
        $USER->id
    );

    if ($success) {
        redirect(
            new moodle_url('/local/points/manage.php', ['courseid' => $courseid]),
            get_string('pointsawarded', 'local_points'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

echo $OUTPUT->header();

// Award points form.
if (has_capability('local/points:award', $context) ||
    ($courseid && has_capability('local/points:awardincourse', $context))) {
    $mform->display();
}

// Course filter.
if (!$courseid) {
    $courses = get_courses();
    $options = ['' => get_string('globalpoints', 'local_points')];
    foreach ($courses as $course) {
        if ($course->id != SITEID) {
            $options[$course->id] = $course->fullname;
        }
    }

    echo html_writer::start_div('course-filter mb-4');
    $select = new single_select(
        new moodle_url('/local/points/manage.php'),
        'courseid',
        $options,
        $courseid,
        null
    );
    $select->set_label(get_string('selectcourse', 'local_points'));
    echo $OUTPUT->render($select);
    echo html_writer::end_div();
}

// Users list with points.
echo html_writer::tag('h3', get_string('leaderboard', 'local_points'));

$leaderboard = \local_points\manager::get_leaderboard($courseid, 100);

if (empty($leaderboard)) {
    echo html_writer::tag('p', get_string('nopointsyet', 'local_points'), ['class' => 'alert alert-info']);
} else {
    $table = new html_table();
    $table->head = [
        get_string('rank', 'local_points'),
        get_string('user', 'local_points'),
        get_string('points', 'local_points'),
        get_string('actions', 'local_points'),
    ];
    $table->attributes['class'] = 'table table-striped';

    $rank = 1;
    foreach ($leaderboard as $entry) {
        $row = [];

        $row[] = $rank;

        // User.
        $userobj = new stdClass();
        $userobj->id = $entry->userid;
        $userobj->firstname = $entry->firstname;
        $userobj->lastname = $entry->lastname;
        $userobj->email = $entry->email;

        $row[] = $OUTPUT->user_picture($userobj, ['size' => 30]) . ' ' . fullname($userobj);

        $row[] = $entry->points;

        // Actions.
        $actions = [];
        $actions[] = html_writer::link(
            new moodle_url('/local/points/view.php', ['userid' => $entry->userid, 'courseid' => $courseid]),
            get_string('viewhistory', 'local_points'),
            ['class' => 'btn btn-sm btn-outline-primary']
        );
        $row[] = implode(' ', $actions);

        $table->data[] = $row;
        $rank++;
    }

    echo html_writer::table($table);
}

// Link to manage rules and report.
echo html_writer::start_div('mt-3');

if (has_capability('local/points:managerules', $context) ||
    ($courseid && has_capability('local/points:managerulesincourse', $context))) {
    echo html_writer::link(
        new moodle_url('/local/points/rules.php', ['courseid' => $courseid]),
        get_string('managerules', 'local_points'),
        ['class' => 'btn btn-primary mr-2']
    );
}

// Link to report.
echo html_writer::link(
    new moodle_url('/local/points/report.php', ['courseid' => $courseid]),
    get_string('viewreport', 'local_points'),
    ['class' => 'btn btn-info']
);

echo html_writer::end_div();

echo $OUTPUT->footer();
