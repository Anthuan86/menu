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
 * Manage reward categories.
 *
 * @package    local_points
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');

$action = optional_param('action', '', PARAM_ALPHA);
$id = optional_param('id', 0, PARAM_INT);

require_login();

$context = context_system::instance();
require_capability('local/points:managerules', $context);

// Setup page.
$PAGE->set_context($context);
$url = new moodle_url('/local/points/store/manage_categories.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('managecategories', 'local_points'));
$PAGE->set_heading(get_string('managecategories', 'local_points'));
$PAGE->set_pagelayout('admin');

// Handle delete action.
if ($action === 'delete' && $id && confirm_sesskey()) {
    // Check if category has rewards.
    $rewardcount = $DB->count_records('local_points_rewards', ['categoryid' => $id]);
    if ($rewardcount > 0) {
        redirect($url, get_string('categoryhasrewards', 'local_points'), null, \core\output\notification::NOTIFY_ERROR);
    } else {
        $DB->delete_records('local_points_reward_categories', ['id' => $id]);
        redirect($url, get_string('categorydeleted', 'local_points'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

// Handle toggle visibility.
if ($action === 'toggle' && $id && confirm_sesskey()) {
    $category = $DB->get_record('local_points_reward_categories', ['id' => $id], '*', MUST_EXIST);
    $category->visible = $category->visible ? 0 : 1;
    $category->timemodified = time();
    $DB->update_record('local_points_reward_categories', $category);
    redirect($url);
}

// Handle move up/down.
if (($action === 'up' || $action === 'down') && $id && confirm_sesskey()) {
    $category = $DB->get_record('local_points_reward_categories', ['id' => $id], '*', MUST_EXIST);

    if ($action === 'up') {
        $swapcategory = $DB->get_record_select(
            'local_points_reward_categories',
            'sortorder < :sortorder',
            ['sortorder' => $category->sortorder],
            '*',
            IGNORE_MULTIPLE
        );
    } else {
        $swapcategory = $DB->get_record_select(
            'local_points_reward_categories',
            'sortorder > :sortorder',
            ['sortorder' => $category->sortorder],
            '*',
            IGNORE_MULTIPLE
        );
    }

    if ($swapcategory) {
        $tempsort = $category->sortorder;
        $category->sortorder = $swapcategory->sortorder;
        $swapcategory->sortorder = $tempsort;
        $DB->update_record('local_points_reward_categories', $category);
        $DB->update_record('local_points_reward_categories', $swapcategory);
    }

    redirect($url);
}

echo $OUTPUT->header();

// Add button.
echo html_writer::start_div('mb-4');
echo html_writer::link(
    new moodle_url('/local/points/store/edit_category.php'),
    get_string('addcategory', 'local_points'),
    ['class' => 'btn btn-primary']
);
echo html_writer::end_div();

// Get all categories.
$sql = "SELECT c.*,
               (SELECT COUNT(*) FROM {local_points_rewards} WHERE categoryid = c.id) as rewardcount
        FROM {local_points_reward_categories} c
        ORDER BY c.sortorder ASC";

$categories = $DB->get_records_sql($sql);

if (empty($categories)) {
    echo $OUTPUT->notification(get_string('nocategories', 'local_points'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('name'),
        get_string('rewards', 'local_points'),
        get_string('visible'),
        get_string('actions', 'local_points'),
    ];
    $table->attributes['class'] = 'generaltable';

    $count = count($categories);
    $i = 0;

    foreach ($categories as $category) {
        $i++;
        $row = [];

        // Name.
        $row[] = format_string($category->name);

        // Reward count.
        $row[] = $category->rewardcount;

        // Visibility.
        if ($category->visible) {
            $visible = html_writer::tag('span', get_string('yes'), ['class' => 'badge badge-success']);
        } else {
            $visible = html_writer::tag('span', get_string('no'), ['class' => 'badge badge-secondary']);
        }
        $row[] = $visible;

        // Actions.
        $actions = [];

        // Move up/down.
        if ($i > 1) {
            $actions[] = html_writer::link(
                new moodle_url($url, ['action' => 'up', 'id' => $category->id, 'sesskey' => sesskey()]),
                $OUTPUT->pix_icon('t/up', get_string('moveup'))
            );
        }
        if ($i < $count) {
            $actions[] = html_writer::link(
                new moodle_url($url, ['action' => 'down', 'id' => $category->id, 'sesskey' => sesskey()]),
                $OUTPUT->pix_icon('t/down', get_string('movedown'))
            );
        }

        // Edit.
        $actions[] = html_writer::link(
            new moodle_url('/local/points/store/edit_category.php', ['id' => $category->id]),
            $OUTPUT->pix_icon('t/edit', get_string('edit'))
        );

        // Toggle visibility.
        $toggleicon = $category->visible ? 't/hide' : 't/show';
        $togglestr = $category->visible ? get_string('hide') : get_string('show');
        $actions[] = html_writer::link(
            new moodle_url($url, ['action' => 'toggle', 'id' => $category->id, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon($toggleicon, $togglestr)
        );

        // Delete.
        $actions[] = html_writer::link(
            new moodle_url($url, ['action' => 'delete', 'id' => $category->id, 'sesskey' => sesskey()]),
            $OUTPUT->pix_icon('t/delete', get_string('delete')),
            ['onclick' => 'return confirm("' . get_string('confirmdelete', 'local_points') . '");']
        );

        $row[] = implode(' ', $actions);

        $table->data[] = $row;
    }

    echo html_writer::table($table);
}

// Back link.
echo html_writer::start_div('mt-4');
echo html_writer::link(
    new moodle_url('/local/points/store/manage.php'),
    get_string('managerewards', 'local_points'),
    ['class' => 'btn btn-secondary']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
