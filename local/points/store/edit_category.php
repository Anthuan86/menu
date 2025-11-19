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
 * Edit/Create reward category.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/formslib.php');

$id = optional_param('id', 0, PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/points:managerules', $context);

// Setup page.
$PAGE->set_context($context);
$url = new moodle_url('/local/points/store/edit_category.php', ['id' => $id]);
$PAGE->set_url($url);

if ($id) {
    $category = $DB->get_record('local_points_reward_categories', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('editcategory', 'local_points'));
    $PAGE->set_heading(get_string('editcategory', 'local_points'));
} else {
    $category = new stdClass();
    $category->id = 0;
    $PAGE->set_title(get_string('addcategory', 'local_points'));
    $PAGE->set_heading(get_string('addcategory', 'local_points'));
}

$PAGE->set_pagelayout('admin');

// Define the form.
class category_form extends moodleform {
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Description.
        $mform->addElement('textarea', 'description', get_string('description'), [
            'rows' => 4,
            'cols' => 50
        ]);
        $mform->setType('description', PARAM_TEXT);

        // Visible.
        $mform->addElement('advcheckbox', 'visible', get_string('visible'));
        $mform->setDefault('visible', 1);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['name'])) {
            $errors['name'] = get_string('required');
        }

        return $errors;
    }
}

// Create form.
$mform = new category_form($url);

// Set form data.
if ($id) {
    $mform->set_data($category);
}

// Handle form submission.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/points/store/manage_categories.php'));
} else if ($data = $mform->get_data()) {
    $now = time();

    $record = new stdClass();
    $record->name = $data->name;
    $record->description = $data->description;
    $record->visible = $data->visible;
    $record->timemodified = $now;

    if ($data->id) {
        $record->id = $data->id;
        $DB->update_record('local_points_reward_categories', $record);
        $message = get_string('categoryupdated', 'local_points');
    } else {
        // Get next sortorder.
        $maxsort = $DB->get_field('local_points_reward_categories', 'MAX(sortorder)', []);
        $record->sortorder = $maxsort ? $maxsort + 1 : 1;
        $record->timecreated = $now;
        $DB->insert_record('local_points_reward_categories', $record);
        $message = get_string('categorycreated', 'local_points');
    }

    redirect(
        new moodle_url('/local/points/store/manage_categories.php'),
        $message,
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
