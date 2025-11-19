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
 * Manage point rules.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

$courseid = optional_param('courseid', null, PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$ruleid = optional_param('ruleid', 0, PARAM_INT);

require_login();

if ($courseid) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
    $context = context_course::instance($courseid);
    require_capability('local/points:managerulesincourse', $context);
} else {
    $context = context_system::instance();
    require_capability('local/points:managerules', $context);
}

// Setup page.
$PAGE->set_context($context);
$PAGE->set_url('/local/points/rules.php', ['courseid' => $courseid]);
$PAGE->set_title(get_string('managerules', 'local_points'));
$PAGE->set_heading(get_string('managerules', 'local_points'));
$PAGE->set_pagelayout('admin');

// Rule form.
class rule_form extends moodleform {
    protected function definition() {
        $mform = $this->_form;
        $rule = $this->_customdata['rule'] ?? null;
        $courseid = $this->_customdata['courseid'] ?? null;

        // Rule name.
        $mform->addElement('text', 'name', get_string('rulename', 'local_points'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');

        // Description.
        $mform->addElement('textarea', 'description', get_string('ruledescription', 'local_points'),
            ['rows' => 3, 'cols' => 50]);
        $mform->setType('description', PARAM_TEXT);

        // Event selector.
        $events = \local_points\manager::get_available_events();
        $mform->addElement('select', 'eventname', get_string('ruleevent', 'local_points'), $events);
        $mform->addRule('eventname', null, 'required', null, 'client');

        // Points.
        $mform->addElement('text', 'points', get_string('rulepoints', 'local_points'));
        $mform->setType('points', PARAM_INT);
        $mform->addRule('points', null, 'required', null, 'client');
        $mform->setDefault('points', 10);

        // Course scope.
        if (!$courseid) {
            $courses = get_courses();
            $options = ['' => get_string('globalrule', 'local_points')];
            foreach ($courses as $course) {
                if ($course->id != SITEID) {
                    $options[$course->id] = $course->fullname;
                }
            }
            $mform->addElement('select', 'courseid', get_string('rulecourse', 'local_points'), $options);
        } else {
            $mform->addElement('hidden', 'courseid', $courseid);
            $mform->setType('courseid', PARAM_INT);
        }

        // Max awards.
        $mform->addElement('text', 'maxawards', get_string('rulemaxawards', 'local_points'));
        $mform->setType('maxawards', PARAM_INT);
        $mform->addHelpButton('maxawards', 'rulemaxawards', 'local_points');

        // Enabled.
        $mform->addElement('advcheckbox', 'enabled', get_string('ruleenabled', 'local_points'));
        $mform->setDefault('enabled', 1);

        // Conditions.
        $mform->addElement('header', 'conditionsheader', get_string('ruleconditions', 'local_points'));

        $mform->addElement('text', 'condition_min_grade', get_string('condition_min_grade', 'local_points'));
        $mform->setType('condition_min_grade', PARAM_INT);

        // Hidden rule ID for editing.
        if ($rule) {
            $mform->addElement('hidden', 'ruleid', $rule->id);
            $mform->setType('ruleid', PARAM_INT);
        }

        $this->add_action_buttons(true, $rule ? get_string('editrule', 'local_points') : get_string('createrule', 'local_points'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['name'])) {
            $errors['name'] = get_string('error_rulerequired', 'local_points');
        }

        if (empty($data['eventname'])) {
            $errors['eventname'] = get_string('error_rulerequired', 'local_points');
        }

        return $errors;
    }
}

// Handle delete action.
if ($action === 'delete' && $ruleid) {
    require_sesskey();

    $rule = $DB->get_record('local_points_rules', ['id' => $ruleid], '*', MUST_EXIST);

    // Check permission.
    if ($rule->courseid) {
        require_capability('local/points:managerulesincourse', context_course::instance($rule->courseid));
    } else {
        require_capability('local/points:managerules', context_system::instance());
    }

    \local_points\manager::delete_rule($ruleid);

    redirect(
        new moodle_url('/local/points/rules.php', ['courseid' => $courseid]),
        get_string('ruledeleted', 'local_points'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Get rule for editing.
$rule = null;
if ($action === 'edit' && $ruleid) {
    $rule = $DB->get_record('local_points_rules', ['id' => $ruleid], '*', MUST_EXIST);
}

// Create form.
$mform = new rule_form(null, ['rule' => $rule, 'courseid' => $courseid]);

// Set existing data.
if ($rule) {
    $formdata = clone $rule;

    // Decode conditions.
    if (!empty($rule->conditions)) {
        $conditions = json_decode($rule->conditions, true);
        if (isset($conditions['min_grade'])) {
            $formdata->condition_min_grade = $conditions['min_grade'];
        }
    }

    $mform->set_data($formdata);
}

// Process form.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/points/rules.php', ['courseid' => $courseid]));
} else if ($data = $mform->get_data()) {
    // Build conditions.
    $conditions = [];
    if (!empty($data->condition_min_grade)) {
        $conditions['min_grade'] = $data->condition_min_grade;
    }

    $ruledata = new stdClass();
    $ruledata->name = $data->name;
    $ruledata->description = $data->description;
    $ruledata->eventname = $data->eventname;
    $ruledata->points = $data->points;
    $ruledata->courseid = !empty($data->courseid) ? $data->courseid : null;
    $ruledata->conditions = $conditions;
    $ruledata->enabled = $data->enabled;
    $ruledata->maxawards = !empty($data->maxawards) ? $data->maxawards : null;

    if (!empty($data->ruleid)) {
        \local_points\manager::update_rule($data->ruleid, $ruledata);
    } else {
        \local_points\manager::create_rule($ruledata);
    }

    redirect(
        new moodle_url('/local/points/rules.php', ['courseid' => $courseid]),
        get_string('rulesaved', 'local_points'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

// Show form if creating/editing.
if ($action === 'create' || $action === 'edit') {
    $mform->display();
    echo $OUTPUT->footer();
    exit;
}

// Button to create new rule.
echo html_writer::link(
    new moodle_url('/local/points/rules.php', ['courseid' => $courseid, 'action' => 'create']),
    get_string('createrule', 'local_points'),
    ['class' => 'btn btn-primary mb-3']
);

// List existing rules.
$rules = \local_points\manager::get_rules($courseid);

if (empty($rules)) {
    echo html_writer::tag('p', get_string('norules', 'local_points'), ['class' => 'alert alert-info']);
} else {
    $events = \local_points\manager::get_available_events();

    $table = new html_table();
    $table->head = [
        get_string('rulename', 'local_points'),
        get_string('ruleevent', 'local_points'),
        get_string('points', 'local_points'),
        get_string('status', 'local_points'),
        get_string('actions', 'local_points'),
    ];
    $table->attributes['class'] = 'table table-striped';

    foreach ($rules as $rule) {
        $row = [];

        // Name and description.
        $name = format_string($rule->name);
        if (!empty($rule->description)) {
            $name .= html_writer::tag('small', format_string($rule->description), ['class' => 'd-block text-muted']);
        }
        $row[] = $name;

        // Event.
        $row[] = $events[$rule->eventname] ?? $rule->eventname;

        // Points.
        $row[] = $rule->points;

        // Status.
        if ($rule->enabled) {
            $row[] = html_writer::tag('span', get_string('active', 'local_points'), ['class' => 'badge badge-success']);
        } else {
            $row[] = html_writer::tag('span', get_string('inactive', 'local_points'), ['class' => 'badge badge-secondary']);
        }

        // Actions.
        $actions = [];
        $actions[] = html_writer::link(
            new moodle_url('/local/points/rules.php', ['courseid' => $courseid, 'action' => 'edit', 'ruleid' => $rule->id]),
            $OUTPUT->pix_icon('t/edit', get_string('edit')),
            ['class' => 'btn btn-sm btn-outline-primary']
        );
        $actions[] = html_writer::link(
            new moodle_url('/local/points/rules.php', [
                'courseid' => $courseid,
                'action' => 'delete',
                'ruleid' => $rule->id,
                'sesskey' => sesskey()
            ]),
            $OUTPUT->pix_icon('t/delete', get_string('delete')),
            [
                'class' => 'btn btn-sm btn-outline-danger',
                'onclick' => "return confirm('" . get_string('confirmdeleterule', 'local_points') . "');"
            ]
        );
        $row[] = implode(' ', $actions);

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

// Back link.
echo html_writer::link(
    new moodle_url('/local/points/manage.php', ['courseid' => $courseid]),
    get_string('backtooverview', 'local_points'),
    ['class' => 'btn btn-secondary mt-3']
);

echo $OUTPUT->footer();
