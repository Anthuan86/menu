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
 * Edit/Create reward.
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
$url = new moodle_url('/local/points/store/edit_reward.php', ['id' => $id]);
$PAGE->set_url($url);

if ($id) {
    $reward = $DB->get_record('local_points_rewards', ['id' => $id], '*', MUST_EXIST);
    $PAGE->set_title(get_string('editreward', 'local_points'));
    $PAGE->set_heading(get_string('editreward', 'local_points'));
} else {
    $reward = new stdClass();
    $reward->id = 0;
    $PAGE->set_title(get_string('addreward', 'local_points'));
    $PAGE->set_heading(get_string('addreward', 'local_points'));
}

$PAGE->set_pagelayout('admin');

// File picker options.
$filemanageroptions = [
    'maxbytes' => $CFG->maxbytes,
    'maxfiles' => 1,
    'accepted_types' => ['image'],
    'subdirs' => 0
];

// Define the form.
class reward_form extends moodleform {
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        // Name.
        $mform->addElement('text', 'name', get_string('name'), ['size' => 50]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', get_string('required'), 'required', null, 'client');

        // Category.
        $categories = [0 => get_string('nocategory', 'local_points')];
        $cats = $this->_customdata['categories'];
        foreach ($cats as $cat) {
            $categories[$cat->id] = format_string($cat->name);
        }
        $mform->addElement('select', 'categoryid', get_string('category', 'local_points'), $categories);

        // Description.
        $mform->addElement('editor', 'description_editor', get_string('description'), null, [
            'maxfiles' => 0,
            'noclean' => false
        ]);
        $mform->setType('description_editor', PARAM_RAW);

        // Cost.
        $mform->addElement('text', 'cost', get_string('cost', 'local_points'), ['size' => 10]);
        $mform->setType('cost', PARAM_INT);
        $mform->addRule('cost', get_string('required'), 'required', null, 'client');
        $mform->addHelpButton('cost', 'cost', 'local_points');

        // Quantity.
        $mform->addElement('text', 'quantity', get_string('stock', 'local_points'), ['size' => 10]);
        $mform->setType('quantity', PARAM_INT);
        $mform->addHelpButton('quantity', 'stock', 'local_points');

        // Image file picker.
        $mform->addElement('filemanager', 'rewardimage', get_string('image', 'local_points'), null,
            $this->_customdata['filemanageroptions']);
        $mform->addHelpButton('rewardimage', 'image', 'local_points');

        // Enabled.
        $mform->addElement('advcheckbox', 'enabled', get_string('enabled', 'local_points'));
        $mform->setDefault('enabled', 1);

        // Availability dates.
        $mform->addElement('date_time_selector', 'availablefrom', get_string('availablefrom', 'local_points'), [
            'optional' => true
        ]);
        $mform->addElement('date_time_selector', 'availableuntil', get_string('availableuntil', 'local_points'), [
            'optional' => true
        ]);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if (empty($data['name'])) {
            $errors['name'] = get_string('required');
        }

        if (!isset($data['cost']) || $data['cost'] < 0) {
            $errors['cost'] = get_string('invalidcost', 'local_points');
        }

        if (isset($data['quantity']) && $data['quantity'] !== '' && $data['quantity'] < 0) {
            $errors['quantity'] = get_string('invalidquantity', 'local_points');
        }

        return $errors;
    }
}

// Get categories for form.
$categories = $DB->get_records('local_points_reward_categories', null, 'sortorder ASC');

// Create form.
$mform = new reward_form($url, [
    'categories' => $categories,
    'filemanageroptions' => $filemanageroptions
]);

// Prepare form data.
$formdata = clone $reward;
$formdata->description_editor = [
    'text' => $reward->description ?? '',
    'format' => FORMAT_HTML
];

// Prepare file area for existing reward.
$draftitemid = file_get_submitted_draft_itemid('rewardimage');
file_prepare_draft_area($draftitemid, $context->id, 'local_points', 'rewardimage',
    $id ? $id : null, $filemanageroptions);
$formdata->rewardimage = $draftitemid;

$mform->set_data($formdata);

// Handle form submission.
if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/points/store/manage.php'));
} else if ($data = $mform->get_data()) {
    $now = time();

    $record = new stdClass();
    $record->name = $data->name;
    $record->categoryid = $data->categoryid ? $data->categoryid : null;
    $record->description = $data->description_editor['text'];
    $record->cost = $data->cost;
    $record->quantity = ($data->quantity !== '' && $data->quantity !== null) ? $data->quantity : null;
    $record->enabled = $data->enabled;
    $record->availablefrom = $data->availablefrom ? $data->availablefrom : null;
    $record->availableuntil = $data->availableuntil ? $data->availableuntil : null;
    $record->timemodified = $now;

    if ($data->id) {
        $record->id = $data->id;
        $rewardid = $data->id;
        $DB->update_record('local_points_rewards', $record);
        $message = get_string('rewardupdated', 'local_points');
    } else {
        $record->timecreated = $now;
        $record->image = ''; // Will be updated after getting file.
        $rewardid = $DB->insert_record('local_points_rewards', $record);
        $message = get_string('rewardcreated', 'local_points');
    }

    // Save the file.
    file_save_draft_area_files($data->rewardimage, $context->id, 'local_points', 'rewardimage',
        $rewardid, $filemanageroptions);

    // Get the filename and update record.
    $fs = get_file_storage();
    $files = $fs->get_area_files($context->id, 'local_points', 'rewardimage', $rewardid, 'itemid', false);
    $filename = '';
    if (!empty($files)) {
        $file = reset($files);
        $filename = $file->get_filename();
    }

    // Update image filename in database.
    $DB->set_field('local_points_rewards', 'image', $filename, ['id' => $rewardid]);

    redirect(new moodle_url('/local/points/store/manage.php'), $message, null, \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
